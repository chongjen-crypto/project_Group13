<?php
/**
 * staff_view_requests.php – List all pending booking requests for staff.
 * Provides a table with student name, facility, date, time and a link to view details.
 */
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/db.php';
// Fetch pending bookings with student info
$sql = "SELECT b.id, u.full_name AS student, b.facility_type, b.booking_date, b.start_time, b.end_time, b.booking_status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_status = 'pending'
        ORDER BY b.created_at DESC";
$res = mysqli_query($conn, $sql);
$pending = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['time'] = substr($row['start_time'],0,5) . ' - ' . substr($row['end_time'],0,5);
        // Resolve facility name
        $fac = $row['facility_type'];
        $facilityMap = [
            'snooker' => 'Snooker Room',
            'gym' => 'Gym Room',
            'swimming' => 'Swimming Pool',
            'track' => 'Track Field',
            'badminton' => 'Badminton Court',
            'basketball' => 'Basketball Court',
            'futsal' => 'Futsal Court',
            'tennis' => 'Tennis Court',
            'volleyball' => 'Volleyball Court'
        ];
        $row['facility'] = $facilityMap[$fac] ?? $fac;
        $pending[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff – Booking Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php
$staff_nav_active = 'requests';
include __DIR__ . '/includes/staff_sidebar.php';
?>
<main class="content-area">
    <div class="container py-4">
        <h2 class="section-title"><i class="bi bi-inbox"></i> Booking Requests</h2>
        <?php if (empty($pending)): ?>
            <p class="text-muted">No pending booking requests.</p>
        <?php else: ?>
            <div class="table-wrap mb-4">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th class="pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $b): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($b['student'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($b['facility'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($b['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($b['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge rounded-pill text-bg-warning">Pending</span></td>
                                    <td class="pe-4">
                                        <a href="staff_booking_request.php?id=<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
