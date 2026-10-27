/**
 * Log out transition. Loaded on every app page by includes/navigation/footer.php.
 * Clicking "Yes, Log Out" in the confirmation window turns the button into "Logging out...", fades the screen to the
 * sidebar colour, then opens logout.php. The Sign In page that follows slides its card in from the left.
 * Without JavaScript the link simply works.
 */
(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href*="logout.php"]');
        if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        if (link.classList.contains('is-busy')) return;

        link.classList.add('is-busy');
        var icon = link.querySelector('i');
        if (icon) icon.className = 'ph ph-spinner ce-spin-icon';
        var label = link.querySelector('.btn-text');
        if (label) label.textContent = 'Logging out...';

        try { sessionStorage.setItem('ce-auth-dir', 'back'); } catch (err) { /* ignore */ }

        var veil = document.createElement('div');
        veil.className = 'ce-veil';
        veil.setAttribute('role', 'status');
        veil.innerHTML = '<span class="ce-veil-spin" aria-hidden="true"></span><span>Logging you out</span>';
        document.body.appendChild(veil);
        requestAnimationFrame(function () { veil.classList.add('is-on'); });

        setTimeout(function () { window.location.href = link.href; }, reduce ? 150 : 550);
    });
})();
