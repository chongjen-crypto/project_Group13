<?php
/**
 * Scholar Hub — Admin dashboard
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';

$admin_nav_active = 'dashboard';
$admin_page_title = 'Admin Dashboard';
$overview_stats = stats_admin_overview($conn);

?><!DOCTYPE html>
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
                    <a href="admin_booking_requests.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                        <div class="icon-wrap" style="background: linear-gradient(135deg,#059669,#047857);"><i class="bi bi-inbox"></i></div>
                        <h6>Booking Requests</h6>
                        <p>Pending requests</p>
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
                <div class="col-6 col-md-3">
                    <a href="send_notification.php" class="card-soft quick-action-card d-block rounded overflow-hidden">
                        <div class="icon-wrap" style="background: linear-gradient(135deg,#0d9488,#0f766e);"><i class="bi bi-send"></i></div>
                        <h6>Send Notification</h6>
                        <p>Message all students</p>
                    </a>
                </div>
            </div>
        </section>

        <section id="notifications" class="mb-4">
            <h2 class="section-title"><i class="bi bi-megaphone text-secondary"></i> Student Notifications</h2>
            <div class="table-wrap p-4">
                <p class="text-muted small mb-3">Compose a title and message; students receive it in the bell icon on their pages.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="send_notification.php" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-send me-1"></i> Send Notification
                    </a>
                    <a href="view_sent_notifications.php" class="btn btn-outline-dark rounded-pill px-4">
                        <i class="bi bi-bell me-1"></i> View Notifications
                    </a>
                </div>
            </div>
        </section>

        <footer class="text-center text-muted small pb-4">
            Scholar Hub — Sport Facility Booking System · Admin Dashboard
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
