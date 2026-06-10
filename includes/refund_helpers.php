<?php
/**
 * Admin refund management — all refunds credited to in-app wallet.
 */
declare(strict_types=1);

require_once __DIR__ . '/wallet_helpers.php';
require_once __DIR__ . '/booking_helpers.php';
require_once __DIR__ . '/notification_helpers.php';

function refund_ensure_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }

    wallet_ensure_schema($conn);

    $refundsSql = "CREATE TABLE IF NOT EXISTS refunds (
        refund_id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        user_id INT NOT NULL,
        refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        refund_reason VARCHAR(255) NOT NULL DEFAULT '',
        refund_status ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
        admin_remarks TEXT DEFAULT NULL,
        approved_by INT DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_refunds_booking (booking_id),
        KEY idx_refunds_status (refund_status),
        KEY idx_refunds_user (user_id),
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $refundsSql);

    $auditSql = "CREATE TABLE IF NOT EXISTS refund_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        refund_id INT NOT NULL,
        action_taken VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_refund_audit_refund (refund_id),
        KEY idx_refund_audit_admin (admin_id),
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (refund_id) REFERENCES refunds(refund_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $auditSql);

    $enumCheck = mysqli_query($conn, "SHOW COLUMNS FROM wallet_transactions LIKE 'txn_type'");
    if ($enumCheck && ($col = mysqli_fetch_assoc($enumCheck))) {
        $type = (string) ($col['Type'] ?? '');
        if (stripos($type, 'refund') === false) {
            mysqli_query($conn, "ALTER TABLE wallet_transactions MODIFY txn_type ENUM('topup','payment','refund') NOT NULL");
        }
    }

    $done = true;
}

function refund_facility_label_sql(): string
{
    return "CASE WHEN b.facility_type = 'snooker' THEN 'Snooker Room'
             WHEN b.facility_type = 'gym' THEN 'Gym Room'
             WHEN b.facility_type = 'swimming' THEN 'Swimming Pool'
             WHEN b.facility_type = 'track' THEN 'Track Field'
             WHEN b.facility_type = 'badminton' THEN 'Badminton Court'
             WHEN b.facility_type = 'basketball' THEN 'Basketball Court'
             WHEN b.facility_type = 'futsal' THEN 'Futsal Court'
             WHEN b.facility_type = 'tennis' THEN 'Tennis Court'
             WHEN b.facility_type = 'volleyball' THEN 'Volleyball Court'
             ELSE b.facility_type END";
}

/** Create pending refund rows for eligible paid bookings (cancelled/rejected). */
function refund_sync_eligible_bookings(mysqli $conn): void
{
    refund_ensure_schema($conn);

    $facilityCase = refund_facility_label_sql();
    $sql = "SELECT b.booking_id, b.user_id, b.payment_amount, b.booking_status, b.reject_reason
            FROM bookings b
            LEFT JOIN refunds r ON r.booking_id = b.booking_id
            WHERE b.booking_status IN ('cancelled', 'rejected')
              AND b.payment_status = 'paid'
              AND b.payment_amount IS NOT NULL
              AND b.payment_amount > 0
              AND r.refund_id IS NULL";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return;
    }

    $insert = mysqli_prepare(
        $conn,
        'INSERT INTO refunds (booking_id, user_id, refund_amount, refund_reason, refund_status)
         VALUES (?, ?, ?, ?, ?)'
    );
    if (!$insert) {
        return;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $bookingId = (int) $row['booking_id'];
        $userId = (int) $row['user_id'];
        $amount = (float) $row['payment_amount'];
        $status = (string) $row['booking_status'];
        $reason = $status === 'rejected'
            ? trim((string) ($row['reject_reason'] ?? ''))
            : 'Booking cancelled by student';
        if ($reason === '') {
            $reason = $status === 'rejected'
                ? 'Booking rejected by staff/admin'
                : 'Booking cancelled by student';
        }
        $pending = 'pending';
        mysqli_stmt_bind_param($insert, 'iidss', $bookingId, $userId, $amount, $reason, $pending);
        mysqli_stmt_execute($insert);
    }
    mysqli_stmt_close($insert);
}

