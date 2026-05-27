<?php
/**
 * Student notifications (DB table: notifications).
 */

function notifications_ensure_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_created (user_id, created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
}

/**
 * @return array{success: bool, message: string, count: int}
 */
function notifications_send_to_all_students(mysqli $conn, string $title, string $message): array
{
    notifications_ensure_table($conn);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO notifications (user_id, title, message) SELECT id, ?, ? FROM users WHERE role = 'student'"
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error.', 'count' => 0];
    }
    mysqli_stmt_bind_param($stmt, 'ss', $title, $message);
    $ok = mysqli_stmt_execute($stmt);
    $count = $ok ? (int) mysqli_stmt_affected_rows($stmt) : 0;
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['success' => false, 'message' => 'Could not send notification.', 'count' => 0];
    }

    return [
        'success' => true,
        'message' => $count > 0
            ? "Notification sent to {$count} student(s)."
            : 'No student accounts found to notify.',
        'count' => $count,
    ];
}

/**
 * @return array{success: bool, message: string}
 */
function notifications_send_to_user(mysqli $conn, int $user_id, string $title, string $message): array
{
    notifications_ensure_table($conn);
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user.'];
    }
    $title = trim($title);
    $message = trim($message);
    if ($title === '' || $message === '') {
        return ['success' => false, 'message' => 'Title and message are required.'];
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)'
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error.'];
    }
    mysqli_stmt_bind_param($stmt, 'iss', $user_id, $title, $message);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $ok,
        'message' => $ok ? 'Notification sent.' : 'Could not send notification.',
    ];
}

/**
 * @param list<string> $roles
 * @return array{success: bool, message: string, count: int}
 */
function notifications_send_to_roles(mysqli $conn, array $roles, string $title, string $message): array
{
    notifications_ensure_table($conn);
    $cleanRoles = [];
    foreach ($roles as $role) {
        $r = strtolower(trim((string) $role));
        if (in_array($r, ['student', 'staff', 'admin'], true) && !in_array($r, $cleanRoles, true)) {
            $cleanRoles[] = $r;
        }
    }
    if ($cleanRoles === []) {
        return ['success' => false, 'message' => 'No valid roles provided.', 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($cleanRoles), '?'));
    $types = str_repeat('s', count($cleanRoles));
    $sql = "INSERT INTO notifications (user_id, title, message)
            SELECT id, ?, ? FROM users WHERE role IN ({$placeholders})";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error.', 'count' => 0];
    }
    $params = array_merge([$title, $message], $cleanRoles);
    mysqli_stmt_bind_param($stmt, 'ss' . $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    $count = $ok ? (int) mysqli_stmt_affected_rows($stmt) : 0;
    mysqli_stmt_close($stmt);

    return [
        'success' => $ok,
        'message' => $ok ? "Notification sent to {$count} user(s)." : 'Could not send notification.',
        'count' => $count,
    ];
}

function notifications_facility_label(string $facility_type): string
{
    $map = [
        'snooker' => 'Snooker Room',
        'gym' => 'Gym Room',
        'swimming' => 'Swimming Pool',
        'track' => 'Track Field',
        'badminton' => 'Badminton Court',
        'basketball' => 'Basketball Court',
        'futsal' => 'Futsal Court',
        'tennis' => 'Tennis Court',
        'volleyball' => 'Volleyball Court',
    ];
    $type = strtolower(trim($facility_type));
    return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * @return list<array<string, mixed>>
 */
function notifications_fetch_for_user(mysqli $conn, int $user_id, int $limit = 30): array
{
    notifications_ensure_table($conn);

    $items = [];
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, title, message, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT ?"
    );
    if (!$stmt) {
        return $items;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $items;
}

function notifications_unread_count(mysqli $conn, int $user_id): int
{
    notifications_ensure_table($conn);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    if (!$stmt) {
        return 0;
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    return (int) ($row['c'] ?? 0);
}

function notifications_mark_read(mysqli $conn, int $user_id, int $notification_id): bool
{
    notifications_ensure_table($conn);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function notifications_mark_all_read(mysqli $conn, int $user_id): bool
{
    notifications_ensure_table($conn);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

/**
 * Broadcasts sent to students (grouped by title, message, and send minute).
 *
 * @return list<array{title: string, message: string, sent_at: string, sent_minute: int, recipient_count: int}>
 */
function notifications_fetch_sent_broadcasts(mysqli $conn, int $limit = 100): array
{
    notifications_ensure_table($conn);

    $items = [];
    $sql = "SELECT title, message, MIN(created_at) AS sent_at,
                   FLOOR(UNIX_TIMESTAMP(MIN(created_at)) / 60) AS sent_minute,
                   COUNT(*) AS recipient_count
            FROM notifications
            GROUP BY title, message, FLOOR(UNIX_TIMESTAMP(created_at) / 60)
            ORDER BY sent_at DESC
            LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $items;
    }
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = [
            'title' => (string) $row['title'],
            'message' => (string) $row['message'],
            'sent_at' => (string) $row['sent_at'],
            'sent_minute' => (int) $row['sent_minute'],
            'recipient_count' => (int) $row['recipient_count'],
        ];
    }
    mysqli_stmt_close($stmt);

    return $items;
}

/**
 * Delete one broadcast (all student copies for that title/message/send minute).
 *
 * @return array{success: bool, message: string, deleted: int}
 */
function notifications_delete_broadcast(mysqli $conn, string $title, string $message, int $sent_minute): array
{
    notifications_ensure_table($conn);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM notifications
         WHERE title = ? AND message = ? AND FLOOR(UNIX_TIMESTAMP(created_at) / 60) = ?"
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error.', 'deleted' => 0];
    }
    mysqli_stmt_bind_param($stmt, 'ssi', $title, $message, $sent_minute);
    $ok = mysqli_stmt_execute($stmt);
    $deleted = $ok ? (int) mysqli_stmt_affected_rows($stmt) : 0;
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['success' => false, 'message' => 'Could not delete notification.', 'deleted' => 0];
    }
    if ($deleted === 0) {
        return ['success' => false, 'message' => 'Notification not found or already removed.', 'deleted' => 0];
    }

    return [
        'success' => true,
        'message' => "Removed notification from {$deleted} student inbox(es).",
        'deleted' => $deleted,
    ];
}

function notifications_format_datetime(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    return date('M j, Y g:i A', $ts);
}

function notifications_format_time(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return (int) floor($diff / 60) . ' min ago';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . ' hr ago';
    }
    return date('M j, Y g:i A', $ts);
}
