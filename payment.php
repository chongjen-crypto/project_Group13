<?php
/**
 * Scholar Hub — Checkout / payment page (Epic-inspired dark UI).
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/includes/payment_checkout.php';
require_once __DIR__ . '/includes/facility_pricing.php';

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
$payment_error = '';
if (!empty($_SESSION['payment_error'])) {
    $payment_error = (string) $_SESSION['payment_error'];
    unset($_SESSION['payment_error']);
}

$methods = payment_method_options();
$selected_method = 'tng';

$facility_name = (string) ($checkout['facility_name'] ?? 'Facility');
$booking_date  = (string) ($checkout['booking_date'] ?? '');
$slot_labels   = $checkout['slot_labels'] ?? [];
$is_court      = !empty($checkout['is_court_based']);
$court_label   = (string) ($checkout['court_label'] ?? 'Court');
$court_name    = (string) ($checkout['court_name'] ?? '');
$total_hours   = (int) ($checkout['total_hours'] ?? 1);
$unit_price    = (float) ($checkout['unit_price'] ?? 0);
$price_label   = (string) ($checkout['price_label'] ?? '');
$pricing_mode  = (string) ($checkout['pricing_mode'] ?? 'hourly');
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
    <style>
        :root {
            --epic-bg: #0e0e10;
            --epic-panel: #18181c;
            --epic-card: #1f1f24;
            --epic-border: #2d2d35;
            --epic-text: #f3f4f6;
            --epic-muted: #9ca3af;
            --epic-accent: #26bbff;
            --epic-success: #22c55e;
            --radius: 14px;
            --t: 0.22s ease;
        }
        html, body {
            min-height: 100%;
            background: var(--epic-bg);
            color: var(--epic-text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        a { color: var(--epic-accent); text-decoration: none; }
        a:hover { color: #7dd3fc; }

        .checkout-top {
            border-bottom: 1px solid var(--epic-border);
            padding: 1rem 0;
            background: rgba(14,14,16,0.95);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(8px);
        }
        .checkout-brand {
            font-weight: 800;
            letter-spacing: 0.06em;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--epic-muted);
        }
        .checkout-title {
            font-size: clamp(1.35rem, 3vw, 1.75rem);
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .panel {
            background: var(--epic-panel);
            border: 1px solid var(--epic-border);
            border-radius: var(--radius);
            padding: clamp(1.25rem, 3vw, 1.75rem);
        }
        .panel h2 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--epic-muted);
            margin-bottom: 1.25rem;
            font-weight: 700;
        }

        /* Payment method cards */
        .pay-method {
            display: block;
            width: 100%;
            text-align: left;
            background: var(--epic-card);
            font: inherit;
            border: 2px solid var(--epic-border);
            border-radius: 12px;
            padding: 1rem 1.15rem;
            color: var(--epic-text);
            cursor: pointer;
            transition: border-color var(--t), background var(--t), transform var(--t), box-shadow var(--t);
            margin-bottom: 0.75rem;
        }
        .pay-method:hover {
            border-color: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
        }
        .pay-method.active {
            border-color: var(--epic-accent);
            background: rgba(38, 187, 255, 0.08);
            box-shadow: 0 0 0 1px rgba(38, 187, 255, 0.35);
        }
        .pay-method .icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            background: #2a2a32;
        }
        .pay-method.active .icon-wrap {
            background: rgba(38, 187, 255, 0.2);
            color: var(--epic-accent);
        }
        .pay-method.tng .icon-wrap { background: #003d7a; color: #38bdf8; }
        .pay-method.in_app .icon-wrap { background: #312e81; color: #a5b4fc; }

        /* Summary sticky */
        .summary-panel {
            position: sticky;
            top: 5.5rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--epic-border);
            font-size: 0.92rem;
        }
        .summary-row:last-of-type { border-bottom: none; }
        .summary-row dt { color: var(--epic-muted); margin: 0; font-weight: 500; }
        .summary-row dd { margin: 0; text-align: right; font-weight: 600; max-width: 60%; }
        .slots-list {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: right;
            font-size: 0.85rem;
        }
        .slots-list li { margin-bottom: 0.2rem; }
        .total-box {
            background: var(--epic-card);
            border-radius: 12px;
            padding: 1rem 1.15rem;
            margin-top: 1rem;
            border: 1px solid var(--epic-border);
        }
        .total-box .total-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--epic-muted);
        }
        .total-box .total-amount {
            font-size: clamp(1.75rem, 4vw, 2.1rem);
            font-weight: 800;
            color: var(--epic-success);
        }
        .btn-pay {
            width: 100%;
            margin-top: 1.25rem;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            border: none;
            border-radius: 12px;
            background: linear-gradient(180deg, #2ecc71 0%, #16a34a 100%);
            color: #fff;
            transition: transform var(--t), box-shadow var(--t), filter var(--t);
        }
        .btn-pay:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(34, 197, 94, 0.35);
            color: #fff;
        }
        .btn-pay:disabled {
            background: #3f3f46;
            color: #71717a;
            cursor: not-allowed;
        }
        .btn-back-link {
            color: var(--epic-muted);
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .btn-back-link:hover { color: var(--epic-text); }

        .alert-epic {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fecaca;
            border-radius: 10px;
        }

        @media (max-width: 991.98px) {
            .summary-panel { position: static; margin-top: 1.5rem; }
        }
    </style>
</head>
<body>

<header class="checkout-top">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="checkout-brand"><i class="bi bi-mortarboard-fill me-1"></i> Scholar Hub</div>
                <div class="checkout-title">Complete your payment</div>
            </div>
            <div class="text-end">
                <div class="small" style="color: var(--epic-muted);">Signed in as</div>
                <div class="fw-semibold"><?php echo $student_name; ?></div>
            </div>
        </div>
    </div>
</header>

<main class="container py-4 py-lg-5">
    <?php if ($payment_error !== ''): ?>
        <div class="alert alert-epic mb-4"><?php echo htmlspecialchars($payment_error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="payment_process.php" id="paymentForm">
        <input type="hidden" name="payment_method" id="paymentMethodInput" value="tng">

        <div class="row g-4">
            <!-- LEFT: Payment methods -->
            <div class="col-lg-7">
                <div class="panel">
                    <h2>Payment method</h2>

                    <button type="button" class="pay-method tng active" data-method="tng">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-wrap"><i class="bi bi-phone"></i></div>
                            <div class="flex-grow-1 text-start">
                                <div class="fw-bold">Touch 'n Go eWallet</div>
                                <div class="small" style="color: var(--epic-muted);">Pay with TNG balance (demo)</div>
                            </div>
                            <i class="bi bi-check-circle-fill text-info fs-5 check-icon"></i>
                        </div>
                    </button>

                    <button type="button" class="pay-method in_app" data-method="in_app">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-wrap"><i class="bi bi-wallet2"></i></div>
                            <div class="flex-grow-1 text-start">
                                <div class="fw-bold">In-App Money</div>
                                <div class="small" style="color: var(--epic-muted);">Scholar Hub wallet balance (demo)</div>
                            </div>
                            <i class="bi bi-circle check-icon fs-5" style="color: var(--epic-border);"></i>
                        </div>
                    </button>

                    <p class="small mt-3 mb-0" style="color: var(--epic-muted);">
                        <i class="bi bi-shield-lock me-1"></i> Demo checkout — no real charge is processed.
                    </p>
                </div>

                <a href="booking.php?type=<?php echo urlencode((string) ($checkout['facility_type'] ?? '')); ?>" class="btn-back-link mt-3">
                    <i class="bi bi-arrow-left"></i> Back to booking
                </a>
            </div>

            <!-- RIGHT: Order summary -->
            <div class="col-lg-5">
                <div class="panel summary-panel">
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
                                <span class="d-block small fw-normal" style="color: var(--epic-muted);"><?php echo htmlspecialchars($price_label, ENT_QUOTES, 'UTF-8'); ?></span>
                            </dd>
                        </div>
                    </dl>

                    <div class="total-box">
                        <div class="total-label">Total amount</div>
                        <div class="total-amount"><?php echo htmlspecialchars(facility_pricing_format_rm($total_amount), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="small mt-2" style="color: var(--epic-muted);"><?php echo htmlspecialchars($breakdown, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>

                    <button type="submit" class="btn-pay" id="btnPay">
                        PAY <?php echo htmlspecialchars(facility_pricing_format_rm($total_amount), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<!-- Success modal (shown via query after redirect alternative — primary success is dashboard redirect) -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="background: var(--epic-panel); color: var(--epic-text);">
            <div class="modal-body text-center p-4">
                <i class="bi bi-check-circle-fill text-success display-4"></i>
                <h3 class="h5 mt-3">Payment successful</h3>
                <p class="text-muted mb-0">Your booking is pending staff approval.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    var methods = document.querySelectorAll('.pay-method');
    var input = document.getElementById('paymentMethodInput');
    var form = document.getElementById('paymentForm');
    var btnPay = document.getElementById('btnPay');

    methods.forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            var m = el.getAttribute('data-method');
            input.value = m;
            methods.forEach(function (card) {
                card.classList.remove('active');
                var icon = card.querySelector('.check-icon');
                if (icon) {
                    icon.className = 'bi bi-circle check-icon fs-5';
                    icon.style.color = 'var(--epic-border)';
                }
            });
            el.classList.add('active');
            var activeIcon = el.querySelector('.check-icon');
            if (activeIcon) {
                activeIcon.className = 'bi bi-check-circle-fill check-icon fs-5 text-info';
            }
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            btnPay.disabled = true;
            btnPay.textContent = 'Processing…';
        });
    }
})();
</script>
</body>
</html>
