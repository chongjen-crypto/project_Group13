<?php
/**
 * Scholar Hub — Admin: Wallet overview (from paid bookings).
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';

$admin_nav_active = 'wallet';
$admin_page_title = 'Wallet Overview';

$page = max(1, (int) ($_GET['page'] ?? 1));
$wallet = stats_wallet_overview($conn, $page, 10);
$transactions = $wallet['transactions'];
$pagination = $wallet['pagination'];

/**
 * Build compact page numbers for Gmail-style pager (e.g. 1 … 4 5 6 … 12).
 * @return list<int|string>
 */
function admin_wallet_page_items(int $current, int $total): array
{
    if ($total <= 7) {
        return range(1, $total);
    }
    $items = [1];
    $start = max(2, $current - 1);
    $end = min($total - 1, $current + 1);
    if ($start > 2) {
        $items[] = '…';
    }
    for ($i = $start; $i <= $end; $i++) {
        $items[] = $i;
    }
    if ($end < $total - 1) {
        $items[] = '…';
    }
    $items[] = $total;
    return $items;
}

$pageItems = admin_wallet_page_items($pagination['page'], $pagination['total_pages']);
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
    <style>
        .wallet-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            padding: 0.85rem 1.25rem;
            border-top: 1px solid #eef0f3;
            background: #fafbfc;
        }
        .wallet-pager-info {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .wallet-pager-nav {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .wallet-pager-btn {
            min-width: 2.25rem;
            height: 2.25rem;
            padding: 0 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .wallet-pager-btn:hover:not(.disabled):not(.active) {
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #111827;
        }
        .wallet-pager-btn.active {
            background: #111827;
            border-color: #111827;
            color: #fff;
            pointer-events: none;
        }
        .wallet-pager-btn.disabled {
            opacity: 0.45;
            pointer-events: none;
        }
        .wallet-pager-ellipsis {
            min-width: 1.5rem;
            text-align: center;
            color: #9ca3af;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">
        <h2 class="section-title"><i class="bi bi-wallet2 text-success"></i> Wallet Summary</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="analytics-card border border-primary border-opacity-25">
                    <div class="text-muted small text-uppercase fw-bold">Total Paid (All Time)</div>
                    <div class="fs-4 fw-bold mt-2">RM <?php echo number_format($wallet['total_income'], 2); ?></div>
                    <small class="text-muted">From completed booking payments</small>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="analytics-card border border-success border-opacity-25">
                    <div class="text-muted small text-uppercase fw-bold">This Month Paid</div>
                    <div class="fs-4 fw-bold mt-2 text-success">RM <?php echo number_format($wallet['paid_this_month'], 2); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars(date('F Y'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="analytics-card">
                    <div class="text-muted small text-uppercase fw-bold">Paid Transactions</div>
                    <div class="fs-4 fw-bold mt-2"><?php echo (int) $wallet['paid_count']; ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
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
                            <th>Facility / Court</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th class="pe-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No payment transactions yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="ps-4 font-monospace small"><?php echo htmlspecialchars($tx['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($tx['user'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="small"><?php echo htmlspecialchars($tx['facility_court'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tx['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="text-success fw-semibold">RM <?php echo number_format($tx['amount'], 2); ?></td>
                                <td class="pe-4 text-muted small"><?php echo htmlspecialchars($tx['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total'] > 0): ?>
            <div class="wallet-pager">
                <div class="wallet-pager-info">
                    <?php if ($pagination['total'] <= $pagination['per_page']): ?>
                        <?php echo (int) $pagination['total']; ?> transaction<?php echo $pagination['total'] === 1 ? '' : 's'; ?>
                    <?php else: ?>
                        <?php echo (int) $pagination['from']; ?>–<?php echo (int) $pagination['to']; ?>
                        of <?php echo (int) $pagination['total']; ?>
                    <?php endif; ?>
                </div>
                <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="wallet-pager-nav" aria-label="Transaction pages">
                    <?php
                    $prevPage = $pagination['page'] - 1;
                    $nextPage = $pagination['page'] + 1;
                    ?>
                    <a href="admin_wallet.php?page=<?php echo $prevPage; ?>"
                       class="wallet-pager-btn <?php echo $pagination['page'] <= 1 ? 'disabled' : ''; ?>"
                       aria-label="Previous page"
                       <?php echo $pagination['page'] <= 1 ? 'tabindex="-1"' : ''; ?>>
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <?php foreach ($pageItems as $item): ?>
                        <?php if ($item === '…'): ?>
                            <span class="wallet-pager-ellipsis">…</span>
                        <?php else: ?>
                            <a href="admin_wallet.php?page=<?php echo (int) $item; ?>"
                               class="wallet-pager-btn <?php echo (int) $item === $pagination['page'] ? 'active' : ''; ?>"
                               <?php echo (int) $item === $pagination['page'] ? 'aria-current="page"' : ''; ?>>
                                <?php echo (int) $item; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <a href="admin_wallet.php?page=<?php echo $nextPage; ?>"
                       class="wallet-pager-btn <?php echo $pagination['page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>"
                       aria-label="Next page"
                       <?php echo $pagination['page'] >= $pagination['total_pages'] ? 'tabindex="-1"' : ''; ?>>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
