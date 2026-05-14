<?php
/**
 * Scholar Hub — Admin: User lists (students & staff), simple view only (demo data).
 */
require_once __DIR__ . '/includes/admin_auth.php';

$admin_nav_active = 'users';
$admin_page_title = 'User Management';

// ---- Demo lists (sorted A–Z within each group) ----
$student_rows = [
    ['name' => 'Ahmad Zulkarnain', 'email' => 'ahmad.z@student.edu'],
    ['name' => 'Emily Chen', 'email' => 'emily.c@student.edu'],
    ['name' => 'Nur Aina', 'email' => 'nur.aina@student.edu'],
    ['name' => 'Raj Kumar', 'email' => 'raj.k@student.edu'],
    ['name' => 'Wei Ming', 'email' => 'wei.ming@student.edu'],
];
usort($student_rows, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});

$staff_rows = [
    ['name' => 'James Ong', 'email' => 'james.o@staff.edu'],
    ['name' => 'Priya Nair', 'email' => 'priya.n@staff.edu'],
    ['name' => 'Sarah Lim', 'email' => 'sarah.lim@staff.edu'],
];
usort($staff_rows, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});
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
                        <?php foreach ($student_rows as $row): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="pe-4 text-muted"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h2 class="section-title"><i class="bi bi-person-badge text-secondary"></i> Staff</h2>
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
                        <?php foreach ($staff_rows as $row): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="pe-4 text-muted"><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-center text-muted small pb-3 mb-0">Demo data — connect to your database when ready.</p>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