function refund_build_reason(string $booking_status, ?string $reject_reason): string
{
    if ($booking_status === 'cancelled') {
        return 'Booking cancelled by student';
    }
    $r = trim((string) $reject_reason);
    return $r !== '' ? $r : 'Booking rejected by staff/admin';
}

/**
 * @return array{pending: int, completed: int, rejected: int, total_amount: float}
 */
function refund_fetch_stats(mysqli $conn): array
{
    refund_ensure_schema($conn);
    refund_sync_eligible_bookings($conn);

    $stats = ['pending' => 0, 'completed' => 0, 'rejected' => 0, 'total_amount' => 0.0];

    $res = mysqli_query(
        $conn,
        "SELECT refund_status, COUNT(*) AS c, COALESCE(SUM(refund_amount), 0) AS total
         FROM refunds
         GROUP BY refund_status"
    );
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $st = (string) ($row['refund_status'] ?? '');
            $count = (int) ($row['c'] ?? 0);
            if ($st === 'pending') {
                $stats['pending'] = $count;
            } elseif ($st === 'completed') {
                $stats['completed'] = $count;
                $stats['total_amount'] = (float) ($row['total'] ?? 0);
            } elseif ($st === 'rejected') {
                $stats['rejected'] = $count;
            }
        }
    }

    return $stats;
}

/**
 * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
 */
