<style>
    :root {
        --sidebar-width: 260px;
        --sidebar-bg: #0b0b0b;
        --sidebar-hover: #1a1a1a;
        --page-bg: #f3f4f6;
        --card-radius: 14px;
        --transition: 0.22s ease;
    }
    html { overflow-x: hidden; }
    body {
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: var(--page-bg);
        min-height: 100vh;
        min-height: 100dvh;
        overflow-x: hidden;
    }
    a { text-decoration: none; }
    .sidebar {
        position: fixed; top: 0; left: 0;
        width: var(--sidebar-width);
        height: 100vh; height: 100dvh;
        max-height: 100dvh;
        overflow-y: auto;
        background: var(--sidebar-bg);
        color: #fff;
        z-index: 1040;
        display: flex;
        flex-direction: column;
        padding: max(1.25rem, env(safe-area-inset-top)) 1rem max(1rem, env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left));
        transition: transform var(--transition);
        box-shadow: 4px 0 24px rgba(0,0,0,0.12);
    }
    .sidebar-brand {
        font-weight: 800;
        letter-spacing: 0.04em;
        font-size: 1.05rem;
        padding: 0.5rem 0.75rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 1rem;
    }
    .sidebar-brand small {
        display: block;
        font-weight: 500;
        opacity: 0.65;
        font-size: 0.72rem;
        margin-top: 0.25rem;
    }
    .nav-link-sidebar {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        color: rgba(255,255,255,0.88);
        padding: 0.65rem 0.85rem;
        border-radius: 10px;
        margin-bottom: 0.25rem;
        font-weight: 500;
        font-size: 0.95rem;
        transition: background var(--transition), color var(--transition), transform var(--transition);
    }
    .nav-link-sidebar i { font-size: 1.15rem; }
    .nav-link-sidebar:hover {
        background: var(--sidebar-hover);
        color: #fff;
        transform: translateX(3px);
    }
    .nav-link-sidebar.active { background: #fff; color: #0b0b0b; }
    .nav-link-sidebar.active i { color: #0b0b0b; }
    .sidebar-footer {
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    .sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 1030;
    }
    .sidebar-backdrop.show { display: block; }
    .main-wrap {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        transition: margin-left var(--transition);
    }
    .top-header {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: max(0.65rem, env(safe-area-inset-top)) clamp(0.75rem, 2.5vw, 1.5rem) 1rem;
        position: sticky;
        top: 0;
        z-index: 1020;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .page-title {
        font-weight: 700;
        font-size: clamp(1.05rem, 2.8vw, 1.35rem);
        color: #111827;
    }
    .welcome-text { color: #6b7280; font-size: clamp(0.8rem, 2.2vw, 0.95rem); }
    .datetime-pill {
        font-size: clamp(0.72rem, 1.8vw, 0.85rem);
        color: #374151;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e3a5f, #475569);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        border: 2px solid #e5e7eb;
        flex-shrink: 0;
    }
    .content-area {
        padding: clamp(0.75rem, 2.5vw, 1.5rem);
        padding-bottom: max(1.5rem, env(safe-area-inset-bottom));
    }
    .btn-menu-toggle {
        display: none;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 10px;
        padding: 0.45rem 0.6rem;
        min-width: 2.75rem;
        min-height: 2.75rem;
        align-items: center;
        justify-content: center;
    }
    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.show { transform: translateX(0); }
        .main-wrap { margin-left: 0; }
        .btn-menu-toggle { display: inline-flex; }
    }
</style>
