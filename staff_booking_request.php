<?php
/**
 * Scholar Hub — Staff booking request detail (UI placeholder)
 * Opened from staff_dashboard.php → View. Approve / Reject live here.
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Dummy map (replace with DB later)
$dummy = [
    1 => ['student' => 'Ahmad Zaki', 'facility' => 'Badminton Court', 'date' => '2026-05-14', 'time' => '10:00 - 11:00'],
    2 => ['student' => 'Sarah Lee', 'facility' => 'Swimming Pool', 'date' => '2026-05-14', 'time' => '15:30 - 16:30'],
    3 => ['student' => 'Raj Kumar', 'facility' => 'Gym Room', 'date' => '2026-05-14', 'time' => '18:00 - 19:00'],
    4 => ['student' => 'Emily Chen', 'facility' => 'Tennis Court', 'date' => '2026-05-15', 'time' => '09:00 - 10:30'],
];

$row = $dummy[$id] ?? null;
$staff_name = isset($_SESSION['full_name']) ? htmlspecialchars((string) $_SESSION['full_name'], ENT_QUOTES, 'UTF-8') : 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Booking request — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f3f4f6; min-height: 100vh; font-family: system-ui, sans-serif; }
        .card-box { max-width: 560px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="py-4 py-md-5">
    <div class="container px-3">
        <a href="staff_dashboard.php" class="btn btn-outline-dark btn-sm rounded-pill mb-3">
            <i class="bi bi-arrow-left me-1"></i> Back to dashboard
        </a>

        <?php if (!$row): ?>
            <div class="alert alert-warning">Request not found (dummy data only supports id 1–4).</div>
        <?php else: ?>
            <div class="card card-box border-0 mx-auto">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 fw-bold mb-1">Booking request #<?php echo $id; ?></h1>
                    <p class="text-muted small mb-4">Review details, then approve or reject.</p>

                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Student</dt>
                        <dd class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($row['student'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Facility</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['facility'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Date</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Time</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8"><span class="badge text-bg-warning">Pending</span></dd>
                    </dl>

                    <hr class="my-4">

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success rounded-pill px-4"><i class="bi bi-check-lg me-1"></i> Approve</button>
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4"><i class="bi bi-x-lg me-1"></i> Reject</button>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Buttons are UI-only until you connect the database.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
