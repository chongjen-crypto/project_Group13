<?php
/**
 * Student in-app wallet (balance on users + transaction log).
 */

function wallet_ensure_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'wallet_balance'");
    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($conn, 'ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00');
    }

    $sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        txn_type ENUM('topup','payment') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        balance_after DECIMAL(10,2) NOT NULL,
        description VARCHAR(255) NOT NULL,
        booking_id INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_wallet_user (user_id, created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);

    $topupsSql = "CREATE TABLE IF NOT EXISTS wallet_topups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
        bill_code VARCHAR(100) DEFAULT NULL,
        transaction_id VARCHAR(100) DEFAULT NULL,
        paid_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_wallet_topups_user (user_id, created_at),
        INDEX idx_wallet_topups_bill (bill_code),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $topupsSql);

    $done = true;
}

function wallet_get_balance(mysqli $conn, int $user_id): float
{
    wallet_ensure_schema($conn);
    $stmt = mysqli_prepare($conn, 'SELECT wallet_balance FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 0.0;
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $row ? (float) $row['wallet_balance'] : 0.0;
}

/**
 * @return list<array<string, mixed>>
 */
function wallet_fetch_transactions(mysqli $conn, int $user_id, int $limit = 30): array
{
    wallet_ensure_schema($conn);
    $items = [];
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, txn_type, amount, balance_after, description, booking_id, created_at
         FROM wallet_transactions
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT ?'
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

function wallet_format_rm(float $amount): string
{
    return 'RM ' . number_format($amount, 2);
}

/** Preset top-up amounts shown on student wallet page. */
function wallet_topup_preset_amounts(): array
{
    return [1.0, 5.0, 10.0, 20.0];
}

/** Whether online top-up via ToyyibPay is available. */
function wallet_topup_is_available(): bool
{
    require_once dirname(__DIR__) . '/config/toyyibpay.php';
    return toyyibpay_is_configured();
}

function wallet_topup_order_ref(int $topup_id): string
{
    return 'WT-' . $topup_id;
}

function wallet_topup_parse_order_ref(string $order_id): ?int
{
    if (preg_match('/^WT-(\d+)$/', $order_id, $m)) {
        return (int) $m[1];
    }
    return null;
}

/**
 * @return array{valid: bool, amount: float, message: string}
 */
function wallet_topup_validate_amount($raw_amount): array
{
    if (!is_numeric($raw_amount)) {
        return ['valid' => false, 'amount' => 0.0, 'message' => 'Please enter a valid amount.'];
    }

    $amount = round((float) $raw_amount, 2);
    if ($amount < 1) {
        return ['valid' => false, 'amount' => $amount, 'message' => 'Minimum top-up is RM 1.00.'];
    }
    if ($amount > 500) {
        return ['valid' => false, 'amount' => $amount, 'message' => 'Maximum top-up per transaction is RM 500.00.'];
    }

    return ['valid' => true, 'amount' => $amount, 'message' => ''];
}

/**
 * @return array<string, mixed>|null
 */
function wallet_topup_fetch(mysqli $conn, int $topup_id, int $user_id): ?array
{
    wallet_ensure_schema($conn);
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, user_id, amount, payment_status, bill_code, transaction_id, paid_at, created_at
         FROM wallet_topups
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $topup_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

/**
 * @return array<string, mixed>|null
 */
function wallet_topup_fetch_by_bill_code(mysqli $conn, string $bill_code): ?array
{
    wallet_ensure_schema($conn);
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, user_id, amount, payment_status, bill_code, transaction_id, paid_at, created_at
         FROM wallet_topups
         WHERE bill_code = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $bill_code);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

/**
 * Create a pending top-up record before redirecting to ToyyibPay.
 * @return array{success: bool, message: string, topup_id: int}
 */
function wallet_topup_create_pending(mysqli $conn, int $user_id, float $amount): array
{
    wallet_ensure_schema($conn);
    $check = wallet_topup_validate_amount($amount);
    if (!$check['valid']) {
        return ['success' => false, 'message' => $check['message'], 'topup_id' => 0];
    }

    $amount = $check['amount'];
    $status = 'pending';
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO wallet_topups (user_id, amount, payment_status) VALUES (?, ?, ?)'
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Could not start top-up.', 'topup_id' => 0];
    }
    mysqli_stmt_bind_param($stmt, 'ids', $user_id, $amount, $status);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Could not start top-up.', 'topup_id' => 0];
    }
    $topupId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    return ['success' => true, 'message' => 'Top-up started.', 'topup_id' => $topupId];
}

function wallet_topup_save_bill_code(mysqli $conn, int $topup_id, string $bill_code): bool
{
    wallet_ensure_schema($conn);
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE wallet_topups SET bill_code = ?, payment_status = 'pending' WHERE id = ? AND payment_status IN ('pending', 'failed')"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'si', $bill_code, $topup_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Credit wallet balance after a successful ToyyibPay payment.
 * @return array{success: bool, message: string, balance: float, topup_id: int}
 */
function wallet_credit_topup(mysqli $conn, int $user_id, float $amount, string $description = 'Wallet top-up via ToyyibPay'): array
{
    wallet_ensure_schema($conn);
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Invalid amount.', 'balance' => wallet_get_balance($conn, $user_id), 'topup_id' => 0];
    }

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Database error.');
        }
        mysqli_stmt_bind_param($stmt, 'di', $amount, $user_id);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) === 0) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Could not update balance.');
        }
        mysqli_stmt_close($stmt);

        $balance = wallet_get_balance($conn, $user_id);
        $type = 'topup';
        $log = mysqli_prepare(
            $conn,
            'INSERT INTO wallet_transactions (user_id, txn_type, amount, balance_after, description)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$log) {
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_bind_param($log, 'isdds', $user_id, $type, $amount, $balance, $description);
        if (!mysqli_stmt_execute($log)) {
            mysqli_stmt_close($log);
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_close($log);

        mysqli_commit($conn);
        return [
            'success' => true,
            'message' => 'Top-up successful.',
            'balance' => $balance,
            'topup_id' => 0,
        ];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return [
            'success' => false,
            'message' => 'Top-up failed. Please try again.',
            'balance' => wallet_get_balance($conn, $user_id),
            'topup_id' => 0,
        ];
    }
}

