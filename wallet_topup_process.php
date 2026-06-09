<?php
/**
 * Scholar Hub — Start wallet top-up and redirect to ToyyibPay bill creation.
 */
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'topup') {
    header('Location: student_wallet.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/wallet_helpers.php';
require_once __DIR__ . '/config/toyyibpay.php';

$user_id = (int) $_SESSION['user_id'];

if (!wallet_topup_is_available()) {
    $_SESSION['wallet_error'] = 'Online top-up is not configured. Please contact support.';
    header('Location: student_wallet.php');
    exit();
}

$check = wallet_topup_validate_amount($_POST['amount'] ?? '');
if (!$check['valid']) {
    $_SESSION['wallet_error'] = $check['message'];
    header('Location: student_wallet.php');
    exit();
}

$result = wallet_topup_create_pending($conn, $user_id, $check['amount']);
if (!$result['success']) {
    $_SESSION['wallet_error'] = $result['message'];
    header('Location: student_wallet.php');
    exit();
}

header('Location: wallet_topup_bill.php?topup_id=' . (int) $result['topup_id']);
exit();
