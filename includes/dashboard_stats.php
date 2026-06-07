<?php
/**
 * Dashboard statistics from database (returns 0 when empty).
 */

function stats_count(mysqli $conn, string $sql): int
{
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return 0;
    }
    $row = mysqli_fetch_assoc($res);
    return (int) ($row['c'] ?? 0);
}

function stats_sum(mysqli $conn, string $sql): float
{
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return 0.0;
    }
    $row = mysqli_fetch_assoc($res);
    return (float) ($row['total'] ?? 0);
}

function stats_admin_overview(mysqli $conn): array
{
    $students = stats_count($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'student'");
    $staff = stats_count($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'staff'");
    $bookings = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings");
    $income = stats_sum(
        $conn,
        "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings WHERE payment_status = 'paid' AND payment_amount IS NOT NULL"
    );

    return [
        [
            'label' => 'Total Students',
            'value' => (string) $students,
            'icon' => 'bi-mortarboard',
            'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)',
        ],
        [
            'label' => 'Total Staff',
            'value' => (string) $staff,
            'icon' => 'bi-person-workspace',
            'gradient' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)',
        ],
        [
            'label' => 'Total Bookings',
            'value' => (string) $bookings,
            'icon' => 'bi-calendar-check',
            'gradient' => 'linear-gradient(135deg,#10b981,#059669)',
        ],
        [
            'label' => 'Total Income',
            'value' => 'RM ' . number_format($income, 2),
            'icon' => 'bi-cash-stack',
            'gradient' => 'linear-gradient(135deg,#f59e0b,#d97706)',
        ],
    ];
}

function stats_student_overview(mysqli $conn, int $user_id): array
{
    $uid = (int) $user_id;
    $total = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE user_id = {$uid}");
    $pending = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE user_id = {$uid} AND booking_status = 'pending'");
    $approved = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE user_id = {$uid} AND booking_status = 'approved'");

    require_once __DIR__ . '/notification_helpers.php';
    $unread = notifications_unread_count($conn, $uid);

    return [
        [
            'label' => 'My Bookings',
            'value' => (string) $total,
            'icon' => 'bi-calendar-check',
            'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)',
        ],
        [
            'label' => 'Pending',
            'value' => (string) $pending,
            'icon' => 'bi-hourglass-split',
            'gradient' => 'linear-gradient(135deg,#f59e0b,#d97706)',
        ],
        [
            'label' => 'Approved',
            'value' => (string) $approved,
            'icon' => 'bi-check-circle',
            'gradient' => 'linear-gradient(135deg,#10b981,#059669)',
        ],
        [
            'label' => 'Unread Alerts',
            'value' => (string) $unread,
            'icon' => 'bi-bell',
            'gradient' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)',
        ],
    ];
}

function stats_booking_reports(mysqli $conn): array
{
    $bookings_today = stats_count(
        $conn,
        "SELECT COUNT(*) AS c FROM bookings WHERE booking_date = CURDATE() AND booking_status NOT IN ('cancelled', 'rejected')"
    );
    $pending = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE booking_status = 'pending'");

    $most_name = '—';
    $most_count = 0;
    $sql = "SELECT facility_type, COUNT(*) AS c FROM bookings GROUP BY facility_type ORDER BY c DESC LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $most_count = (int) $row['c'];
        require_once __DIR__ . '/facility_admin_helpers.php';
        $most_name = facility_display_name((string) $row['facility_type']);
    }

    $monthly = [];
    $sqlMonths = "SELECT DATE_FORMAT(created_at, '%b') AS month_label,
                         DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
                         COUNT(*) AS value
                  FROM bookings
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                  GROUP BY sort_key, month_label
                  ORDER BY sort_key ASC";
    $resM = mysqli_query($conn, $sqlMonths);
    if ($resM) {
        while ($row = mysqli_fetch_assoc($resM)) {
            $monthly[] = [
                'month' => (string) $row['month_label'],
                'value' => (int) $row['value'],
            ];
        }
    }

    if ($monthly === []) {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        foreach ($labels as $m) {
            $monthly[] = ['month' => $m, 'value' => 0];
        }
    }

    $maxVal = max(1, max(array_column($monthly, 'value')));

    return [
        'bookings_today' => $bookings_today,
        'pending' => $pending,
        'most_facility' => $most_name,
        'most_count' => $most_count,
        'monthly' => $monthly,
        'monthly_max' => $maxVal,
    ];
}

function stats_wallet_overview(mysqli $conn): array
{
    $total_income = stats_sum(
        $conn,
        "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings WHERE payment_status = 'paid' AND payment_amount IS NOT NULL"
    );
    $paid_count = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE payment_status = 'paid' AND payment_amount IS NOT NULL");
    $topups_today = stats_sum(
        $conn,
        "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings
         WHERE payment_status = 'paid' AND payment_amount IS NOT NULL AND DATE(created_at) = CURDATE()"
    );

    $transactions = [];
    $sql = "SELECT b.booking_id, u.full_name, b.payment_method, b.payment_amount, b.payment_status, b.created_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.payment_amount IS NOT NULL AND b.payment_amount > 0
            ORDER BY b.created_at DESC
            LIMIT 50";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $method = (string) ($row['payment_method'] ?? 'payment');
            $type = $row['payment_status'] === 'paid' ? 'Booking Payment' : ucfirst((string) $row['payment_status']);
            if ($method === 'in_app') {
                $type = 'Wallet Payment';
            } elseif ($method === 'online' || $method === 'tng') {
                $type = 'Online Payment';
            }
            $transactions[] = [
                'id' => 'BK-' . (int) $row['booking_id'],
                'user' => (string) $row['full_name'],
                'type' => $type,
                'amount' => (float) $row['payment_amount'],
                'date' => (string) $row['created_at'],
            ];
        }
    }

    return [
        'total_income' => $total_income,
        'paid_count' => $paid_count,
        'topups_today' => $topups_today,
        'transactions' => $transactions,
    ];
}
