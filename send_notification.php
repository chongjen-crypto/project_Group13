<?php
/**
 * send_notification.php — Compose and broadcast a notification to all students.
 */
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'], true)) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];

if ($role === 'staff') {
    require_once __DIR__ . '/includes/staff_auth.php';
    $staff_nav_active = 'notify';
    $staff_page_title = 'Send Notification';
} else {
    require_once __DIR__ . '/includes/admin_auth.php';
    $admin_nav_active = 'notify';
    $admin_page_title = 'Send Notification';
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/notification_helpers.php';
require_once __DIR__ . '/includes/text_input_helpers.php';

$sent = false;
$sent_message = '';
$error = '';
$title_prefill = '';
$message_prefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_prefill = trim($_POST['title'] ?? '');
    $message_prefill = trim($_POST['message'] ?? '');
    if ($title_prefill === '' || $message_prefill === '') {
        $error = 'Both title and message are required.';
    } else {
        $titleCheck = text_input_validate($title_prefill, true);
        $messageCheck = text_input_validate($message_prefill, true);
        if (!$titleCheck['valid']) {
            $error = 'Title: ' . $titleCheck['error'];
        } elseif (!$messageCheck['valid']) {
            $error = 'Message: ' . $messageCheck['error'];
        } else {
        $result = notifications_send_to_all_students($conn, $title_prefill, $message_prefill);
        if ($result['success']) {
            $sent = true;
            $sent_message = $result['message'];
            $title_prefill = '';
            $message_prefill = '';
        } else {
            $error = $result['message'];
        }
        }
    }
}

$cancel_href = $role === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Send Notification — Scholar Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php include __DIR__ . '/includes/admin_styles.php'; ?>
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
        <h2 class="section-title"><i class="bi bi-send text-primary"></i> Send Notification to Students</h2>
        <p class="text-muted small mb-4">Students will see this in the bell icon on their dashboard and booking pages.</p>

        <?php if ($sent): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($sent_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="table-wrap p-4" style="max-width: 640px;">
            <form method="post">
                <div class="mb-3">
                    <label for="notifTitle" class="form-label fw-semibold">Title</label>
                    <input
                        type="text"
                        name="title"
                        id="notifTitle"
                        class="form-control rounded-3"
                        maxlength="255"
                        required
                        placeholder="e.g. Pool maintenance notice"
                        value="<?php echo htmlspecialchars($title_prefill, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="form-text"><span id="titleCharCount">0 / <?php echo TEXT_INPUT_MAX_CHARS; ?> characters</span></div>
                    <div id="titleError" class="text-danger small mt-1 d-none"></div>
                </div>
                <div class="mb-4">
                    <label for="notifMessage" class="form-label fw-semibold">Message</label>
                    <textarea
                        name="message"
                        id="notifMessage"
                        class="form-control rounded-3"
                        rows="6"
                        required
                        placeholder="Enter the information to send to all students…"
                    ><?php echo htmlspecialchars($message_prefill, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div class="form-text"><span id="messageCharCount">0 / <?php echo TEXT_INPUT_MAX_CHARS; ?> characters</span></div>
                    <div id="messageError" class="text-danger small mt-1 d-none"></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-dark rounded-pill px-4" id="btnSendNotif">
                        <i class="bi bi-send me-1"></i> Send
                    </button>
                    <a href="view_sent_notifications.php" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-bell me-1"></i> View sent
                    </a>
                    <a href="<?php echo htmlspecialchars($cancel_href, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/admin_scripts.php'; ?>
<script src="includes/text_input_validation.js"></script>
<script>
(function () {
    var titleInput = document.getElementById('notifTitle');
    var messageInput = document.getElementById('notifMessage');
    var titleVal = TextInputValidation.bindLimitedTextInput(titleInput, document.getElementById('titleError'), {
        required: true,
        counterEl: document.getElementById('titleCharCount')
    });
    var messageVal = TextInputValidation.bindLimitedTextInput(messageInput, document.getElementById('messageError'), {
        required: true,
        counterEl: document.getElementById('messageCharCount')
    });
    document.querySelector('form[method="post"]')?.addEventListener('submit', function (e) {
        var t = titleVal.validate();
        var m = messageVal.validate();
        if (!t.ok || !m.ok) {
            e.preventDefault();
        }
    });
})();
</script>
</body>
</html>
