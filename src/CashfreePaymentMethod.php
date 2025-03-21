<?php

namespace Azuriom\Plugin\CashfreePayment;

use Azuriom\Plugin\Shop\Cart\Cart;
use Azuriom\Plugin\Shop\Models\Payment;
use Azuriom\Plugin\Shop\Payment\PaymentMethod;
use Illuminate\Http\Request;
use Exception;

class CashfreePaymentMethod extends PaymentMethod
{
    /**
     * The payment method id name.
     *
     * @var string
     */
    protected $id = 'cashfree';

    /**
     * The payment method display name.
     *
     * @var string
     */
    protected $name = 'Cashfree';

    /**
     * The only currency supported by Cashfree by default
     *
     * @var string
     */
    protected $supportedCurrency = 'INR';

    /**
     * Exchange Rate API Key
     * 
     * @var string
     */
    protected $exchangeRateApiKey;

    public function startPayment(Cart $cart, float $amount, string $currency)
    {
        // Store original currency and amount
        $originalCurrency = $currency;
        $originalAmount = $amount;
        
        // Check if currency conversion is needed
        if ($currency !== $this->supportedCurrency) {
            try {
                // Convert to INR
                $conversionResult = $this->convertCurrency($amount, $currency, $this->supportedCurrency);
                $amount = $conversionResult['converted_amount'];
                $currency = $this->supportedCurrency;
                
                logger()->info("Currency converted from {$originalCurrency} {$originalAmount} to {$currency} {$amount}");
            } catch (Exception $e) {
                logger()->error('Currency conversion error: ' . $e->getMessage());
                return redirect()->route('shop.cart.index')->with('error', 'Currency conversion failed. Please try again later.');
            }
        }

        $payment = $this->createPayment($cart, $amount, $currency);
        
        // Store original currency/amount in payment metadata
        $payment->update([
            'metadata' => [
                'original_currency' => $originalCurrency,
                'original_amount' => $originalAmount,
                'conversion_rate' => $originalCurrency !== $currency ? ($amount / $originalAmount) : 1,
            ]
        ]);
        
        $api = new CashfreeAPI(
            $this->gateway->data['app-id'], 
            $this->gateway->data['secret-key']
        );
        
        $user = auth()->user();
        
        $orderData = [
            'order_id' => 'azuriom_' . $payment->id,
            'order_amount' => $amount,
            'order_currency' => $currency,
            'customer_details' => [
                'customer_id' => (string) $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?? '9999999999', // Default if not available
            ],
            'order_meta' => [
                'return_url' => route('shop.payments.success', ['gateway' => $this->id, 'payment_id' => $payment->id]),
                'notify_url' => route('shop.payments.notification', ['gateway' => $this->id]),
            ],
            'order_note' => $this->getPurchaseDescription($payment->id),
        ];
        
        $response = $api->createOrder($orderData);
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['payment_session_id'])) {
            logger()->error('Cashfree payment error: ' . $response);
            return redirect()->route('shop.cart.index')->with('error', trans('shop::messages.payment.error'));
        }
        
        $payment->update(['transaction_id' => $responseData['order_id']]);
        
        // Prepare the checkout page
        $cashfreeMode = 'production';
        $paymentSessionId = $responseData['payment_session_id'];
        
        return view('cashfreepayment::payment', compact('cashfreeMode', 'paymentSessionId'));
    }

    /**
     * Convert currency using Exchange Rate API
     * 
     * @param float $amount Amount to convert
     * @param string $fromCurrency Currency to convert from
     * @param string $toCurrency Currency to convert to
     * @return array Conversion result with converted amount and rate
     * @throws Exception If conversion fails
     */
    protected function convertCurrency(float $amount, string $fromCurrency, string $toCurrency)
    {
        // Get API key from gateway data
        $apiKey = $this->gateway->data['exchange-rate-api-key'] ?? null;
        
        if (empty($apiKey)) {
            throw new Exception('Exchange Rate API key is not configured');
        }
        
        // Fetch exchange rates from the API
        $req_url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$fromCurrency}";
        $response_json = @file_get_contents($req_url);
        
        if (false === $response_json) {
            throw new Exception('Failed to connect to Exchange Rate API');
        }
        
        $response = json_decode($response_json);
        
        if (!$response || $response->result !== 'success') {
            $errorType = $response->{'error-type'} ?? 'unknown-error';
            throw new Exception("Exchange Rate API error: {$errorType}");
        }
        
        // Check if the target currency is supported
        if (!isset($response->conversion_rates->{$toCurrency})) {
            throw new Exception("Target currency {$toCurrency} not supported by Exchange Rate API");
        }
        
        // Get the conversion rate
        $rate = $response->conversion_rates->{$toCurrency};
        
        // Calculate the converted amount and round to 2 decimal places
        $convertedAmount = round($amount * $rate, 2);
        
        return [
            'original_amount' => $amount,
            'original_currency' => $fromCurrency,
            'converted_amount' => $convertedAmount,
            'converted_currency' => $toCurrency,
            'rate' => $rate,
        ];
    }

    public function notification(Request $request, ?string $paymentId)
    {
        // Verify the signature
        $postData = $request->all();
        $orderId = isset($postData['order_id']) ? $postData['order_id'] : null;
        
        if (!$orderId) {
            return response('Invalid order data', 400);
        }
        
        // Extract the payment ID from order ID (remove 'azuriom_' prefix)
        $paymentId = str_replace('azuriom_', '', $orderId);
        $payment = Payment::findOrFail($paymentId);
        
        // Verify order status with Cashfree API
        $api = new CashfreeAPI(
            $this->gateway->data['app-id'], 
            $this->gateway->data['secret-key']
        );
        
        $orderResponse = $api->getOrder($orderId);
        $orderData = json_decode($orderResponse, true);
        
        if (!isset($orderData['order_status'])) {
            return response('Failed to verify payment', 400);
        }
        
        if ($orderData['order_status'] === 'PAID') {
            // Payment successful, proceed with processing
            return $this->processPayment($payment, $orderId);
        }
        
        return response('Payment not completed', 200);
    }

    public function success(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $payment = Payment::findOrFail($paymentId);
        
        // Get the order ID from the payment record
        $orderId = $payment->transaction_id;
        
        if (!$orderId) {
            return redirect()->route('shop.cart.index')->with('error', trans('shop::messages.payment.error'));
        }
        
        // Verify payment status with API
        $api = new CashfreeAPI(
            $this->gateway->data['app-id'], 
            $this->gateway->data['secret-key']
        );
        
        $orderResponse = $api->getOrder($orderId);
        $orderData = json_decode($orderResponse, true);
        
        if (isset($orderData['order_status']) && $orderData['order_status'] === 'PAID') {
            // Payment has been completed
            if (!$payment->isPending()) {
                // Payment already processed
                return redirect()->route('shop.home')->with('success', trans('shop::messages.payment.success'));
            }
            
            // Process the payment
            $this->processPayment($payment, $orderId);
            
            return redirect()->route('shop.home')->with('success', trans('shop::messages.payment.success'));
        }
        
        return redirect()->route('shop.home')->with('error', trans('shop::messages.payment.error'));
    }

    public function failure(Request $request)
    {
        return redirect()->route('shop.home')->with('error', trans('shop::messages.payment.error'));
    }

    public function rules()
    {
        return [
            'app-id' => ['required', 'string'],
            'secret-key' => ['required', 'string'],
            'exchange-rate-api-key' => ['required', 'string'],
        ];
    }

    public function image()
    {
        return asset('plugins/cashfreepayment/img/logo.svg');
    }


    public function view()
    {
        return 'cashfreepayment::admin.index';
    }
}
