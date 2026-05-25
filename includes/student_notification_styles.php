<style>
    .notif-bell-wrap { position: relative; }
    .btn-notif-bell {
        position: relative;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 50%;
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #374151;
        padding: 0;
        flex-shrink: 0;
    }
    .btn-notif-bell:hover { background: #f3f4f6; color: #111827; }
    .notif-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 18px;
        text-align: center;
        border-radius: 999px;
        background: #dc2626;
        color: #fff;
    }
    .notif-dropdown {
        width: min(360px, calc(100vw - 2rem));
        max-height: 420px;
        padding: 0;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
    }
    .notif-dropdown-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #eef0f3;
        background: #fafafa;
    }
    .notif-dropdown-body {
        max-height: 340px;
        overflow-y: auto;
    }
    .notif-empty { padding: 1.25rem 1rem; text-align: center; font-size: 0.9rem; }
    .notif-item {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .notif-item:hover { background: #f9fafb; }
    .notif-item.unread { background: #eff6ff; }
    .notif-item.unread .notif-item-title { color: #1d4ed8; }
    .notif-item-msg { margin-top: 0.25rem; line-height: 1.4; }
    .notif-item-time { margin-top: 0.35rem; }
    .notif-dropdown-footer { padding: 0.65rem 1rem; background: #fafafa; }
</style>
