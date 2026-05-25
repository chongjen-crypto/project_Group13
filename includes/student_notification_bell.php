<?php
/**
 * Notification bell + dropdown (student header). Requires $conn and student session.
 */
if (!isset($conn) || !isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    return;
}

require_once __DIR__ . '/notification_helpers.php';

$notif_user_id = (int) $_SESSION['user_id'];
$student_notifications = notifications_fetch_for_user($conn, $notif_user_id, 25);
$student_notif_unread = notifications_unread_count($conn, $notif_user_id);
?>
<div class="notif-bell-wrap dropdown">
    <button
        type="button"
        class="btn-notif-bell"
        id="btnNotifBell"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false"
        aria-label="Notifications"
    >
        <i class="bi bi-bell"></i>
        <?php if ($student_notif_unread > 0): ?>
            <span class="notif-badge" id="notifBadge"><?php echo $student_notif_unread > 99 ? '99+' : (int) $student_notif_unread; ?></span>
        <?php else: ?>
            <span class="notif-badge d-none" id="notifBadge"></span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end notif-dropdown shadow-lg" aria-labelledby="btnNotifBell">
        <div class="notif-dropdown-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Notifications</span>
            <?php if ($student_notif_unread > 0): ?>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnMarkAllRead">Mark all read</button>
            <?php endif; ?>
        </div>
        <div class="notif-dropdown-body" id="notifDropdownBody">
            <?php if (empty($student_notifications)): ?>
                <div class="notif-empty text-muted">No notifications yet.</div>
            <?php else: ?>
                <?php foreach (array_slice($student_notifications, 0, 8) as $n): ?>
                    <div
                        class="notif-item<?php echo (int) $n['is_read'] === 0 ? ' unread' : ''; ?>"
                        data-id="<?php echo (int) $n['id']; ?>"
                    >
                        <div class="notif-item-title fw-semibold"><?php echo htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="notif-item-msg small text-muted"><?php echo nl2br(htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8')); ?></div>
                        <div class="notif-item-time small text-secondary"><?php echo htmlspecialchars(notifications_format_time($n['created_at']), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="notif-dropdown-footer border-top text-center">
            <a href="student_notifications.php" class="small fw-semibold text-decoration-none">View all notifications</a>
        </div>
    </div>
</div>
