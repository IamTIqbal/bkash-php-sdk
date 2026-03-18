<?php
// Example: Handle bKash callback in plain PHP
require_once __DIR__ . '/../src/BkashClient.php';

use Bkash\BkashClient;

$config = require __DIR__ . '/php-config.php';
$bkash = new BkashClient($config, $config['debug'] ?? false);

$paymentId = $_GET['paymentID'] ?? '';
$status = $_GET['status'] ?? '';

if ($status !== 'success' || !$paymentId) {
    // Payment failed or cancelled
    header('Location: /payment-failed.php');
    exit;
}

$tokenResult = $bkash->getIdToken();
if (!$tokenResult['success']) {
    header('Location: /payment-failed.php');
    exit;
}
$idToken = $tokenResult['id_token'];

$executeResult = $bkash->executePayment($idToken, $paymentId);
if (!$executeResult['success']) {
    header('Location: /payment-failed.php');
    exit;
}
// Payment successful, process order here
header('Location: /payment-success.php');
exit;
