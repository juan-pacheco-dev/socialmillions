<?php
// includes/dlocal_service.php
require_once 'payments_config.php';

class DLocalService
{
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = DLOCAL_API_KEY;
        $this->secretKey = DLOCAL_SECRET_KEY;
        $this->baseUrl = DLOCAL_BASE_URL;
    }

    /**
     * Helper para enviar solicitudes autorizadas a dLocal Go
     */
    private function sendRequest($endpoint, $method = 'GET', $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        $auth = "Bearer " . $this->apiKey . ":" . $this->secretKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $auth,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    /**
     * Crear un pago con redirección (Checkout)
     */
    public function createPayment($amount, $currency = 'USD', $description = 'Test Payment')
    {
        $orderId = 'ORD-' . time();
        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'country' => 'CO', // Default for testing, but can be dynamic
            'description' => $description,
            'order_id' => $orderId,
            'success_url' => DLOCAL_SUCCESS_URL,
            'back_url' => DLOCAL_BACK_URL,
            'notification_url' => DLOCAL_BACK_URL // For test purposes
        ];

        return $this->sendRequest('/v1/payments', 'POST', $payload);
    }

    /**
     * Verificar el estado de la cuenta (Probar el entorno)
     */
    public function checkMe()
    {
        return $this->sendRequest('/v1/me', 'GET');
    }
}
?>