/**
 * Apply ToyyibPay callback/return status to a wallet top-up.
 * @return array{success: bool, message: string, topup_id: int, payment_status: string, balance: float}
 */
function wallet_topup_apply_payment(
    mysqli $conn,
    string $bill_code,
    string $payment_status,
    string $transaction_id,
    ?string $paid_at = null
): array {
    wallet_ensure_schema($conn);
    $topup = wallet_topup_fetch_by_bill_code($conn, $bill_code);
    if ($topup === null) {
        return ['success' => false, 'message' => 'No wallet top-up found for bill code.', 'topup_id' => 0, 'payment_status' => $payment_status, 'balance' => 0.0];
    }

    $topupId = (int) $topup['id'];
    $userId = (int) $topup['user_id'];
    $amount = (float) $topup['amount'];
    $currentStatus = (string) ($topup['payment_status'] ?? 'pending');

    if ($payment_status === 'pending') {
        return ['success' => true, 'message' => 'Top-up still pending.', 'topup_id' => $topupId, 'payment_status' => 'pending', 'balance' => wallet_get_balance($conn, $userId)];
    }

    if ($currentStatus === 'paid') {
        return ['success' => true, 'message' => 'Top-up already completed.', 'topup_id' => $topupId, 'payment_status' => 'paid', 'balance' => wallet_get_balance($conn, $userId)];
    }

    if ($payment_status === 'failed') {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE wallet_topups SET payment_status = 'failed', transaction_id = ? WHERE id = ? AND payment_status = 'pending'"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error.', 'topup_id' => $topupId, 'payment_status' => 'failed', 'balance' => wallet_get_balance($conn, $userId)];
        }
        mysqli_stmt_bind_param($stmt, 'si', $transaction_id, $topupId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['success' => true, 'message' => 'Top-up marked as failed.', 'topup_id' => $topupId, 'payment_status' => 'failed', 'balance' => wallet_get_balance($conn, $userId)];
    }

    if ($payment_status !== 'paid') {
        return ['success' => false, 'message' => 'Unknown payment status.', 'topup_id' => $topupId, 'payment_status' => $payment_status, 'balance' => wallet_get_balance($conn, $userId)];
    }

    mysqli_begin_transaction($conn);
    try {
        $paidAt = $paid_at ?: date('Y-m-d H:i:s');
        $lock = mysqli_prepare(
            $conn,
            "UPDATE wallet_topups
             SET payment_status = 'paid', transaction_id = ?, paid_at = ?
             WHERE id = ? AND payment_status = 'pending'"
        );
        if (!$lock) {
            throw new RuntimeException('Database error.');
        }
        mysqli_stmt_bind_param($lock, 'ssi', $transaction_id, $paidAt, $topupId);
        if (!mysqli_stmt_execute($lock) || mysqli_stmt_affected_rows($lock) === 0) {
            mysqli_stmt_close($lock);
            mysqli_commit($conn);
            return [
                'success' => true,
                'message' => 'Top-up already processed.',
                'topup_id' => $topupId,
                'payment_status' => 'paid',
                'balance' => wallet_get_balance($conn, $userId),
            ];
        }
        mysqli_stmt_close($lock);

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Database error.');
        }
        mysqli_stmt_bind_param($stmt, 'di', $amount, $userId);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) === 0) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Could not update balance.');
        }
        mysqli_stmt_close($stmt);

        $balance = wallet_get_balance($conn, $userId);
        $desc = 'Wallet top-up via ToyyibPay';
        $type = 'topup';
        $log = mysqli_prepare(
            $conn,
            'INSERT INTO wallet_transactions (user_id, txn_type, amount, balance_after, description)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$log) {
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_bind_param($log, 'isdds', $userId, $type, $amount, $balance, $desc);
        if (!mysqli_stmt_execute($log)) {
            mysqli_stmt_close($log);
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_close($log);

        mysqli_commit($conn);
        return [
            'success' => true,
            'message' => 'Wallet credited.',
            'topup_id' => $topupId,
            'payment_status' => 'paid',
            'balance' => $balance,
        ];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return [
            'success' => false,
            'message' => 'Could not complete top-up.',
            'topup_id' => $topupId,
            'payment_status' => 'paid',
            'balance' => wallet_get_balance($conn, $userId),
        ];
    }
}

