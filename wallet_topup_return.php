<?php
/**
 * Scholar Hub — ToyyibPay return page after wallet top-up payment.
 */
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/wallet_helpers.php';
require_once __DIR__ . '/config/toyyibpay.php';

$user_id = (int) $_SESSION['user_id'];
$student_nav_active = 'wallet';

$status = trim((string) ($_GET['status'] ?? $_GET['status_id'] ?? ''));
$billcode = trim((string) ($_GET['billcode'] ?? ''));
$order_id = trim((string) ($_GET['order_id'] ?? ''));
$refno = trim((string) ($_GET['refno'] ?? ''));
$reason = trim((string) ($_GET['reason'] ?? $_GET['msg'] ?? ''));
$amount = trim((string) ($_GET['amount'] ?? ''));
$hash = trim((string) ($_GET['hash'] ?? ''));

$transaction_id = trim((string) ($_GET['transaction_id'] ?? ''));

$syncResult = toyyibpay_sync_payment_from_return(
    $conn,
    $user_id,
    $status,
    $billcode,
    $order_id,
    $refno,
    $hash,
    $transaction_id
);
$hashValid = (bool) ($syncResult['hash_valid'] ?? false);

$isSuccess = ($status === '1');
$isFailed = ($status === '3');
$isPending = ($status === '2' || $status === '');

$topup = $billcode !== '' ? wallet_topup_fetch_by_bill_code($conn, $billcode) : null;
if ($topup !== null && (int) ($topup['user_id'] ?? 0) !== $user_id) {
    $topup = null;
}

if ($topup === null && wallet_topup_parse_order_ref($order_id) !== null) {
    $topup = wallet_topup_fetch($conn, (int) wallet_topup_parse_order_ref($order_id), $user_id);
}

$topupAmount = $topup ? (float) ($topup['amount'] ?? 0) : 0.0;
if ($amount !== '' && is_numeric($amount)) {
    $topupAmount = ((float) $amount) / 100;
}

if ($topup !== null) {
    $dbStatus = (string) ($topup['payment_status'] ?? 'pending');
    if ($dbStatus === 'paid') {
        $isSuccess = true;
        $isFailed = false;
        $isPending = false;
    } elseif ($dbStatus === 'failed') {
        $isFailed = true;
        $isSuccess = false;
    }
}

$balance = wallet_get_balance($conn, $user_id);
$student_name = isset($_SESSION['full_name'])
    ? htmlspecialchars((string) $_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';
$student_email = isset($_SESSION['email'])
    ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Top-up Result — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_styles.php'; ?>
    <style>
        .result-card {
            max-width: 560px;
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
                <div class="page-title">Top-up Result</div>
                <div class="welcome-text">ToyyibPay wallet top-up</div>
            </div>
        </div>
    </header>

    <main class="content-area">
        <div class="result-card text-center">
            <?php if ($isSuccess): ?>
                <div class="result-icon success"><i class="bi bi-check-lg"></i></div>
                <h1 class="h4 fw-bold text-success mb-2">Top-up Successful</h1>
                <p class="text-muted mb-3">
                    <?php echo htmlspecialchars(wallet_format_rm($topupAmount), ENT_QUOTES, 'UTF-8'); ?> has been added to your wallet.
                </p>
                <p class="fw-semibold mb-4">New balance: <?php echo htmlspecialchars(wallet_format_rm($balance), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php elseif ($isFailed): ?>
                <div class="result-icon failed"><i class="bi bi-x-lg"></i></div>
                <h1 class="h4 fw-bold text-danger mb-2">Top-up Failed</h1>
                <p class="text-muted mb-4">
                    <?php echo htmlspecialchars($reason !== '' ? $reason : 'Payment could not be completed.', ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php else: ?>
                <div class="result-icon pending"><i class="bi bi-hourglass-split"></i></div>
                <h1 class="h4 fw-bold text-warning mb-2">Top-up Pending</h1>
                <p class="text-muted mb-4">Your payment is being processed. Check your wallet balance shortly.</p>
            <?php endif; ?>

            <?php if (!$hashValid && $hash !== ''): ?>
                <div class="alert alert-warning small text-start">Payment details could not be fully verified. Balance shown reflects our latest records.</div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="student_wallet.php" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-wallet2 me-1"></i> Back to Wallet
                </a>
                <?php if ($isFailed && $topup !== null): ?>
                <a href="wallet_topup_bill.php?topup_id=<?php echo (int) ($topup['id'] ?? 0); ?>" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-arrow-repeat me-1"></i> Try Again
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
