<?php
/**
 * Scholar Hub — Admin: Wallet overview (from paid bookings).
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';

$admin_nav_active = 'wallet';
$admin_page_title = 'Wallet Overview';

$wallet = stats_wallet_overview($conn);
$transactions = $wallet['transactions'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Wallet Overview — Scholar Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">
        <h2 class="section-title"><i class="bi bi-wallet2 text-success"></i> Wallet Summary</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="analytics-card border border-primary border-opacity-25">
                    <div class="text-muted small text-uppercase fw-bold">Total Paid (All Time)</div>
                    <div class="fs-4 fw-bold mt-2">RM <?php echo number_format($wallet['total_income'], 2); ?></div>
                    <small class="text-muted">From completed booking payments</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold">Paid Transactions</div>
                    <div class="fs-4 fw-bold mt-2"><?php echo (int) $wallet['paid_count']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold">Payments Today</div>
                    <div class="fs-4 fw-bold mt-2 text-success">RM <?php echo number_format($wallet['topups_today'], 2); ?></div>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="bi bi-receipt text-secondary"></i> Recent Transactions</h2>
        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th class="pe-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No payment transactions yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="ps-4 font-monospace small"><?php echo htmlspecialchars($tx['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($tx['user'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tx['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="text-success fw-semibold">RM <?php echo number_format($tx['amount'], 2); ?></td>
                                <td class="pe-4 text-muted small"><?php echo htmlspecialchars($tx['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
