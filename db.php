<?php
/**
 * Scholar Hub — MySQL connection
 *
 * Configure using ONE of:
 *   1) includes/db_local.php (copy from includes/db_local.example.php) — recommended
 *   2) Environment variables: DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT
 *   3) Defaults below (local XAMPP)
 */
declare(strict_types=1);

if (is_file(__DIR__ . '/includes/db_local.php')) {
    require_once __DIR__ . '/includes/db_local.php';
}

/**
 * @return non-empty-string
 */
function scholarhub_db_env(string $key, string $default = ''): string
{
    if (defined($key)) {
        return (string) constant($key);
    }
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return (string) $v;
    }

    return $default;
}

$host = scholarhub_db_env('DB_HOST', 'localhost');
$user = scholarhub_db_env('DB_USER', 'root');
$pass = scholarhub_db_env('DB_PASS', '');
$name = scholarhub_db_env('DB_NAME', 'facility_booking_system');
$port = (int) scholarhub_db_env('DB_PORT', '3306');

$conn = mysqli_connect($host, $user, $pass, $name, $port);

if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
