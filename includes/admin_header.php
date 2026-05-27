<?php
/**
 * Top header strip (expects $admin_page_title string)
 */
if (!isset($admin_page_title)) {
    $admin_page_title = 'Admin';
}
?>
<header class="top-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn-menu-toggle" id="btnMenuToggle" aria-label="Open menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <div class="page-title"><?php echo htmlspecialchars($admin_page_title, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="welcome-text">Welcome back, <?php echo $admin_name; ?> 👋</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="datetime-pill" id="liveDateTime"></div>
            <?php include __DIR__ . '/staff_admin_notification_bell.php'; ?>
            <div class="avatar" title="<?php echo $admin_email !== '' ? $admin_email : $admin_name; ?>">
                <?php
                $parts = preg_split('/\s+/', trim((string) ($_SESSION['full_name'] ?? 'A')));
                $ini = strtoupper(substr($parts[0] ?? 'A', 0, 1));
                if (isset($parts[1]) && $parts[1] !== '') {
                    $ini .= strtoupper(substr($parts[1], 0, 1));
                }
                echo htmlspecialchars($ini, ENT_QUOTES, 'UTF-8');
                ?>
            </div>
        </div>
    </div>
</header>
