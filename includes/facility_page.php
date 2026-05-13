<?php
/**
 * Scholar Hub — shared facility detail layout
 * Expects $FACILITY array (set by badminton.php, tennis.php, etc.)
 */

if (!isset($FACILITY) || !is_array($FACILITY)) {
    http_response_code(500);
    exit('Facility configuration missing.');
}

session_start();

// Student-only (same as student_dashboard)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

/** @var array $FACILITY */
$F = $FACILITY;

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$name           = $F['name'] ?? 'Facility';
$tagline        = $F['tagline'] ?? '';
$image          = $F['image'] ?? 'assets/trackfield.webp';
$description    = $F['description'] ?? '';
$hours_lines    = $F['hours_lines'] ?? [];
$location       = $F['location'] ?? '';
$rules          = $F['rules'] ?? [];
$status         = $F['status'] ?? 'Available';
$status_class   = $F['status_class'] ?? 'bg-success';
$capacity       = $F['capacity'] ?? '';
$equipment      = $F['equipment'] ?? [];
$booking_name   = $F['booking_name'] ?? $name;

$booking_href = 'booking.php?facility=' . rawurlencode($booking_name);
$page_title = h($name) . ' — Scholar Hub';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sh-black: #0b0b0b;
            --sh-gray: #f3f4f6;
            --card-radius: 16px;
            --t: 0.25s ease;
        }
        html {
            overflow-x: hidden;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--sh-gray);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
        }
        .top-bar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            padding-top: env(safe-area-inset-top);
        }
        .btn-back {
            border-radius: 10px;
            font-weight: 600;
            transition: transform var(--t), box-shadow var(--t);
        }
        .btn-back:hover {
            transform: translateX(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
        }
        /* Gradient hero + facility image */
        .facility-hero {
            position: relative;
            min-height: clamp(200px, 42vw, 320px);
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            margin-bottom: 1.75rem;
        }
        .facility-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(11,11,11,0.92) 0%, rgba(11,11,11,0.55) 45%, rgba(11,11,11,0.25) 100%);
            z-index: 1;
        }
        .facility-hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transform: scale(1.02);
            transition: transform 0.6s ease;
        }
        @media (hover: hover) {
            .facility-hero:hover .facility-hero-bg {
                transform: scale(1.06);
            }
        }
        .facility-hero-content {
            position: relative;
            z-index: 2;
            color: #fff;
            padding: clamp(1.25rem, 4vw, 2.25rem) clamp(1rem, 3vw, 1.75rem);
            min-height: clamp(200px, 42vw, 320px);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .facility-hero h1 {
            font-weight: 800;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 20px rgba(0,0,0,0.35);
            font-size: clamp(1.35rem, 4.5vw, 2.25rem);
            line-height: 1.15;
            word-wrap: break-word;
        }
        .info-card {
            background: #fff;
            border-radius: var(--card-radius);
            border: 1px solid #eef0f3;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            padding: clamp(1rem, 3vw, 1.35rem) clamp(1rem, 3vw, 1.5rem);
            height: 100%;
            transition: transform var(--t), box-shadow var(--t);
        }
        @media (hover: hover) {
            .info-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 14px 36px rgba(0,0,0,0.1);
            }
        }
        .info-card h3 {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            margin-bottom: 0.85rem;
        }
        .rule-list li {
            margin-bottom: 0.45rem;
            padding-left: 0.25rem;
        }
        .equip-badge {
            font-weight: 600;
            border-radius: 999px;
            padding: 0.4rem 0.85rem;
            margin: 0.25rem;
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            font-size: 0.85rem;
            transition: background var(--t), border-color var(--t);
        }
        .equip-badge:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }
        .book-bar {
            background: linear-gradient(135deg, #111827 0%, #374151 100%);
            border-radius: var(--card-radius);
            padding: clamp(1.15rem, 3vw, 1.5rem) clamp(1rem, 3vw, 1.75rem);
            color: #fff;
            box-shadow: 0 12px 36px rgba(0,0,0,0.18);
        }
        .btn-book-main {
            border-radius: 12px;
            font-weight: 700;
            padding: 0.75rem 1.75rem;
            transition: transform var(--t), box-shadow var(--t);
            width: 100%;
        }
        @media (min-width: 768px) {
            .btn-book-main {
                width: auto;
            }
        }
        @media (hover: hover) {
            .btn-book-main:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.25);
                color: #fff;
            }
        }
        .desc-lead {
            font-size: clamp(0.95rem, 2.2vw, 1.05rem);
            line-height: 1.65;
            color: #374151;
        }

        main.container {
            padding-left: max(0.75rem, env(safe-area-inset-left));
            padding-right: max(0.75rem, env(safe-area-inset-right));
            padding-bottom: max(1rem, env(safe-area-inset-bottom));
        }

        .top-bar .container {
            padding-left: max(0.75rem, env(safe-area-inset-left));
            padding-right: max(0.75rem, env(safe-area-inset-right));
        }
    </style>
