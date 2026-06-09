<?php
/**
 * Scholar Hub — Create ToyyibPay bill for wallet top-up and redirect to payment page.
 */
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/wallet_helpers.php';
require_once __DIR__ . '/config/toyyibpay.php';

$user_id = (int) $_SESSION['user_id'];
$topup_id = isset($_GET['topup_id']) ? (int) $_GET['topup_id'] : 0;

if ($topup_id <= 0) {
    $_SESSION['wallet_error'] = 'Invalid top-up reference.';
    header('Location: student_wallet.php');
    exit();
}

if (!wallet_topup_is_available()) {
    $_SESSION['wallet_error'] = 'Online top-up is not configured.';
    header('Location: student_wallet.php');
    exit();
}

$topup = wallet_topup_fetch($conn, $topup_id, $user_id);
if ($topup === null) {
    $_SESSION['wallet_error'] = 'Top-up request not found.';
    header('Location: student_wallet.php');
    exit();
}

$paymentStatus = (string) ($topup['payment_status'] ?? 'pending');
$amount = (float) ($topup['amount'] ?? 0);

if ($paymentStatus === 'paid') {
    $_SESSION['wallet_success'] = 'This top-up was already completed. Balance: ' . wallet_format_rm(wallet_get_balance($conn, $user_id));
    header('Location: student_wallet.php');
    exit();
}

if (!in_array($paymentStatus, ['pending', 'failed'], true)) {
    $_SESSION['wallet_error'] = 'This top-up cannot be paid online.';
    header('Location: student_wallet.php');
    exit();
}

if ($paymentStatus === 'failed') {
    $reset = mysqli_prepare(
        $conn,
        "UPDATE wallet_topups SET payment_status = 'pending', bill_code = NULL, transaction_id = NULL WHERE id = ? AND user_id = ?"
    );
    if ($reset) {
        mysqli_stmt_bind_param($reset, 'ii', $topup_id, $user_id);
        mysqli_stmt_execute($reset);
        mysqli_stmt_close($reset);
    }
    $paymentStatus = 'pending';
    $topup['payment_status'] = 'pending';
    $topup['bill_code'] = null;
}

$existingBill = trim((string) ($topup['bill_code'] ?? ''));
if ($existingBill !== '' && $paymentStatus === 'pending') {
    toyyibpay_log('Resume wallet top-up bill', ['topup_id' => $topup_id, 'bill_code' => $existingBill]);
    header('Location: ' . toyyibpay_payment_url($existingBill));
    exit();
}

$payerName = trim((string) ($_SESSION['full_name'] ?? 'Student'));
$payerEmail = trim((string) ($_SESSION['email'] ?? ''));
$payerPhone = '0123456789';

$userStmt = mysqli_prepare($conn, 'SELECT full_name, email, phone FROM users WHERE id = ? LIMIT 1');
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, 'i', $user_id);
    mysqli_stmt_execute($userStmt);
    $userRes = mysqli_stmt_get_result($userStmt);
    $userRow = $userRes ? mysqli_fetch_assoc($userRes) : null;
    mysqli_stmt_close($userStmt);
    if (is_array($userRow)) {
        $payerName = trim((string) ($userRow['full_name'] ?? $payerName));
        $payerEmail = trim((string) ($userRow['email'] ?? $payerEmail));
        $phone = trim((string) ($userRow['phone'] ?? ''));
        if ($phone !== '') {
            $payerPhone = $phone;
        }
    }
}

if ($payerEmail === '') {
    $_SESSION['wallet_error'] = 'Your account email is required for online top-up.';
    header('Location: student_wallet.php');
    exit();
}

$config = toyyibpay_config();
$orderRef = wallet_topup_order_ref($topup_id);
$amountSen = (int) round($amount * 100);
$appBase = toyyibpay_app_base_url();

$payload = [
    'billName'                => substr('Wallet Top-up #' . $topup_id, 0, 30),
    'billDescription'         => substr('Scholar Hub wallet top-up RM ' . number_format($amount, 2), 0, 100),
    'billPriceSetting'        => 1,
    'billPayorInfo'           => 1,
    'billAmount'              => $amountSen,
    'billReturnUrl'           => $appBase . '/wallet_topup_return.php',
    'billCallbackUrl'         => $config['callback_url'],
    'billExternalReferenceNo' => $orderRef,
    'billTo'                  => substr($payerName, 0, 120),
    'billEmail'               => $payerEmail,
    'billPhone'               => $payerPhone,
    'billSplitPayment'        => 0,
    'billPaymentChannel'      => 2,
];

$result = toyyibpay_create_bill_api($payload);
if (!$result['success']) {
    toyyibpay_log('wallet top-up create_bill failed', ['topup_id' => $topup_id, 'message' => $result['message']]);
    $_SESSION['wallet_error'] = $result['message'];
    header('Location: student_wallet.php');
    exit();
}

$billCode = $result['bill_code'];
if (!wallet_topup_save_bill_code($conn, $topup_id, $billCode)) {
    toyyibpay_log('Failed to save wallet top-up bill_code', ['topup_id' => $topup_id, 'bill_code' => $billCode]);
    $_SESSION['wallet_error'] = 'Could not save payment reference. Please try again.';
    header('Location: student_wallet.php');
    exit();
}

toyyibpay_log('Wallet top-up bill created', ['topup_id' => $topup_id, 'bill_code' => $billCode, 'amount' => $amount]);

header('Location: ' . toyyibpay_payment_url($billCode));
exit();
