<?php
/**
 * Scholar Hub — ToyyibPay configuration and reusable API helpers.
 *
 * Credentials: config/toyyibpay_local.php (copy from toyyibpay_local.example.php)
 * or environment variables TOYYIBPAY_SECRET_KEY / TOYYIBPAY_SANDBOX.
 *
 * Category code: Facility Booking System
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/env_loader.php';
scholarhub_load_env_file();

$localConfig = __DIR__ . '/toyyibpay_local.php';
if (is_readable($localConfig)) {
    require_once $localConfig;
}

/** Category code from ToyyibPay merchant dashboard. */
const TOYYIBPAY_CATEGORY_CODE = 'pxf2xhg2';

/** Default sandbox flag when not set in local config or .env */
const TOYYIBPAY_USE_SANDBOX_DEFAULT = true;

/** Resolve public base URL for return/callback (project root on web server). */
function toyyibpay_app_base_url(): string
{
    if (defined('TOYYIBPAY_APP_BASE_URL') && TOYYIBPAY_APP_BASE_URL !== '') {
        return rtrim((string) TOYYIBPAY_APP_BASE_URL, '/');
    }

    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    $projectDir = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';

    if ($docRoot !== '' && str_starts_with(str_replace('\\', '/', $projectDir), str_replace('\\', '/', $docRoot))) {
        $webPath = substr(str_replace('\\', '/', $projectDir), strlen(str_replace('\\', '/', $docRoot)));
        $webPath = '/' . trim($webPath, '/');
    } else {
        $webPath = '/project_Group13';
    }

    return rtrim($scheme . '://' . $host . $webPath, '/');
}

/**
 * @return array{
 *   secret_key: string,
 *   category_code: string,
 *   api_base: string,
 *   payment_base: string,
 *   return_url: string,
 *   callback_url: string,
 *   sandbox: bool
 * }
 */
function toyyibpay_config(): array
{
    $secret = '';
    if (defined('TOYYIBPAY_SECRET_KEY')) {
        $secret = trim((string) TOYYIBPAY_SECRET_KEY);
    }
    if ($secret === '') {
        $secret = trim((string) (getenv('TOYYIBPAY_SECRET_KEY') ?: ''));
    }

    $sandbox = TOYYIBPAY_USE_SANDBOX_DEFAULT;
    if (defined('TOYYIBPAY_USE_SANDBOX')) {
        $sandbox = (bool) TOYYIBPAY_USE_SANDBOX;
    } elseif (getenv('TOYYIBPAY_SANDBOX') !== false) {
        $sandbox = filter_var(getenv('TOYYIBPAY_SANDBOX'), FILTER_VALIDATE_BOOLEAN);
    }

    $apiHost = $sandbox ? 'https://dev.toyyibpay.com' : 'https://toyyibpay.com';
    $appBase = toyyibpay_app_base_url();

    return [
        'secret_key'    => $secret,
        'category_code' => TOYYIBPAY_CATEGORY_CODE,
        'api_base'      => $apiHost . '/index.php/api',
        'payment_base'  => $apiHost,
        'return_url'    => $appBase . '/payment_return.php',
        'callback_url'  => $appBase . '/payment_callback.php',
        'sandbox'       => $sandbox,
    ];
}

/** Whether secret key and category are ready for createBill. */
function toyyibpay_is_configured(): bool
{
    $c = toyyibpay_config();
    return $c['secret_key'] !== '' && $c['category_code'] !== '';
}

/** Append a line to logs/toyyibpay.log (or PHP error_log on failure). */
function toyyibpay_log(string $message, array $context = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/toyyibpay.log';
    if (@file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log($line);
    }
}

/** Ensure ToyyibPay-related columns exist on bookings. */
function toyyibpay_ensure_booking_columns(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $columns = [
        'bill_code' => "ADD COLUMN bill_code VARCHAR(100) DEFAULT NULL AFTER payment_status",
        'transaction_id' => "ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL AFTER bill_code",
        'paid_at' => "ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER transaction_id",
    ];

    foreach ($columns as $name => $alter) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE '" . mysqli_real_escape_string($conn, $name) . "'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, 'ALTER TABLE bookings ' . $alter);
        }
    }

    $idx = mysqli_query($conn, "SHOW INDEX FROM bookings WHERE Key_name = 'idx_bookings_bill_code'");
    if ($idx && mysqli_num_rows($idx) === 0) {
        mysqli_query($conn, 'ALTER TABLE bookings ADD INDEX idx_bookings_bill_code (bill_code)');
    }

    $done = true;
}

/**
 * Load a booking owned by the student, with facility label.
 * @return array<string, mixed>|null
 */