/**
 * @return array{success: bool, message: string, balance: float}
 */
function wallet_deduct_for_booking(mysqli $conn, int $user_id, float $amount, int $booking_id, string $description): array
{
    wallet_ensure_schema($conn);
    if ($amount <= 0) {
        return ['success' => true, 'message' => 'No charge.', 'balance' => wallet_get_balance($conn, $user_id)];
    }

    $balance = wallet_get_balance($conn, $user_id);
    if ($balance < $amount) {
        return [
            'success' => false,
            'message' => 'Insufficient wallet balance. Please top up in Wallet.',
            'balance' => $balance,
        ];
    }

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Database error.');
        }
        mysqli_stmt_bind_param($stmt, 'did', $amount, $user_id, $amount);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) === 0) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Insufficient balance.');
        }
        mysqli_stmt_close($stmt);

        $newBalance = wallet_get_balance($conn, $user_id);
        $type = 'payment';
        $bookingRef = $booking_id > 0 ? $booking_id : 0;
        $log = mysqli_prepare(
            $conn,
            'INSERT INTO wallet_transactions (user_id, txn_type, amount, balance_after, description, booking_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$log) {
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_bind_param($log, 'isddsi', $user_id, $type, $amount, $newBalance, $description, $bookingRef);
        if (!mysqli_stmt_execute($log)) {
            mysqli_stmt_close($log);
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_close($log);

        mysqli_commit($conn);
        return ['success' => true, 'message' => 'Payment deducted.', 'balance' => $newBalance];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return [
            'success' => false,
            'message' => 'Wallet payment failed.',
            'balance' => wallet_get_balance($conn, $user_id),
        ];
    }
}

function wallet_txn_type_label(string $type): string
{
    return match ($type) {
        'topup' => 'Top-up',
        'refund' => 'Refund',
        default => 'Payment',
    };
}

function wallet_format_datetime(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    return date('M j, Y g:i A', $ts);
}
