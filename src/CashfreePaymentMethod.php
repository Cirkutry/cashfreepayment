<?php

namespace Azuriom\Plugin\CashfreePayment;

use Azuriom\Plugin\Shop\Cart\Cart;
use Azuriom\Plugin\Shop\Models\Payment;
use Azuriom\Plugin\Shop\Payment\PaymentMethod;
use Illuminate\Http\Request;

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

    public function startPayment(Cart $cart, float $amount, string $currency)
    {
        $payment = $this->createPayment($cart, $amount, $currency);
        
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
                'return_url' => route('shop.payments.success', ['gateway' => $this->id, 'payment_id' => $payment->id]) . '?order_id={order_id}&order_token={order_token}',
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
        $orderId = $request->input('order_id');
        $orderToken = $request->input('order_token');
        
        if (!$orderId || !$orderToken) {
            return redirect()->route('shop.cart.index')->with('error', trans('shop::messages.payment.error'));
        }
        
        // Extract payment ID from order ID
        $paymentId = str_replace('azuriom_', '', $orderId);
        $payment = Payment::findOrFail($paymentId);
        
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
        ];
    }

    public function image()
    {
        return asset('plugins/cashfreepayment/img/logo.png');
    }

    public function view()
    {
        return 'cashfreepayment::admin.index';
    }
}
