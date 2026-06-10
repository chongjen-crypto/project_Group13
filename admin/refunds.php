<?php
/**
 * Scholar Hub — Admin: Refund Management
 * All approved refunds are credited to the student's in-app wallet.
 */
require_once __DIR__ . '/../includes/admin_auth.php';
require __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/refund_helpers.php';

$admin_nav_active = 'refunds';
$admin_page_title = 'Refund Management';

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$flash = null;
if (!empty($_SESSION['refund_flash'])) {
    $flash = $_SESSION['refund_flash'];
    unset($_SESSION['refund_flash']);
}

$stats = refund_fetch_stats($conn);
$list = refund_fetch_list($conn, $search, $statusFilter, $page, 10);
$items = $list['items'];
$pagination = $list;

function refund_pager_items(int $current, int $total): array
{
    if ($total <= 7) {
        return range(1, max(1, $total));
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

function refund_list_url(array $params): string
{
    $base = ['q' => $params['q'] ?? '', 'status' => $params['status'] ?? '', 'page' => $params['page'] ?? 1];
    $parts = [];
    foreach ($base as $k => $v) {
        if ($v === '' || $v === null) {
            continue;
        }
        $parts[] = urlencode($k) . '=' . urlencode((string) $v);
    }
    return 'refunds.php' . ($parts !== [] ? '?' . implode('&', $parts) : '');
}

$pageItems = refund_pager_items($pagination['page'], $pagination['total_pages']);
$from = $pagination['total'] === 0 ? 0 : (($pagination['page'] - 1) * $pagination['per_page']) + 1;
$to = min($pagination['page'] * $pagination['per_page'], $pagination['total']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Refund Management — Scholar Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/../includes/admin_styles.php'; ?>
    <style>
        .refund-stat-card {
            background: #fff;
            border: 1px solid #eef0f3;
            border-radius: var(--card-radius);
            padding: 1.1rem 1.25rem;
            box-shadow: 0 4px 18px rgba(0,0,0,0.04);
            height: 100%;
        }
        .refund-stat-card .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            font-weight: 700;
        }
        .refund-stat-card .value {
            font-size: 1.65rem;
            font-weight: 800;
            margin-top: 0.35rem;
        }
        .refund-filter-bar {
            background: #fff;
            border: 1px solid #eef0f3;
            border-radius: var(--card-radius);
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        }
        .refund-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            border-top: 1px solid #eef0f3;
            background: #fafbfc;
        }
        .refund-pager-btn {
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
        }
        .refund-pager-btn:hover { background: #f3f4f6; color: #111827; }
        .refund-pager-btn.active { background: #111827; border-color: #111827; color: #fff; }
        .refund-pager-btn.disabled { opacity: 0.45; pointer-events: none; }
        .detail-grid dt { color: #6b7280; font-weight: 600; }
        .detail-grid dd { margin-bottom: 0.65rem; }
        .table-refunds th { white-space: nowrap; font-size: 0.72rem; }
        .table-refunds td { font-size: 0.875rem; vertical-align: middle; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="content-area">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            All approved refunds are credited to the student's <strong>in-app wallet</strong> (not bank or ToyyibPay reversal).
        </p>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="refund-stat-card border-warning border-opacity-25">
                    <div class="label">Pending Refunds</div>
                    <div class="value text-warning"><?php echo (int) $stats['pending']; ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="refund-stat-card border-success border-opacity-25">
                    <div class="label">Completed Refunds</div>
                    <div class="value text-success"><?php echo (int) $stats['completed']; ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="refund-stat-card border-danger border-opacity-25">
                    <div class="label">Rejected Refunds</div>
                    <div class="value text-danger"><?php echo (int) $stats['rejected']; ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="refund-stat-card border-primary border-opacity-25">
                    <div class="label">Total Refund Amount</div>
                    <div class="value text-primary">RM <?php echo number_format($stats['total_amount'], 2); ?></div>
                </div>
            </div>
        </div>

        <form method="get" action="refunds.php" class="refund-filter-bar mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="searchQ" class="form-label small fw-semibold mb-1">Search</label>
                    <input type="search" name="q" id="searchQ" class="form-control rounded-3"
                           placeholder="Student, email, booking or refund ID…"
                           value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4">
                    <label for="statusFilter" class="form-label small fw-semibold mb-1">Refund status</label>
                    <select name="status" id="statusFilter" class="form-select rounded-3">
                        <option value="">All statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark rounded-pill flex-grow-1">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="refunds.php" class="btn btn-outline-secondary rounded-pill">Reset</a>
                </div>
            </div>
        </form>

        <h2 class="section-title"><i class="bi bi-arrow-counterclockwise text-danger"></i> Refund Requests</h2>

        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0 table-refunds">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Refund ID</th>
                            <th>Booking ID</th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Facility</th>
                            <th>Court/Table</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Amount Paid</th>
                            <th>Booking Status</th>
                            <th>Refund Status</th>
                            <th>Request Date</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items === []): ?>
                        <tr>
                            <td colspan="13" class="text-center py-5 text-muted">
                                No refund requests found for cancelled or rejected paid bookings.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($items as $row): ?>
                            <?php
                            $refundStatus = (string) ($row['refund_status'] ?? 'pending');
                            $refundBadge = refund_status_badge_class($refundStatus);
                            $bookingStatus = (string) ($row['booking_status'] ?? '');
                            $isPending = $refundStatus === 'pending';
                            ?>
                            <tr>
                                <td class="ps-4 font-monospace small">#<?php echo (int) $row['refund_id']; ?></td>
                                <td class="font-monospace small">#<?php echo (int) $row['booking_id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars((string) $row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="small"><?php echo htmlspecialchars((string) $row['student_email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['facility_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="small"><?php echo htmlspecialchars((string) $row['court_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-nowrap"><?php echo htmlspecialchars((string) $row['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-nowrap small"><?php echo htmlspecialchars((string) $row['time_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="fw-semibold">RM <?php echo number_format((float) $row['refund_amount'], 2); ?></td>
                                <td><span class="badge rounded-pill text-bg-secondary"><?php echo htmlspecialchars(ucfirst($bookingStatus), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><span class="badge rounded-pill <?php echo htmlspecialchars($refundBadge, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(refund_status_label($refundStatus), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="small text-muted"><?php echo htmlspecialchars(wallet_format_datetime((string) $row['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="pe-4 text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill btn-view-refund"
                                            data-refund-id="<?php echo (int) $row['refund_id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($isPending): ?>
                                    <button type="button" class="btn btn-sm btn-success rounded-pill btn-approve-refund"
                                            data-refund-id="<?php echo (int) $row['refund_id']; ?>"
                                            data-amount="<?php echo htmlspecialchars(number_format((float) $row['refund_amount'], 2), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-student="<?php echo htmlspecialchars((string) $row['student_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Approve
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill btn-reject-refund"
                                            data-refund-id="<?php echo (int) $row['refund_id']; ?>">
                                        Reject
                                    </button>
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total'] > 0): ?>
            <div class="refund-pager">
                <div class="small text-muted">
                    <?php if ($pagination['total'] <= $pagination['per_page']): ?>
                        <?php echo (int) $pagination['total']; ?> request<?php echo $pagination['total'] === 1 ? '' : 's'; ?>
                    <?php else: ?>
                        <?php echo $from; ?>–<?php echo $to; ?> of <?php echo (int) $pagination['total']; ?>
                    <?php endif; ?>
                </div>
                <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="d-flex align-items-center gap-1">
                    <?php
                    $prev = max(1, $pagination['page'] - 1);
                    $next = min($pagination['total_pages'], $pagination['page'] + 1);
                    ?>
                    <a href="<?php echo htmlspecialchars(refund_list_url(['q' => $search, 'status' => $statusFilter, 'page' => $prev]), ENT_QUOTES, 'UTF-8'); ?>"
                       class="refund-pager-btn <?php echo $pagination['page'] <= 1 ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <?php foreach ($pageItems as $pi): ?>
                        <?php if ($pi === '…'): ?>
                            <span class="px-1 text-muted">…</span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars(refund_list_url(['q' => $search, 'status' => $statusFilter, 'page' => (int) $pi]), ENT_QUOTES, 'UTF-8'); ?>"
                               class="refund-pager-btn <?php echo (int) $pi === $pagination['page'] ? 'active' : ''; ?>">
                                <?php echo (int) $pi; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <a href="<?php echo htmlspecialchars(refund_list_url(['q' => $search, 'status' => $statusFilter, 'page' => $next]), ENT_QUOTES, 'UTF-8'); ?>"
                       class="refund-pager-btn <?php echo $pagination['page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Details modal -->
<div class="modal fade" id="refundDetailsModal" tabindex="-1" aria-labelledby="refundDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="refundDetailsModalLabel">Refund Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="refundDetailsBody">
                <div class="text-center py-4 text-muted">Loading…</div>
            </div>
        </div>
    </div>
</div>

<!-- Approve confirmation -->
<div class="modal fade" id="approveRefundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-1"></i> Approve Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Credit refund to the student's in-app wallet?</p>
                <div class="bg-light rounded-3 p-3 small">
                    <div><strong>Student:</strong> <span id="approveStudentName">—</span></div>
                    <div><strong>Amount:</strong> RM <span id="approveAmount">0.00</span></div>
                </div>
                <p class="small text-muted mt-3 mb-0">This does not reverse ToyyibPay or bank payments. The amount is added to wallet balance only.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="refund_process.php" class="m-0">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="refund_id" id="approveRefundId" value="">
                    <button type="submit" class="btn btn-success rounded-pill px-4">Confirm Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reject modal -->
<div class="modal fade" id="rejectRefundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-1"></i> Reject Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="refund_process.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="refund_id" id="rejectRefundId" value="">
                    <label for="rejectRemarks" class="form-label fw-semibold">Admin remarks <span class="text-danger">*</span></label>
                    <textarea name="admin_remarks" id="rejectRemarks" class="form-control rounded-3" rows="3" required
                              placeholder="Reason for rejecting this refund request…"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Reject Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_scripts.php'; ?>
<script>
(function () {
    'use strict';

    var detailsModal = document.getElementById('refundDetailsModal');
    var detailsBody = document.getElementById('refundDetailsBody');
    var approveModal = document.getElementById('approveRefundModal');
    var rejectModal = document.getElementById('rejectRefundModal');

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function renderDetails(r) {
        return '<div class="row g-4 detail-grid">' +
            '<div class="col-md-6"><h6 class="fw-bold mb-3">Student Information</h6>' +
            '<dl class="row mb-0 small"><dt class="col-5">Name</dt><dd class="col-7">' + escapeHtml(r.student_name) + '</dd>' +
            '<dt class="col-5">Email</dt><dd class="col-7">' + escapeHtml(r.student_email) + '</dd>' +
            '<dt class="col-5">Phone</dt><dd class="col-7">' + escapeHtml(r.student_phone || '—') + '</dd>' +
            '<dt class="col-5">Wallet balance</dt><dd class="col-7 fw-semibold">' + escapeHtml(r.wallet_balance) + '</dd></dl></div>' +
            '<div class="col-md-6"><h6 class="fw-bold mb-3">Facility Information</h6>' +
            '<dl class="row mb-0 small"><dt class="col-5">Facility</dt><dd class="col-7">' + escapeHtml(r.facility_name) + '</dd>' +
            '<dt class="col-5">Court/Table</dt><dd class="col-7">' + escapeHtml(r.court_label) + '</dd></dl></div>' +
            '<div class="col-md-6"><h6 class="fw-bold mb-3">Booking Information</h6>' +
            '<dl class="row mb-0 small"><dt class="col-5">Booking ID</dt><dd class="col-7">#' + escapeHtml(r.booking_id) + '</dd>' +
            '<dt class="col-5">Date</dt><dd class="col-7">' + escapeHtml(r.booking_date) + '</dd>' +
            '<dt class="col-5">Time</dt><dd class="col-7">' + escapeHtml(r.time_label) + '</dd>' +
            '<dt class="col-5">Status</dt><dd class="col-7">' + escapeHtml(r.booking_status) + '</dd>' +
            '<dt class="col-5">Purpose</dt><dd class="col-7">' + escapeHtml(r.purpose || '—') + '</dd></dl></div>' +
            '<div class="col-md-6"><h6 class="fw-bold mb-3">Payment &amp; Refund</h6>' +
            '<dl class="row mb-0 small"><dt class="col-5">Payment method</dt><dd class="col-7">' + escapeHtml(r.payment_method) + '</dd>' +
            '<dt class="col-5">Payment status</dt><dd class="col-7">' + escapeHtml(r.payment_status) + '</dd>' +
            '<dt class="col-5">Refund amount</dt><dd class="col-7 fw-semibold text-success">' + escapeHtml(r.refund_amount) + '</dd>' +
            '<dt class="col-5">Refund reason</dt><dd class="col-7">' + escapeHtml(r.refund_reason) + '</dd>' +
            '<dt class="col-5">Refund status</dt><dd class="col-7">' + escapeHtml(r.refund_status) + '</dd>' +
            (r.admin_remarks ? '<dt class="col-5">Admin remarks</dt><dd class="col-7">' + escapeHtml(r.admin_remarks) + '</dd>' : '') +
            (r.approved_by_name ? '<dt class="col-5">Processed by</dt><dd class="col-7">' + escapeHtml(r.approved_by_name) + '</dd>' : '') +
            '</dl></div></div>';
    }

    document.querySelectorAll('.btn-view-refund').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-refund-id');
            if (!detailsBody || !detailsModal) return;
            detailsBody.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading…</div>';
            bootstrap.Modal.getOrCreateInstance(detailsModal).show();
            fetch('refund_details.php?id=' + encodeURIComponent(id))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        detailsBody.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(data.message || 'Error') + '</div>';
                        return;
                    }
                    detailsBody.innerHTML = renderDetails(data.refund);
                })
                .catch(function () {
                    detailsBody.innerHTML = '<div class="alert alert-danger mb-0">Could not load refund details.</div>';
                });
        });
    });

    document.querySelectorAll('.btn-approve-refund').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('approveRefundId').value = btn.getAttribute('data-refund-id');
            document.getElementById('approveStudentName').textContent = btn.getAttribute('data-student') || '—';
            document.getElementById('approveAmount').textContent = btn.getAttribute('data-amount') || '0.00';
            bootstrap.Modal.getOrCreateInstance(approveModal).show();
        });
    });

    document.querySelectorAll('.btn-reject-refund').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('rejectRefundId').value = btn.getAttribute('data-refund-id');
            document.getElementById('rejectRemarks').value = '';
            bootstrap.Modal.getOrCreateInstance(rejectModal).show();
        });
    });
})();
</script>
</body>
</html>
