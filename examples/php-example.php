<?php
// Example: Initiate a bKash payment in plain PHP
require_once __DIR__ . '/../src/BkashClient.php';

use Bkash\BkashClient;

$config = require __DIR__ . '/php-config.php';
$bkash = new BkashClient($config, $config['debug'] ?? false);

// Step 1: Get ID token
tokenResult = $bkash->getIdToken();
if (!$tokenResult['success']) {
    die('Auth failed: ' . $tokenResult['message']);
}
$idToken = $tokenResult['id_token'];

// Step 2: Create payment
$paymentPayload = [
    'mode' => '0011',
    'payerReference' => '017XXXXXXXX', // Customer phone or unique ref
    'callbackURL' => 'https://yourdomain.com/bkash-callback.php',
    'amount' => '100.00',
    'currency' => 'BDT',
    'intent' => 'sale',
    'merchantInvoiceNumber' => 'INV' . time(),
];
$createResult = $bkash->createPayment($idToken, $paymentPayload);
if (!$createResult['success']) {
    die('Create payment failed: ' . $createResult['message']);
}
// Redirect user to bKash payment page:
header('Location: ' . $createResult['bkash_url']);
exit;
