<?php
/**
 * Scholar Hub - Sport Facility Booking System
 * student_dashboard.php — Student role dashboard (presentation-ready)
 */

session_start();

// =========================
// SESSION PROTECTION
// =========================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

// Student display name (fallback if not set)
$student_name = isset($_SESSION['full_name']) && trim($_SESSION['full_name']) !== ''
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';

// Optional: student email for avatar tooltip
$student_email = isset($_SESSION['email'])
    ? htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';

require_once __DIR__ . '/includes/facility_pricing.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';
require_once __DIR__ . '/includes/facility_admin_helpers.php';

$user_id = (int) $_SESSION['user_id'];
$student_overview_stats = stats_student_overview($conn, $user_id);

// Facility cards loaded entirely from database (same records admin edits)
$facilities = facilities_fetch_student_cards($conn);

$recent_bookings = [];
$sql = "SELECT b.facility_type, b.booking_date, b.start_time, b.end_time, b.booking_status,
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
        ORDER BY b.created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $status_class = 'text-bg-secondary';
        if ($row['booking_status'] === 'pending') $status_class = 'text-bg-warning';
        elseif ($row['booking_status'] === 'approved') $status_class = 'text-bg-success';
        elseif ($row['booking_status'] === 'rejected') $status_class = 'text-bg-danger';
        elseif ($row['booking_status'] === 'cancelled') $status_class = 'text-bg-dark';

        $recent_bookings[] = [
            'facility' => $row['facility_name'],
            'date' => $row['booking_date'],
            'time' => substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5),
            'status' => ucfirst($row['booking_status']),
            'status_class' => $status_class
        ];
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scholar Hub — Student Dashboard</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_notification_styles.php'; ?>

    <style>
        /* =========================
           ROOT & BASE
           ========================= */
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #0b0b0b;
            --sidebar-hover: #1a1a1a;
            --page-bg: #f3f4f6;
            --card-radius: 14px;
            --transition: 0.22s ease;
        }

        html {
            overflow-x: hidden;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
        }

        a { text-decoration: none; }

        /* =========================
           SIDEBAR
           ========================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            height: 100dvh;
            max-height: 100dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            background: var(--sidebar-bg);
            color: #fff;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            padding: max(1.25rem, env(safe-area-inset-top)) 1rem max(1rem, env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left));
            transition: transform var(--transition);
            box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        }

        .sidebar-brand {
            font-weight: 800;
            letter-spacing: 0.04em;
            font-size: 1.05rem;
            padding: 0.5rem 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 1rem;
        }

        .sidebar-brand small {
            display: block;
            font-weight: 500;
            opacity: 0.65;
            font-size: 0.72rem;
            letter-spacing: 0.02em;
            margin-top: 0.25rem;
        }

        .nav-link-sidebar {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: rgba(255,255,255,0.88);
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            margin-bottom: 0.25rem;
            font-weight: 500;
            font-size: 0.95rem;
            transition: background var(--transition), color var(--transition), transform var(--transition);
        }

        .nav-link-sidebar i {
            font-size: 1.15rem;
            opacity: 0.9;
        }

        .nav-link-sidebar:hover {
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(3px);
        }

        .nav-link-sidebar.active {
            background: #fff;
            color: #0b0b0b;
        }

        .nav-link-sidebar.active i {
            color: #0b0b0b;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* Mobile sidebar overlay */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1030;
        }

        .sidebar-backdrop.show { display: block; }

        /* =========================
           MAIN CONTENT
           ========================= */
        .main-wrap {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left var(--transition);
        }

        .top-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: max(0.65rem, env(safe-area-inset-top)) clamp(0.75rem, 2.5vw, 1.5rem) 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .page-title {
            font-weight: 700;
            font-size: clamp(1.05rem, 2.8vw, 1.35rem);
            color: #111827;
            line-height: 1.2;
        }

        .welcome-text {
            color: #6b7280;
            font-size: clamp(0.8rem, 2.2vw, 0.95rem);
            word-wrap: break-word;
        }

        .datetime-pill {
            font-size: clamp(0.72rem, 1.8vw, 0.85rem);
            color: #374151;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            white-space: nowrap;
            max-width: 100%;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #111827, #4b5563);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }

        .content-area {
            padding: clamp(0.75rem, 2.5vw, 1.5rem);
            padding-bottom: max(1.5rem, env(safe-area-inset-bottom));
            max-width: 100%;
        }

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

        /* Cards */
        .card-soft {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
            transition: transform var(--transition), box-shadow var(--transition);
            height: 100%;
        }

        @media (hover: hover) {
            .card-soft:hover {
                transform: translateY(-4px) scale(1.01);
                box-shadow: 0 12px 32px rgba(0,0,0,0.1);
            }
        }

        @media (hover: none) {
            .card-soft:active {
                transform: translateY(-2px);
            }
        }

        .quick-action-card {
            text-align: center;
            padding: clamp(1rem, 3vw, 1.5rem) clamp(0.5rem, 2vw, 1rem);
            cursor: pointer;
            color: inherit;
            display: block;
        }

        .quick-action-card .icon-wrap {
            width: 56px;
            height: 56px;
            margin: 0 auto 0.75rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }

        .quick-action-card h6 {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #111827;
        }

        .quick-action-card p {
            font-size: 0.8rem;
            color: #6b7280;
            margin: 0;
        }

        /* Facility cards — real photos from /assets */
        .facility-img {
            height: clamp(120px, 28vw, 180px);
            border-radius: var(--card-radius) var(--card-radius) 0 0;
            overflow: hidden;
            background: #e5e7eb;
        }

        .facility-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }

        @media (hover: hover) {
            .card-soft:hover .facility-img img {
                transform: scale(1.06);
            }
        }

        /* Whole facility card links to detail page */
        a.facility-card-link {
            color: inherit;
            text-decoration: none;
        }
        a.facility-card-link:focus-visible {
            outline: 3px solid #0d6efd;
            outline-offset: 2px;
        }

        /* Unavailable facility — whole card greyed out */
        .facility-card-link.facility-unavailable .card-soft {
            background: #e5e7eb;
            border-color: #d1d5db;
            box-shadow: none;
            opacity: 0.78;
        }
        .facility-card-link.facility-unavailable .facility-img {
            position: relative;
        }
        .facility-card-link.facility-unavailable .facility-img::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(55, 65, 81, 0.35);
            pointer-events: none;
        }
        .facility-card-link.facility-unavailable .facility-img img {
            filter: grayscale(1) brightness(0.75);
        }
        .facility-card-link.facility-unavailable .facility-body h6,
        .facility-card-link.facility-unavailable .facility-body p {
            color: #6b7280;
        }
        .facility-card-link.facility-unavailable .badge.bg-dark {
            background: #9ca3af !important;
        }
        @media (hover: hover) {
            .facility-card-link.facility-unavailable:hover .card-soft {
                transform: none;
                box-shadow: none;
            }
            .facility-card-link.facility-unavailable:hover .facility-img img {
                transform: none;
            }
        }

        .facility-body {
            padding: 1rem 1.1rem 1.15rem;
        }

        .facility-body h6 {
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .facility-body p {
            font-size: 0.82rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
            min-height: 2.4rem;
        }

        .btn-book {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Table */
        .table-modern thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            border-bottom-width: 1px;
        }

        .table-modern tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Hamburger (mobile) */
        .btn-menu-toggle {
            display: none;
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 10px;
            padding: 0.45rem 0.6rem;
            min-width: 2.75rem;
            min-height: 2.75rem;
        }

        /* =========================
           RESPONSIVE
           ========================= */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-wrap {
                margin-left: 0;
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }

            .btn-menu-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .datetime-pill {
                font-size: 0.78rem;
            }
        }

        /* Very small phones: allow date/time to wrap */
        @media (max-width: 575.98px) {
            .datetime-pill {
                white-space: normal;
                text-align: center;
                line-height: 1.35;
            }

            .table-modern tbody td {
                font-size: 0.82rem;
            }
        }
    </style>
