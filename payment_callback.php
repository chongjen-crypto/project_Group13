<?php
/**
 * Scholar Hub — ToyyibPay server-to-server payment callback.
 *
 * ToyyibPay POSTs payment status here after the customer pays.
 * This endpoint must be publicly reachable (not localhost).
 *
 * Validates callback hash, updates bookings, returns HTTP 200 on success.
 */
declare(strict_types=1);

require __DIR__ . '/db.php';
require_once __DIR__ . '/config/toyyibpay.php';

// -------------------------------------------------------------------------
// Section 1 — Read callback parameters (POST)
// -------------------------------------------------------------------------
$refno = trim((string) ($_POST['refno'] ?? ''));
$status = trim((string) ($_POST['status'] ?? $_POST['status_id'] ?? ''));
$reason = trim((string) ($_POST['reason'] ?? $_POST['msg'] ?? ''));
$billcode = trim((string) ($_POST['billcode'] ?? ''));
$order_id = trim((string) ($_POST['order_id'] ?? ''));
$amount = trim((string) ($_POST['amount'] ?? ''));
$transaction_time = trim((string) ($_POST['transaction_time'] ?? ''));
$transaction_id = trim((string) ($_POST['transaction_id'] ?? $refno));
$hash = trim((string) ($_POST['hash'] ?? ''));

toyyibpay_log('Callback received', [
    'billcode' => $billcode,
    'order_id' => $order_id,
    'status' => $status,
    'refno' => $refno,
    'amount' => $amount,
]);

// -------------------------------------------------------------------------
// Section 2 — Validate required fields
// -------------------------------------------------------------------------
if ($billcode === '' || $status === '' || $order_id === '' || $refno === '') {
    toyyibpay_log('Callback rejected: missing fields', $_POST);
    http_response_code(400);
    echo 'FAIL: Missing required callback fields.';
    exit();
}

// -------------------------------------------------------------------------
// Section 3 — Verify callback hash (security)
// -------------------------------------------------------------------------
if (!toyyibpay_verify_callback_hash($status, $order_id, $refno, $hash)) {
    toyyibpay_log('Callback rejected: invalid hash', [
        'billcode' => $billcode,
        'order_id' => $order_id,
        'status' => $status,
    ]);
    http_response_code(403);
    echo 'FAIL: Invalid callback signature.';
    exit();
}

// -------------------------------------------------------------------------
// Section 4 — Map status and update database
// -------------------------------------------------------------------------
$paymentStatus = toyyibpay_map_payment_status($status);
$paidAt = $transaction_time !== '' ? date('Y-m-d H:i:s', strtotime($transaction_time) ?: time()) : date('Y-m-d H:i:s');

if ($paymentStatus === 'pending') {
    toyyibpay_log('Callback pending', ['billcode' => $billcode, 'reason' => $reason]);
    http_response_code(200);
    echo 'OK: Payment pending.';
    exit();
}

$update = toyyibpay_apply_payment_update($conn, $billcode, $paymentStatus, $transaction_id, $paidAt);

if (!$update['success']) {
    toyyibpay_log('Callback update failed', ['billcode' => $billcode, 'message' => $update['message']]);
    http_response_code(500);
    echo 'FAIL: ' . $update['message'];
    exit();
}

toyyibpay_log('Callback processed', [
    'billcode' => $billcode,
    'payment_status' => $paymentStatus,
    'booking_ids' => $update['booking_ids'],
    'transaction_id' => $transaction_id,
]);

http_response_code(200);
echo 'OK: Payment ' . $paymentStatus . '.';
