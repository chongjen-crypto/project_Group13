<?php
/**
 * Scholar Hub — Admin: Booking reports & analytics (database).
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_stats.php';

$admin_nav_active = 'reports';
$admin_page_title = 'Booking Reports';

$view = isset($_GET['view']) && $_GET['view'] === 'month' ? 'month' : 'overall';
$monthYm = isset($_GET['month']) ? trim((string) $_GET['month']) : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
    $monthYm = date('Y-m');
}

$reports = stats_booking_reports($conn);
$monthlyTrend = stats_booking_monthly_trend($conn);
$monthOptions = stats_booking_report_months($conn);
$facilityAnalytics = stats_facility_booking_breakdown($conn, $view, $monthYm);

$grandTotal = 0;
$grandApproved = 0;
$grandEarnings = 0.0;
foreach ($facilityAnalytics as $row) {
    $grandTotal += (int) $row['total_bookings'];
    $grandApproved += (int) $row['approved_bookings'];
    $grandEarnings += (float) ($row['earnings'] ?? 0);
}

$selectedMonthLabel = $monthYm;
foreach ($monthOptions as $opt) {
    if ($opt['value'] === $monthYm) {
        $selectedMonthLabel = $opt['label'];
        break;
    }
}

$facilityViewLabel = $view === 'month' ? $selectedMonthLabel : 'Overall';
$chartLabelsJson = json_encode(array_column($monthlyTrend['months'], 'month'));
$chartValuesJson = json_encode(array_column($monthlyTrend['months'], 'value'));
$pdfExportJson = json_encode([
    'generated' => date('M j, Y g:i A'),
    'facilityView' => $facilityViewLabel,
    'mostFacility' => $reports['most_facility'],
    'mostCount' => (int) $reports['most_count'],
    'bookingsToday' => (int) $reports['bookings_today'],
    'pending' => (int) $reports['pending'],
    'facilities' => $facilityAnalytics,
    'grandTotal' => $grandTotal,
    'grandApproved' => $grandApproved,
    'grandEarnings' => $grandEarnings,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
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
    <style>
        .report-filter-bar {
            background: #fff;
            border: 1px solid #eef0f3;
            border-radius: var(--card-radius);
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        }
        .report-filter-btn {
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            border-radius: 999px;
            padding: 0.45rem 1.1rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .report-filter-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #111827;
        }
        .report-filter-btn.active {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }
        .facility-analytics-table tfoot td {
            background: #f9fafb;
            border-top: 2px solid #e5e7eb;
        }
        .trend-chart-card {
            background: #fff;
            border: 1px solid #eef0f3;
            border-radius: var(--card-radius);
            padding: 1.25rem 1.5rem 1rem;
            box-shadow: 0 4px 18px rgba(0,0,0,0.05);
        }
        .trend-chart-wrap {
            position: relative;
            height: 320px;
        }
        .btn-export-pdf {
            border: none;
            background: #111827;
            color: #fff;
            border-radius: 999px;
            padding: 0.5rem 1.15rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background 0.15s, transform 0.15s;
        }
        .btn-export-pdf:hover {
            background: #1f2937;
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-export-pdf:disabled {
            opacity: 0.7;
            transform: none;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div></div>
            <button type="button" class="btn-export-pdf" id="btnExportPdf">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export to PDF
            </button>
        </div>

        <div id="reportExportArea">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="analytics-card">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Most Booked Facility</div>
                        <div class="fs-5 fw-bold"><?php echo htmlspecialchars($reports['most_facility'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <p class="text-muted small mb-0 mt-2"><i class="bi bi-trophy text-warning"></i> <?php echo (int) $reports['most_count']; ?> booking(s)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="analytics-card">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Total Bookings Today</div>
                        <div class="fs-5 fw-bold text-primary"><?php echo (int) $reports['bookings_today']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="analytics-card">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Pending Approvals</div>
                        <div class="fs-5 fw-bold text-warning"><?php echo (int) $reports['pending']; ?></div>
                        <p class="text-muted small mb-0 mt-2">Awaiting staff review</p>
                    </div>
                </div>
            </div>

            <div class="trend-chart-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1"><i class="bi bi-bar-chart-line text-primary me-1"></i> Monthly Booking Trend</h2>
                        <p class="small text-muted mb-0">Total bookings recorded each month</p>
                    </div>
                </div>
                <div class="trend-chart-wrap">
                    <canvas id="monthlyTrendChart" aria-label="Monthly booking trend bar chart"></canvas>
                </div>
            </div>

            <form method="get" action="admin_booking_reports.php" class="report-filter-bar mb-4" id="reportFilterForm">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold mb-1">Facility analytics view</div>
                        <div class="small text-muted">Filter totals for all facilities</div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <input type="hidden" name="view" id="viewInput" value="<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button"
                                class="report-filter-btn <?php echo $view === 'overall' ? 'active' : ''; ?>"
                                data-view="overall">
                            Overall
                        </button>
                        <button type="button"
                                class="report-filter-btn <?php echo $view === 'month' ? 'active' : ''; ?>"
                                data-view="month">
                            By Month
                        </button>
                        <select name="month"
                                id="monthSelect"
                                class="form-select form-select-sm rounded-pill"
                                style="width: auto; min-width: 10rem; <?php echo $view === 'month' ? '' : 'display:none;'; ?>">
                            <?php foreach ($monthOptions as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt['value'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $opt['value'] === $monthYm ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <h2 class="section-title">
                <i class="bi bi-building text-info"></i>
                All Facilities
                <?php if ($view === 'month'): ?>
                    <span class="text-muted fw-normal fs-6">— <?php echo htmlspecialchars($selectedMonthLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="text-muted fw-normal fs-6">— Overall</span>
                <?php endif; ?>
            </h2>

            <div class="table-wrap mb-4">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0 facility-analytics-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Facility</th>
                                <th class="text-center">Total Bookings</th>
                                <th class="text-center">Approved Bookings</th>
                                <th class="text-end pe-4">Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facilityAnalytics as $row): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['facility_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center"><?php echo (int) $row['total_bookings']; ?></td>
                                <td class="text-center text-success fw-semibold"><?php echo (int) $row['approved_bookings']; ?></td>
                                <td class="text-end pe-4 fw-semibold">RM <?php echo number_format((float) ($row['earnings'] ?? 0), 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="ps-4 fw-bold">All facilities</td>
                                <td class="text-center fw-bold"><?php echo $grandTotal; ?></td>
                                <td class="text-center fw-bold text-success"><?php echo $grandApproved; ?></td>
                                <td class="text-end pe-4 fw-bold">RM <?php echo number_format($grandEarnings, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.4/dist/jspdf.plugin.autotable.min.js"></script>
<script>
(function () {
    'use strict';

    var chartLabels = <?php echo $chartLabelsJson; ?>;
    var chartValues = <?php echo $chartValuesJson; ?>;
    var trendChart = null;

    var canvas = document.getElementById('monthlyTrendChart');
    if (canvas && typeof Chart !== 'undefined') {
        trendChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Bookings',
                    data: chartValues,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.parsed.y + ' booking' + (ctx.parsed.y === 1 ? '' : 's');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 24
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: { display: true, text: 'Bookings' }
                    }
                }
            }
        });
    }

    var form = document.getElementById('reportFilterForm');
    var viewInput = document.getElementById('viewInput');
    var monthSelect = document.getElementById('monthSelect');
    var buttons = document.querySelectorAll('.report-filter-btn[data-view]');

    function setView(view) {
        if (!viewInput) return;
        viewInput.value = view;
        buttons.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-view') === view);
        });
        if (monthSelect) {
            monthSelect.style.display = view === 'month' ? '' : 'none';
        }
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setView(btn.getAttribute('data-view'));
            if (form) form.submit();
        });
    });

    if (monthSelect) {
        monthSelect.addEventListener('change', function () {
            if (viewInput) viewInput.value = 'month';
            if (form) form.submit();
        });
    }

    var pdfExportData = <?php echo $pdfExportJson; ?>;

    function buildBookingReportPdf() {
        if (!window.jspdf || !window.jspdf.jsPDF) {
            throw new Error('PDF library not loaded');
        }
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        var pageWidth = doc.internal.pageSize.getWidth();
        var margin = 14;
        var y = 16;

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.text('Scholar Hub — Booking Reports', margin, y);

        y += 8;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor(90, 90, 90);
        doc.text(
            'Generated: ' + pdfExportData.generated + '   |   Facility view: ' + pdfExportData.facilityView,
            margin,
            y
        );

        y += 10;
        doc.setTextColor(0, 0, 0);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.text('Monthly Booking Trend', margin, y);
        y += 4;

        if (trendChart && canvas) {
            // Composite chart onto a white background so the PDF image isn't transparent/black.
            var srcCanvas = canvas;
            var whiteCanvas = document.createElement('canvas');
            whiteCanvas.width = srcCanvas.width;
            whiteCanvas.height = srcCanvas.height;
            var ctx = whiteCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, whiteCanvas.width, whiteCanvas.height);
            ctx.drawImage(srcCanvas, 0, 0);

            var chartImg = whiteCanvas.toDataURL('image/jpeg', 0.95);
            var chartWidth = pageWidth - (margin * 2);
            var chartHeight = 58;
            doc.addImage(chartImg, 'JPEG', margin, y, chartWidth, chartHeight);
            y += chartHeight + 8;
        } else {
            y += 4;
        }

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.text('All Facilities — ' + pdfExportData.facilityView, margin, y);
        y += 4;

        function formatRm(value) {
            var num = Number(value) || 0;
            return 'RM ' + num.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        var tableBody = (pdfExportData.facilities || []).map(function (row) {
            return [
                String(row.facility_name || ''),
                String(row.total_bookings != null ? row.total_bookings : 0),
                String(row.approved_bookings != null ? row.approved_bookings : 0),
                formatRm(row.earnings)
            ];
        });

        doc.autoTable({
            startY: y + 2,
            head: [['Facility', 'Total Bookings', 'Approved Bookings', 'Earnings']],
            body: tableBody,
            foot: [[
                'All facilities',
                String(pdfExportData.grandTotal),
                String(pdfExportData.grandApproved),
                formatRm(pdfExportData.grandEarnings)
            ]],
            showFoot: 'lastPage',
            theme: 'grid',
            styles: { fontSize: 9, cellPadding: 2.5 },
            headStyles: { fillColor: [17, 24, 39], textColor: 255 },
            footStyles: { fillColor: [249, 250, 251], textColor: [17, 24, 39], fontStyle: 'bold' },
            columnStyles: { 3: { halign: 'right' } },
            margin: { left: margin, right: margin }
        });

        doc.save('booking-reports-' + new Date().toISOString().slice(0, 10) + '.pdf');
    }

    var btnExport = document.getElementById('btnExportPdf');
    if (btnExport) {
        btnExport.addEventListener('click', function () {
            btnExport.disabled = true;
            var originalText = btnExport.innerHTML;
            btnExport.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';

            try {
                buildBookingReportPdf();
            } catch (err) {
                console.error(err);
                alert('Could not generate PDF. Please check your internet connection and try again.');
            }

            btnExport.disabled = false;
            btnExport.innerHTML = originalText;
        });
    }
})();
</script>
</body>
</html>
