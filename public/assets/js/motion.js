/**
 * Count-up for the big numbers (dashboard stats, fine totals): they run up from 0 to their value when the page opens.
 * Loaded by includes/navigation/footer.php. Only plain numbers are touched ("12", "₱1,250.00"); anything else is left alone.
 * Add data-count-up to any other element that holds just a number.
 * If other code changes the number while it is counting (e.g. the pending-requests tile after an approval), the count stops
 * and the other code wins.
 */
(function () {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var PATTERN = /^(\D*?)(\d[\d,]*)(\.\d+)?(\D*)$/;
    var DURATION = 800;

    document.querySelectorAll('.stat-value, .fine-stat-value, [data-count-up]').forEach(function (el) {
        if (el.children.length) return;
        var original = el.textContent.trim();
        var m = PATTERN.exec(original);
        if (!m) return;

        var target = parseFloat(m[2].replace(/,/g, '') + (m[3] || ''));
        if (!(target > 0)) return;
        var decimals = m[3] ? m[3].length - 1 : 0;
        var grouped = m[2].indexOf(',') !== -1;

        function format(n) {
            return m[1] + n.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
                useGrouping: grouped
            }) + m[4];
        }

        var last = format(0);
        el.textContent = last;
        var begin = null;

        function step(now) {
            if (el.textContent !== last) return;              // someone else changed it: stop
            if (begin === null) begin = now;
            var t = Math.min(1, (now - begin) / DURATION);
            var eased = 1 - Math.pow(1 - t, 3);               // fast start, soft landing
            last = t < 1 ? format(target * eased) : original;
            el.textContent = last;
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();
