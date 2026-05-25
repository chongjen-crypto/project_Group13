<?php
/**
 * Scholar Hub — Staff booking request detail
 * Opened from staff_dashboard.php or admin_booking_requests.php
 */

session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'approve') {
        $sql = "UPDATE bookings SET booking_status = 'approved' WHERE booking_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $msg = 'Booking approved.';
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reject_reason'] ?? '');
        $sql = "UPDATE bookings SET booking_status = 'rejected', reject_reason = ? WHERE booking_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $reason, $id);
        mysqli_stmt_execute($stmt);
        $msg = 'Booking rejected.';
    }
}

$row = null;
if ($id > 0) {
    $sql = "SELECT b.*, u.full_name as student, 
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
            JOIN users u ON b.user_id = u.id 
            WHERE b.booking_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
}

$back_link = $_SESSION['role'] === 'admin' ? 'admin_booking_requests.php' : 'staff_view_requests.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Booking request — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f3f4f6; min-height: 100vh; font-family: system-ui, sans-serif; }
        .card-box { max-width: 560px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="py-4 py-md-5">
    <div class="container px-3">
        <a href="<?php echo $back_link; ?>" class="btn btn-outline-dark btn-sm rounded-pill mb-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>

        <?php if ($msg): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if (!$row): ?>
            <div class="alert alert-warning">Request not found.</div>
        <?php else: ?>
            <div class="card card-box border-0 mx-auto">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 fw-bold mb-1">Booking request #<?php echo htmlspecialchars($row['booking_id']); ?></h1>
                    <p class="text-muted small mb-4">Review details, then approve or reject.</p>

                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Student</dt>
                        <dd class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($row['student'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Facility</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['facility_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Date</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['booking_date'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Time</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars(substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?php echo $row['booking_status'] === 'pending' ? 'text-bg-warning' : ($row['booking_status'] === 'approved' ? 'text-bg-success' : 'text-bg-danger'); ?>">
                                <?php echo ucfirst(htmlspecialchars($row['booking_status'])); ?>
                            </span>
                        </dd>
                        <?php if ($row['booking_status'] === 'rejected' && !empty($row['reject_reason'])): ?>
                            <dt class="col-sm-4 text-muted mt-2">Reject Reason</dt>
                            <dd class="col-sm-8 mt-2 text-danger"><?php echo htmlspecialchars($row['reject_reason'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <?php endif; ?>
                    </dl>

                    <hr class="my-4">

                    <?php if ($row['booking_status'] === 'pending'): ?>
                    <form method="POST" id="approvalForm">
                        <input type="hidden" name="action" id="formAction" value="">
                        
                        <div id="rejectReasonDiv" class="mb-3 d-none">
                            <label for="rejectReason" class="form-label small fw-semibold">Reason for rejection (required)</label>
                            <textarea name="reject_reason" id="rejectReason" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2" id="actionButtons">
                            <button type="button" class="btn btn-success rounded-pill px-4" onclick="submitApprove()">
                                <i class="bi bi-check-lg me-1"></i> Approve
                            </button>
                            <button type="button" class="btn btn-outline-danger rounded-pill px-4" onclick="showReject()">
                                <i class="bi bi-x-lg me-1"></i> Reject
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2 d-none" id="confirmRejectButtons">
                            <button type="button" class="btn btn-danger rounded-pill px-4" onclick="submitReject()">
                                Confirm Reject
                            </button>
                            <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="cancelReject()">
                                Cancel
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function submitApprove() {
        document.getElementById('formAction').value = 'approve';
        document.getElementById('approvalForm').submit();
    }
    function showReject() {
        document.getElementById('rejectReasonDiv').classList.remove('d-none');
        document.getElementById('actionButtons').classList.add('d-none');
        document.getElementById('confirmRejectButtons').classList.remove('d-none');
    }
    function cancelReject() {
        document.getElementById('rejectReasonDiv').classList.add('d-none');
        document.getElementById('actionButtons').classList.remove('d-none');
        document.getElementById('confirmRejectButtons').classList.add('d-none');
        document.getElementById('rejectReason').value = '';
    }
    function submitReject() {
        if (document.getElementById('rejectReason').value.trim() === '') {
            alert('Please provide a reason for rejection.');
            return;
        }
        document.getElementById('formAction').value = 'reject';
        document.getElementById('approvalForm').submit();
    }
    </script>
</body>
</html>
