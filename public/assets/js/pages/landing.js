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
