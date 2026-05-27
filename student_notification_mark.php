<?php
/**
 * Mark notification(s) as read (JSON) for logged-in users.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'staff', 'admin'], true) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/notification_helpers.php';

$user_id = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$notif_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($action === 'mark_all') {
    notifications_mark_all_read($conn, $user_id);
    echo json_encode(['ok' => true, 'unread' => 0]);
    exit;
}

if ($action === 'mark_one' && $notif_id > 0) {
    notifications_mark_read($conn, $user_id, $notif_id);
    echo json_encode(['ok' => true, 'unread' => notifications_unread_count($conn, $user_id)]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Invalid request']);
