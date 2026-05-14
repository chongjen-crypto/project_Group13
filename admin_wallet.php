<?php
/**
 * Scholar Hub — Admin: Wallet overview (demo data).
 */
require_once __DIR__ . '/includes/admin_auth.php';

$admin_nav_active = 'wallet';
$admin_page_title = 'Wallet Overview';

$total_wallet_demo = 48250.75;
$dummy_transactions = [
    ['id' => 'TX-9201', 'user' => 'Ahmad Zulkarnain', 'type' => 'Top-up', 'amount' => 50.00, 'date' => '2026-05-13 09:14'],
    ['id' => 'TX-9200', 'user' => 'Wei Ming', 'type' => 'Booking Payment', 'amount' => -12.50, 'date' => '2026-05-13 08:02'],
    ['id' => 'TX-9198', 'user' => 'Nur Hidayah', 'type' => 'Refund', 'amount' => 8.00, 'date' => '2026-05-12 16:41'],
    ['id' => 'TX-9195', 'user' => 'Sarah Lim', 'type' => 'Top-up', 'amount' => 100.00, 'date' => '2026-05-12 11:20'],
];
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
                    <div class="text-muted small text-uppercase fw-bold">Total Wallet Amount</div>
                    <div class="fs-4 fw-bold mt-2">RM <?php echo number_format($total_wallet_demo, 2); ?></div>
                    <small class="text-muted">Demo aggregate</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold">Recent Transactions</div>
                    <div class="fs-4 fw-bold mt-2"><?php echo count($dummy_transactions); ?> <span class="fs-6 text-muted fw-normal">(shown)</span></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold">Student Top-ups (today)</div>
                    <div class="fs-4 fw-bold mt-2 text-success">RM 1,240.00</div>
                    <small class="text-muted">Demo</small>
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
                        <?php foreach ($dummy_transactions as $tx): ?>
                        <tr>
                            <td class="ps-4 font-monospace small"><?php echo htmlspecialchars($tx['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tx['user'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tx['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td class="<?php echo $tx['amount'] >= 0 ? 'text-success' : 'text-danger'; ?> fw-semibold">
                                <?php echo ($tx['amount'] >= 0 ? '+' : '') . 'RM ' . number_format(abs($tx['amount']), 2); ?>
                            </td>
                            <td class="pe-4 text-muted small"><?php echo htmlspecialchars($tx['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-center text-muted small pb-3 mb-0">Demo data — connect wallet tables when ready.</p>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
