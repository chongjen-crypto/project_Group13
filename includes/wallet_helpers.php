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

/** Top-up will redirect to ToyyibPay once integrated. */
function wallet_topup_is_available(): bool
{
    return false;
}

/**
 * @return array{success: bool, message: string, balance: float}
 */
function wallet_topup(mysqli $conn, int $user_id, float $amount): array
{
    wallet_ensure_schema($conn);
    if (!wallet_topup_is_available()) {
        return [
            'success' => false,
            'message' => 'Wallet top-up is temporarily unavailable. ToyyibPay integration is coming soon.',
            'balance' => wallet_get_balance($conn, $user_id),
        ];
    }
    if ($amount < 1) {
        return ['success' => false, 'message' => 'Minimum top-up is RM 1.00.', 'balance' => wallet_get_balance($conn, $user_id)];
    }
    if ($amount > 500) {
        return ['success' => false, 'message' => 'Maximum top-up per transaction is RM 500.00.', 'balance' => wallet_get_balance($conn, $user_id)];
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
        $desc = 'Wallet top-up';
        $type = 'topup';
        $log = mysqli_prepare(
            $conn,
            'INSERT INTO wallet_transactions (user_id, txn_type, amount, balance_after, description)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$log) {
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_bind_param($log, 'isdds', $user_id, $type, $amount, $balance, $desc);
        if (!mysqli_stmt_execute($log)) {
            mysqli_stmt_close($log);
            throw new RuntimeException('Could not log transaction.');
        }
        mysqli_stmt_close($log);

        mysqli_commit($conn);
        return [
            'success' => true,
            'message' => 'Top-up successful. New balance: ' . wallet_format_rm($balance),
            'balance' => $balance,
        ];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return [
            'success' => false,
            'message' => 'Top-up failed. Please try again.',
            'balance' => wallet_get_balance($conn, $user_id),
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
    return $type === 'topup' ? 'Top-up' : 'Payment';
}

function wallet_format_datetime(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    return date('M j, Y g:i A', $ts);
}