function refund_fetch_list(
    mysqli $conn,
    string $search = '',
    string $statusFilter = '',
    int $page = 1,
    int $perPage = 10
): array {
    refund_ensure_schema($conn);
    refund_sync_eligible_bookings($conn);

    $facilityCase = refund_facility_label_sql();
    $where = ["b.booking_status IN ('cancelled', 'rejected')", "b.payment_status IN ('paid', 'refunded')"];
    $types = '';
    $params = [];

    if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'completed', 'rejected'], true)) {
        $where[] = 'r.refund_status = ?';
        $types .= 's';
        $params[] = $statusFilter;
    }

    if ($search !== '') {
        $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR CAST(b.booking_id AS CHAR) LIKE ? OR CAST(r.refund_id AS CHAR) LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) AS c
                 FROM refunds r
                 INNER JOIN bookings b ON b.booking_id = r.booking_id
                 INNER JOIN users u ON u.id = r.user_id
                 WHERE {$whereSql}";
    $countStmt = mysqli_prepare($conn, $countSql);
    $total = 0;
    if ($countStmt) {
        if ($types !== '') {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        $countRow = $countRes ? mysqli_fetch_assoc($countRes) : null;
        $total = (int) ($countRow['c'] ?? 0);
        mysqli_stmt_close($countStmt);
    }

    $perPage = max(1, min(50, $perPage));
    $page = max(1, $page);
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT r.refund_id, r.booking_id, r.user_id, r.refund_amount, r.refund_reason,
                   r.refund_status, r.admin_remarks, r.approved_at, r.created_at,
                   u.full_name AS student_name, u.email AS student_email,
                   b.booking_date, b.start_time, b.end_time, b.booking_status,
                   b.payment_method, b.payment_status, b.payment_amount, b.court_id, b.facility_type,
                   ({$facilityCase}) AS facility_name,
                   admin_u.full_name AS approved_by_name
            FROM refunds r
            INNER JOIN bookings b ON b.booking_id = r.booking_id
            INNER JOIN users u ON u.id = r.user_id
            LEFT JOIN users admin_u ON admin_u.id = r.approved_by
            WHERE {$whereSql}
            ORDER BY r.created_at DESC, r.refund_id DESC
            LIMIT ? OFFSET ?";

    $listTypes = $types . 'ii';
    $listParams = array_merge($params, [$perPage, $offset]);

    $items = [];
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $listTypes, ...$listParams);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $facilityType = (string) ($row['facility_type'] ?? '');
            $courtId = isset($row['court_id']) && $row['court_id'] !== null ? (int) $row['court_id'] : null;
            $courtLabel = '—';
            if ($courtId !== null && $courtId > 0 && booking_is_court_based($facilityType)) {
                $courtLabel = booking_court_display_name($conn, $facilityType, $courtId);
            }
            $row['court_label'] = $courtLabel;
            $row['time_label'] = substr((string) ($row['start_time'] ?? ''), 0, 5)
                . ' - ' . substr((string) ($row['end_time'] ?? ''), 0, 5);
            $items[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function refund_fetch_details(mysqli $conn, int $refund_id): ?array
{
    refund_ensure_schema($conn);

    $facilityCase = refund_facility_label_sql();
    $stmt = mysqli_prepare(
        $conn,
        "SELECT r.*, u.full_name AS student_name, u.email AS student_email, u.phone AS student_phone,
                b.booking_date, b.start_time, b.end_time, b.booking_status, b.purpose,
                b.payment_method, b.payment_status, b.payment_amount, b.court_id, b.facility_type,
                b.reject_reason, b.created_at AS booking_created_at,
                ({$facilityCase}) AS facility_name,
                admin_u.full_name AS approved_by_name
         FROM refunds r
         INNER JOIN bookings b ON b.booking_id = r.booking_id
         INNER JOIN users u ON u.id = r.user_id
         LEFT JOIN users admin_u ON admin_u.id = r.approved_by
         WHERE r.refund_id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $refund_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if ($row === null) {
        return null;
    }

    $facilityType = (string) ($row['facility_type'] ?? '');
    $courtId = isset($row['court_id']) && $row['court_id'] !== null ? (int) $row['court_id'] : null;
    $row['court_label'] = ($courtId !== null && $courtId > 0 && booking_is_court_based($facilityType))
        ? booking_court_display_name($conn, $facilityType, $courtId)
        : '—';
    $row['time_label'] = substr((string) ($row['start_time'] ?? ''), 0, 5)
        . ' - ' . substr((string) ($row['end_time'] ?? ''), 0, 5);
    $row['wallet_balance'] = wallet_get_balance($conn, (int) $row['user_id']);

    return $row;
}

function refund_audit_log(mysqli $conn, int $admin_id, int $refund_id, string $action, string $details = ''): void
{
    refund_ensure_schema($conn);
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO refund_audit_log (admin_id, refund_id, action_taken, details) VALUES (?, ?, ?, ?)'
    );
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param($stmt, 'iiss', $admin_id, $refund_id, $action, $details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Credit refund amount to student wallet.
 * @return array{success: bool, message: string, balance: float}
 */
function wallet_credit_refund(mysqli $conn, int $user_id, float $amount, int $booking_id, string $description, bool $manageTransaction = true): array
{
    refund_ensure_schema($conn);
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Invalid refund amount.', 'balance' => wallet_get_balance($conn, $user_id)];
    }

    if ($manageTransaction) {
        mysqli_begin_transaction($conn);
    }
    try {
        $stmt = mysqli_prepare($conn, 'UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('Database error.');
        }
        mysqli_stmt_bind_param($stmt, 'di', $amount, $user_id);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) === 0) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Could not update wallet balance.');
        }
        mysqli_stmt_close($stmt);

        $balance = wallet_get_balance($conn, $user_id);
        $type = 'refund';
        $log = mysqli_prepare(
            $conn,
            'INSERT INTO wallet_transactions (user_id, txn_type, amount, balance_after, description, booking_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$log) {
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_bind_param($log, 'isddsi', $user_id, $type, $amount, $balance, $description, $booking_id);
        if (!mysqli_stmt_execute($log)) {
            mysqli_stmt_close($log);
            throw new RuntimeException('Could not log wallet transaction.');
        }
        mysqli_stmt_close($log);

        if ($manageTransaction) {
            mysqli_commit($conn);
        }
        return ['success' => true, 'message' => 'Refund credited to wallet.', 'balance' => $balance];
    } catch (Throwable $e) {
        if ($manageTransaction) {
            mysqli_rollback($conn);
        }
        return [
            'success' => false,
            'message' => 'Refund wallet credit failed.',
            'balance' => wallet_get_balance($conn, $user_id),
        ];
    }
}

/**
 * @return array{success: bool, message: string}
 */
