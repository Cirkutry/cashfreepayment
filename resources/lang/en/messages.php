<?php
return [
    'info' => 'You will find the App ID and Secret Key in your Cashfree merchant dashboard under the API Keys section.',
    'payment' => [
        'info' => 'Purchase from Mobcraft will now be processed via the Cashfree payment gateway.',
        'processing' => 'Please wait as we redirect you to process your payment. Do not close this window.',
    ],
    'errors' => [
        'currency_conversion' => 'Currency conversion failed. Please try again later or contact support.',
        'api_connection' => 'Unable to connect to Cashfree payment gateway. Please try again later.',
        'payment_creation' => 'Failed to create payment with Cashfree. Please check your payment details and try again.',
        'payment_verification' => 'Payment verification failed. If you believe this is an error, please contact support.',
        'payment_cancelled' => 'Your payment was cancelled. No charges have been made to your account.',
        'payment_expired' => 'Your payment session has expired. Please try again.',
        'invalid_order' => 'Invalid order information. Please contact support with your order details.',
        'general' => 'An error occurred while processing your payment. Please try again or use a different payment method.',
        'exchange_rate' => [
            'api_key_missing' => 'Currency conversion is not properly configured. Please contact the administrator.',
            'connection_failed' => 'Unable to connect to currency conversion service. Please try again later.',
            'unsupported_currency' => 'The selected currency is not supported for conversion. Please choose a different currency.',
        ],
    ],
    'success' => [
        'payment_completed' => 'Payment completed successfully! Your order is being processed.',
    ],
];