<?php
/**
 * Scholar Hub — Student in-app wallet
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/wallet_helpers.php';

$user_id = (int) $_SESSION['user_id'];
$student_name = isset($_SESSION['full_name']) && trim((string) $_SESSION['full_name']) !== ''
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';
$student_email = isset($_SESSION['email'])
    ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';

$student_nav_active = 'wallet';
$success_msg = '';
$error_msg = '';

if (!empty($_SESSION['wallet_success'])) {
    $success_msg = (string) $_SESSION['wallet_success'];
    unset($_SESSION['wallet_success']);
}
if (!empty($_SESSION['wallet_error'])) {
    $error_msg = (string) $_SESSION['wallet_error'];
    unset($_SESSION['wallet_error']);
}

$balance = wallet_get_balance($conn, $user_id);
$transactions = wallet_fetch_transactions($conn, $user_id, 50);
$topup_available = wallet_topup_is_available();
$topup_presets = wallet_topup_preset_amounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>My Wallet — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_styles.php'; ?>
    <style>
        .section-title {
            font-weight: 700;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            color: #111827;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, #e5e7eb, transparent);
            margin-left: 0.5rem;
        }
        .wallet-balance-card {
            background: linear-gradient(135deg, #111827, #374151);
            color: #fff;
            border-radius: var(--card-radius);
            padding: 1.5rem 1.75rem;
            box-shadow: 0 12px 32px rgba(17, 24, 39, 0.2);
        }
        .wallet-balance-card .label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.75;
        }
        .wallet-balance-card .amount {
            font-size: clamp(2rem, 5vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .card-soft {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
        }
        .topup-unavailable {
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
        }
        .topup-preset {
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 10px;
            padding: 0.65rem 0.5rem;
            font-weight: 600;
            color: #111827;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
        }
        .topup-preset:hover {
            border-color: #111827;
            background: #f9fafb;
        }
        .topup-preset.active {
            border-color: #111827;
            background: #111827;
            color: #fff;
            box-shadow: 0 6px 18px rgba(17, 24, 39, 0.18);
        }
        .topup-amount-input {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .btn-topup {
            width: 100%;
            padding: 0.85rem 1.25rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            background: #111827;
            color: #fff;
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .btn-topup:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(17, 24, 39, 0.18);
            background: #1f2937;
            color: #fff;
        }
        .btn-topup:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .table-wrap {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table-modern thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }
        .txn-credit { color: #059669; font-weight: 600; }
        .txn-debit { color: #dc2626; font-weight: 600; }
    </style>
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
                    <div class="page-title">My Wallet</div>
                    <div class="welcome-text">View balance and pay for bookings</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime"></div>
                <?php include __DIR__ . '/includes/student_notification_bell.php'; ?>
                <div class="avatar" title="<?php echo $student_email !== '' ? $student_email : $student_name; ?>">
                    <?php
                    $parts = preg_split('/\s+/', trim((string) ($_SESSION['full_name'] ?? 'S')));
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
        <?php if ($success_msg !== ''): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_msg !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="wallet-balance-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="label mb-1"><i class="bi bi-wallet2 me-1"></i> Available balance</div>
                        <div class="amount"><?php echo htmlspecialchars(wallet_format_rm($balance), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <p class="small mb-0 mt-3 opacity-75">Use In-App Money at checkout to pay for facility bookings instantly.</p>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card-soft p-4 h-100">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle text-secondary me-1"></i> Top up wallet</h2>

                    <?php if ($topup_available): ?>
                    <form method="post" action="wallet_topup_process.php" id="topupForm">
                        <input type="hidden" name="action" value="topup">

                        <label for="topupAmount" class="form-label small text-muted mb-2">Choose amount or enter custom value</label>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($topup_presets as $preset): ?>
                            <button type="button"
                                    class="topup-preset flex-fill"
                                    data-amount="<?php echo htmlspecialchars(number_format($preset, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars(wallet_format_rm($preset), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text fw-semibold">RM</span>
                            <input type="number"
                                   class="form-control topup-amount-input"
                                   id="topupAmount"
                                   name="amount"
                                   min="1"
                                   max="500"
                                   step="0.01"
                                   placeholder="0.00"
                                   value="10.00"
                                   required>
                        </div>

                        <p class="small text-muted mb-3">
                            <i class="bi bi-globe2 me-1"></i>
                            You will be redirected to <strong>ToyyibPay</strong> to complete payment securely.
                        </p>

                        <button type="submit" class="btn-topup" id="btnTopup">
                            <i class="bi bi-credit-card me-1"></i> Top Up via ToyyibPay
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="topup-unavailable">
                        <div class="d-flex align-items-start gap-3">
                            <div class="text-secondary fs-4"><i class="bi bi-globe2"></i></div>
                            <div>
                                <div class="fw-semibold mb-1">Top-up unavailable</div>
                                <p class="small text-muted mb-0">
                                    Online top-up requires ToyyibPay configuration. Copy
                                    <code>config/toyyibpay_local.example.php</code> to
                                    <code>config/toyyibpay_local.php</code> and add your secret key.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0"><i class="bi bi-clock-history text-primary"></i> Transaction history</h2>
            <a href="booking.php" class="btn btn-dark btn-sm rounded-pill px-3">
                <i class="bi bi-calendar2-plus me-1"></i> Book a facility
            </a>
        </div>

        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th class="pe-4 text-end">Balance after</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No transactions yet. Top up to get started.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                            <?php
                            $isTopup = ($t['txn_type'] ?? '') === 'topup';
                            $isRefund = ($t['txn_type'] ?? '') === 'refund';
                            $amtClass = ($isTopup || $isRefund) ? 'txn-credit' : 'txn-debit';
                            $amtPrefix = ($isTopup || $isRefund) ? '+' : '-';
                            ?>
                            <tr>
                                <td class="ps-4 text-nowrap small"><?php echo htmlspecialchars(wallet_format_datetime((string) $t['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $isTopup ? 'text-bg-success' : ($isRefund ? 'text-bg-info' : 'text-bg-secondary'); ?>">
                                        <?php echo htmlspecialchars(wallet_txn_type_label((string) $t['txn_type']), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars((string) $t['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="<?php echo $amtClass; ?>">
                                    <?php echo $amtPrefix . wallet_format_rm((float) $t['amount']); ?>
                                </td>
                                <td class="pe-4 text-end fw-semibold"><?php echo htmlspecialchars(wallet_format_rm((float) $t['balance_after']), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
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

    var topupForm = document.getElementById('topupForm');
    var topupAmount = document.getElementById('topupAmount');
    var presetButtons = document.querySelectorAll('.topup-preset');
    var btnTopup = document.getElementById('btnTopup');

    function syncPresetHighlight() {
        if (!topupAmount) return;
        var current = parseFloat(topupAmount.value || '0').toFixed(2);
        presetButtons.forEach(function (btn) {
            var preset = parseFloat(btn.getAttribute('data-amount') || '0').toFixed(2);
            btn.classList.toggle('active', preset === current);
        });
    }

    presetButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!topupAmount) return;
            topupAmount.value = btn.getAttribute('data-amount');
            syncPresetHighlight();
            topupAmount.focus();
        });
    });

    if (topupAmount) {
        topupAmount.addEventListener('input', syncPresetHighlight);
        syncPresetHighlight();
    }

    if (topupForm) {
        topupForm.addEventListener('submit', function () {
            if (btnTopup) {
                btnTopup.disabled = true;
                btnTopup.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Redirecting to ToyyibPay…';
            }
        });
    }
})();
</script>
<?php include __DIR__ . '/includes/student_notification_scripts.php'; ?>
</body>
</html>