function toyyibpay_fetch_booking(mysqli $conn, int $booking_id, int $user_id): ?array
{
    toyyibpay_ensure_booking_columns($conn);
    $stmt = mysqli_prepare(
        $conn,
        "SELECT b.*,
            CASE WHEN b.facility_type = 'snooker' THEN 'Snooker Room'
                 WHEN b.facility_type = 'gym' THEN 'Gym Room'
                 WHEN b.facility_type = 'swimming' THEN 'Swimming Pool'
                 WHEN b.facility_type = 'track' THEN 'Track Field'
                 WHEN b.facility_type = 'badminton' THEN 'Badminton Court'
                 WHEN b.facility_type = 'basketball' THEN 'Basketball Court'
                 WHEN b.facility_type = 'futsal' THEN 'Futsal Court'
                 WHEN b.facility_type = 'tennis' THEN 'Tennis Court'
                 WHEN b.facility_type = 'volleyball' THEN 'Volleyball Court'
                 ELSE b.facility_type END AS facility_name
         FROM bookings b
         WHERE b.booking_id = ? AND b.user_id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

/**
 * Find unpaid booking rows submitted together (multi-slot checkout).
 * @return list<int>
 */
function toyyibpay_resolve_booking_group(mysqli $conn, array $booking, ?array $sessionGroup = null): array
{
    if (is_array($sessionGroup) && !empty($sessionGroup['booking_ids']) && is_array($sessionGroup['booking_ids'])) {
        $ids = array_map('intval', $sessionGroup['booking_ids']);
        $primary = (int) ($sessionGroup['primary_booking_id'] ?? $booking['booking_id']);
        if (in_array($primary, $ids, true)) {
            return array_values(array_unique($ids));
        }
    }

    $bookingId = (int) ($booking['booking_id'] ?? 0);
    $userId = (int) ($booking['user_id'] ?? 0);
    $createdAt = (string) ($booking['created_at'] ?? '');
    if ($bookingId <= 0 || $userId <= 0 || $createdAt === '') {
        return [$bookingId];
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT booking_id FROM bookings
         WHERE user_id = ?
           AND facility_type = ?
           AND booking_date = ?
           AND payment_status = 'pending'
           AND booking_status != 'cancelled'
           AND created_at BETWEEN DATE_SUB(?, INTERVAL 10 SECOND) AND DATE_ADD(?, INTERVAL 10 SECOND)
         ORDER BY booking_id ASC"
    );
    if (!$stmt) {
        return [$bookingId];
    }

    $facilityType = (string) $booking['facility_type'];
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $facilityType, $booking['booking_date'], $createdAt, $createdAt);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $ids[] = (int) $row['booking_id'];
    }
    mysqli_stmt_close($stmt);

    return $ids !== [] ? $ids : [$bookingId];
}

/**
 * Sum payment_amount for a list of booking IDs.
 */
function toyyibpay_sum_booking_amount(mysqli $conn, array $booking_ids): float
{
    if ($booking_ids === []) {
        return 0.0;
    }
    $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
    $types = str_repeat('i', count($booking_ids));
    $sql = "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings WHERE booking_id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0.0;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$booking_ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $row ? (float) $row['total'] : 0.0;
}

/**
 * Persist bill code on all rows in a payment group.
 */
