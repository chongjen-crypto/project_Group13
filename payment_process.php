<?php
/**
 * Scholar Hub — Process payment (POST from payment.php PAY button).
 */
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/booking_helpers.php';
require_once __DIR__ . '/includes/payment_checkout.php';
require_once __DIR__ . '/includes/wallet_helpers.php';

$checkout = payment_checkout_load();
if ($checkout === null) {
    header('Location: booking.php?error=checkout_expired');
    exit();
}

if ((int) ($checkout['user_id'] ?? 0) !== (int) $_SESSION['user_id']) {
    payment_checkout_clear();
    header('Location: student_dashboard.php');
    exit();
}

$payment_method = strtolower(trim((string) ($_POST['payment_method'] ?? '')));
if (!payment_method_is_valid($payment_method)) {
    $_SESSION['payment_error'] = 'Please select a payment method.';
    header('Location: payment.php');
    exit();
}

$facility_type = (string) ($checkout['facility_type'] ?? '');
$booking_date  = (string) ($checkout['booking_date'] ?? '');
$court_id      = $checkout['court_id'] ?? null;
$court_id      = ($court_id === null || $court_id === '') ? null : (int) $court_id;
$starts        = $checkout['slots'] ?? [];
$purpose       = (string) ($checkout['purpose'] ?? '');

if (!is_array($starts) || $starts === []) {
    payment_checkout_clear();
    header('Location: booking.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$total_amount = (float) ($checkout['total_amount'] ?? 0);

if ($payment_method === 'in_app') {
    $balance = wallet_get_balance($conn, $user_id);
    if ($balance < $total_amount) {
        $_SESSION['payment_error'] = 'Insufficient wallet balance. Please top up in Wallet.';
        header('Location: payment.php');
        exit();
    }
}

// Online (ToyyibPay): save booking as payment pending, then redirect to create_bill.php
if ($payment_method === 'online') {
    $result = booking_create_reservations_with_payment(
        $conn,
        $user_id,
        $facility_type,
        $booking_date,
        $court_id,
        $starts,
        $purpose,
        'online',
        'pending',
        'pending'
    );

    if ($result['success']) {
        $firstBookingId = (int) ($result['booking_ids'][0] ?? 0);
        $_SESSION['toyyibpay_payment_group'] = [
            'booking_ids' => $result['booking_ids'],
            'primary_booking_id' => $firstBookingId,
            'total_amount' => (float) ($result['total_paid'] ?? $total_amount),
        ];
        payment_checkout_clear();
        header('Location: create_bill.php?booking_id=' . $firstBookingId);
        exit();
    }

    $_SESSION['payment_error'] = $result['message'];
    header('Location: payment.php');
    exit();
}

$result = booking_create_reservations_with_payment(
    $conn,
    $user_id,
    $facility_type,
    $booking_date,
    $court_id,
    $starts,
    $purpose,
    $payment_method,
    'paid',
    'pending'
);

if ($result['success']) {
    if ($payment_method === 'in_app') {
        $firstBookingId = (int) ($result['booking_ids'][0] ?? 0);
        $deduct = wallet_deduct_for_booking(
            $conn,
            $user_id,
            (float) ($result['total_paid'] ?? $total_amount),
            $firstBookingId,
            'Booking payment #' . $firstBookingId
        );
        if (!$deduct['success']) {
            $_SESSION['payment_error'] = $deduct['message'];
            header('Location: payment.php');
            exit();
        }
    }
    payment_checkout_clear();
    header('Location: student_dashboard.php?booked=1&paid=1');
    exit();
}

$_SESSION['payment_error'] = $result['message'];
header('Location: payment.php');
exit();
