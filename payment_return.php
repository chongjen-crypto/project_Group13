<?php
/**
 * Scholar Hub — ToyyibPay return page (user browser redirect after payment).
 *
 * Displays success or failure message and booking summary.
 * Note: Authoritative payment status is set by payment_callback.php.
 */
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/config/toyyibpay.php';

$user_id = (int) $_SESSION['user_id'];
$student_nav_active = 'history';

// -------------------------------------------------------------------------
// Section 1 — Read return parameters (GET from ToyyibPay)
// -------------------------------------------------------------------------
$status = trim((string) ($_GET['status'] ?? $_GET['status_id'] ?? ''));
$billcode = trim((string) ($_GET['billcode'] ?? ''));
$order_id = trim((string) ($_GET['order_id'] ?? ''));
$refno = trim((string) ($_GET['refno'] ?? ''));
$reason = trim((string) ($_GET['reason'] ?? $_GET['msg'] ?? ''));
$amount = trim((string) ($_GET['amount'] ?? ''));
$hash = trim((string) ($_GET['hash'] ?? ''));

$hashValid = false;
if ($status !== '' && $order_id !== '' && $refno !== '' && $hash !== '') {
    $hashValid = toyyibpay_verify_callback_hash($status, $order_id, $refno, $hash);
}

// On localhost the server callback often cannot reach XAMPP; sync status from return URL when hash is valid.
if ($hashValid && $billcode !== '' && in_array($status, ['1', '3'], true)) {
    $mappedStatus = toyyibpay_map_payment_status($status);
    $transactionId = $refno !== '' ? $refno : ('return-' . $order_id);
    toyyibpay_apply_payment_update($conn, $billcode, $mappedStatus, $transactionId);
}

$isSuccess = ($status === '1');
$isFailed = ($status === '3');
$isPending = ($status === '2' || $status === '');

// -------------------------------------------------------------------------
// Section 2 — Load booking details from bill code or order reference
// -------------------------------------------------------------------------
$bookings = [];
$primaryBookingId = 0;

if ($billcode !== '') {
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
         WHERE b.bill_code = ? AND b.user_id = ?
         ORDER BY b.booking_id ASC"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $billcode, $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $bookings[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

if ($bookings === [] && preg_match('/^SH-(\d+)$/', $order_id, $m)) {
    $primaryBookingId = (int) $m[1];
    $row = toyyibpay_fetch_booking($conn, $primaryBookingId, $user_id);
    if ($row !== null) {
        $groupIds = toyyibpay_resolve_booking_group($conn, $row, null);
        foreach ($groupIds as $gid) {
            $b = toyyibpay_fetch_booking($conn, $gid, $user_id);
            if ($b !== null) {
                $bookings[] = $b;
            }
        }
    }
}

if ($bookings !== []) {
    $primaryBookingId = (int) ($bookings[0]['booking_id'] ?? 0);
    $dbStatus = strtolower((string) ($bookings[0]['payment_status'] ?? 'pending'));
    if ($dbStatus === 'paid') {
        $isSuccess = true;
        $isFailed = false;
        $isPending = false;
    } elseif ($dbStatus === 'failed') {
        $isFailed = true;
        $isSuccess = false;
    }
}

$totalPaid = 0.0;
foreach ($bookings as $b) {
    $totalPaid += (float) ($b['payment_amount'] ?? 0);
}
if ($amount !== '' && is_numeric($amount)) {
    $totalPaid = ((float) $amount) / 100;
}

$student_name = isset($_SESSION['full_name'])
    ? htmlspecialchars((string) $_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Payment Result — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_styles.php'; ?>
    <style>
        .result-card {
            max-width: 640px;
            margin: 0 auto;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 8px 28px rgba(0,0,0,0.06);
            background: #fff;
            padding: 2rem;
        }
        .result-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .result-icon.success { background: #d1fae5; color: #059669; }
        .result-icon.failed { background: #fee2e2; color: #dc2626; }
        .result-icon.pending { background: #fef3c7; color: #d97706; }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/student_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <header class="top-header">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <div class="page-title">Payment Result</div>
                <div class="welcome-text">ToyyibPay checkout</div>
            </div>
        </div>
    </header>

    <main class="content-area">
        <div class="result-card text-center">
            <?php if ($isSuccess): ?>
                <div class="result-icon success"><i class="bi bi-check-lg"></i></div>
                <h1 class="h4 fw-bold text-success mb-2">Payment Successful</h1>
                <p class="text-muted mb-4">Your booking payment was received. Staff will review your booking shortly.</p>
            <?php elseif ($isFailed): ?>
                <div class="result-icon failed"><i class="bi bi-x-lg"></i></div>
                <h1 class="h4 fw-bold text-danger mb-2">Payment Failed</h1>
                <p class="text-muted mb-4">
                    <?php echo htmlspecialchars($reason !== '' ? $reason : 'The payment could not be completed.', ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php else: ?>
                <div class="result-icon pending"><i class="bi bi-hourglass-split"></i></div>
                <h1 class="h4 fw-bold text-warning mb-2">Payment Pending</h1>
                <p class="text-muted mb-4">Your payment is being processed. Please check booking history in a few minutes.</p>
            <?php endif; ?>

            <?php if (!$hashValid && $hash !== ''): ?>
                <div class="alert alert-warning small text-start">Payment details could not be fully verified. Status shown reflects our latest records.</div>
            <?php endif; ?>

            <?php if ($bookings !== []): ?>
                <div class="text-start border rounded-3 p-3 mb-4 bg-light">
                    <h2 class="h6 fw-bold mb-3">Booking details</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Reference</dt>
                        <dd class="col-7 fw-semibold">#<?php echo (int) $primaryBookingId; ?><?php echo count($bookings) > 1 ? ' (+' . (count($bookings) - 1) . ' slots)' : ''; ?></dd>

                        <dt class="col-5 text-muted">Facility</dt>
                        <dd class="col-7"><?php echo htmlspecialchars((string) ($bookings[0]['facility_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>

                        <dt class="col-5 text-muted">Date</dt>
                        <dd class="col-7"><?php echo htmlspecialchars((string) ($bookings[0]['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>

                        <dt class="col-5 text-muted">Amount</dt>
                        <dd class="col-7 fw-semibold">RM <?php echo number_format($totalPaid, 2); ?></dd>

                        <?php if ($billcode !== ''): ?>
                        <dt class="col-5 text-muted">Bill code</dt>
                        <dd class="col-7"><code><?php echo htmlspecialchars($billcode, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                        <?php endif; ?>

                        <?php if ($refno !== ''): ?>
                        <dt class="col-5 text-muted">Transaction ref</dt>
                        <dd class="col-7"><code><?php echo htmlspecialchars($refno, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="student_dashboard.php" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
                <a href="booking_history.php" class="btn btn-outline-dark rounded-pill px-4">
                    <i class="bi bi-clock-history me-1"></i> Booking History
                </a>
                <?php if ($isFailed && $primaryBookingId > 0): ?>
                <a href="create_bill.php?booking_id=<?php echo (int) $primaryBookingId; ?>" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-credit-card me-1"></i> Try Again
                </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var btnMenu = document.getElementById('btnMenuToggle');
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    if (btnMenu) {
        btnMenu.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('show')) closeSidebar();
            else openSidebar();
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
})();
</script>
</body>
</html>
