<?php
/**
 * Scholar Hub — Admin: Booking reports & analytics (database).
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';

$admin_nav_active = 'reports';
$admin_page_title = 'Booking Reports';

$reports = stats_booking_reports($conn);
$monthly_bookings = $reports['monthly'];
$monthly_max = (int) $reports['monthly_max'];
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
                    <div class="fs-5 fw-bold"><?php echo htmlspecialchars($reports['most_facility'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <p class="text-muted small mb-0 mt-2"><i class="bi bi-trophy text-warning"></i> <?php echo (int) $reports['most_count']; ?> booking(s)</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Total Bookings Today</div>
                    <div class="fs-5 fw-bold text-primary"><?php echo (int) $reports['bookings_today']; ?></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Pending Approvals</div>
                    <div class="fs-5 fw-bold text-warning"><?php echo (int) $reports['pending']; ?></div>
                    <p class="text-muted small mb-0 mt-2">Awaiting staff review</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold mb-3">Monthly Bookings (6 mo.)</div>
                    <div class="mini-chart" role="img" aria-label="Monthly bookings chart">
                        <?php foreach ($monthly_bookings as $mb): ?>
                        <?php
                        $pct = $monthly_max > 0 ? round(($mb['value'] / $monthly_max) * 100) : 0;
                        $height = max(4, $pct);
                        ?>
                        <div class="bar" style="height: <?php echo (int) $height; ?>%;" title="<?php echo htmlspecialchars($mb['month'] . ': ' . $mb['value'], ENT_QUOTES, 'UTF-8'); ?>"></div>
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
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
