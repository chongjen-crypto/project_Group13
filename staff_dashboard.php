<?php
/**
 * Scholar Hub — Staff dashboard
 */
require_once __DIR__ . '/includes/staff_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';

$staff_nav_active = 'dashboard';
$staff_page_title = 'Staff Dashboard';

$pending_count = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings WHERE booking_status = 'pending'");
$approved_today = stats_count(
    $conn,
    "SELECT COUNT(*) AS c FROM bookings WHERE booking_status = 'approved' AND booking_date = CURDATE()"
);
$total_bookings = stats_count($conn, "SELECT COUNT(*) AS c FROM bookings");

$overview_stats = [
    [
        'label' => 'Pending Booking Requests',
        'value' => (string) $pending_count,
        'icon' => 'bi-inbox',
        'gradient' => 'linear-gradient(135deg,#f59e0b,#d97706)',
    ],
    [
        'label' => 'Approved Today',
        'value' => (string) $approved_today,
        'icon' => 'bi-check-circle',
        'gradient' => 'linear-gradient(135deg,#10b981,#059669)',
    ],
    [
        'label' => 'Total Bookings',
        'value' => (string) $total_bookings,
        'icon' => 'bi-calendar-check',
        'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)',
    ],
];

$pending_bookings = [];
$sql = "SELECT b.booking_id AS id, u.full_name AS student, b.facility_type, b.booking_date AS date, b.start_time, b.end_time,
        CASE WHEN b.facility_type = 'snooker' THEN 'Snooker Room'
             WHEN b.facility_type = 'gym' THEN 'Gym Room'
             WHEN b.facility_type = 'swimming' THEN 'Swimming Pool'
             WHEN b.facility_type = 'track' THEN 'Track Field'
             WHEN b.facility_type = 'badminton' THEN 'Badminton Court'
             WHEN b.facility_type = 'basketball' THEN 'Basketball Court'
             WHEN b.facility_type = 'futsal' THEN 'Futsal Court'
             WHEN b.facility_type = 'tennis' THEN 'Tennis Court'
             WHEN b.facility_type = 'volleyball' THEN 'Volleyball Court'
             ELSE b.facility_type END AS facility
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_status = 'pending'
        ORDER BY b.created_at ASC
        LIMIT 10";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['time'] = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5);
        $pending_bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scholar Hub — Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/staff_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/staff_header.php'; ?>

    <main class="content-area">

        <h2 class="section-title"><i class="bi bi-graph-up-arrow text-primary"></i> Overview</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($overview_stats as $s): ?>
            <div class="col-6 col-lg-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: <?php echo htmlspecialchars($s['gradient'], ENT_QUOTES, 'UTF-8'); ?>;">
                        <i class="bi <?php echo htmlspecialchars($s['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($s['value'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2 class="section-title"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Actions</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <a href="staff_view_requests.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#ea580c,#c2410c);"><i class="bi bi-clipboard-check"></i></div>
                    <h6>Review Bookings</h6>
                    <p>View all student requests</p>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="send_notification.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#7c3aed,#6d28d9);"><i class="bi bi-send"></i></div>
                    <h6>Send Notification</h6>
                    <p>Message all students</p>
                </a>
            </div>
        </div>

        <h2 class="section-title"><i class="bi bi-journal-text text-secondary"></i> Pending Bookings</h2>
        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Student Name</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th class="pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_bookings)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No pending bookings right now.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pending_bookings as $b): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($b['student'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['facility'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge rounded-pill text-bg-warning">Pending</span></td>
                                <td class="pe-4">
                                    <a href="staff_booking_request.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top bg-light text-end">
                <a href="staff_view_requests.php" class="btn btn-sm btn-outline-dark rounded-pill">View all booking requests</a>
            </div>
        </div>

        <h2 class="section-title"><i class="bi bi-megaphone text-secondary"></i> Student Notifications</h2>
        <div class="table-wrap p-4 mb-4">
            <p class="text-muted small mb-3">Send a title and message that appears in each student&apos;s notification bell.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="send_notification.php" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-send me-1"></i> Send Notification
                </a>
                <a href="view_sent_notifications.php" class="btn btn-outline-dark rounded-pill px-4">
                    <i class="bi bi-bell me-1"></i> View Sent
                </a>
            </div>
        </div>

        <footer class="text-center text-muted small mt-4 pb-2">
            &copy; <?php echo date('Y'); ?> Scholar Hub — Staff Portal
        </footer>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
