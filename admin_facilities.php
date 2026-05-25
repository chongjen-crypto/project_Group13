<?php
/**
 * Scholar Hub — Admin: All facilities (demo data).
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_facilities_data.php';

$admin_nav_active = 'facilities';
$admin_page_title = 'Facility Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Facility Management — Scholar Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">
        <h2 class="section-title"><i class="bi bi-building text-info"></i> All Facilities</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($admin_all_facilities as $f): ?>
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
                        <p class="small text-muted mb-1">
                            <i class="bi bi-calendar-day me-1"></i>Bookings today: <strong><?php echo htmlspecialchars($f['bookings_today'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-graph-up me-1"></i>Total bookings (demo): <strong><?php echo number_format($f['total_bookings']); ?></strong>
                        </p>
                        <p class="small text-muted mb-3">
                            <i class="bi bi-clock me-1"></i>Next slot: <strong><?php echo htmlspecialchars($f['next_slot'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill">Disable</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center text-muted small pb-3 mb-0"><?php echo count($admin_all_facilities); ?> facilities (demo) — replace with database later.</p>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
