<?php
/**
 * staff_view_requests.php – All student booking requests for staff.
 */
require_once __DIR__ . '/includes/staff_auth.php';
require_once __DIR__ . '/db.php';

$staff_nav_active = 'requests';
$staff_page_title = 'Booking Requests';

$status_badges = [
    'pending' => 'text-bg-warning',
    'approved' => 'text-bg-success',
    'rejected' => 'text-bg-danger',
    'cancelled' => 'text-bg-secondary',
    'completed' => 'text-bg-info',
];

$all_bookings = [];
$sql = "SELECT b.booking_id AS id, u.full_name AS student, b.facility_type, b.booking_date AS date,
        b.start_time, b.end_time, b.booking_status,
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
        ORDER BY b.created_at DESC";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['time'] = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5);
        $all_bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Booking Requests — Scholar Hub Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/staff_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/staff_header.php'; ?>

    <main class="content-area">
        <h2 class="section-title"><i class="bi bi-inbox text-primary"></i> All Student Booking Requests</h2>

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
                        <?php if (empty($all_bookings)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No booking requests yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($all_bookings as $b): ?>
                            <?php
                            $st = $b['booking_status'];
                            $badge = $status_badges[$st] ?? 'text-bg-secondary';
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($b['student'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['facility'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($b['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php echo htmlspecialchars($badge, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="pe-4">
                                    <a href="staff_booking_request.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i><?php echo $st === 'pending' ? 'View &amp; Action' : 'View'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
