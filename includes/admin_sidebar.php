<?php
/**
 * Admin sidebar (expects $admin_nav_active: dashboard|users|facilities|reports|wallet)
 */
if (!isset($admin_nav_active)) {
    $admin_nav_active = 'dashboard';
}
?>
<aside class="sidebar" id="sidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill me-1"></i> SCHOLAR HUB
        <small>Sport Facility Booking — Admin</small>
    </div>
    <nav class="d-flex flex-column flex-grow-1">
        <a href="admin_dashboard.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'dashboard' ? 'active' : ''; ?>" data-scroll="dashboard-overview">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="admin_users.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> User Management
        </a>
        <a href="admin_facilities.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'facilities' ? 'active' : ''; ?>">
            <i class="bi bi-building"></i> Facility Management
        </a>
        <a href="admin_booking_reports.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'reports' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up-arrow"></i> Booking Reports
        </a>
        <a href="admin_wallet.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'wallet' ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i> Wallet Overview
        </a>
        <a href="send_notification.php" class="nav-link-sidebar <?php echo ($admin_nav_active === 'notify' || $admin_nav_active === 'notify_send') ? 'active' : ''; ?>">
            <i class="bi bi-send"></i> Send Notification
        </a>
        <a href="view_sent_notifications.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'notify_view' ? 'active' : ''; ?>">
            <i class="bi bi-bell"></i> View Sent Notifications
        </a>
        <a href="admin_dashboard.php#system-settings" class="nav-link-sidebar" data-scroll="system-settings">
            <i class="bi bi-gear"></i> System Settings
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link-sidebar text-danger" style="background: rgba(220,53,69,0.12);">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
