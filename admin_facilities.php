<?php
/**
 * Scholar Hub — Admin: Facility management (database).
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/facility_admin_helpers.php';
require_once __DIR__ . '/includes/notification_helpers.php';

$admin_nav_active = 'facilities';
$admin_page_title = 'Facility Management';

/** Safe value for HTML data-* attributes (avoids PHP 8 null warnings breaking markup). */
function admin_facility_attr($value): string
{
    $s = str_replace(["\r", "\n", "\t"], ' ', (string) ($value ?? ''));
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$success_msg = '';
$error_msg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $success_msg = 'Facility updated successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_facility') {
        $facility_id = (int) ($_POST['facility_id'] ?? 0);
        $before = facility_fetch_one($conn, $facility_id);
        $newUiStatus = (string) ($_POST['ui_status'] ?? 'available');
        $result = facility_update($conn, $facility_id, [
            'facility_name' => $_POST['facility_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'location' => $_POST['location'] ?? '',
            'opening_time' => $_POST['opening_time'] ?? '',
            'closing_time' => $_POST['closing_time'] ?? '',
            'image' => $_POST['image'] ?? '',
            'ui_status' => $newUiStatus,
            'price_amount' => $_POST['price_amount'] ?? 0,
            'price_mode' => $_POST['price_mode'] ?? 'hourly',
            'rules' => $_POST['rules'] ?? '',
        ]);
        if ($result['success']) {
            $prevStatus = is_array($before) ? (string) ($before['status'] ?? '') : '';
            $facilityName = trim((string) ($_POST['facility_name'] ?? 'This facility'));
            notifications_facility_status_changed($conn, $prevStatus, $newUiStatus, $facilityName);
            header('Location: admin_facilities.php?msg=saved');
            exit();
        }
        $error_msg = $result['message'];
    } elseif ($action === 'toggle_unavailable') {
        $facility_id = (int) ($_POST['facility_id'] ?? 0);
        $row = facility_fetch_one($conn, $facility_id);
        if ($row) {
            $prevStatus = (string) ($row['status'] ?? '');
            $newUi = $prevStatus === 'active' ? 'unavailable' : 'available';
            $result = facility_update($conn, $facility_id, [
                'facility_name' => $row['facility_name'],
                'description' => $row['description'] ?? '',
                'location' => $row['location'] ?? '',
                'opening_time' => substr((string) ($row['opening_time'] ?? '08:00:00'), 0, 5),
                'closing_time' => substr((string) ($row['closing_time'] ?? '22:00:00'), 0, 5),
                'image' => $row['image'] ?? '',
                'ui_status' => $newUi,
                'price_amount' => $row['price_amount'] ?? 0,
                'price_mode' => $row['price_mode'] ?? 'hourly',
                'rules' => $row['rules'] ?? '',
            ]);
            if ($result['success']) {
                $facilityName = trim((string) ($row['facility_name'] ?? 'This facility'));
                notifications_facility_status_changed($conn, $prevStatus, $newUi, $facilityName);
                header('Location: admin_facilities.php?msg=saved');
                exit();
            }
            $error_msg = $result['message'];
        } else {
            $error_msg = 'Facility not found.';
        }
    }
}

