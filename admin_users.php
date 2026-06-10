<?php
/**
 * Scholar Hub — Admin: User lists (students & staff) from database + promote/demote/remove.
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/db.php';

require_once __DIR__ . '/includes/staff_registration_config.php';

$admin_nav_active = 'users';
$admin_page_title = 'User Management';

$success_msg = '';
$error_msg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'promoted') $success_msg = 'Student was promoted to staff successfully.';
    if ($_GET['msg'] === 'demoted') $success_msg = 'Staff was demoted to student successfully.';
    if ($_GET['msg'] === 'removed') $success_msg = 'User account was removed successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'promote_staff') {
        $student_id = (int) ($_POST['student_id'] ?? 0);
        if ($student_id <= 0) {
            $error_msg = 'Please select a student.';
        } else {
            $sql = "UPDATE users SET role = 'staff' WHERE id = ? AND role = 'student' LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $student_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) === 1) {
                    header('Location: admin_users.php?msg=promoted');
                    exit();
                } else {
                    $error_msg = 'Could not promote this account.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'demote_staff') {
        $staff_id = (int) ($_POST['staff_id'] ?? 0);
        if ($staff_id <= 0) {
            $error_msg = 'Please select a staff member.';
        } else {
            $sql = "UPDATE users SET role = 'student' WHERE id = ? AND role = 'staff' LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $staff_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) === 1) {
                    header('Location: admin_users.php?msg=demoted');
                    exit();
                } else {
                    $error_msg = 'Could not demote this account.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'remove_user') {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            $error_msg = 'Please select a user to remove.';
        } elseif ($user_id === (int) ($_SESSION['user_id'] ?? 0)) {
            $error_msg = 'You cannot remove your own account.';
        } else {
            $sql = "DELETE FROM users WHERE id = ? AND role IN ('student', 'staff') LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) === 1) {
                    header('Location: admin_users.php?msg=removed');
                    exit();
                }
                $error_msg = 'Could not remove this user. They may have linked records or are an admin account.';
                mysqli_stmt_close($stmt);
            }
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
        $student_rows[] = $row;
    }
}

$res = mysqli_query($conn, $sql_staff);
if ($res === false) {
    $db_list_error .= ' Could not load staff.';
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        $staff_rows[] = $row;
    }
}

$removable_users = [];
foreach ($student_rows as $row) {
    $removable_users[] = [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'role' => 'student',
    ];
}
foreach ($staff_rows as $row) {
    $removable_users[] = [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'role' => 'staff',
    ];
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

        <?php if ($success_msg !== ''): ?>
            <div class="alert alert-success py-2"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error_msg !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <h2 class="section-title"><i class="bi bi-mortarboard text-primary"></i> Students</h2>
        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Email</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($student_rows === []): ?>
                        <tr>
                            <td colspan="3" class="ps-4 pe-4 text-muted text-center py-4">No student accounts yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($student_rows as $row): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="pe-4 text-end">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove student?');">
                                        <input type="hidden" name="action" value="remove_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="section-title mb-0 flex-grow-1"><i class="bi bi-person-badge text-secondary"></i> Staff</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#demoteStaffModal" <?php echo empty($staff_rows) ? 'disabled' : ''; ?>>
                    <i class="bi bi-person-dash me-1"></i> Demote staff
                </button>
                <button type="button" class="btn btn-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addStaffModal" <?php echo empty($student_rows) ? 'disabled' : ''; ?>>
                    <i class="bi bi-person-plus me-1"></i> Add staff
                </button>
            </div>
        </div>
        <p class="small text-muted mb-3">Promote an existing <strong>student</strong> account to <strong>staff</strong>, or demote back to student.</p>

        <div class="table-wrap mb-4">
            <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Email</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($staff_rows === []): ?>
                        <tr>
                            <td colspan="3" class="ps-4 pe-4 text-muted text-center py-4">No staff accounts yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($staff_rows as $row): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="pe-4 text-end">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove staff member?');">
                                        <input type="hidden" name="action" value="remove_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="analytics-card border border-warning border-opacity-25 mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h2 class="section-title mb-2"><i class="bi bi-key-fill text-warning"></i> Staff Registration Code</h2>
                    <p class="small text-muted mb-0">
                        Share this code with new staff so they can open the
                        <a href="staff_registration.php" class="text-decoration-none">staff registration form</a>.
                        The code expires after <?php echo (int) (STAFF_CODE_TTL_SECONDS / 60); ?> minutes once entered.
                    </p>
                </div>
                <div class="text-end">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Current code</div>
                    <div class="d-inline-flex align-items-center gap-2 bg-light border rounded-3 px-3 py-2">
                        <code class="fs-5 fw-bold text-dark mb-0 user-select-all" id="staffRegCode"><?php echo htmlspecialchars(STAFF_REGISTRATION_CODE, ENT_QUOTES, 'UTF-8'); ?></code>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="copyStaffRegCode" title="Copy code">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Fixed bottom-right: Remove user -->
<div class="position-fixed bottom-0 end-0 p-3 p-md-4" style="z-index: 1030;">
    <button
        type="button"
        class="btn btn-danger rounded-pill shadow px-4 py-2"
        data-bs-toggle="modal"
        data-bs-target="#removeUserModal"
        <?php echo empty($removable_users) ? 'disabled' : ''; ?>
    >
        <i class="bi bi-person-x me-1"></i> Remove user
    </button>
</div>

<!-- Modal: Remove student or staff -->
<div class="modal fade" id="removeUserModal" tabindex="-1" aria-labelledby="removeUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="removeUserModalLabel">
                    <i class="bi bi-person-x text-danger me-2"></i>Remove user
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="admin_users.php" onsubmit="return confirm('Permanently remove this user account? Their bookings and related data may also be affected.');">
                <input type="hidden" name="action" value="remove_user">
                <div class="modal-body pt-2">
                    <p class="small text-muted">Select a <strong>student</strong> or <strong>staff</strong> account to delete. Admin accounts cannot be removed here.</p>
                    <label for="remove_user_id" class="form-label">User</label>
                    <select name="user_id" id="remove_user_id" class="form-select rounded-3" required>
                        <option value="" disabled selected>Select user…</option>
                        <?php if (!empty($student_rows)): ?>
                        <optgroup label="Students">
                            <?php foreach ($student_rows as $row): ?>
                            <option value="<?php echo (int) $row['id']; ?>">
                                <?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($staff_rows)): ?>
                        <optgroup label="Staff">
                            <?php foreach ($staff_rows as $row): ?>
                            <option value="<?php echo (int) $row['id']; ?>">
                                <?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Remove user</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Promote to staff -->
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
                            <?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>
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

<!-- Modal: Demote to student -->
<div class="modal fade" id="demoteStaffModal" tabindex="-1" aria-labelledby="demoteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="demoteStaffModalLabel"><i class="bi bi-person-dash text-dark me-2"></i>Demote staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="admin_users.php">
                <input type="hidden" name="action" value="demote_staff">
                <div class="modal-body pt-2">
                    <label for="staff_id" class="form-label">Choose staff to demote to student</label>
                    <select name="staff_id" id="staff_id" class="form-select rounded-3" required>
                        <option value="" disabled selected>Select staff…</option>
                        <?php foreach ($staff_rows as $row): ?>
                        <option value="<?php echo (int) $row['id']; ?>">
                            <?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Demote to student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
<script>
(function () {
    const btn = document.getElementById('copyStaffRegCode');
    const codeEl = document.getElementById('staffRegCode');
    if (!btn || !codeEl) return;
    btn.addEventListener('click', function () {
        const code = codeEl.textContent.trim();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(function () {
                btn.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
            });
        } else {
            const ta = document.createElement('textarea');
            ta.value = code;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
        }
    });
})();
</script>
</body>
</html>
