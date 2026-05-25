<?php
/**
 * send_notification.php — Compose and broadcast a notification to students
 */
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/db.php';

$role = $_GET['role'] ?? $_SESSION['role']; // staff or admin
$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($title === '' || $message === '') {
        $error = 'Both title and message are required.';
    } else {
        // Insert a notification row for each student
        $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, title, message) SELECT id, ?, ? FROM users WHERE role='student'");
        mysqli_stmt_bind_param($stmt, "ss", $title, $message);
        if (mysqli_stmt_execute($stmt)) {
            $sent = true;
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {background:#f3f4f6;}
        .card {max-width:600px; margin:auto; margin-top:2rem;}
    </style>
</head>
<body>
<?php
// Include appropriate sidebar based on role
if ($role === 'admin') {
    include __DIR__ . '/includes/admin_sidebar.php';
} else {
    include __DIR__ . '/includes/staff_sidebar.php';
}
?>
<main class="content-area" style="margin-left: var(--sidebar-width); padding:1rem;">
    <h2 class="section-title"><i class="bi bi-send text-primary"></i> Send Notification</h2>
    <?php if ($sent): ?>
        <div class="alert alert-success">Notification sent to all students.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div>
    <?php endif; ?>
    <form method="post" class="card p-4">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required value="<?=htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required><?=htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8')?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Send</button>
        <a href="<?php echo $role === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'; ?>" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
