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

// Facilities data (could later come from DB)
$facilities = [
    [
        'name' => 'Badminton Court',
        'desc' => 'Indoor air-conditioned court',
        'status' => 'Available',
        'status_class' => 'bg-success',
        'image' => 'assets/badmintoncourt.webp',
        'detail_url' => 'badminton.php',
    ],
    [
        'name' => 'Tennis Court',
        'desc' => 'Outdoor hard court with lighting',
        'status' => 'Limited Slots',
        'status_class' => 'bg-warning text-dark',
        'image' => 'assets/tenniscourt.jpg',
        'detail_url' => 'tennis.php',
    ],
    [
        'name' => 'Swimming Pool',
        'desc' => 'Olympic-size swimming pool',
        'status' => 'Available',
        'status_class' => 'bg-success',
        'image' => 'assets/swimmingpool.jpg',
        'detail_url' => 'swimming_pool.php',
    ],
    [
        'name' => 'Gym Room',
        'desc' => 'Modern gym equipment',
        'status' => 'Maintenance',
        'status_class' => 'bg-danger',
        'image' => 'assets/gymroom.jpg',
        'detail_url' => 'gym_room.php',
    ],
    [
        'name' => 'Track Field',
        'desc' => '400m synthetic running track',
        'status' => 'Available',
        'status_class' => 'bg-success',
        'image' => 'assets/trackfield.webp',
        'detail_url' => 'track_field.php',
    ],
    [
        'name' => 'Volleyball Court',
        'desc' => 'Sand and indoor options',
        'status' => 'Limited Slots',
        'status_class' => 'bg-warning text-dark',
        'image' => 'assets/volleyballcourt.webp',
        'detail_url' => 'volleyball_court.php',
    ],
    [
        'name' => 'Basketball Court',
        'desc' => 'Full-size indoor court',
        'status' => 'Available',
        'status_class' => 'bg-success',
        'image' => 'assets/basketballcourt.jpeg',
        'detail_url' => 'basketball_court.php',
    ],
    [
        'name' => 'Snooker Room',
        'desc' => 'Quiet room with professional tables',
        'status' => 'Available',
        'status_class' => 'bg-success',
        'image' => 'assets/snookerroom.jpg',
        'detail_url' => 'snooker_room.php',
    ],
    [
        'name' => 'Futsal Court',
        'desc' => 'Indoor 5-a-side pitch',
        'status' => 'Maintenance',
        'status_class' => 'bg-danger',
        'image' => 'assets/futsalcourt.jpg',
        'detail_url' => 'futsal.php',
    ],
];

// Dummy recent bookings (replace with DB query later)
$recent_bookings = [
    ['facility' => 'Badminton Court', 'date' => '2026-05-12', 'time' => '14:00 - 15:00', 'status' => 'Approved', 'status_class' => 'text-bg-success'],
    ['facility' => 'Swimming Pool', 'date' => '2026-05-10', 'time' => '09:00 - 10:00', 'status' => 'Pending', 'status_class' => 'text-bg-warning'],
    ['facility' => 'Gym Room', 'date' => '2026-05-08', 'time' => '18:00 - 19:00', 'status' => 'Rejected', 'status_class' => 'text-bg-danger'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholar Hub — Student Dashboard</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

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

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            min-height: 100vh;
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
            background: var(--sidebar-bg);
            color: #fff;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            padding: 1.25rem 1rem;
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
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .page-title {
            font-weight: 700;
            font-size: 1.35rem;
            color: #111827;
        }

        .welcome-text {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .datetime-pill {
            font-size: 0.85rem;
            color: #374151;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            white-space: nowrap;
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
            padding: 1.5rem;
        }

        /* Section titles */
        .section-title {
            font-weight: 700;
            font-size: 1.1rem;
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

        .card-soft:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }

        .quick-action-card {
            text-align: center;
            padding: 1.5rem 1rem;
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
            height: 160px;
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

        .card-soft:hover .facility-img img {
            transform: scale(1.06);
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
    </style>
</head>
<body>

<!-- ========================= SIDEBAR ========================= -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill me-1"></i> SCHOLAR HUB
        <small>Sport Facility Booking</small>
    </div>

    <nav class="d-flex flex-column flex-grow-1">
        <a href="student_dashboard.php" class="nav-link-sidebar active" data-nav="dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="book">
            <i class="bi bi-calendar2-plus"></i> Book Facility
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="history">
            <i class="bi bi-clock-history"></i> Booking History
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="wallet">
            <i class="bi bi-wallet2"></i> Wallet
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="profile">
            <i class="bi bi-person-circle"></i> Profile
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link-sidebar text-danger" style="background: rgba(220,53,69,0.12);">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

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

        <!-- ========================= QUICK ACTIONS ========================= -->
        <h2 class="section-title"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Actions</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#111827,#374151);">
                        <i class="bi bi-calendar2-plus"></i>
                    </div>
                    <h6>Book Facility</h6>
                    <p>Reserve a slot</p>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#0d6efd,#3b82f6);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h6>Booking History</h6>
                    <p>Track your bookings</p>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#059669,#10b981);">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h6>Wallet</h6>
                    <p>Balance & top-up</p>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#7c3aed,#a78bfa);">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h6>My Profile</h6>
                    <p>Account details</p>
                </a>
            </div>
        </div>

        <!-- ========================= AVAILABLE FACILITIES ========================= -->
        <h2 class="section-title"><i class="bi bi-building text-secondary"></i> Available Facilities</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($facilities as $f): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="<?php echo htmlspecialchars($f['detail_url'], ENT_QUOTES, 'UTF-8'); ?>" class="facility-card-link d-block h-100">
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
                            <span class="btn btn-dark w-100 btn-book d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar-check me-1"></i> Book Now
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

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
</body>
</html>