<?php
/**
 * Scholar Hub — Sport Facility Booking System
 * staff_dashboard.php — Staff role dashboard (UI only, dummy data)
 */

session_start();

// =========================
// SESSION PROTECTION
// =========================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit();
}

$staff_name = isset($_SESSION['full_name']) && trim((string) $_SESSION['full_name']) !== ''
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Staff';

$staff_email = isset($_SESSION['email'])
    ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';

// ---- Dummy overview stats (replace with DB later) ----
$overview_stats = [
    ['label' => 'Pending Booking Requests', 'value' => '14', 'icon' => 'bi-inbox', 'gradient' => 'linear-gradient(135deg,#f59e0b,#d97706)'],
    ['label' => 'Approved Today', 'value' => '23', 'icon' => 'bi-check-circle', 'gradient' => 'linear-gradient(135deg,#10b981,#059669)'],
    ['label' => 'Facilities Active', 'value' => '7', 'icon' => 'bi-building', 'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)'],
    ['label' => 'Pending Payments', 'value' => '5', 'icon' => 'bi-credit-card', 'gradient' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)'],
];

// ---- Dummy pending bookings (dashboard list: all Pending; Approve/Reject on detail page after View) ----
$pending_bookings = [
    ['id' => 1, 'student' => 'Ahmad Zaki', 'facility' => 'Badminton Court', 'date' => '2026-05-14', 'time' => '10:00 - 11:00'],
    ['id' => 2, 'student' => 'Sarah Lee', 'facility' => 'Swimming Pool', 'date' => '2026-05-14', 'time' => '15:30 - 16:30'],
    ['id' => 3, 'student' => 'Raj Kumar', 'facility' => 'Gym Room', 'date' => '2026-05-14', 'time' => '18:00 - 19:00'],
    ['id' => 4, 'student' => 'Emily Chen', 'facility' => 'Tennis Court', 'date' => '2026-05-15', 'time' => '09:00 - 10:30'],
];

// ---- Dummy facility status (all 9 facilities) ----
$facility_status = [
    ['name' => 'Badminton Court', 'status' => 'Available', 'status_class' => 'bg-success', 'bookings_today' => '12', 'next_slot' => '11:00 AM', 'image' => 'assets/badmintoncourt.webp'],
    ['name' => 'Tennis Court', 'status' => 'Limited Slots', 'status_class' => 'bg-warning text-dark', 'bookings_today' => '8', 'next_slot' => '2:00 PM', 'image' => 'assets/tenniscourt.jpg'],
    ['name' => 'Swimming Pool', 'status' => 'Available', 'status_class' => 'bg-success', 'bookings_today' => '34', 'next_slot' => '8:30 AM', 'image' => 'assets/swimmingpool.jpg'],
    ['name' => 'Gym Room', 'status' => 'Maintenance', 'status_class' => 'bg-danger', 'bookings_today' => '0', 'next_slot' => '—', 'image' => 'assets/gymroom.jpg'],
    ['name' => 'Track Field', 'status' => 'Available', 'status_class' => 'bg-success', 'bookings_today' => '22', 'next_slot' => '6:00 AM', 'image' => 'assets/trackfield.webp'],
    ['name' => 'Volleyball Court', 'status' => 'Limited Slots', 'status_class' => 'bg-warning text-dark', 'bookings_today' => '5', 'next_slot' => '3:00 PM', 'image' => 'assets/volleyballcourt.webp'],
    ['name' => 'Basketball Court', 'status' => 'Available', 'status_class' => 'bg-success', 'bookings_today' => '6', 'next_slot' => '4:30 PM', 'image' => 'assets/basketballcourt.jpeg'],
    ['name' => 'Snooker Room', 'status' => 'Available', 'status_class' => 'bg-success', 'bookings_today' => '4', 'next_slot' => '7:00 PM', 'image' => 'assets/snookerroom.jpg'],
    ['name' => 'Futsal Court', 'status' => 'Maintenance', 'status_class' => 'bg-danger', 'bookings_today' => '0', 'next_slot' => '—', 'image' => 'assets/futsalcourt.jpg'],
];

