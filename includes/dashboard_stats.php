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

    return [
        'bookings_today' => $bookings_today,
        'pending' => $pending,
        'most_facility' => $most_name,
        'most_count' => $most_count,
    ];
}

/**
 * Monthly booking counts for trending chart (all months with data).
 * @return array{months: list<array{sort_key: string, month: string, value: int}>, max: int}
 */
function stats_booking_monthly_trend(mysqli $conn): array
{
    $monthly = [];
    $sqlMonths = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
                         DATE_FORMAT(created_at, '%b %Y') AS month_label,
                         COUNT(*) AS value
                  FROM bookings
                  GROUP BY sort_key, month_label
                  ORDER BY sort_key ASC";
    $resM = mysqli_query($conn, $sqlMonths);
    if ($resM) {
        while ($row = mysqli_fetch_assoc($resM)) {
            $monthly[] = [
                'sort_key' => (string) $row['sort_key'],
                'month' => (string) $row['month_label'],
                'value' => (int) $row['value'],
            ];
        }
    }

    if ($monthly === []) {
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime("-{$i} months");
            $monthly[] = [
                'sort_key' => date('Y-m', $ts),
                'month' => date('M Y', $ts),
                'value' => 0,
            ];
        }
    }

    $maxVal = max(1, max(array_column($monthly, 'value')));

    return [
        'months' => $monthly,
        'max' => $maxVal,
    ];
}

/**
 * Month options for booking report filters (newest first).
 * @return list<array{value: string, label: string}>
 */
function stats_booking_report_months(mysqli $conn): array
{
    $months = [];
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                   DATE_FORMAT(created_at, '%M %Y') AS label
            FROM bookings
            GROUP BY ym, label
            ORDER BY ym DESC
            LIMIT 24";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $months[] = [
                'value' => (string) $row['ym'],
                'label' => (string) $row['label'],
            ];
        }
    }

    $currentYm = date('Y-m');
    $hasCurrent = false;
    foreach ($months as $m) {
        if ($m['value'] === $currentYm) {
            $hasCurrent = true;
            break;
        }
    }
    if (!$hasCurrent) {
        array_unshift($months, [
            'value' => $currentYm,
            'label' => date('F Y'),
        ]);
    }

    return $months;
}

/**
 * Per-facility booking totals for admin reports (overall or by month).
 *
 * @return list<array{facility_type: string, facility_name: string, total_bookings: int, approved_bookings: int, earnings: float}>
 */
function stats_facility_booking_breakdown(mysqli $conn, string $view = 'overall', ?string $monthYm = null): array
{
    require_once __DIR__ . '/facility_admin_helpers.php';

    $view = $view === 'month' ? 'month' : 'overall';
    if ($view === 'month' && ($monthYm === null || !preg_match('/^\d{4}-\d{2}$/', $monthYm))) {
        $monthYm = date('Y-m');
    }

    $items = [];
    foreach (facility_canonical_meta() as $type => $meta) {
        $facilityName = facility_display_name($type);
        $total = 0;
        $approved = 0;
        $earnings = 0.0;

        if ($view === 'month') {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT
                    COUNT(*) AS total_bookings,
                    SUM(CASE WHEN booking_status = 'approved' THEN 1 ELSE 0 END) AS approved_bookings,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END), 0) AS earnings
                 FROM bookings
                 WHERE facility_type = ?
                   AND DATE_FORMAT(created_at, '%Y-%m') = ?"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $type, $monthYm);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);
                if ($row) {
                    $total = (int) ($row['total_bookings'] ?? 0);
                    $approved = (int) ($row['approved_bookings'] ?? 0);
                    $earnings = (float) ($row['earnings'] ?? 0);
                }
            }
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT
                    COUNT(*) AS total_bookings,
                    SUM(CASE WHEN booking_status = 'approved' THEN 1 ELSE 0 END) AS approved_bookings,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END), 0) AS earnings
                 FROM bookings
                 WHERE facility_type = ?"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $type);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);
                if ($row) {
                    $total = (int) ($row['total_bookings'] ?? 0);
                    $approved = (int) ($row['approved_bookings'] ?? 0);
                    $earnings = (float) ($row['earnings'] ?? 0);
                }
            }
        }

        $items[] = [
            'facility_type' => $type,
            'facility_name' => $facilityName,
            'total_bookings' => $total,
            'approved_bookings' => $approved,
            'earnings' => $earnings,
        ];
    }

    return $items;
}

