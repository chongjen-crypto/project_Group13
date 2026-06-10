<?php
/**
 * Scholar Hub — Refund details JSON (admin modal).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';
require __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/refund_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$refund_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($refund_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid refund ID.']);
    exit();
}

$row = refund_fetch_details($conn, $refund_id);
if ($row === null) {
    echo json_encode(['success' => false, 'message' => 'Refund not found.']);
    exit();
}

echo json_encode([
    'success' => true,
    'refund' => [
        'refund_id' => (int) $row['refund_id'],
        'booking_id' => (int) $row['booking_id'],
        'student_name' => (string) $row['student_name'],
        'student_email' => (string) $row['student_email'],
        'student_phone' => (string) ($row['student_phone'] ?? ''),
        'wallet_balance' => wallet_format_rm((float) ($row['wallet_balance'] ?? 0)),
        'facility_name' => (string) $row['facility_name'],
        'court_label' => (string) $row['court_label'],
        'booking_date' => (string) $row['booking_date'],
        'time_label' => (string) $row['time_label'],
        'booking_status' => ucfirst((string) $row['booking_status']),
        'purpose' => (string) ($row['purpose'] ?? ''),
        'payment_method' => refund_payment_method_label((string) ($row['payment_method'] ?? '')),
        'payment_status' => ucfirst((string) ($row['payment_status'] ?? '')),
        'refund_amount' => wallet_format_rm((float) $row['refund_amount']),
        'refund_reason' => (string) $row['refund_reason'],
        'refund_status' => refund_status_label((string) $row['refund_status']),
        'refund_status_key' => (string) $row['refund_status'],
        'admin_remarks' => (string) ($row['admin_remarks'] ?? ''),
        'approved_by_name' => (string) ($row['approved_by_name'] ?? ''),
        'approved_at' => (string) ($row['approved_at'] ?? ''),
        'created_at' => wallet_format_datetime((string) $row['created_at']),
    ],
], JSON_UNESCAPED_UNICODE);