// ---- Dummy payments (all Pending until staff accepts; action Accept / Reject) ----
$payment_rows = [
    ['student' => 'Daniel Wong', 'amount' => 'RM 25.00', 'method' => 'Online Banking'],
    ['student' => 'Nur Aina', 'amount' => 'RM 40.00', 'method' => 'E-Wallet'],
    ['student' => 'James Ong', 'amount' => 'RM 15.00', 'method' => 'Card'],
    ['student' => 'Priya Sharma', 'amount' => 'RM 30.00', 'method' => 'Online Banking'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Scholar Hub — Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ========================= ROOT & BASE ========================= */
        :root {
            --sidebar-width: 268px;
            --sidebar-bg: #0b0b0b;
            --sidebar-hover: #1f1f1f;
            --page-bg: #f3f4f6;
            --card-radius: 14px;
            --transition: 0.22s ease;
        }
        html { overflow-x: hidden; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
        }
        a { text-decoration: none; color: inherit; }

        /* ========================= SIDEBAR ========================= */
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
            box-shadow: 4px 0 24px rgba(0,0,0,0.15);
        }
        .sidebar-brand {
            font-weight: 800;
            letter-spacing: 0.04em;
            font-size: 1.02rem;
            padding: 0.5rem 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 0.75rem;
        }
        .sidebar-brand small {
            display: block;
            font-weight: 500;
            opacity: 0.65;
            font-size: 0.7rem;
            letter-spacing: 0.02em;
            margin-top: 0.25rem;
        }
        .nav-link-sidebar {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: rgba(255,255,255,0.9);
            padding: 0.6rem 0.85rem;
            border-radius: 10px;
            margin-bottom: 0.2rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background var(--transition), color var(--transition), transform var(--transition);
        }
        .nav-link-sidebar i { font-size: 1.1rem; opacity: 0.92; }
        .nav-link-sidebar:hover {
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(3px);
        }
        .nav-link-sidebar.active {
            background: #fff;
            color: #0b0b0b;
        }
        .nav-link-sidebar.active i { color: #0b0b0b; }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1030;
        }
        .sidebar-backdrop.show { display: block; }

        /* ========================= MAIN ========================= */
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
            background: linear-gradient(135deg, #1e3a5f, #475569);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }
        .content-area {
            padding: clamp(0.75rem, 2.5vw, 1.5rem);
            padding-bottom: max(1.5rem, env(safe-area-inset-bottom));
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

        /* Stat cards */
        .stat-card {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            padding: 1.25rem 1.35rem;
            height: 100%;
            transition: transform var(--transition), box-shadow var(--transition);
        }
        @media (hover: hover) {
            .stat-card:hover {
                transform: translateY(-4px) scale(1.02);
                box-shadow: 0 14px 36px rgba(0,0,0,0.1);
            }
        }
        .stat-card .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            color: #fff;
            margin-bottom: 0.75rem;
        }
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.02em;
        }
        .stat-card .stat-label {
            font-size: 0.82rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* Quick action cards */
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
        .quick-action-card {
            text-align: center;
            padding: clamp(1rem, 3vw, 1.5rem) clamp(0.5rem, 2vw, 1rem);
            cursor: pointer;
            display: block;
            color: inherit;
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
        .quick-action-card h6 { font-weight: 700; color: #111827; margin-bottom: 0.2rem; }
        .quick-action-card p { font-size: 0.8rem; color: #6b7280; margin: 0; }

        /* Tables */
        .table-modern thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            border-bottom-width: 1px;
        }
        .table-modern tbody td { vertical-align: middle; font-size: 0.88rem; }
        .table-wrap {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        /* Facility status mini cards */
        .staff-facility-img {
            height: 120px;
            overflow: hidden;
            background: #e5e7eb;
        }
        .staff-facility-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }
        @media (hover: hover) {
            .card-soft:hover .staff-facility-img img { transform: scale(1.05); }
        }
        .staff-facility-body { padding: 1rem 1.1rem; }

        .btn-menu-toggle {
            display: none;
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 10px;
            padding: 0.45rem 0.6rem;
            min-width: 2.75rem;
            min-height: 2.75rem;
        }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
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
        }
        @media (max-width: 575.98px) {
            .datetime-pill { white-space: normal; text-align: center; line-height: 1.35; }
        }
    </style>
</head>
<body>

<!-- ========================= SIDEBAR ========================= -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill me-1"></i> SCHOLAR HUB
        <small>Sport Facility Booking — Staff</small>
    </div>
    <nav class="d-flex flex-column flex-grow-1">
        <a href="staff_dashboard.php" class="nav-link-sidebar active" data-nav="dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="requests">
            <i class="bi bi-inbox"></i> Booking Requests
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="schedule">
            <i class="bi bi-calendar3"></i> Facility Schedule
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="status">
            <i class="bi bi-activity"></i> Facility Status
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="payments">
            <i class="bi bi-wallet2"></i> Payment Verification
        </a>
        <a href="#" class="nav-link-sidebar" data-nav="notifications">
            <i class="bi bi-bell"></i> Notifications
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
    <header class="top-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <div class="page-title">Staff Dashboard</div>
                    <div class="welcome-text">Welcome back, <?php echo $staff_name; ?> 👋</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime"></div>
                <div class="avatar" title="<?php echo $staff_email !== '' ? $staff_email : $staff_name; ?>">
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

        <!-- ========================= OVERVIEW STATS ========================= -->
        <h2 class="section-title"><i class="bi bi-graph-up-arrow text-primary"></i> Overview</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($overview_stats as $s): ?>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: <?php echo htmlspecialchars($s['gradient'], ENT_QUOTES, 'UTF-8'); ?>;">
                        <i class="bi <?php echo htmlspecialchars($s['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($s['value'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ========================= QUICK ACTIONS ========================= -->
        <h2 class="section-title"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Actions</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card d-block rounded overflow-hidden">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#ea580c,#c2410c);"><i class="bi bi-clipboard-check"></i></div>
                    <h6>Review Bookings</h6>
                    <p>Approve or reject requests</p>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card d-block rounded overflow-hidden">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="bi bi-calendar-week"></i></div>
                    <h6>Facility Schedule</h6>
                    <p>View daily timetable</p>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card d-block rounded overflow-hidden">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#059669,#047857);"><i class="bi bi-cash-coin"></i></div>
                    <h6>Verify Payments</h6>
                    <p>Pending transactions</p>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#" class="card-soft quick-action-card d-block rounded overflow-hidden">
                    <div class="icon-wrap" style="background: linear-gradient(135deg,#7c3aed,#6d28d9);"><i class="bi bi-send"></i></div>
                    <h6>Send Notifications</h6>
                    <p>Broadcast to students</p>
                </a>
            </div>
        </div>

        <!-- ========================= PENDING BOOKINGS ========================= -->
        <h2 class="section-title"><i class="bi bi-journal-text text-secondary"></i> Pending Bookings</h2>
        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Student Name</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th class="pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_bookings as $b): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($b['student'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($b['facility'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($b['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($b['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="badge rounded-pill text-bg-warning">Pending</span>
                            </td>
                            <td class="pe-4">
                                <a href="staff_booking_request.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ========================= FACILITY STATUS ========================= -->
        <h2 class="section-title"><i class="bi bi-building text-info"></i> Facility Status</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($facility_status as $f): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card-soft overflow-hidden h-100">
                    <div class="staff-facility-img">
                        <img src="<?php echo htmlspecialchars($f['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                    </div>
                    <div class="staff-facility-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                            <span class="badge <?php echo htmlspecialchars($f['status_class'], ENT_QUOTES, 'UTF-8'); ?> rounded-pill" style="font-size: 0.65rem;">
                                <?php echo htmlspecialchars($f['status'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <p class="small text-muted mb-1"><i class="bi bi-calendar-day me-1"></i>Bookings today: <strong><?php echo htmlspecialchars($f['bookings_today'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <p class="small text-muted mb-0"><i class="bi bi-clock me-1"></i>Next booking: <strong><?php echo htmlspecialchars($f['next_slot'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ========================= PAYMENT VERIFICATION ========================= -->
        <h2 class="section-title"><i class="bi bi-credit-card-2-front text-success"></i> Payment Verification</h2>
        <div class="table-wrap mb-2">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th class="pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_rows as $p): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($p['student'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($p['amount'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="badge rounded-pill text-bg-warning">Pending</span>
                            </td>
                            <td class="pe-4">
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-sm btn-success rounded-pill px-2">Accept</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2">Reject</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="text-center text-muted small mt-4 pb-2">
            &copy; <?php echo date('Y'); ?> Scholar Hub — Staff Portal <span class="d-none d-md-inline">·</span> <span class="d-block d-md-inline">Dummy data for presentation</span>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    // Live date & time
    function updateDateTime() {
        var el = document.getElementById('liveDateTime');
        if (!el) return;
        var now = new Date();
        el.textContent = now.toLocaleString(undefined, {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Mobile sidebar
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
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) closeSidebar();
    });

    // Active nav (demo: highlight on click; skip logout link)
    document.querySelectorAll('.nav-link-sidebar[data-nav]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (link.getAttribute('href') === 'logout.php') return;
            if (link.getAttribute('href') === '#') e.preventDefault();
            document.querySelectorAll('.nav-link-sidebar[data-nav]').forEach(function (l) {
                l.classList.remove('active');
            });
            link.classList.add('active');
            if (window.innerWidth < 992) closeSidebar();
        });
    });
})();
</script>
</body>
</html>