function refund_approve(mysqli $conn, int $refund_id, int $admin_id): array
{
    refund_ensure_schema($conn);

    $refund = refund_fetch_details($conn, $refund_id);
    if ($refund === null) {
        return ['success' => false, 'message' => 'Refund request not found.'];
    }
    if ((string) ($refund['refund_status'] ?? '') !== 'pending') {
        return ['success' => false, 'message' => 'This refund is no longer pending.'];
    }

    $userId = (int) $refund['user_id'];
    $bookingId = (int) $refund['booking_id'];
    $amount = (float) $refund['refund_amount'];

    mysqli_begin_transaction($conn);
    try {
        $lock = mysqli_prepare(
            $conn,
            "UPDATE refunds SET refund_status = 'completed', approved_by = ?, approved_at = NOW()
             WHERE refund_id = ? AND refund_status = 'pending'"
        );
        if (!$lock) {
            throw new RuntimeException('Database error.');
        }
        mysqli_stmt_bind_param($lock, 'ii', $admin_id, $refund_id);
        if (!mysqli_stmt_execute($lock) || mysqli_stmt_affected_rows($lock) === 0) {
            mysqli_stmt_close($lock);
            throw new RuntimeException('Refund already processed.');
        }
        mysqli_stmt_close($lock);

        $desc = 'Refund for booking #' . $bookingId;
        $credit = wallet_credit_refund($conn, $userId, $amount, $bookingId, $desc, false);
        if (!$credit['success']) {
            throw new RuntimeException($credit['message']);
        }

        $payUpdate = mysqli_prepare(
            $conn,
            "UPDATE bookings SET payment_status = 'refunded' WHERE booking_id = ? AND payment_status = 'paid'"
        );
        if ($payUpdate) {
            mysqli_stmt_bind_param($payUpdate, 'i', $bookingId);
            mysqli_stmt_execute($payUpdate);
            mysqli_stmt_close($payUpdate);
        }

        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }

    refund_audit_log(
        $conn,
        $admin_id,
        $refund_id,
        'approved',
        'Refunded RM ' . number_format($amount, 2) . ' to wallet for booking #' . $bookingId
    );

    notifications_send_to_user(
        $conn,
        $userId,
        'Refund Approved',
        'Your refund of ' . wallet_format_rm($amount) . ' for booking #' . $bookingId . ' has been credited to your wallet.'
    );

    return [
        'success' => true,
        'message' => 'Refund approved. ' . wallet_format_rm($amount) . ' credited to student wallet.',
    ];
}

/**
 * @return array{success: bool, message: string}
 */
function refund_reject(mysqli $conn, int $refund_id, int $admin_id, string $remarks): array
{
    refund_ensure_schema($conn);
    $remarks = trim($remarks);
    if ($remarks === '') {
        return ['success' => false, 'message' => 'Please provide a reason for rejecting this refund.'];
    }

    $refund = refund_fetch_details($conn, $refund_id);
    if ($refund === null) {
        return ['success' => false, 'message' => 'Refund request not found.'];
    }
    if ((string) ($refund['refund_status'] ?? '') !== 'pending') {
        return ['success' => false, 'message' => 'This refund is no longer pending.'];
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE refunds
         SET refund_status = 'rejected', admin_remarks = ?, approved_by = ?, approved_at = NOW()
         WHERE refund_id = ? AND refund_status = 'pending'"
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error.'];
    }
    mysqli_stmt_bind_param($stmt, 'sii', $remarks, $admin_id, $refund_id);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['success' => false, 'message' => 'Could not reject refund.'];
    }

    refund_audit_log($conn, $admin_id, $refund_id, 'rejected', $remarks);

    notifications_send_to_user(
        $conn,
        (int) $refund['user_id'],
        'Refund Rejected',
        'Your refund request for booking #' . (int) $refund['booking_id'] . ' was rejected. Reason: ' . $remarks
    );

    return ['success' => true, 'message' => 'Refund request rejected.'];
}

function refund_status_badge_class(string $status): string
{
    return match ($status) {
        'completed' => 'text-bg-success',
        'rejected' => 'text-bg-danger',
        default => 'text-bg-warning',
    };
}

function refund_status_label(string $status): string
{
    return match ($status) {
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        default => 'Pending',
    };
}

function refund_payment_method_label(?string $method): string
{
    $m = strtolower(trim((string) $method));
    return match ($m) {
        'in_app' => 'In-App Wallet',
        'online', 'tng' => 'ToyyibPay (Online)',
        default => $m !== '' ? ucfirst(str_replace('_', ' ', $m)) : '—',
    };
}
