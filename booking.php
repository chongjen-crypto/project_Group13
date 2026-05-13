<?php
/**
 * Scholar Hub — Booking entry (placeholder)
 * Receives: booking.php?facility=Badminton%20Court
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$facility = isset($_GET['facility']) ? trim($_GET['facility']) : '';
$facility_display = htmlspecialchars($facility, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book facility — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f3f4f6; min-height: 100vh; font-family: system-ui, sans-serif; }
        .card-box { max-width: 640px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container">
        <div class="card card-box mx-auto border-0">
            <div class="card-body p-4 p-md-5">
                <a href="student_dashboard.php" class="btn btn-outline-dark btn-sm rounded-pill mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <h1 class="h4 fw-bold mb-2"><i class="bi bi-calendar2-check me-2"></i>Booking</h1>
                <p class="text-muted mb-4">This page receives your selected facility from the URL.</p>

                <?php if ($facility === ''): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>No facility selected. Choose a facility from the dashboard.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <strong>Facility:</strong> <?php echo $facility_display; ?>
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        Replace this placeholder with your full booking form (date, time slot, payment, etc.).
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
