<?php

namespace Azuriom\Plugin\CashfreePayment;

use Exception;

class CashfreeAPI
{
    private $clientId;
    private $clientSecret;
    private $environment;

    public const API_BASE_PROD = 'https://api.cashfree.com/pg/';

    public function __construct(string $clientId, string $clientSecret)
    {
        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception('API credentials are not specified');
        }
        
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->environment = 'PRODUCTION';
    }

    private function call($method, $endpoint, $data = [])
    {
        $ch = curl_init();
        
        $headers = [
            'x-client-id: ' . $this->clientId,
            'x-client-secret: ' . $this->clientSecret,
            'Content-Type: application/json',
            'x-api-version: 2023-08-01'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        switch ($method) {
            case 'GET':
                if (!empty($data) && is_string($data)) {
                    curl_setopt($ch, CURLOPT_URL, self::API_BASE_PROD . $endpoint . '/' . $data);
                } else {
                    curl_setopt($ch, CURLOPT_URL, self::API_BASE_PROD . $endpoint);
                }
                break;

            case 'POST':
                $data = json_encode($data);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_URL, self::API_BASE_PROD . $endpoint);
                break;

            default:
                break;
        }

        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }

    /**
     * Create a new payment order
     * 
     * @param array $params Array of order parameters
     * @return string JSON response
     */
    public function createOrder(array $params)
    {
        return $this->call('POST', 'orders', $params);
    }

    /**
     * Get order status
     * 
     * @param string $orderId Order ID to check
     * @return string JSON response
     */
    public function getOrder(string $orderId)
    {
        return $this->call('GET', 'orders', $orderId);
    }
}
