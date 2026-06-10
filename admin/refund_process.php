<?php
/**
 * Scholar Hub — Process admin refund approve/reject (POST).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';
require __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/refund_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: refunds.php');
    exit();
}

$admin_id = (int) $_SESSION['user_id'];
$action = trim((string) ($_POST['action'] ?? ''));
$refund_id = (int) ($_POST['refund_id'] ?? 0);
$remarks = trim((string) ($_POST['admin_remarks'] ?? ''));

if ($refund_id <= 0) {
    $_SESSION['refund_flash'] = ['type' => 'danger', 'message' => 'Invalid refund reference.'];
    header('Location: refunds.php');
    exit();
}

if ($action === 'approve') {
    $result = refund_approve($conn, $refund_id, $admin_id);
} elseif ($action === 'reject') {
    $result = refund_reject($conn, $refund_id, $admin_id, $remarks);
} else {
    $result = ['success' => false, 'message' => 'Unknown action.'];
}

$_SESSION['refund_flash'] = [
    'type' => $result['success'] ? 'success' : 'danger',
    'message' => $result['message'],
];

header('Location: refunds.php');
exit();
