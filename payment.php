<?php
/**
 * Scholar Hub — Checkout / payment page
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/includes/payment_checkout.php';
require_once __DIR__ . '/includes/facility_pricing.php';
require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/wallet_helpers.php';

$wallet_balance = wallet_get_balance($conn, (int) ($_SESSION['user_id'] ?? 0));

$checkout = payment_checkout_load();
if ($checkout === null) {
    header('Location: student_dashboard.php');
    exit();
}

if ((int) ($checkout['user_id'] ?? 0) !== (int) ($_SESSION['user_id'] ?? 0)) {
    payment_checkout_clear();
    header('Location: student_dashboard.php');
    exit();
}

$student_name = isset($_SESSION['full_name']) ? htmlspecialchars((string) $_SESSION['full_name'], ENT_QUOTES, 'UTF-8') : 'Student';
$student_email = isset($_SESSION['email']) ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8') : '';
$student_nav_active = 'book';

$payment_error = '';
if (!empty($_SESSION['payment_error'])) {
    $payment_error = (string) $_SESSION['payment_error'];
    unset($_SESSION['payment_error']);
}

$facility_type = (string) ($checkout['facility_type'] ?? '');
$total_hours   = max(1, (int) ($checkout['total_hours'] ?? count($checkout['slots'] ?? [])));
if ($facility_type !== '') {
    $calc = facility_pricing_calculate($facility_type, $total_hours, $conn);
    $checkout['unit_price']   = $calc['unit_price'];
    $checkout['price_label']  = $calc['label'];
    $checkout['pricing_mode'] = $calc['mode'];
    $checkout['total_amount'] = $calc['total'];
    $checkout['breakdown']    = $calc['breakdown'];
    $checkout['total_hours']  = $total_hours;
    payment_checkout_save($checkout);
}

$facility_name = (string) ($checkout['facility_name'] ?? 'Facility');
$booking_date  = (string) ($checkout['booking_date'] ?? '');
$slot_labels   = $checkout['slot_labels'] ?? [];
$is_court      = !empty($checkout['is_court_based']);
$court_label   = (string) ($checkout['court_label'] ?? 'Court');
$court_name    = (string) ($checkout['court_name'] ?? '');
$unit_price    = (float) ($checkout['unit_price'] ?? 0);
$price_label   = (string) ($checkout['price_label'] ?? '');
$total_amount  = (float) ($checkout['total_amount'] ?? 0);
$breakdown     = (string) ($checkout['breakdown'] ?? '');

$date_display = $booking_date;
$dt = DateTime::createFromFormat('Y-m-d', $booking_date);
if ($dt) {
    $date_display = $dt->format('l, j M Y');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Checkout — Scholar Hub</title>
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
        .card-soft {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
            padding: clamp(1.25rem, 3vw, 1.75rem);
        }
        .card-soft h2 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6b7280;
            margin-bottom: 1.25rem;
            font-weight: 700;
        }
        .pay-method {
            display: block;
            width: 100%;
            text-align: left;
            background: #fff;
            font: inherit;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem 1.15rem;
            color: #111827;
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition), transform var(--transition), box-shadow var(--transition);
            margin-bottom: 0.75rem;
        }
        .pay-method:hover {
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .pay-method.active {
            border-color: #111827;
            background: #f9fafb;
            box-shadow: 0 0 0 1px rgba(17, 24, 39, 0.15);
        }
        .pay-method .icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #fff;
        }
        .pay-method.online .icon-wrap {
            background: linear-gradient(135deg, #0d6efd, #2563eb);
        }
        .pay-method.in_app .icon-wrap {
            background: linear-gradient(135deg, #059669, #10b981);
        }
        .pay-method.active .check-icon {
            color: #111827 !important;
        }
        .summary-panel { position: sticky; top: 5.5rem; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.92rem;
        }
        .summary-row:last-of-type { border-bottom: none; }
        .summary-row dt { color: #6b7280; margin: 0; font-weight: 500; }
        .summary-row dd { margin: 0; text-align: right; font-weight: 600; color: #111827; max-width: 60%; }
        .slots-list {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: right;
            font-size: 0.85rem;
        }
        .slots-list li { margin-bottom: 0.2rem; }
        .total-box {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1rem 1.15rem;
            margin-top: 1rem;
            border: 1px solid #e5e7eb;
        }
        .total-box .total-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            font-weight: 600;
        }
        .total-box .total-amount {
            font-size: clamp(1.75rem, 4vw, 2.1rem);
            font-weight: 800;
            color: #059669;
        }
        .btn-pay {
            width: 100%;
            margin-top: 1.25rem;
            padding: 0.9rem 1.5rem;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            background: #111827;
            color: #fff;
            transition: transform var(--transition), box-shadow var(--transition), background var(--transition);
        }
        .btn-pay:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(17, 24, 39, 0.2);
            background: #1f2937;
            color: #fff;
        }
        .btn-pay:disabled {
            background: #9ca3af;
            color: #f3f4f6;
            cursor: not-allowed;
        }
        @media (max-width: 991.98px) {
            .summary-panel { position: static; margin-top: 0; }
        }
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
                    <div class="page-title">Checkout</div>
                    <div class="welcome-text">Complete your payment, <?php echo $student_name; ?></div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime"></div>
                <?php
                require __DIR__ . '/db.php';
                include __DIR__ . '/includes/student_notification_bell.php';
                ?>
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
        <?php if ($payment_error !== ''): ?>
            <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($payment_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <h2 class="section-title"><i class="bi bi-credit-card text-primary"></i> Payment</h2>

        <form method="post" action="payment_process.php" id="paymentForm">
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="online">

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card-soft">
                        <h2>Payment method</h2>

                        <?php
                        $payment_methods = payment_method_options();
                        $first_method = true;
                        foreach ($payment_methods as $method_key => $method_meta):
                            $method_hint = (string) ($method_meta['hint'] ?? '');
                            if ($method_key === 'in_app') {
                                $method_hint .= ' · Balance: ' . wallet_format_rm($wallet_balance);
                            }
                        ?>
                        <button type="button" class="pay-method <?php echo htmlspecialchars($method_key, ENT_QUOTES, 'UTF-8'); ?><?php echo $first_method ? ' active' : ''; ?>" data-method="<?php echo htmlspecialchars($method_key, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-wrap"><i class="bi <?php echo htmlspecialchars($method_meta['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></div>
                                <div class="flex-grow-1 text-start">
                                    <div class="fw-bold"><?php echo htmlspecialchars($method_meta['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($method_hint, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <i class="bi <?php echo $first_method ? 'bi-check-circle-fill' : 'bi-circle text-secondary'; ?> check-icon fs-5"></i>
                            </div>
                        </button>
                        <?php
                        $first_method = false;
                        endforeach;
                        ?>

                    </div>

                    <a href="booking.php?type=<?php echo urlencode((string) ($checkout['facility_type'] ?? '')); ?>" class="btn btn-outline-dark rounded-pill mt-3">
                        <i class="bi bi-arrow-left me-1"></i> Back to booking
                    </a>
                </div>

                <div class="col-lg-5">
                    <div class="card-soft summary-panel">
                        <h2>Order summary</h2>

                        <dl>
                            <div class="summary-row">
                                <dt>Facility</dt>
                                <dd><?php echo htmlspecialchars($facility_name, ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <?php if ($is_court && $court_name !== ''): ?>
                            <div class="summary-row">
                                <dt><?php echo htmlspecialchars($court_label, ENT_QUOTES, 'UTF-8'); ?></dt>
                                <dd><?php echo htmlspecialchars($court_name, ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <dt>Date</dt>
                                <dd><?php echo htmlspecialchars($date_display, ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div class="summary-row">
                                <dt>Time slots</dt>
                                <dd>
                                    <ul class="slots-list">
                                        <?php foreach ($slot_labels as $lbl): ?>
                                        <li><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </dd>
                            </div>
                            <div class="summary-row">
                                <dt>Total hours</dt>
                                <dd><?php echo (int) $total_hours; ?> hour<?php echo $total_hours === 1 ? '' : 's'; ?></dd>
                            </div>
                            <div class="summary-row">
                                <dt>Rate</dt>
                                <dd>
                                    <?php echo htmlspecialchars(facility_pricing_format_rm($unit_price), ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="d-block small fw-normal text-muted"><?php echo htmlspecialchars($price_label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </dd>
                            </div>
                        </dl>

                        <div class="total-box">
                            <div class="total-label">Total amount</div>
                            <div class="total-amount"><?php echo htmlspecialchars(facility_pricing_format_rm($total_amount), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small mt-2 text-muted"><?php echo htmlspecialchars($breakdown, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>

                        <button type="submit" class="btn-pay" id="btnPay">
                            <span id="btnPayLabel">Continue to ToyyibPay — <?php echo htmlspecialchars(facility_pricing_format_rm($total_amount), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
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

    var methods = document.querySelectorAll('.pay-method');
    var input = document.getElementById('paymentMethodInput');
    var form = document.getElementById('paymentForm');
    var btnPay = document.getElementById('btnPay');
    var btnPayLabel = document.getElementById('btnPayLabel');
    var totalFormatted = <?php echo json_encode(facility_pricing_format_rm($total_amount)); ?>;

    function updatePayButtonLabel() {
        if (!btnPayLabel) return;
        if (input.value === 'online') {
            btnPayLabel.textContent = 'Continue to ToyyibPay — ' + totalFormatted;
        } else {
            btnPayLabel.textContent = 'Pay ' + totalFormatted + ' from Wallet';
        }
    }

    methods.forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            input.value = el.getAttribute('data-method');
            methods.forEach(function (card) {
                card.classList.remove('active');
                var icon = card.querySelector('.check-icon');
                if (icon) {
                    icon.className = 'bi bi-circle check-icon fs-5 text-secondary';
                }
            });
            el.classList.add('active');
            var activeIcon = el.querySelector('.check-icon');
            if (activeIcon) {
                activeIcon.className = 'bi bi-check-circle-fill check-icon fs-5';
            }
            updatePayButtonLabel();
        });
    });

    updatePayButtonLabel();

    if (form) {
        form.addEventListener('submit', function () {
            btnPay.disabled = true;
            if (btnPayLabel) {
                btnPayLabel.textContent = 'Processing…';
            }
        });
    }
})();
</script>
<?php include __DIR__ . '/includes/student_notification_scripts.php'; ?>
</body>
</html>
