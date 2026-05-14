<?php
/**
 * Scholar Hub — Sport Facility Booking System
 * admin_dashboard.php — Admin home (UI only, demo data; no database)
 */
require_once __DIR__ . '/includes/admin_auth.php';

$admin_nav_active = 'dashboard';
$admin_page_title = 'Admin Dashboard';

// ---- Overview stats (same card pattern as staff dashboard) ----
$overview_stats = [
    [
        'label' => 'Total Students',
        'value' => '842',
        'icon' => 'bi-mortarboard',
        'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)',
    ],
    [
        'label' => 'Total Staff',
        'value' => '36',
        'icon' => 'bi-person-workspace',
        'gradient' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)',
    ],
    [
        'label' => 'Total Bookings',
        'value' => '12,847',
        'icon' => 'bi-calendar-check',
        'gradient' => 'linear-gradient(135deg,#10b981,#059669)',
    ],
    [
        'label' => 'Total Income',
        'value' => 'RM 125,430.50',
        'icon' => 'bi-cash-stack',
        'gradient' => 'linear-gradient(135deg,#f59e0b,#d97706)',
    ],
];

$announcements = [
    ['title' => 'Semester booking window opens Monday', 'time' => '2 hours ago', 'type' => 'announcement'],
    ['title' => 'Swimming pool lane 3 closed for maintenance', 'time' => '5 hours ago', 'type' => 'maintenance'],
    ['title' => 'System update scheduled — 02:00 AM', 'time' => 'Yesterday', 'type' => 'system'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">

        <!-- ========================= Overview + quick actions ========================= -->
        <section id="dashboard-overview" class="mb-4">
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

            <h2 class="section-title"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Actions</h2>
            <div class="row g-3 mb-2">
                <div class="col-6 col-md-3">
                    <a href="admin_users.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                        <div class="icon-wrap" style="background: linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="bi bi-people-fill"></i></div>
                        <h6>User Management</h6>
                        <p>Student &amp; staff lists</p>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="admin_booking_reports.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                        <div class="icon-wrap" style="background: linear-gradient(135deg,#059669,#047857);"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
                        <h6>Booking Reports</h6>
                        <p>Analytics &amp; trends</p>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="admin_wallet.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                        <div class="icon-wrap" style="background: linear-gradient(135deg,#ea580c,#c2410c);"><i class="bi bi-wallet2"></i></div>
                        <h6>Wallet Overview</h6>
                        <p>Balances &amp; transactions</p>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="admin_facilities.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                        <div class="icon-wrap" style="background: linear-gradient(135deg,#7c3aed,#6d28d9);"><i class="bi bi-building-fill-gear"></i></div>
                        <h6>Facility Management</h6>
                        <p>All venues &amp; status</p>
                    </a>
                </div>
            </div>
        </section>

        <!-- ========================= Notifications ========================= -->
        <section id="notifications" class="mb-4">
            <h2 class="section-title"><i class="bi bi-megaphone text-secondary"></i> Notifications</h2>
            <div class="table-wrap">
                <?php foreach ($announcements as $a): ?>
                    <?php
                    if ($a['type'] === 'maintenance') {
                        $dotColor = '#f59e0b';
                    } elseif ($a['type'] === 'system') {
                        $dotColor = '#6366f1';
                    } else {
                        $dotColor = '#0ea5e9';
                    }
                    $typeLabel = ucfirst($a['type']);
                    ?>
                <div class="notif-item d-flex gap-3">
                    <div class="notif-dot" style="background: <?php echo htmlspecialchars($dotColor, ENT_QUOTES, 'UTF-8'); ?>;"></div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold"><?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($a['time'], ENT_QUOTES, 'UTF-8'); ?> · <span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted">Dismiss</button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ========================= System settings placeholder ========================= -->
        <section id="system-settings" class="mb-4">
            <h2 class="section-title"><i class="bi bi-gear-fill text-dark"></i> System Settings</h2>
            <div class="settings-placeholder">
                <i class="bi bi-sliders fs-1 text-secondary mb-3 d-block"></i>
                <p class="mb-0 fw-semibold text-dark">Configuration panel (demo)</p>
                <p class="small mb-0 mt-2">SMTP, booking rules, and maintenance windows can be wired here later.</p>
            </div>
        </section>

        <footer class="text-center text-muted small pb-4">
            Scholar Hub — Sport Facility Booking System · Admin Dashboard (UI prototype)
        </footer>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>

<script>
(function () {
    'use strict';
    // Scroll spy: highlight sidebar items with data-scroll (dashboard home only)
    var scrollLinks = document.querySelectorAll('[data-scroll]');
    var sections = Array.from(document.querySelectorAll('section[id]'));
    if (!scrollLinks.length || sections.length < 2) return;

    function getDocTop(el) {
        return window.scrollY + el.getBoundingClientRect().top;
    }

    var ticking = false;
    function onScroll() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(function () {
            var scrollPos = window.scrollY + 140;
            var current = sections[0] ? sections[0].id : 'dashboard-overview';
            sections.forEach(function (sec) {
                if (scrollPos >= getDocTop(sec)) {
                    current = sec.id;
                }
            });
            scrollLinks.forEach(function (link) {
                var key = link.getAttribute('data-scroll');
                link.classList.toggle('active', key === current);
            });
            ticking = false;
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
</body>
</html>
