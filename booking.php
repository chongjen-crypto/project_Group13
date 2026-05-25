<?php
/**
 * Scholar Hub — Facility booking page
 * Flow: date → court (if court-based) → time slots → proceed booking
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/booking_helpers.php';
require_once __DIR__ . '/includes/facility_pricing.php';

$student_name = (isset($_SESSION['full_name']) && trim((string) $_SESSION['full_name']) !== '')
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';
$student_email = isset($_SESSION['email'])
    ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';

$student_nav_active = 'book';

// Facility from URL: booking.php?facility=Badminton%20Court  or  ?type=badminton
$facility_param = isset($_GET['facility']) ? trim((string) $_GET['facility']) : '';
$type_param     = isset($_GET['type']) ? trim((string) $_GET['type']) : '';

$facility = booking_load_facility($conn, $facility_param, $type_param);
$facility_error = '';

if ($facility === null && ($facility_param !== '' || $type_param !== '')) {
    $facility_error = 'Facility not found or is not available for booking.';
} elseif ($facility === null) {
    $facility_error = 'No facility selected. Please choose a facility from your dashboard.';
}

// Default images when DB image is empty
$facility_images = [
    'badminton'   => 'assets/badmintoncourt.webp',
    'basketball'  => 'assets/basketballcourt.jpeg',
    'futsal'      => 'assets/futsalcourt.jpg',
    'tennis'      => 'assets/tenniscourt.jpg',
    'volleyball'  => 'assets/volleyballcourt.webp',
    'snooker'     => 'assets/snookerroom.jpg',
    'gym'         => 'assets/gymroom.jpg',
    'swimming'    => 'assets/swimmingpool.jpg',
    'track'       => 'assets/trackfield.webp',
];

$booking_config = null;
if ($facility !== null) {
    $ftype = (string) $facility['facility_type'];
    $img = trim((string) ($facility['image'] ?? ''));
    if ($img === '') {
        $img = $facility_images[$ftype] ?? 'assets/trackfield.webp';
    }
    $pricing = facility_pricing_get($ftype);
    $booking_config = [
        'facility_id'   => (int) $facility['facility_id'],
        'facility_name' => (string) $facility['facility_name'],
        'facility_type' => $ftype,
        'image'         => $img,
        'is_court_based'=> booking_is_court_based($ftype),
        'court_label'   => $ftype === 'snooker' ? 'Table' : 'Court',
        'unit_price'    => $pricing ? (float) $pricing['amount'] : 0,
        'price_label'   => $pricing ? (string) $pricing['label'] : '',
        'pricing_mode'  => $pricing ? (string) $pricing['mode'] : 'hourly',
    ];
}

$min_date = date('Y-m-d');
$booked_flash = isset($_GET['booked']) && $_GET['booked'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Book Facility — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_styles.php'; ?>
    <style>
        /* ========================= Booking page cards ========================= */
        .booking-card {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            padding: clamp(1rem, 3vw, 1.5rem);
            margin-bottom: 1.25rem;
        }
        .booking-card h3 {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .booking-card h3 .step-num {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: #0b0b0b;
            color: #fff;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .facility-hero-mini {
            border-radius: 12px;
            overflow: hidden;
            height: 140px;
            background: #e5e7eb;
            position: relative;
        }
        .facility-hero-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .facility-hero-mini .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(0deg, rgba(11,11,11,0.75), transparent 55%);
            display: flex;
            align-items: flex-end;
            padding: 1rem;
            color: #fff;
        }
        .court-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.65rem;
        }
        .court-btn {
            border: 2px solid #e5e7eb;
            background: #fff;
            border-radius: 12px;
            padding: 0.85rem 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #111827;
            transition: border-color var(--transition), background var(--transition), transform var(--transition);
        }
        .court-btn:hover:not(:disabled) {
            border-color: #111827;
            transform: translateY(-2px);
        }
        .court-btn.active {
            border-color: #059669;
            background: #ecfdf5;
            color: #047857;
        }
        .court-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.6rem;
        }
        .slot-btn {
            border: 2px solid #d1d5db;
            background: #fff;
            border-radius: 10px;
            padding: 0.65rem 0.5rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: #111827;
            text-align: center;
            transition: background var(--transition), border-color var(--transition), color var(--transition), transform var(--transition);
        }
        .slot-btn:hover:not(:disabled):not(.booked) {
            border-color: #111827;
            transform: scale(1.02);
        }
        .slot-btn.booked {
            background: #e5e7eb;
            border-color: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
            pointer-events: none;
        }
        .slot-btn.selected {
            background: #059669;
            border-color: #047857;
            color: #fff;
        }
        .summary-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem 1.15rem;
        }
        .summary-box dt {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        .summary-box dd {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.65rem;
        }
        .btn-proceed {
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.75rem 2rem;
        }
        .section-disabled {
            opacity: 0.45;
            pointer-events: none;
        }
        .loading-inline {
            display: none;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .loading-inline.show { display: inline-flex; }
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
                    <div class="page-title">Book Facility</div>
                    <div class="welcome-text">Welcome, <?php echo $student_name; ?></div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime"></div>
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

        <?php if ($booked_flash): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>Booking submitted successfully. Status: <strong>Pending</strong> approval.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="bookingAlert" class="alert d-none" role="alert"></div>

        <?php if ($facility_error !== ''): ?>
            <div class="booking-card">
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($facility_error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <a href="student_dashboard.php" class="btn btn-dark rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        <?php else: ?>

            <div class="row g-3 mb-3">
                <div class="col-lg-8">
                    <div class="facility-hero-mini">
                        <img src="<?php echo htmlspecialchars($booking_config['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <div class="overlay">
                            <div>
                                <h1 class="h5 fw-bold mb-0"><?php echo htmlspecialchars($booking_config['facility_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                                <small class="opacity-75">Operating hours 8:00 AM – 10:00 PM · 1-hour slots</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 d-flex align-items-stretch">
                    <a href="student_dashboard.php" class="btn btn-outline-dark rounded-pill align-self-center w-100">
                        <i class="bi bi-arrow-left me-1"></i> Change facility
                    </a>
                </div>
            </div>

            <!-- Step 1: Date -->
            <div class="booking-card" id="stepDate">
                <h3><span class="step-num">1</span> Select booking date</h3>
                <div class="row g-3 align-items-end">
                    <div class="col-sm-6 col-md-4">
                        <label for="bookingDate" class="form-label fw-semibold">Date</label>
                        <input type="date" class="form-control form-control-lg rounded-3" id="bookingDate" name="booking_date" min="<?php echo htmlspecialchars($min_date, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Bookings are available from today onward.</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Court / table (court-based only) -->
            <div class="booking-card section-disabled" id="stepCourt" <?php echo $booking_config['is_court_based'] ? '' : 'style="display:none;"'; ?>>
                <h3>
                    <span class="step-num">2</span>
                    Select <?php echo htmlspecialchars(strtolower($booking_config['court_label']), ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <p class="small text-muted mb-3">Choose a <?php echo htmlspecialchars(strtolower($booking_config['court_label']), ENT_QUOTES, 'UTF-8'); ?> before viewing time slots.</p>
                <div class="loading-inline mb-2" id="courtsLoading"><span class="spinner-border spinner-border-sm"></span> Loading…</div>
                <div class="court-grid" id="courtGrid"></div>
                <p class="small text-danger mt-2 d-none" id="courtHint">Please select a <?php echo htmlspecialchars(strtolower($booking_config['court_label']), ENT_QUOTES, 'UTF-8'); ?>.</p>
            </div>

            <!-- Step 3: Time slots -->
            <div class="booking-card section-disabled" id="stepSlots">
                <h3>
                    <span class="step-num"><?php echo $booking_config['is_court_based'] ? '3' : '2'; ?></span>
                    Select time slots
                </h3>
                <p class="small text-muted mb-2">Tap consecutive 1-hour slots (e.g. 2:00–3:00, 3:00–4:00). Grey slots are unavailable.</p>
                <div class="loading-inline mb-2" id="slotsLoading"><span class="spinner-border spinner-border-sm"></span> Loading slots…</div>
                <div class="slot-grid" id="slotGrid"></div>
            </div>

            <!-- Summary + submit -->
            <div class="booking-card section-disabled" id="stepSummary">
                <h3><i class="bi bi-clipboard-check text-success"></i> Booking summary</h3>
                <div class="row g-3">
                    <div class="col-md-7">
                        <dl class="summary-box mb-0">
                            <dt>Facility</dt>
                            <dd id="sumFacility"><?php echo htmlspecialchars($booking_config['facility_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                            <dt>Date</dt>
                            <dd id="sumDate">—</dd>
                            <dt id="sumCourtLabel" <?php echo $booking_config['is_court_based'] ? '' : 'class="d-none"'; ?>><?php echo htmlspecialchars($booking_config['court_label'], ENT_QUOTES, 'UTF-8'); ?></dt>
                            <dd id="sumCourt" <?php echo $booking_config['is_court_based'] ? '' : 'class="d-none"'; ?>">—</dd>
                            <dt>Selected slots</dt>
                            <dd id="sumSlots">None</dd>
                            <dt>Duration</dt>
                            <dd id="sumDuration">0 hour(s)</dd>
                            <dt>Estimated total</dt>
                            <dd id="sumTotal" class="text-success fs-5">RM 0.00</dd>
                        </dl>
                    </div>
                    <div class="col-md-5">
                        <label for="bookingPurpose" class="form-label small fw-semibold">Purpose (optional)</label>
                        <textarea class="form-control rounded-3 mb-3" id="bookingPurpose" rows="3" placeholder="Training, club session, etc."></textarea>
                        <button type="button" class="btn btn-dark btn-proceed w-100" id="btnProceed" disabled>
                            <i class="bi bi-credit-card me-1"></i> Proceed to Payment
                        </button>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <footer class="text-center text-muted small py-3 border-top bg-white">
        Scholar Hub — Sport Facility Booking System
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    // ========================= Sidebar + clock (same as student dashboard) =========================
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
    window.addEventListener('resize', function () { if (window.innerWidth >= 992) closeSidebar(); });

    // ========================= Booking UI (only when facility loaded) =========================
    var CONFIG = <?php echo $booking_config !== null ? json_encode($booking_config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null'; ?>;
    if (!CONFIG) return;

    var dateInput = document.getElementById('bookingDate');
    var stepCourt = document.getElementById('stepCourt');
    var stepSlots = document.getElementById('stepSlots');
    var stepSummary = document.getElementById('stepSummary');
    var courtGrid = document.getElementById('courtGrid');
    var slotGrid = document.getElementById('slotGrid');
    var courtsLoading = document.getElementById('courtsLoading');
    var slotsLoading = document.getElementById('slotsLoading');
    var courtHint = document.getElementById('courtHint');
    var btnProceed = document.getElementById('btnProceed');
    var bookingAlert = document.getElementById('bookingAlert');

    var selectedDate = '';
    var selectedCourtId = null;
    var selectedCourtName = '';
    var selectedStarts = [];
    var courtsLoaded = false;

    function showAlert(type, msg) {
        if (!bookingAlert) return;
        bookingAlert.className = 'alert alert-' + type + ' alert-dismissible fade show';
        bookingAlert.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        bookingAlert.classList.remove('d-none');
        bookingAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        if (bookingAlert) bookingAlert.classList.add('d-none');
    }

    function enableSection(el, on) {
        if (!el) return;
        el.classList.toggle('section-disabled', !on);
    }

    function formatDisplayDate(ymd) {
        if (!ymd) return '—';
        var p = ymd.split('-');
        if (p.length !== 3) return ymd;
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        return d.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }

    function updateSummary() {
        document.getElementById('sumDate').textContent = formatDisplayDate(selectedDate);
        var sumCourt = document.getElementById('sumCourt');
        if (sumCourt) sumCourt.textContent = CONFIG.is_court_based ? (selectedCourtName || '—') : 'N/A';
        var labels = [];
        selectedStarts.slice().sort().forEach(function (start) {
            var btn = slotGrid.querySelector('[data-start="' + start + '"]');
            labels.push(btn ? btn.textContent.trim() : start);
        });
        document.getElementById('sumSlots').textContent = labels.length ? labels.join(', ') : 'None';
        document.getElementById('sumDuration').textContent = selectedStarts.length + ' hour(s)';
        var hours = selectedStarts.length;
        var total = 0;
        if (hours > 0 && CONFIG.unit_price) {
            if (CONFIG.pricing_mode === 'entry') {
                total = CONFIG.unit_price;
            } else {
                total = CONFIG.unit_price * hours;
            }
        }
        var sumTotal = document.getElementById('sumTotal');
        if (sumTotal) {
            sumTotal.textContent = 'RM ' + total.toFixed(2) + (CONFIG.price_label ? ' (' + CONFIG.price_label + ')' : '');
        }
        btnProceed.disabled = selectedStarts.length === 0 || !selectedDate;
        if (CONFIG.is_court_based && (!selectedCourtId || selectedCourtId <= 0)) {
            btnProceed.disabled = true;
        }
        enableSection(stepSummary, selectedDate !== '');
    }

    function clearSlots() {
        selectedStarts = [];
        slotGrid.innerHTML = '';
        updateSummary();
    }

    function fetchCourts() {
        if (!CONFIG.is_court_based) return Promise.resolve();
        courtsLoading.classList.add('show');
        courtGrid.innerHTML = '';
        return fetch('booking_ajax.php?action=get_courts&facility_type=' + encodeURIComponent(CONFIG.facility_type), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                courtsLoading.classList.remove('show');
                if (!data.success) throw new Error(data.message || 'Failed to load courts');
                courtsLoaded = true;
                if (!data.courts || !data.courts.length) {
                    courtGrid.innerHTML = '<p class="text-muted small">No courts available.</p>';
                    return;
                }
                data.courts.forEach(function (c) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'court-btn';
                    b.textContent = c.court_name;
                    b.dataset.courtId = String(c.court_id);
                    b.addEventListener('click', function () { onCourtSelect(c.court_id, c.court_name, b); });
                    courtGrid.appendChild(b);
                });
            })
            .catch(function (err) {
                courtsLoading.classList.remove('show');
                showAlert('danger', err.message || 'Could not load courts.');
            });
    }

    function onCourtSelect(courtId, courtName, btnEl) {
        selectedCourtId = courtId;
        selectedCourtName = courtName;
        courtHint.classList.add('d-none');
        courtGrid.querySelectorAll('.court-btn').forEach(function (b) { b.classList.remove('active'); });
        btnEl.classList.add('active');
        clearSlots();
        loadSlots();
        updateSummary();
    }

    function loadSlots() {
        if (!selectedDate) return;
        if (CONFIG.is_court_based && (!selectedCourtId || selectedCourtId <= 0)) {
            enableSection(stepSlots, false);
            return;
        }

        slotsLoading.classList.add('show');
        slotGrid.innerHTML = '';
        selectedStarts = [];

        var url = 'booking_ajax.php?action=get_slots'
            + '&facility_type=' + encodeURIComponent(CONFIG.facility_type)
            + '&booking_date=' + encodeURIComponent(selectedDate);
        if (CONFIG.is_court_based) {
            url += '&court_id=' + encodeURIComponent(selectedCourtId);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                slotsLoading.classList.remove('show');
                if (!data.success) {
                    if (data.require_court) courtHint.classList.remove('d-none');
                    throw new Error(data.message || 'Failed to load slots');
                }
                enableSection(stepSlots, true);
                data.slots.forEach(function (slot) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'slot-btn' + (slot.available ? '' : ' booked');
                    b.textContent = slot.label;
                    b.dataset.start = slot.start;
                    b.dataset.end = slot.end;
                    if (slot.available) {
                        b.addEventListener('click', function () { toggleSlot(slot.start, b); });
                    }
                    slotGrid.appendChild(b);
                });
                updateSummary();
            })
            .catch(function (err) {
                slotsLoading.classList.remove('show');
                showAlert('danger', err.message || 'Could not load time slots.');
            });
    }

    function hourFromStart(start) {
        return parseInt(start.substr(0, 2), 10);
    }

    function startFromHour(h) {
        return ('0' + h).slice(-2) + ':00:00';
    }

    function refreshSlotSelectionUI() {
        slotGrid.querySelectorAll('.slot-btn:not(.booked)').forEach(function (b) {
            b.classList.toggle('selected', selectedStarts.indexOf(b.dataset.start) >= 0);
        });
    }

    /** Toggle slot — only consecutive hours allowed */
    function toggleSlot(start, btnEl) {
        var h = hourFromStart(start);
        var idx = selectedStarts.indexOf(start);

        if (idx >= 0) {
            var hours = selectedStarts.map(hourFromStart).sort(function (a, b) { return a - b; });
            var minH = hours[0];
            var maxH = hours[hours.length - 1];
            if (h !== minH && h !== maxH) {
                showAlert('warning', 'Deselect from the start or end of your block only.');
                return;
            }
            if (h === minH) {
                selectedStarts = selectedStarts.filter(function (s) { return hourFromStart(s) !== minH; });
            } else {
                selectedStarts = selectedStarts.filter(function (s) { return hourFromStart(s) !== maxH; });
            }
        } else if (selectedStarts.length === 0) {
            selectedStarts = [start];
        } else {
            var sorted = selectedStarts.map(hourFromStart).sort(function (a, b) { return a - b; });
            var lo = sorted[0];
            var hi = sorted[sorted.length - 1];
            if (h === lo - 1 || h === hi + 1) {
                selectedStarts.push(start);
                selectedStarts = selectedStarts.map(hourFromStart).sort(function (a, b) { return a - b; })
                    .map(startFromHour);
            } else {
                showAlert('warning', 'Please select consecutive time slots only (e.g. 2–3 PM, then 3–4 PM).');
                return;
            }
        }

        hideAlert();
        refreshSlotSelectionUI();
        updateSummary();
    }

    function onDateChange() {
        selectedDate = dateInput.value;
        selectedCourtId = null;
        selectedCourtName = '';
        clearSlots();
        hideAlert();

        if (!selectedDate) {
            enableSection(stepCourt, false);
            enableSection(stepSlots, false);
            enableSection(stepSummary, false);
            return;
        }

        if (CONFIG.is_court_based) {
            enableSection(stepCourt, true);
            enableSection(stepSlots, false);
            if (!courtsLoaded) fetchCourts();
            courtGrid.querySelectorAll('.court-btn').forEach(function (b) { b.classList.remove('active'); });
        } else {
            enableSection(stepSlots, true);
            loadSlots();
        }
        updateSummary();
    }

    dateInput.addEventListener('change', onDateChange);

    btnProceed.addEventListener('click', function () {
        hideAlert();
        if (!selectedDate || selectedStarts.length === 0) {
            showAlert('warning', 'Please select date and at least one time slot.');
            return;
        }
        if (CONFIG.is_court_based && !selectedCourtId) {
            showAlert('warning', 'Please select a ' + CONFIG.court_label.toLowerCase() + ' first.');
            return;
        }

        btnProceed.disabled = true;
        var body = new FormData();
        body.append('action', 'prepare_checkout');
        body.append('facility_type', CONFIG.facility_type);
        body.append('booking_date', selectedDate);
        body.append('slots', JSON.stringify(selectedStarts));
        body.append('purpose', document.getElementById('bookingPurpose').value.trim());
        if (CONFIG.is_court_based) {
            body.append('court_id', String(selectedCourtId));
        }

        fetch('booking_ajax.php', { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.success) {
                    window.location.href = 'payment.php';
                } else {
                    showAlert('danger', data.message || 'Booking failed.');
                    btnProceed.disabled = false;
                }
            })
            .catch(function () {
                showAlert('danger', 'Network error. Please try again.');
                btnProceed.disabled = false;
            });
    });

    // Initial state
    enableSection(stepCourt, false);
    enableSection(stepSlots, false);
    enableSection(stepSummary, false);
})();
</script>
</body>
</html>
