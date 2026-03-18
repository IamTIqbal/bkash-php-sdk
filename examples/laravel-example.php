<?php
// Example: Laravel Controller for bKash payment
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Bkash\BkashClient;

class BkashPaymentController extends Controller
{
    public function initiate(Request $request, BkashClient $bkash)
    {
        $tokenResult = $bkash->getIdToken();
        if (!$tokenResult['success']) {
            return response()->json(['error' => $tokenResult['message']], 400);
        }
        $idToken = $tokenResult['id_token'];
        $payload = [
            'mode' => '0011',
            'payerReference' => $request->user()->phone ?? '017XXXXXXXX',
            'callbackURL' => route('bkash.callback'),
            'amount' => $request->amount,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => 'INV' . time(),
        ];
        $createResult = $bkash->createPayment($idToken, $payload);
        if (!$createResult['success']) {
            return response()->json(['error' => $createResult['message']], 400);
        }
        return redirect($createResult['bkash_url']);
    }

    public function callback(Request $request, BkashClient $bkash)
    {
        $paymentId = $request->query('paymentID');
        $status = $request->query('status');
        if ($status !== 'success' || !$paymentId) {
            return redirect('/payment-failed');
        }
        $tokenResult = $bkash->getIdToken();
        if (!$tokenResult['success']) {
            return redirect('/payment-failed');
        }
        $idToken = $tokenResult['id_token'];
        $executeResult = $bkash->executePayment($idToken, $paymentId);
        if (!$executeResult['success']) {
            return redirect('/payment-failed');
        }
        // Payment successful, process order here
        return redirect('/payment-success');
    }
}
