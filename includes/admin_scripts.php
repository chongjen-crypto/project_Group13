<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var btnMenu = document.getElementById('btnMenuToggle');
    var liveEl = document.getElementById('liveDateTime');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function tickClock() {
        if (!liveEl) return;
        var now = new Date();
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var d = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        var t = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        liveEl.textContent = d + ' · ' + t;
    }

    if (liveEl) {
        tickClock();
        setInterval(tickClock, 1000);
    }

    function closeSb() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    function openSb() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    if (btnMenu && sidebar) {
        btnMenu.addEventListener('click', function () {
            if (sidebar.classList.contains('show')) closeSb();
            else openSb();
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSb);
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) closeSb();
    });
})();
</script>
<?php include __DIR__ . '/student_notification_scripts.php'; ?>
