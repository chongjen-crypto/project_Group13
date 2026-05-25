<script>
(function () {
    'use strict';
    var bell = document.getElementById('btnNotifBell');
    if (!bell) return;

    function updateBadge(count) {
        var badge = document.getElementById('notifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    function postMark(data) {
        return fetch('student_notification_mark.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data).toString()
        }).then(function (r) { return r.json(); });
    }

    document.getElementById('btnMarkAllRead')?.addEventListener('click', function () {
        postMark({ action: 'mark_all' }).then(function (res) {
            if (!res.ok) return;
            document.querySelectorAll('.notif-item.unread').forEach(function (el) {
                el.classList.remove('unread');
            });
            updateBadge(0);
        });
    });

    document.querySelectorAll('.notif-item[data-id]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (!el.classList.contains('unread')) return;
            var id = el.getAttribute('data-id');
            postMark({ action: 'mark_one', id: id }).then(function (res) {
                if (!res.ok) return;
                el.classList.remove('unread');
                updateBadge(res.unread || 0);
            });
        });
    });
})();
</script>
