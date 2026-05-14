<?php
/**
 * Scholar Hub — Admin: User lists (students & staff) from database + promote student → staff.
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';

$admin_nav_active = 'users';
$admin_page_title = 'User Management';

$promote_ok = isset($_GET['promoted']) && $_GET['promoted'] === '1';
$promote_error = '';

// ---- POST: promote selected student to staff (then redirect to avoid resubmit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'promote_staff') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    if ($student_id <= 0) {
        $promote_error = 'Please select a student.';
    } else {
        $role_staff = 'staff';
        $role_student = 'student';
        $sql = 'UPDATE users SET role = ? WHERE id = ? AND role = ? LIMIT 1';
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $promote_error = 'Database error. Please try again.';
        } else {
            mysqli_stmt_bind_param($stmt, 'sis', $role_staff, $student_id, $role_student);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) === 1) {
                mysqli_stmt_close($stmt);
                header('Location: admin_users.php?promoted=1');
                exit();
            }
            $promote_error = 'Could not promote this account (not found or not a student).';
            mysqli_stmt_close($stmt);
        }
    }
}

// ---- Load lists from database ----
$student_rows = [];
$staff_rows = [];
$db_list_error = '';

$sql_students = "SELECT id, full_name, email FROM users WHERE role = 'student' ORDER BY full_name ASC";
$sql_staff = "SELECT id, full_name, email FROM users WHERE role = 'staff' ORDER BY full_name ASC";

$res = mysqli_query($conn, $sql_students);
if ($res === false) {
    $db_list_error = 'Could not load students: ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8');
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        $student_rows[] = [
            'id' => (int) $row['id'],
            'name' => $row['full_name'],
            'email' => $row['email'],
        ];
    }
    mysqli_free_result($res);
}

$res = mysqli_query($conn, $sql_staff);
if ($res === false) {
    $db_list_error = ($db_list_error !== '' ? $db_list_error . ' ' : '') . 'Could not load staff: ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8');
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        $staff_rows[] = [
            'id' => (int) $row['id'],
            'name' => $row['full_name'],
            'email' => $row['email'],
        ];
    }
    mysqli_free_result($res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>User Management — Scholar Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <?php include __DIR__ . '/includes/admin_header.php'; ?>

    <main class="content-area">

        <?php if ($db_list_error !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo $db_list_error; ?></div>
        <?php endif; ?>

        <?php if ($promote_ok): ?>
            <div class="alert alert-success py-2">Student was promoted to <strong>staff</strong> successfully.</div>
        <?php endif; ?>
        <?php if ($promote_error !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($promote_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <h2 class="section-title"><i class="bi bi-mortarboard text-primary"></i> Students</h2>
        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th class="pe-4">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($student_rows === []): ?>
                        <tr>
                            <td colspan="2" class="ps-4 pe-4 text-muted text-center py-4">No student accounts yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($student_rows as $row): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="pe-4 text-muted"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="section-title mb-0 flex-grow-1"><i class="bi bi-person-badge text-secondary"></i> Staff</h2>
            <button
                type="button"
                class="btn btn-dark rounded-pill px-3"
                data-bs-toggle="modal"
                data-bs-target="#addStaffModal"
                <?php echo $student_rows === [] ? 'disabled title="No students to promote"' : ''; ?>
            >
                <i class="bi bi-person-plus me-1"></i> Add staff
            </button>
        </div>
        <p class="small text-muted mb-3">Promote an existing <strong>student</strong> account to <strong>staff</strong> (same email and password; they should log in again).</p>

        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th class="pe-4">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($staff_rows === []): ?>
                        <tr>
                            <td colspan="2" class="ps-4 pe-4 text-muted text-center py-4">No staff accounts yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($staff_rows as $row): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="pe-4 text-muted"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-center text-muted small pb-3 mb-0">Lists load from the <code>users</code> table (<code>role</code> = student / staff).</p>
    </main>
</div>

<!-- Modal: choose student → staff -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="addStaffModalLabel"><i class="bi bi-person-plus text-dark me-2"></i>Add staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="admin_users.php">
                <input type="hidden" name="action" value="promote_staff">
                <div class="modal-body pt-2">
                    <label for="student_id" class="form-label">Choose a student to promote</label>
                    <select name="student_id" id="student_id" class="form-select rounded-3" required>
                        <option value="" disabled selected>Select student…</option>
                        <?php foreach ($student_rows as $row): ?>
                        <option value="<?php echo (int) $row['id']; ?>">
                            <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Promote to staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
