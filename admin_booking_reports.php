<?php
/**
 * Scholar Hub — Admin: Booking reports & analytics (demo data).
 */
require_once __DIR__ . '/includes/admin_auth.php';

$admin_nav_active = 'reports';
$admin_page_title = 'Booking Reports';

$monthly_bookings = [
    ['month' => 'Jan', 'value' => 62],
    ['month' => 'Feb', 'value' => 78],
    ['month' => 'Mar', 'value' => 85],
    ['month' => 'Apr', 'value' => 91],
    ['month' => 'May', 'value' => 88],
    ['month' => 'Jun', 'value' => 95],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Booking Reports — Scholar Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">
        <h2 class="section-title"><i class="bi bi-graph-up text-primary"></i> Booking Analytics</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Most Booked Facility</div>
                    <div class="fs-5 fw-bold">Swimming Pool</div>
                    <p class="text-muted small mb-0 mt-2"><i class="bi bi-trophy text-warning"></i> Demo: 9,102 bookings</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Total Bookings Today</div>
                    <div class="fs-5 fw-bold text-primary">47</div>
                    <p class="text-muted small mb-0 mt-2">Peak hours: 4 PM – 8 PM</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Pending Approvals</div>
                    <div class="fs-5 fw-bold text-warning">12</div>
                    <p class="text-muted small mb-0 mt-2">Awaiting staff review</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-3">Monthly Booking Statistics</div>
                    <div class="mini-chart" role="img" aria-label="Placeholder monthly bookings chart">
                        <?php foreach ($monthly_bookings as $mb): ?>
                        <div class="bar" style="height: <?php echo (int) $mb['value']; ?>%;" title="<?php echo htmlspecialchars($mb['month'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <?php foreach ($monthly_bookings as $mb): ?>
                        <span><?php echo htmlspecialchars($mb['month'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-muted small pb-3 mb-0">Placeholder analytics — connect reports to your database later.</p>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
