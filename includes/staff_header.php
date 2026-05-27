<?php
/**
 * Top header strip for staff pages (expects $staff_page_title string).
 */
if (!isset($staff_page_title)) {
    $staff_page_title = 'Staff';
}
?>
<header class="top-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <div class="page-title"><?php echo htmlspecialchars($staff_page_title, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="welcome-text">Welcome back, <?php echo $staff_name; ?> 👋</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="datetime-pill" id="liveDateTime"></div>
            <?php include __DIR__ . '/staff_admin_notification_bell.php'; ?>
            <div class="avatar" title="<?php echo $staff_email !== '' ? $staff_email : $staff_name; ?>">
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
