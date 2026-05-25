<?php
/**
 * Student sidebar (set $student_nav_active: dashboard|book|history|wallet|profile)
 */
if (!isset($student_nav_active)) {
    $student_nav_active = 'dashboard';
}
?>
<aside class="sidebar" id="sidebar" aria-label="Student navigation">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill me-1"></i> SCHOLAR HUB
        <small>Sport Facility Booking</small>
    </div>
    <nav class="d-flex flex-column flex-grow-1">
        <a href="student_dashboard.php" class="nav-link-sidebar <?php echo $student_nav_active === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="booking.php" class="nav-link-sidebar <?php echo $student_nav_active === 'book' ? 'active' : ''; ?>">
            <i class="bi bi-calendar2-plus"></i> Book Facility
        </a>
        <a href="booking_history.php" class="nav-link-sidebar <?php echo $student_nav_active === 'history' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> Booking History
        </a>
        <a href="payment.php" class="nav-link-sidebar <?php echo $student_nav_active === 'wallet' ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i> Wallet
        </a>

    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link-sidebar text-danger" style="background: rgba(220,53,69,0.12);">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