function toyyibpay_save_bill_code(mysqli $conn, array $booking_ids, string $bill_code): bool
{
    if ($booking_ids === [] || $bill_code === '') {
        return false;
    }
    $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
    $types = 's' . str_repeat('i', count($booking_ids));
    $sql = "UPDATE bookings SET bill_code = ? WHERE booking_id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    $params = array_merge([$bill_code], $booking_ids);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Create a ToyyibPay bill via API.
 * @param array<string, string|int> $payload
 * @return array{success: bool, bill_code: string, message: string, raw: mixed}
 */
function toyyibpay_create_bill_api(array $payload): array
{
    $config = toyyibpay_config();
    if ($config['secret_key'] === '') {
        return ['success' => false, 'bill_code' => '', 'message' => 'ToyyibPay secret key is not configured. Copy config/toyyibpay_local.example.php to config/toyyibpay_local.php', 'raw' => null];
    }
    if ($config['category_code'] === '') {
        return ['success' => false, 'bill_code' => '', 'message' => 'ToyyibPay category code is not configured.', 'raw' => null];
    }

    $payload['userSecretKey'] = $config['secret_key'];
    $payload['categoryCode'] = $config['category_code'];

    $url = $config['api_base'] . '/createBill';
    $ch = curl_init($url);
    if ($ch === false) {
        return ['success' => false, 'bill_code' => '', 'message' => 'Could not initialize payment request.', 'raw' => null];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        toyyibpay_log('createBill curl error', ['error' => $curlError]);
        return ['success' => false, 'bill_code' => '', 'message' => 'Payment gateway unreachable. Enable PHP curl extension.', 'raw' => null];
    }

    $decoded = json_decode($response, true);
    toyyibpay_log('createBill response', ['http' => $httpCode, 'body' => $response]);

    $billCode = '';
    if (is_array($decoded)) {
        if (isset($decoded[0]['BillCode'])) {
            $billCode = (string) $decoded[0]['BillCode'];
        } elseif (isset($decoded['BillCode'])) {
            $billCode = (string) $decoded['BillCode'];
        }
    }

    if ($billCode === '') {
        $msg = 'ToyyibPay did not return a bill code.';
        if (is_array($decoded) && isset($decoded[0]['msg'])) {
            $msg = (string) $decoded[0]['msg'];
        } elseif (is_array($decoded) && isset($decoded['msg'])) {
            $msg = (string) $decoded['msg'];
        }
        return ['success' => false, 'bill_code' => '', 'message' => $msg, 'raw' => $decoded];
    }

    return ['success' => true, 'bill_code' => $billCode, 'message' => 'Bill created.', 'raw' => $decoded];
}

/** Build redirect URL to ToyyibPay hosted payment page. */
function toyyibpay_payment_url(string $bill_code): string
{
    $config = toyyibpay_config();
    return rtrim($config['payment_base'], '/') . '/' . rawurlencode($bill_code);
}

/**
 * Verify ToyyibPay callback hash.
 * Formula: MD5(userSecretKey + status + order_id + refno + "ok")
 */
function toyyibpay_verify_callback_hash(string $status, string $order_id, string $refno, string $received_hash): bool
{
    $secret = toyyibpay_config()['secret_key'];
    if ($secret === '' || $received_hash === '') {
        return false;
    }
    $expected = md5($secret . $status . $order_id . $refno . 'ok');
    return hash_equals($expected, $received_hash);
}

/**
 * Map ToyyibPay status code to internal payment_status.
 */
function toyyibpay_map_payment_status(string $status): string
{
    return match ($status) {
        '1' => 'paid',
        '3' => 'failed',
        default => 'pending',
    };
}

/**
 * Update all bookings sharing a bill code after gateway notification.
 * @return array{success: bool, message: string, booking_ids: list<int>, payment_status: string}
 */
function toyyibpay_apply_payment_update(
    mysqli $conn,
    string $bill_code,
    string $payment_status,
    string $transaction_id,
    ?string $paid_at = null
): array {
    toyyibpay_ensure_booking_columns($conn);

    $find = mysqli_prepare($conn, 'SELECT booking_id FROM bookings WHERE bill_code = ?');
    if (!$find) {
        return ['success' => false, 'message' => 'Database error.', 'booking_ids' => [], 'payment_status' => $payment_status];
    }
    mysqli_stmt_bind_param($find, 's', $bill_code);
    mysqli_stmt_execute($find);
    $res = mysqli_stmt_get_result($find);
    $ids = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $ids[] = (int) $row['booking_id'];
    }
    mysqli_stmt_close($find);

    if ($ids === []) {
        return ['success' => false, 'message' => 'No booking found for bill code.', 'booking_ids' => [], 'payment_status' => $payment_status];
    }

    if ($payment_status === 'paid') {
        $paidAt = $paid_at ?: date('Y-m-d H:i:s');
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE bookings
             SET payment_status = 'paid',
                 payment_method = 'online',
                 transaction_id = ?,
                 paid_at = ?
             WHERE bill_code = ?"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error.', 'booking_ids' => $ids, 'payment_status' => $payment_status];
        }
        mysqli_stmt_bind_param($stmt, 'sss', $transaction_id, $paidAt, $bill_code);
    } elseif ($payment_status === 'failed') {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE bookings
             SET payment_status = 'failed',
                 transaction_id = ?
             WHERE bill_code = ? AND payment_status = 'pending'"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error.', 'booking_ids' => $ids, 'payment_status' => $payment_status];
        }
        mysqli_stmt_bind_param($stmt, 'ss', $transaction_id, $bill_code);
    } else {
        return ['success' => true, 'message' => 'Payment still pending.', 'booking_ids' => $ids, 'payment_status' => 'pending'];
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => (bool) $ok,
        'message' => $ok ? 'Booking payment updated.' : 'Failed to update booking payment.',
        'booking_ids' => $ids,
        'payment_status' => $payment_status,
    ];
}