</head>
<body>

<!-- ========================= SIDEBAR ========================= -->
<?php
$student_nav_active = 'dashboard';
include __DIR__ . '/includes/student_sidebar.php';
?>

<!-- ========================= MAIN ========================= -->
<div class="main-wrap" id="mainWrap">
    <!-- Top Header -->
    <header class="top-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <div class="page-title">Student Dashboard</div>
                    <div class="welcome-text">
                        Welcome back, <?php echo $student_name; ?> 👋
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime">
                    <!-- Filled by JavaScript -->
                </div>
                <?php include __DIR__ . '/includes/student_notification_bell.php'; ?>
                <div class="avatar" title="<?php echo $student_email !== '' ? $student_email : $student_name; ?>">
                    <?php
                    // Initials from full name
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

        <?php if (isset($_GET['booked']) && $_GET['booked'] === '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php if (isset($_GET['paid']) && $_GET['paid'] === '1'): ?>
                    Payment received. Your booking is <strong>pending</strong> staff approval.
                <?php else: ?>
                    Your booking was submitted and is <strong>pending</strong> staff approval.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2 class="section-title"><i class="bi bi-graph-up-arrow text-primary"></i> Overview</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($student_overview_stats as $s): ?>
            <div class="col-6 col-xl-3">
                <div class="card-soft p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width:48px;height:48px;background:<?php echo htmlspecialchars($s['gradient'], ENT_QUOTES, 'UTF-8'); ?>;">
                            <i class="bi <?php echo htmlspecialchars($s['icon'], ENT_QUOTES, 'UTF-8'); ?> fs-5"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold lh-1"><?php echo htmlspecialchars($s['value'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ========================= QUICK ACTIONS ========================= -->
        <h2 class="section-title"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Actions</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <a href="booking.php" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#111827,#374151);">
                        <i class="bi bi-calendar2-plus"></i>
                    </div>
                    <h6>Book Facility</h6>
                    <p>Reserve a slot</p>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="booking_history.php" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#0d6efd,#3b82f6);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h6>Booking History</h6>
                    <p>Track your bookings</p>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="student_wallet.php" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#059669,#10b981);">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h6>Wallet</h6>
                    <p>Balance & top-up</p>
                </a>
            </div>
        </div>

        <!-- ========================= AVAILABLE FACILITIES ========================= -->
        <h2 class="section-title"><i class="bi bi-building text-secondary"></i> Facilities</h2>
        <?php if (empty($facilities)): ?>
            <div class="card-soft p-4 text-center text-muted">No facilities configured yet.</div>
        <?php else: ?>
        <div class="row g-3 mb-4">
            <?php foreach ($facilities as $f): ?>
            <?php $can_book = !empty($f['bookable']); ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="<?php echo htmlspecialchars($f['detail_url'], ENT_QUOTES, 'UTF-8'); ?>" class="facility-card-link d-block h-100<?php echo $can_book ? '' : ' facility-unavailable'; ?>">
                    <div class="card-soft overflow-hidden h-100">
                        <div class="facility-img">
                            <img
                                src="<?php echo htmlspecialchars($f['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                loading="lazy"
                            >
                        </div>
                        <div class="facility-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <h6><?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                <span class="badge <?php echo htmlspecialchars($f['status_class'], ENT_QUOTES, 'UTF-8'); ?> rounded-pill" style="font-size: 0.65rem;">
                                    <?php echo htmlspecialchars($f['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <p><?php echo htmlspecialchars($f['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php
                            $fp = facility_pricing_get($f['facility_type'] ?? '', $conn);
                            if ($fp !== null):
                            ?>
                            <p class="small mb-2">
                                <span class="badge bg-dark rounded-pill px-2 py-1">
                                    <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars(facility_pricing_format_rm((float) $fp['amount']), ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="opacity-75"> · <?php echo htmlspecialchars($fp['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </p>
                            <?php endif; ?>
                            <?php if ($can_book): ?>
                            <span class="btn btn-dark w-100 btn-book d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar-check me-1"></i> View &amp; Book
                            </span>
                            <?php else: ?>
                            <span class="btn btn-secondary w-100 d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-slash-circle me-1"></i> Unavailable
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ========================= RECENT BOOKINGS ========================= -->
        <h2 class="section-title"><i class="bi bi-journal-text text-primary"></i> Recent Bookings</h2>
        <div class="card-soft p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_bookings)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No bookings yet.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_bookings as $b): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($b['facility'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($b['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($b['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="pe-4">
                                <span class="badge rounded-pill <?php echo htmlspecialchars($b['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($b['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="text-center text-muted small mt-4 pb-3">
            &copy; <?php echo date('Y'); ?> Scholar Hub — Student Portal
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    // =========================
    // Live date & time
    // =========================
    function updateDateTime() {
        var el = document.getElementById('liveDateTime');
        if (!el) return;
        var now = new Date();
        var options = {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        el.textContent = now.toLocaleString(undefined, options);
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // =========================
    // Mobile sidebar + backdrop
    // =========================
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var btnToggle = document.getElementById('btnMenuToggle');

    function closeSidebar() {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    function openSidebar() {
        sidebar.classList.add('show');
        backdrop.classList.add('show');
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

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) closeSidebar();
    });

    // =========================
    // Sidebar active state (demo: click highlights; dashboard default)
    // =========================
    var navLinks = document.querySelectorAll('.nav-link-sidebar[data-nav]');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Don't hijack real navigation for logout
            if (link.getAttribute('href') === 'logout.php') return;

            // Demo links use href="#" — prevent jump
            if (link.getAttribute('href') === '#') e.preventDefault();

            navLinks.forEach(function (l) { l.classList.remove('active'); });
            link.classList.add('active');
            if (window.innerWidth < 992) closeSidebar();
        });
    });
})();
</script>
<?php include __DIR__ . '/includes/student_notification_scripts.php'; ?>
</body>
</html>