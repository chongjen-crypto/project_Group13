<?php
/**
 * Scholar Hub - Sport Facility Booking System
 * booking_history.php — Student booking history
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/notification_helpers.php';
require_once __DIR__ . '/config/toyyibpay.php';
require_once __DIR__ . '/includes/list_pager.php';

$student_name = isset($_SESSION['full_name']) && trim($_SESSION['full_name']) !== ''
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';

$student_email = isset($_SESSION['email'])
    ? htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';

$student_nav_active = 'history';
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$listPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

// Manual payment sync (avoid blocking every page load with ToyyibPay API calls).
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'sync_payments'
    && $user_id > 0
) {
    $syncCount = 0;
    if (toyyibpay_is_configured()) {
        $syncCount = toyyibpay_sync_user_pending_bookings($conn, $user_id, 5);
    }
    $syncParam = $syncCount > 0 ? 'synced=' . $syncCount : 'synced=0';
    header('Location: booking_history.php?' . $syncParam);
    exit();
}

// Handle Cancel Action
$cancel_msg = '';
$sync_msg = '';
if (isset($_GET['synced'])) {
    $syncCount = (int) $_GET['synced'];
    $sync_msg = $syncCount > 0
        ? $syncCount . ' payment(s) updated to Paid.'
        : 'No new paid bookings found. If you just paid, wait a moment and try again.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = (int)$_POST['booking_id'];
    $user_id = (int)$_SESSION['user_id'];
    $bookingMeta = null;
    $metaStmt = mysqli_prepare(
        $conn,
        "SELECT facility_type, booking_date, start_time, end_time
         FROM bookings WHERE booking_id = ? AND user_id = ? LIMIT 1"
    );
    if ($metaStmt) {
        mysqli_stmt_bind_param($metaStmt, "ii", $booking_id, $user_id);
        mysqli_stmt_execute($metaStmt);
        $metaRes = mysqli_stmt_get_result($metaStmt);
        $bookingMeta = $metaRes ? mysqli_fetch_assoc($metaRes) : null;
        mysqli_stmt_close($metaStmt);
    }
    
    // Update booking status to cancelled if it's currently pending or approved and belongs to this user
    $sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND booking_status IN ('pending', 'approved')";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $cancel_msg = "Booking #{$booking_id} has been cancelled successfully.";
            if (is_array($bookingMeta)) {
                $facility = notifications_facility_label((string) ($bookingMeta['facility_type'] ?? ''));
                $time = substr((string) ($bookingMeta['start_time'] ?? ''), 0, 5) . ' - ' . substr((string) ($bookingMeta['end_time'] ?? ''), 0, 5);
                $studentName = trim((string) ($_SESSION['full_name'] ?? 'A student'));
                notifications_send_to_roles(
                    $conn,
                    ['staff', 'admin'],
                    'Student Booking Cancelled',
                    "{$studentName} cancelled booking #{$booking_id} for {$facility} on {$bookingMeta['booking_date']} ({$time})."
                );
            }
        } else {
            $cancel_msg = "Could not cancel booking. It may have already been processed.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch bookings for this user (paginated)
$bookings = [];
$bookingTotal = 0;
$countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c FROM bookings WHERE user_id = ?');
if ($countStmt) {
    mysqli_stmt_bind_param($countStmt, 'i', $user_id);
    mysqli_stmt_execute($countStmt);
    $countRes = mysqli_stmt_get_result($countStmt);
    $countRow = $countRes ? mysqli_fetch_assoc($countRes) : null;
    $bookingTotal = (int) ($countRow['c'] ?? 0);
    mysqli_stmt_close($countStmt);
}

$pagination = list_pager_meta($bookingTotal, $listPage, $perPage);
$pageItems = list_pager_page_items($pagination['page'], $pagination['total_pages']);

$sql = "SELECT b.*, 
        CASE WHEN b.facility_type = 'snooker' THEN 'Snooker Room'
             WHEN b.facility_type = 'gym' THEN 'Gym Room'
             WHEN b.facility_type = 'swimming' THEN 'Swimming Pool'
             WHEN b.facility_type = 'track' THEN 'Track Field'
             WHEN b.facility_type = 'badminton' THEN 'Badminton Court'
             WHEN b.facility_type = 'basketball' THEN 'Basketball Court'
             WHEN b.facility_type = 'futsal' THEN 'Futsal Court'
             WHEN b.facility_type = 'tennis' THEN 'Tennis Court'
             WHEN b.facility_type = 'volleyball' THEN 'Volleyball Court'
             ELSE b.facility_type END as facility_name
        FROM bookings b 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    $limit = $pagination['per_page'];
    $offset = $pagination['offset'];
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $bookings[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$has_pending_online_payment = false;
$pendingPayStmt = mysqli_prepare(
    $conn,
    "SELECT 1 FROM bookings
     WHERE user_id = ?
       AND LOWER(payment_status) = 'pending'
       AND bill_code IS NOT NULL AND bill_code != ''
       AND booking_status NOT IN ('cancelled', 'rejected')
     LIMIT 1"
);
if ($pendingPayStmt) {
    mysqli_stmt_bind_param($pendingPayStmt, 'i', $user_id);
    mysqli_stmt_execute($pendingPayStmt);
    mysqli_stmt_store_result($pendingPayStmt);
    $has_pending_online_payment = mysqli_stmt_num_rows($pendingPayStmt) > 0;
    mysqli_stmt_close($pendingPayStmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Booking History — Scholar Hub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_styles.php'; ?>
    <?php include __DIR__ . '/includes/list_pager_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/student_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <header class="top-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <div class="page-title">Booking History</div>
                    <div class="welcome-text">View and manage your bookings</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime"></div>
                <?php include __DIR__ . '/includes/student_notification_bell.php'; ?>
                <div class="avatar" title="<?php echo $student_email !== '' ? $student_email : $student_name; ?>">
                    <?php
                    $parts = preg_split('/\s+/', trim($_SESSION['full_name'] ?? 'S'));
                    $ini = strtoupper(substr($parts[0] ?? 'S', 0, 1));
                    if (isset($parts[1]) && $parts[1] !== '') {
                        $ini .= strtoupper(substr($parts[1], 0, 1));
                    }
                    echo htmlspecialchars($ini, ENT_QUOTES, 'UTF-8');
                    ?>
                </div>
            </div>
        </div>
    </header>

    <main class="content-area">
        <?php if ($cancel_msg): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($cancel_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($sync_msg !== ''): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($sync_msg, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0"><i class="bi bi-clock-history text-primary"></i> My Bookings</h2>
            <?php if ($has_pending_online_payment && toyyibpay_is_configured()): ?>
            <form method="post" class="m-0">
                <input type="hidden" name="action" value="sync_payments">
                <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                    <i class="bi bi-arrow-repeat me-1"></i> Refresh payment status
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="card card-soft p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Booking ID</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Payment</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">You have no bookings yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $b): 
                                $status_class = 'text-bg-secondary';
                                if ($b['booking_status'] === 'pending') $status_class = 'text-bg-warning';
                                elseif ($b['booking_status'] === 'approved') $status_class = 'text-bg-success';
                                elseif ($b['booking_status'] === 'rejected') $status_class = 'text-bg-danger';
                                elseif ($b['booking_status'] === 'cancelled') $status_class = 'text-bg-dark';
                            ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?php echo htmlspecialchars($b['booking_id']); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($b['facility_name']); ?></td>
                                <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                                <td><?php echo htmlspecialchars(substr($b['start_time'], 0, 5) . ' - ' . substr($b['end_time'], 0, 5)); ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $status_class; ?>">
                                        <?php echo ucfirst(htmlspecialchars($b['booking_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($b['booking_status'] === 'rejected'): ?>
                                        <?php if (!empty($b['reject_reason'])): ?>
                                            <span class="text-danger small fw-semibold"><?php echo htmlspecialchars($b['reject_reason']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">No remarks provided.</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $payStatus = strtolower((string) ($b['payment_status'] ?? 'pending'));
                                    $payBadge = 'text-bg-warning';
                                    $payLabel = 'Pending';
                                    if ($payStatus === 'paid') {
                                        $payBadge = 'text-bg-success';
                                        $payLabel = 'Paid';
                                    } elseif ($payStatus === 'failed') {
                                        $payBadge = 'text-bg-danger';
                                        $payLabel = 'Failed';
                                    }
                                    ?>
                                    <span class="badge rounded-pill <?php echo $payBadge; ?>"><?php echo $payLabel; ?></span>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if (in_array($payStatus, ['pending', 'failed'], true) && !in_array($b['booking_status'], ['cancelled', 'rejected'], true)): ?>
                                    <a href="create_bill.php?booking_id=<?php echo (int) $b['booking_id']; ?>" class="btn btn-sm btn-success rounded-pill me-1">
                                        <i class="bi bi-credit-card me-1"></i>Pay Now
                                    </a>
                                    <?php endif; ?>
                                    <?php if (in_array($b['booking_status'], ['pending', 'approved'])): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b['booking_id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Cancel</button>
                                    </form>
                                    <?php elseif (!in_array($payStatus, ['pending', 'failed'], true) || in_array($b['booking_status'], ['cancelled', 'rejected'], true)): ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
            list_pager_render(
                $pagination,
                $pageItems,
                'booking_history.php',
                'booking',
                'bookings',
                [],
                'Booking pages'
            );
            ?>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    function updateDateTime() {
        var el = document.getElementById('liveDateTime');
        if (!el) return;
        el.textContent = new Date().toLocaleString(undefined, {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var btnToggle = document.getElementById('btnMenuToggle');

    function closeSidebar() {
        if(sidebar) sidebar.classList.remove('show');
        if(backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    function openSidebar() {
        if(sidebar) sidebar.classList.add('show');
        if(backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('show')) closeSidebar();
            else openSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }
})();
</script>
<?php include __DIR__ . '/includes/student_notification_scripts.php'; ?>
</body>
</html>