</head>
<body>

<!-- Top bar: back + brand -->
<header class="top-bar py-3">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
        <a href="student_dashboard.php" class="btn btn-outline-dark btn-back">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
        <span class="text-muted small fw-semibold text-uppercase letter-spacing-1">
            <i class="bi bi-mortarboard-fill me-1"></i> Scholar Hub
        </span>
    </div>
</header>

<main class="container py-4 py-md-5">

    <!-- Hero banner -->
    <section class="facility-hero">
        <div class="facility-hero-bg" style="background-image: url('<?php echo h($image); ?>');"></div>
        <div class="facility-hero-content">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge <?php echo h($status_class); ?> rounded-pill px-3 py-2">
                    <?php echo h($status); ?>
                </span>
                <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                    <i class="bi bi-people-fill me-1"></i><?php echo h($capacity); ?>
                </span>
            </div>
            <h1 class="display-6 mb-2"><?php echo h($name); ?></h1>
            <?php if ($tagline !== ''): ?>
                <p class="lead mb-0 opacity-90" style="font-size: 1rem;"><?php echo h($tagline); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Description -->
    <section class="mb-4">
        <div class="info-card">
            <h3><i class="bi bi-info-circle me-2 text-primary"></i>About this facility</h3>
            <p class="desc-lead mb-0"><?php echo nl2br(h($description)); ?></p>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <!-- Opening hours -->
        <div class="col-md-6">
            <div class="info-card">
                <h3><i class="bi bi-clock me-2 text-dark"></i>Opening hours</h3>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($hours_lines as $line): ?>
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-success mt-1"></i>
                            <span><?php echo h($line); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <!-- Location -->
        <div class="col-md-6">
            <div class="info-card">
                <h3><i class="bi bi-geo-alt me-2 text-danger"></i>Location</h3>
                <p class="mb-0 fs-6 text-secondary"><?php echo nl2br(h($location)); ?></p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Rules -->
        <div class="col-lg-7">
            <div class="info-card">
                <h3><i class="bi bi-shield-check me-2 text-warning"></i>Rules &amp; guidelines</h3>
                <ol class="rule-list ps-3 mb-0">
                    <?php foreach ($rules as $rule): ?>
                        <li><?php echo h($rule); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <!-- Equipment / features -->
        <div class="col-lg-5">
            <div class="info-card">
                <h3><i class="bi bi-stars me-2 text-info"></i>Equipment &amp; features</h3>
                <div class="d-flex flex-wrap">
                    <?php foreach ($equipment as $item): ?>
                        <span class="equip-badge"><i class="bi bi-check-lg text-success me-1"></i><?php echo h($item); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Book CTA -->
    <section class="book-bar d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="fw-bold fs-5 mb-1">Ready to book?</div>
            <div class="opacity-75 small">You will be taken to the booking form for this facility.</div>
        </div>
        <a href="<?php echo h($booking_href); ?>" class="btn btn-light btn-book-main text-dark">
            <i class="bi bi-calendar2-plus me-2"></i>Book Now
        </a>
    </section>

    <footer class="text-center text-muted small mt-5 mb-3">
        &copy; <?php echo date('Y'); ?> Scholar Hub — Facility information
    </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