function stats_wallet_facility_court_label(mysqli $conn, string $facility_type, ?int $court_id): string
{
    require_once __DIR__ . '/notification_helpers.php';
    require_once __DIR__ . '/booking_helpers.php';

    $facility = notifications_facility_label($facility_type);
    if ($court_id === null || $court_id <= 0 || !booking_is_court_based($facility_type)) {
        return $facility;
    }

    return $facility . ' · ' . booking_court_display_name($conn, $facility_type, $court_id);
}

function stats_wallet_format_datetime(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    return date('M j, Y g:i A', $ts);
}

/**
 * @return array{
 *   total_income: float,
 *   paid_count: int,
 *   paid_this_month: float,
 *   topups_today: float,
 *   transactions: list<array<string, mixed>>,
 *   pagination: array{page: int, per_page: int, total: int, total_pages: int, from: int, to: int}
 * }
 */
function stats_wallet_overview(mysqli $conn, int $page = 1, int $per_page = 10): array
{
    $total_income = stats_sum(
        $conn,
        "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings WHERE payment_status = 'paid' AND payment_amount IS NOT NULL"
    );
    $paid_count = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE payment_status = 'paid' AND payment_amount IS NOT NULL");
    $paid_this_month = stats_sum(
        $conn,
        "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings
         WHERE payment_status = 'paid' AND payment_amount IS NOT NULL
           AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURDATE())
           AND MONTH(COALESCE(paid_at, created_at)) = MONTH(CURDATE())"
    );
    $topups_today = stats_sum(
        $conn,
        "SELECT COALESCE(SUM(payment_amount), 0) AS total FROM bookings
         WHERE payment_status = 'paid' AND payment_amount IS NOT NULL AND DATE(COALESCE(paid_at, created_at)) = CURDATE()"
    );

    $per_page = max(1, min(50, $per_page));
    $page = max(1, $page);
    $total_tx = stats_count(
        $conn,
        "SELECT COUNT(*) AS c FROM bookings WHERE payment_amount IS NOT NULL AND payment_amount > 0"
    );
    $total_pages = max(1, (int) ceil($total_tx / $per_page));
    if ($page > $total_pages) {
        $page = $total_pages;
    }
    $offset = ($page - 1) * $per_page;

    $transactions = [];
    $sql = "SELECT b.booking_id, b.facility_type, b.court_id, u.full_name,
                   b.payment_method, b.payment_amount, b.payment_status,
                   COALESCE(b.paid_at, b.created_at) AS paid_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.payment_amount IS NOT NULL AND b.payment_amount > 0
            ORDER BY COALESCE(b.paid_at, b.created_at) DESC, b.booking_id DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $per_page, $offset);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $method = (string) ($row['payment_method'] ?? 'payment');
            $type = $row['payment_status'] === 'paid' ? 'Booking Payment' : ucfirst((string) $row['payment_status']);
            if ($method === 'in_app') {
                $type = 'Wallet Payment';
            } elseif ($method === 'online' || $method === 'tng') {
                $type = 'Online Payment';
            }
            $facilityType = (string) ($row['facility_type'] ?? '');
            $courtId = isset($row['court_id']) && $row['court_id'] !== null ? (int) $row['court_id'] : null;
            $transactions[] = [
                'id' => 'BK-' . (int) $row['booking_id'],
                'user' => (string) $row['full_name'],
                'facility_court' => stats_wallet_facility_court_label($conn, $facilityType, $courtId),
                'type' => $type,
                'amount' => (float) $row['payment_amount'],
                'date' => stats_wallet_format_datetime((string) ($row['paid_at'] ?? '')),
            ];
        }
        mysqli_stmt_close($stmt);
    }

    $from = $total_tx === 0 ? 0 : $offset + 1;
    $to = min($offset + $per_page, $total_tx);

    return [
        'total_income' => $total_income,
        'paid_count' => $paid_count,
        'paid_this_month' => $paid_this_month,
        'topups_today' => $topups_today,
        'transactions' => $transactions,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total_tx,
            'total_pages' => $total_pages,
            'from' => $from,
            'to' => $to,
        ],
    ];
}
