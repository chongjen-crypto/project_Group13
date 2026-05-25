<?php
/**
 * student_notifications.php — All notifications for the logged-in student.
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/notification_helpers.php';

$student_name = isset($_SESSION['full_name']) && trim((string) $_SESSION['full_name']) !== ''
    ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8')
    : 'Student';

$student_email = isset($_SESSION['email'])
    ? htmlspecialchars((string) $_SESSION['email'], ENT_QUOTES, 'UTF-8')
    : '';

$student_nav_active = 'notifications';
$user_id = (int) $_SESSION['user_id'];
$marked_all = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all_read') {
    notifications_mark_all_read($conn, $user_id);
    $marked_all = true;
}

$notifications = notifications_fetch_for_user($conn, $user_id, 200);
$unread_count = notifications_unread_count($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Notifications — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/student_styles.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/student_sidebar.php'; ?>

<div class="main-wrap" id="mainWrap">
    <header class="top-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <div class="page-title">Notifications</div>
                    <div class="welcome-text">Messages from staff and admin</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="datetime-pill" id="liveDateTime"></div>
                <?php include __DIR__ . '/includes/student_notification_bell.php'; ?>
                <div class="avatar" title="<?php echo $student_email !== '' ? $student_email : $student_name; ?>">
                    <?php
                    $parts = preg_split('/\s+/', trim((string) ($_SESSION['full_name'] ?? 'S')));
                    $ini = strtoupper(substr($parts[0] ?? 'S', 0, 1));
                    if (isset($parts[1]) && $parts[1] !== '') {
                        $ini .= strtoupper(substr($parts[1], 0, 1));
                    }
                    echo htmlspecialchars($ini, ENT_QUOTES, 'UTF-8');
                    ?>
                </div>
            </div>
        </div>
    </header>

    <main class="content-area">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0"><i class="bi bi-bell text-primary"></i> All Notifications</h2>
            <?php if ($unread_count > 0): ?>
                <form method="post" class="mb-0">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                        <i class="bi bi-check2-all me-1"></i> Mark all as read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($marked_all): ?>
            <div class="alert alert-success py-2">All notifications marked as read.</div>
        <?php endif; ?>

        <?php if (empty($notifications)): ?>
            <div class="table-wrap p-5 text-center text-muted">
                <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-50"></i>
                You have no notifications yet.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <article
                    class="notif-page-card<?php echo (int) $n['is_read'] === 0 ? ' unread' : ''; ?>"
                    data-id="<?php echo (int) $n['id']; ?>"
                >
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h3 class="notif-page-title h6 mb-0"><?php echo htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if ((int) $n['is_read'] === 0): ?>
                            <span class="badge text-bg-primary rounded-pill">New</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted mb-2 small" style="white-space: pre-wrap;"><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="small text-secondary">
                        <i class="bi bi-clock me-1"></i><?php echo htmlspecialchars(notifications_format_datetime($n['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                        <span class="mx-1">·</span>
                        <?php echo htmlspecialchars(notifications_format_time($n['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    function updateDateTime() {
        var el = document.getElementById('liveDateTime');
        if (!el) return;
        el.textContent = new Date().toLocaleString(undefined, {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var btnToggle = document.getElementById('btnMenuToggle');
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('show')) closeSidebar();
            else openSidebar();
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    window.addEventListener('resize', function () { if (window.innerWidth >= 992) closeSidebar(); });

    document.querySelectorAll('.notif-page-card.unread[data-id]').forEach(function (card) {
        card.addEventListener('click', function () {
            var id = card.getAttribute('data-id');
            fetch('student_notification_mark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'mark_one', id: id }).toString()
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (!res.ok) return;
                card.classList.remove('unread');
                var badge = card.querySelector('.badge');
                if (badge) badge.remove();
                var bellBadge = document.getElementById('notifBadge');
                if (bellBadge) {
                    if ((res.unread || 0) > 0) {
                        bellBadge.textContent = res.unread > 99 ? '99+' : String(res.unread);
                        bellBadge.classList.remove('d-none');
                    } else {
                        bellBadge.classList.add('d-none');
                    }
                }
            });
        });
    });
})();
</script>
<?php include __DIR__ . '/includes/student_notification_scripts.php'; ?>
</body>
</html>
