<?php

namespace Bkash;

class BkashClient
{
    private array $config;
    private bool $debug;

    public function __construct(array $config, bool $debug = false)
    {
        $this->config = $config;
        $this->debug = $debug;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }
        // Implement your own logging here if needed
        error_log("[bKash][$level] $message: " . json_encode($context));
    }

    private function getConfig(string $key): string
    {
        return trim((string)($this->config[$key] ?? ''));
    }

    private function postJson(string $url, array $headers, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_TIMEOUT => 25,
        ]);
        $result = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        $decoded = is_string($result) ? json_decode($result, true) : null;
        return [
            'http_code' => $httpCode,
            'raw' => $result,
            'data' => is_array($decoded) ? $decoded : null,
            'error' => $error,
        ];
    }

    public function getIdToken(): array
    {
        $baseUrl = $this->getConfig('base_url');
        $response = $this->postJson(
            $baseUrl . '/tokenized/checkout/token/grant',
            [
                'username: ' . $this->getConfig('username'),
                'password: ' . $this->getConfig('password'),
            ],
            [
                'app_key' => $this->getConfig('app_key'),
                'app_secret' => $this->getConfig('app_secret'),
            ]
        );
        $idToken = $response['data']['id_token'] ?? null;
        if ($response['http_code'] !== 200 || !$idToken) {
            $this->log('error', 'bKash authentication failed.', ['response' => $response]);
            return [
                'success' => false,
                'message' => 'bKash authentication failed.',
                'debug' => $response,
            ];
        }
        return ['success' => true, 'id_token' => $idToken];
    }

    public function createPayment(string $idToken, array $paymentPayload): array
    {
        $baseUrl = $this->getConfig('base_url');
        $response = $this->postJson(
            $baseUrl . '/tokenized/checkout/create',
            [
                'Authorization: ' . $idToken,
                'x-app-key: ' . $this->getConfig('app_key'),
            ],
            $paymentPayload
        );
        $bkashUrl = $response['data']['bkashURL'] ?? null;
        if ($response['http_code'] !== 200 || !$bkashUrl) {
            $this->log('error', 'bKash create payment failed.', ['response' => $response]);
            return [
                'success' => false,
                'message' => $response['data']['statusMessage'] ?? 'Failed to create bKash payment.',
                'debug' => $response,
            ];
        }
        return [
            'success' => true,
            'bkash_url' => $bkashUrl,
            'payment_id' => $response['data']['paymentID'] ?? null,
            'raw' => $response['data'],
        ];
    }

    public function executePayment(string $idToken, string $paymentId): array
    {
        $baseUrl = $this->getConfig('base_url');
        $response = $this->postJson(
            $baseUrl . '/tokenized/checkout/execute',
            [
                'Authorization: ' . $idToken,
                'x-app-key: ' . $this->getConfig('app_key'),
            ],
            ['paymentID' => $paymentId]
        );
        $txStatus = $response['data']['transactionStatus'] ?? null;
        if ($response['http_code'] !== 200 || $txStatus !== 'Completed') {
            $this->log('error', 'bKash execute payment failed.', ['response' => $response]);
            return [
                'success' => false,
                'message' => $response['data']['statusMessage'] ?? 'Payment execution failed.',
                'debug' => $response,
            ];
        }
        return [
            'success' => true,
            'data' => $response['data'],
        ];
    }
}
