<?php
/**
 * Admin sidebar (expects $admin_nav_active: dashboard|users|facilities|reports|wallet|refunds)
 */
if (!isset($admin_nav_active)) {
    $admin_nav_active = 'dashboard';
}
$admin_base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../' : '';
?>
<aside class="sidebar" id="sidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill me-1"></i> SCHOLAR HUB
        <small>Sport Facility Booking — Admin</small>
    </div>
    <nav class="d-flex flex-column flex-grow-1">
        <a href="<?php echo $admin_base; ?>admin_dashboard.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'dashboard' ? 'active' : ''; ?>" data-scroll="dashboard-overview">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?php echo $admin_base; ?>admin_users.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> User Management
        </a>
        <a href="<?php echo $admin_base; ?>admin_facilities.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'facilities' ? 'active' : ''; ?>">
            <i class="bi bi-building"></i> Facility Management
        </a>
        <a href="<?php echo $admin_base; ?>admin_booking_requests.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'requests' ? 'active' : ''; ?>">
            <i class="bi bi-inbox"></i> Booking Requests
        </a>
        <a href="<?php echo $admin_base; ?>admin_booking_reports.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'reports' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up-arrow"></i> Booking Reports
        </a>
        <a href="<?php echo $admin_base; ?>admin_wallet.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'wallet' ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i> Wallet Overview
        </a>
        <a href="<?php echo $admin_base; ?>admin/refunds.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'refunds' ? 'active' : ''; ?>">
            <i class="bi bi-arrow-counterclockwise"></i> Refund Management
        </a>
        <a href="<?php echo $admin_base; ?>send_notification.php" class="nav-link-sidebar <?php echo ($admin_nav_active === 'notify' || $admin_nav_active === 'notify_send') ? 'active' : ''; ?>">
            <i class="bi bi-send"></i> Send Notification
        </a>
        <a href="<?php echo $admin_base; ?>view_sent_notifications.php" class="nav-link-sidebar <?php echo $admin_nav_active === 'notify_view' ? 'active' : ''; ?>">
            <i class="bi bi-bell"></i> View Notifications
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?php echo $admin_base; ?>logout.php" class="nav-link-sidebar text-danger" style="background: rgba(220,53,69,0.12);">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
