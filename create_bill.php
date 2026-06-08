<?php
/**
 * Scholar Hub — Create ToyyibPay bill for a pending booking and redirect to payment page.
 *
 * Flow:
 * 1. Student clicks "Pay Now" (from checkout or booking history).
 * 2. This script loads the booking, creates a ToyyibPay bill via API.
 * 3. Bill code is saved to bookings.bill_code.
 * 4. User is redirected to ToyyibPay hosted payment page.
 */
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/config/toyyibpay.php';

$user_id = (int) $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    $_SESSION['payment_error'] = 'Invalid booking reference.';
    header('Location: booking_history.php');
    exit();
}

// -------------------------------------------------------------------------
// Section 1 — Load booking and validate ownership / payment state
// -------------------------------------------------------------------------
$booking = toyyibpay_fetch_booking($conn, $booking_id, $user_id);
if ($booking === null) {
    $_SESSION['payment_error'] = 'Booking not found.';
    header('Location: booking_history.php');
    exit();
}

$paymentStatus = strtolower((string) ($booking['payment_status'] ?? 'pending'));
if ($paymentStatus === 'paid') {
    header('Location: payment_return.php?status=1&order_id=SH-' . $booking_id . '&billcode=' . urlencode((string) ($booking['bill_code'] ?? '')));
    exit();
}

if (!in_array($paymentStatus, ['pending', 'failed'], true)) {
    $_SESSION['payment_error'] = 'This booking cannot be paid online.';
    header('Location: booking_history.php');
    exit();
}

if (in_array((string) ($booking['booking_status'] ?? ''), ['cancelled', 'rejected'], true)) {
    $_SESSION['payment_error'] = 'This booking is no longer active.';
    header('Location: booking_history.php');
    exit();
}

// -------------------------------------------------------------------------
// Section 2 — Resolve payment group (multi-slot bookings)
// -------------------------------------------------------------------------
$sessionGroup = $_SESSION['toyyibpay_payment_group'] ?? null;
$bookingIds = toyyibpay_resolve_booking_group($conn, $booking, is_array($sessionGroup) ? $sessionGroup : null);
$totalAmount = toyyibpay_sum_booking_amount($conn, $bookingIds);

if ($totalAmount <= 0 && is_array($sessionGroup)) {
    $totalAmount = (float) ($sessionGroup['total_amount'] ?? 0);
}

if ($totalAmount <= 0) {
    $totalAmount = (float) ($booking['payment_amount'] ?? 0);
}

if ($totalAmount <= 0) {
    $_SESSION['payment_error'] = 'Invalid payment amount for this booking.';
    header('Location: booking_history.php');
    exit();
}

// -------------------------------------------------------------------------
// Section 3 — Reuse existing bill if already created (resume payment)
// -------------------------------------------------------------------------
$existingBill = trim((string) ($booking['bill_code'] ?? ''));
if ($existingBill !== '' && $paymentStatus === 'pending') {
    toyyibpay_log('Resume existing bill', ['booking_id' => $booking_id, 'bill_code' => $existingBill]);
    header('Location: ' . toyyibpay_payment_url($existingBill));
    exit();
}

// -------------------------------------------------------------------------
// Section 4 — Load payer details from users table
// -------------------------------------------------------------------------
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
    $_SESSION['payment_error'] = 'Your account email is required for online payment.';
    header('Location: booking_history.php');
    exit();
}

// -------------------------------------------------------------------------
// Section 5 — Build ToyyibPay bill payload
// -------------------------------------------------------------------------
$config = toyyibpay_config();
$primaryId = (int) $bookingIds[0];
$orderRef = 'SH-' . $primaryId;
$amountSen = (int) round($totalAmount * 100);

$facilityName = (string) ($booking['facility_name'] ?? 'Facility');
$billName = substr('Booking #' . $primaryId . ' - ' . $facilityName, 0, 30);
$billDescription = substr(
    'Facility booking on ' . ($booking['booking_date'] ?? '') . ' (' . count($bookingIds) . ' slot(s))',
    0,
    100
);

$payload = [
    'billName'                  => $billName,
    'billDescription'           => $billDescription,
    'billPriceSetting'          => 1,
    'billPayorInfo'             => 1,
    'billAmount'                => $amountSen,
    'billReturnUrl'             => $config['return_url'],
    'billCallbackUrl'           => $config['callback_url'],
    'billExternalReferenceNo'   => $orderRef,
    'billTo'                    => substr($payerName, 0, 120),
    'billEmail'                 => $payerEmail,
    'billPhone'                 => $payerPhone,
    'billSplitPayment'          => 0,
    'billPaymentChannel'        => 2,
];

// -------------------------------------------------------------------------
// Section 6 — Call ToyyibPay API and persist bill code
// -------------------------------------------------------------------------
$result = toyyibpay_create_bill_api($payload);
if (!$result['success']) {
    toyyibpay_log('create_bill failed', ['booking_id' => $booking_id, 'message' => $result['message']]);
    $_SESSION['payment_error'] = $result['message'];
    header('Location: booking_history.php');
    exit();
}

$billCode = $result['bill_code'];
if (!toyyibpay_save_bill_code($conn, $bookingIds, $billCode)) {
    toyyibpay_log('Failed to save bill_code', ['booking_ids' => $bookingIds, 'bill_code' => $billCode]);
    $_SESSION['payment_error'] = 'Could not save payment reference. Please try again.';
    header('Location: booking_history.php');
    exit();
}

// Mark rows as online payment method while pending gateway confirmation
$placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
$types = str_repeat('i', count($bookingIds));
$updateMethod = mysqli_prepare(
    $conn,
    "UPDATE bookings SET payment_method = 'online' WHERE booking_id IN ($placeholders) AND payment_status = 'pending'"
);
if ($updateMethod) {
    mysqli_stmt_bind_param($updateMethod, $types, ...$bookingIds);
    mysqli_stmt_execute($updateMethod);
    mysqli_stmt_close($updateMethod);
}

unset($_SESSION['toyyibpay_payment_group']);

toyyibpay_log('Bill created, redirecting', ['booking_id' => $booking_id, 'bill_code' => $billCode, 'amount' => $totalAmount]);

header('Location: ' . toyyibpay_payment_url($billCode));
exit();
