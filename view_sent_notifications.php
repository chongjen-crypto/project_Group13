<?php
/**
 * view_sent_notifications.php — Staff/admin log of notifications sent to students.
 */
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'], true)) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];

if ($role === 'staff') {
    require_once __DIR__ . '/includes/staff_auth.php';
    $staff_nav_active = 'notify_view';
    $staff_page_title = 'View Notifications';
} else {
    require_once __DIR__ . '/includes/admin_auth.php';
    $admin_nav_active = 'notify_view';
    $admin_page_title = 'View Notifications';
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/notification_helpers.php';
require_once __DIR__ . '/includes/list_pager.php';

$success_msg = '';
$error_msg = '';
$listPage = max(1, (int) ($_GET['page'] ?? 1));

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $success_msg = 'Notification was deleted successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_broadcast') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $sent_minute = (int) ($_POST['sent_minute'] ?? 0);

    if ($title === '' || $message === '' || $sent_minute <= 0) {
        $error_msg = 'Invalid notification selected.';
    } else {
        $result = notifications_delete_broadcast($conn, $title, $message, $sent_minute);
        if ($result['success']) {
            header('Location: view_sent_notifications.php?msg=deleted');
            exit();
        }
        $error_msg = $result['message'];
    }
}

$sentList = notifications_fetch_sent_broadcasts_paginated($conn, $listPage, 10);
$sent_notifications = $sentList['items'];
$pagination = $sentList['pagination'];
$pageItems = list_pager_page_items($pagination['page'], $pagination['total_pages']);
$cancel_href = $role === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php';
$send_href = 'send_notification.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>View Notifications — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
    <?php include __DIR__ . '/includes/list_pager_styles.php'; ?>
</head>
<body>

<?php
if ($role === 'admin') {
    include __DIR__ . '/includes/admin_sidebar.php';
} else {
    include __DIR__ . '/includes/staff_sidebar.php';
}
?>

<div class="main-wrap" id="mainWrap">
    <?php
    if ($role === 'admin') {
        include __DIR__ . '/includes/admin_header.php';
    } else {
        include __DIR__ . '/includes/staff_header.php';
    }
    ?>

    <main class="content-area">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0"><i class="bi bi-bell text-primary"></i> View Notifications</h2>
            <a href="<?php echo htmlspecialchars($send_href, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-dark btn-sm rounded-pill px-3">
                <i class="bi bi-send me-1"></i> Send new
            </a>
        </div>
        <p class="text-muted small mb-4">All broadcasts sent to students. Deleting removes it from every student inbox.</p>

        <?php if ($success_msg !== ''): ?>
            <div class="alert alert-success py-2"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error_msg !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (empty($sent_notifications)): ?>
            <div class="table-wrap p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                No notifications have been sent yet.
                <div class="mt-3">
                    <a href="<?php echo htmlspecialchars($send_href, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-dark rounded-pill px-4">Send your first notification</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap mb-4">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Message</th>
                                <th>Sent at</th>
                                <th>Recipients</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sent_notifications as $n): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="max-width: 280px; white-space: pre-wrap;"><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-nowrap"><?php echo htmlspecialchars(notifications_format_datetime($n['sent_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge text-bg-secondary rounded-pill"><?php echo (int) $n['recipient_count']; ?> student(s)</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <form
                                        method="post"
                                        class="d-inline"
                                        onsubmit="return confirm('Delete this notification from all student inboxes? This cannot be undone.');"
                                    >
                                        <input type="hidden" name="action" value="delete_broadcast">
                                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="message" value="<?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="sent_minute" value="<?php echo (int) $n['sent_minute']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                list_pager_render(
                    $pagination,
                    $pageItems,
                    'view_sent_notifications.php',
                    'notification',
                    'notifications',
                    [],
                    'Notification pages'
                );
                ?>
            </div>
        <?php endif; ?>

        <a href="<?php echo htmlspecialchars($cancel_href, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Back to dashboard
        </a>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
</body>
</html>
