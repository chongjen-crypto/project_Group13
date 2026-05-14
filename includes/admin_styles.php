<style>
    /* ========================= Scholar Hub — Admin UI (matches staff/student) ========================= */
    :root {
        --sidebar-width: 268px;
        --sidebar-bg: #0b0b0b;
        --sidebar-hover: #1f1f1f;
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
        color: #111827;
    }
    a { text-decoration: none; color: inherit; }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        height: 100dvh;
        max-height: 100dvh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        background: var(--sidebar-bg);
        color: #fff;
        z-index: 1040;
        display: flex;
        flex-direction: column;
        padding: max(1.25rem, env(safe-area-inset-top)) 1rem max(1rem, env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left));
        transition: transform var(--transition);
        box-shadow: 4px 0 24px rgba(0,0,0,0.15);
    }
    .sidebar-brand {
        font-weight: 800;
        letter-spacing: 0.04em;
        font-size: 1.02rem;
        padding: 0.5rem 0.75rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 0.75rem;
    }
    .sidebar-brand small {
        display: block;
        font-weight: 500;
        opacity: 0.65;
        font-size: 0.7rem;
        letter-spacing: 0.02em;
        margin-top: 0.25rem;
    }
    .nav-link-sidebar {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        color: rgba(255,255,255,0.9);
        padding: 0.6rem 0.85rem;
        border-radius: 10px;
        margin-bottom: 0.2rem;
        font-weight: 500;
        font-size: 0.9rem;
        transition: background var(--transition), color var(--transition), transform var(--transition);
    }
    .nav-link-sidebar i { font-size: 1.1rem; opacity: 0.92; }
    .nav-link-sidebar:hover {
        background: var(--sidebar-hover);
        color: #fff;
        transform: translateX(3px);
    }
    .nav-link-sidebar.active {
        background: #fff;
        color: #0b0b0b;
    }
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
        line-height: 1.2;
    }
    .welcome-text {
        color: #6b7280;
        font-size: clamp(0.8rem, 2.2vw, 0.95rem);
    }
    .datetime-pill {
        font-size: clamp(0.72rem, 1.8vw, 0.85rem);
        color: #374151;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        white-space: nowrap;
        max-width: 100%;
    }
    .avatar {
        width: 42px;
        height: 42px;
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
    .section-title {
        font-weight: 700;
        font-size: clamp(1rem, 2.5vw, 1.1rem);
        color: #111827;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, #e5e7eb, transparent);
        margin-left: 0.5rem;
    }

    .stat-card {
        background: #fff;
        border-radius: var(--card-radius);
        border: 1px solid #eef0f3;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06);
        padding: 1.25rem 1.35rem;
        height: 100%;
        transition: transform var(--transition), box-shadow var(--transition);
    }
    @media (hover: hover) {
        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 14px 36px rgba(0,0,0,0.1);
        }
    }
    .stat-card .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        color: #fff;
        margin-bottom: 0.75rem;
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #111827;
        letter-spacing: -0.02em;
    }
    .stat-card .stat-label {
        font-size: 0.82rem;
        color: #6b7280;
        font-weight: 500;
    }

    .card-soft {
        background: #fff;
        border-radius: var(--card-radius);
        border: 1px solid #eef0f3;
        box-shadow: 0 4px 18px rgba(0,0,0,0.05);
        transition: transform var(--transition), box-shadow var(--transition);
        height: 100%;
    }
    @media (hover: hover) {
        .card-soft:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }
    }
    .quick-action-card {
        text-align: center;
        padding: clamp(1rem, 3vw, 1.5rem) clamp(0.5rem, 2vw, 1rem);
        cursor: pointer;
        display: block;
        color: inherit;
    }
    .quick-action-card .icon-wrap {
        width: 56px;
        height: 56px;
        margin: 0 auto 0.75rem;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #fff;
    }
    .quick-action-card h6 { font-weight: 700; color: #111827; margin-bottom: 0.2rem; }
    .quick-action-card p { font-size: 0.8rem; color: #6b7280; margin: 0; }

    .table-modern thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6b7280;
        border-bottom-width: 1px;
    }
    .table-modern tbody td { vertical-align: middle; font-size: 0.88rem; }
    .table-wrap {
        background: #fff;
        border-radius: var(--card-radius);
        border: 1px solid #eef0f3;
        box-shadow: 0 4px 18px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .analytics-card {
        background: #fff;
        border-radius: var(--card-radius);
        padding: 1.25rem;
        border: 1px solid #eef0f3;
        box-shadow: 0 4px 18px rgba(0,0,0,0.05);
        height: 100%;
    }
    .mini-chart {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        height: 120px;
        padding-top: 0.5rem;
    }
    .mini-chart .bar {
        flex: 1;
        border-radius: 6px 6px 2px 2px;
        background: linear-gradient(180deg, #3b82f6, #2563eb);
        opacity: 0.88;
        transition: opacity var(--transition), transform var(--transition);
        min-height: 8px;
    }
    @media (hover: hover) {
        .mini-chart .bar:hover {
            opacity: 1;
            transform: scaleY(1.05);
            transform-origin: bottom;
        }
    }

    .staff-facility-img {
        height: 120px;
        overflow: hidden;
        background: #e5e7eb;
    }
    .staff-facility-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    @media (hover: hover) {
        .card-soft:hover .staff-facility-img img { transform: scale(1.05); }
    }
    .staff-facility-body { padding: 1rem 1.1rem; }

    .notif-item {
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #f3f4f6;
        transition: background var(--transition);
    }
    .notif-item:last-child { border-bottom: none; }
    @media (hover: hover) {
        .notif-item:hover { background: #f9fafb; }
    }
    .notif-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 6px;
    }

    .settings-placeholder {
        background: linear-gradient(135deg, #f9fafb, #fff);
        border: 1px dashed #d1d5db;
        border-radius: var(--card-radius);
        padding: 2rem;
        text-align: center;
        color: #6b7280;
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
        .main-wrap {
            margin-left: 0;
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }
        .btn-menu-toggle { display: inline-flex; }
    }
    @media (max-width: 575.98px) {
        .datetime-pill { white-space: normal; text-align: center; line-height: 1.35; }
    }

    section[id] { scroll-margin-top: 88px; }
</style>