$admin_all_facilities = facilities_fetch_admin_list($conn);
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

        <?php if ($success_msg !== ''): ?>
            <div class="alert alert-success py-2"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error_msg !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (empty($admin_all_facilities)): ?>
            <div class="table-wrap p-5 text-center text-muted">No facilities in the database yet.</div>
        <?php else: ?>
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
                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($f['location'] !== '' ? $f['location'] : '—', ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                        <p class="small text-muted mb-1">
                            <i class="bi bi-calendar-day me-1"></i>Bookings today: <strong><?php echo htmlspecialchars($f['bookings_today'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-graph-up me-1"></i>Total bookings: <strong><?php echo number_format((int) $f['total_bookings']); ?></strong>
                        </p>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-clock me-1"></i>Hours: <strong><?php echo htmlspecialchars($f['opening_time'] . ' – ' . $f['closing_time'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <p class="small text-muted mb-3">
                            <i class="bi bi-tag me-1"></i>Price: <strong>RM <?php echo number_format((float) $f['price_amount'], 2); ?></strong>
                            <span class="text-muted">(<?php echo htmlspecialchars($f['price_label'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </p>
                        <script type="application/json" class="facility-edit-json" data-facility-id="<?php echo (int) $f['facility_id']; ?>"><?php
                            echo json_encode(
                                facility_admin_edit_payload($f),
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
                            );
                        ?></script>
                        <div class="d-flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill btn-edit-facility"
                                data-bs-toggle="modal"
                                data-bs-target="#editFacilityModal"
                                data-id="<?php echo (int) $f['facility_id']; ?>"
                            >
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="toggle_unavailable">
                                <input type="hidden" name="facility_id" value="<?php echo (int) $f['facility_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill">
                                    <?php echo $f['ui_status'] === 'available' ? 'Set Unavailable' : 'Set Available'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <p class="text-center text-muted small pb-3 mb-0"><?php echo count($admin_all_facilities); ?> facilities — changes sync to the student portal immediately.</p>
    </main>
</div>

<div class="modal fade" id="editFacilityModal" tabindex="-1" aria-labelledby="editFacilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editFacilityModalLabel"><i class="bi bi-pencil me-2"></i>Edit Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="admin_facilities.php">
                <input type="hidden" name="action" value="update_facility">
                <input type="hidden" name="facility_id" id="edit_facility_id" value="">
                <div class="modal-body pt-2">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_facility_name" class="form-label fw-semibold">Facility name</label>
                            <input type="text" name="facility_name" id="edit_facility_name" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_ui_status" class="form-label fw-semibold">Status</label>
                            <select name="ui_status" id="edit_ui_status" class="form-select rounded-3" required>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control rounded-3" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_location" class="form-label fw-semibold">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_image" class="form-label fw-semibold">Image path</label>
                            <input type="text" name="image" id="edit_image" class="form-control rounded-3" placeholder="assets/example.jpg">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_opening_time" class="form-label fw-semibold">Opening time</label>
                            <input type="time" name="opening_time" id="edit_opening_time" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_closing_time" class="form-label fw-semibold">Closing time</label>
                            <input type="time" name="closing_time" id="edit_closing_time" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_price_amount" class="form-label fw-semibold">Price (RM)</label>
                            <input type="number" name="price_amount" id="edit_price_amount" class="form-control rounded-3" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_price_mode" class="form-label fw-semibold">Price type</label>
                            <select name="price_mode" id="edit_price_mode" class="form-select rounded-3" required>
                                <option value="hourly">Per hour</option>
                                <option value="entry">Per entry</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_rules" class="form-label fw-semibold">Rules &amp; guidelines</label>
                            <textarea
                                name="rules"
                                id="edit_rules"
                                class="form-control rounded-3"
                                rows="5"
                                placeholder="One rule per line (shown to students on the facility page)"
                            ></textarea>
                            <div class="form-text">Enter each rule on a new line.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
<script>
(function () {
    document.querySelectorAll('.btn-edit-facility').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id') || '';
            var payload = null;
            var jsonEl = document.querySelector('script.facility-edit-json[data-facility-id="' + id + '"]');
            if (jsonEl) {
                try {
                    payload = JSON.parse(jsonEl.textContent);
                } catch (e) {
                    payload = null;
                }
            }
            if (!payload) {
                return;
            }
            document.getElementById('edit_facility_id').value = payload.id || '';
            document.getElementById('edit_facility_name').value = payload.name || '';
            document.getElementById('edit_description').value = payload.description || '';
            document.getElementById('edit_location').value = payload.location || '';
            document.getElementById('edit_opening_time').value = payload.opening || '';
            document.getElementById('edit_closing_time').value = payload.closing || '';
            document.getElementById('edit_image').value = payload.image || '';
            document.getElementById('edit_ui_status').value = payload.status || 'available';
            document.getElementById('edit_price_amount').value = payload.price_amount != null ? payload.price_amount : '';
            document.getElementById('edit_price_mode').value = payload.price_mode || 'hourly';
            document.getElementById('edit_rules').value = payload.rules_text || '';
        });
    });
})();
</script>
</body>
</html>
