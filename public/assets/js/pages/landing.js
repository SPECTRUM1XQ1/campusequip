/* Landing page: mobile menu + a shadow under the top bar once the page is scrolled. */
(function () {
    const nav = document.getElementById('lpNav');
    const burger = document.getElementById('lpBurger');
    const links = document.getElementById('lpLinks');
    if (!nav) return;

    function onScroll() { nav.classList.toggle('is-scrolled', window.scrollY > 8); }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (!burger || !links) return;

    function setOpen(open) {
        links.classList.toggle('is-open', open);
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        burger.firstElementChild.className = open ? 'ph ph-x' : 'ph ph-list';
    }

    burger.addEventListener('click', () => setOpen(!links.classList.contains('is-open')));
    links.addEventListener('click', e => { if (e.target.closest('a')) setOpen(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
})();

/* Log in / Sign up / Create an account: the button shows it is working, the page slides away, then the form page opens
   and its card slides in from the right (the direction is read by an inline script in login.php / signup.php). */
(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a.lp-btn[href], a.lp-mobile-cta[href], .lp-links a[href*="login.php"], .lp-links a[href*="signup.php"]');
        if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        if (!/(login|signup)\.php/.test(link.getAttribute('href'))) return;
        e.preventDefault();
        if (link.classList.contains('is-going')) return;
        try { sessionStorage.setItem('ce-auth-dir', 'forward'); } catch (err) { /* ignore */ }
        link.classList.add('is-going');
        document.body.classList.add('is-leaving');
        setTimeout(function () { window.location.href = link.href; }, reduce ? 100 : 300);
    });
})();
