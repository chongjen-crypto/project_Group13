<?php
/**
 * Staff sidebar (set $staff_nav_active: dashboard)
 */
if (!isset($staff_nav_active)) {
    $staff_nav_active = 'dashboard';
}
?>
<aside class="sidebar" id="sidebar" aria-label="Staff navigation">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill me-1"></i> SCHOLAR HUB
        <small>Sport Facility Booking — Staff</small>
    </div>
    <nav class="d-flex flex-column flex-grow-1">
        <a href="staff_dashboard.php" class="nav-link-sidebar <?php echo $staff_nav_active === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        <a href="staff_view_requests.php" class="nav-link-sidebar <?php echo $staff_nav_active === 'requests' ? 'active' : ''; ?>" data-nav="requests">
            <i class="bi bi-inbox"></i> View Booking Requests
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link-sidebar text-danger" style="background: rgba(220,53,69,0.12);">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
