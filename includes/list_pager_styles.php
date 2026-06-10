<style>
    .table-pager {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        padding: 0.85rem 1.25rem;
        border-top: 1px solid #eef0f3;
        background: #fafbfc;
    }
    .table-pager-info {
        font-size: 0.875rem;
        color: #6b7280;
    }
    .table-pager-nav {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .table-pager-btn {
        min-width: 2.25rem;
        height: 2.25rem;
        padding: 0 0.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .table-pager-btn:hover:not(.disabled):not(.active) {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #111827;
    }
    .table-pager-btn.active {
        background: #111827;
        border-color: #111827;
        color: #fff;
        pointer-events: none;
    }
    .table-pager-btn.disabled {
        opacity: 0.45;
        pointer-events: none;
    }
    .table-pager-ellipsis {
        min-width: 1.5rem;
        text-align: center;
        color: #9ca3af;
        font-size: 0.875rem;
    }
</style>
