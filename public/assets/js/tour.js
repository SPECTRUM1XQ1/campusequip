/**
 * Guided tour: dims the page, points at one thing at a time and says what it is for (Next / Back / Skip).
 * Loaded on every app page by includes/navigation/footer.php. The words live in assets/js/tour-steps.js.
 *
 *   - The question-mark button in the top bar starts the tour of the page you are on.
 *   - The first time someone opens their home page, the menu tour and the page tour run together once.
 *   - Any page opened with ?tour=1 starts its tour (the User Guide's "Show me" buttons use this).
 *   - CETour.start('shell') or CETour.start('page') can also be called from other scripts.
 * Keys: Right arrow / Enter = next, Left arrow = back, Esc = skip.
 */
(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var DATA = window.CE_TOURS || { shell: [], pages: {} };
    var PAD = 6;
    var root, spot, card, titleEl, textEl, countEl, backBtn, nextBtn, skipBtn, arrow;
    var steps = [], index = 0, current = null, returnFocus = null, raf = 0;

    function pageKey() {
        var parts = location.pathname.replace(/\.php$/, '').split('/').filter(Boolean);
        return parts.slice(-2).join('/');
    }
    function isPhone() { return window.innerWidth <= 768; }
    function userId() {
        var bell = document.getElementById('bellBtn');
        return bell ? bell.getAttribute('data-user-id') : '0';
    }

    function visible(el) {
        if (!el) return false;
        var r = el.getBoundingClientRect();
        if (r.width < 2 || r.height < 2) return false;
        var cs = getComputedStyle(el);
        return cs.visibility !== 'hidden' && cs.display !== 'none';
    }

    function find(step) {
        var list = Array.prototype.slice.call(document.querySelectorAll(step.sel));
        if (step.has) {
            var needle = step.has.toLowerCase();
            list = list.filter(function (el) { return el.textContent.toLowerCase().indexOf(needle) !== -1; });
        }
        var el = null;
        for (var i = 0; i < list.length; i++) {
            if (visible(list[i])) { el = list[i]; break; }
        }
        if (!el) return null;
        if (step.closest) {
            var up = el.closest(step.closest);
            if (up && visible(up)) el = up;
        }
        return el;
    }

    // Keep only the steps that make sense here: right device, and the thing is really on the page.
    function usable(list) {
        return list.filter(function (s) {
            if (s.desktop && isPhone()) return false;
            if (s.mobile && !isPhone()) return false;
            return !!find(s);
        });
    }

    // ---------------------------------------------------------------- the pieces
    function build() {
        if (root) return;
        root = document.createElement('div');
        root.className = 'ce-tour';
        root.innerHTML =
            '<div class="ce-tour-block"></div>' +
            '<div class="ce-tour-spot" aria-hidden="true"></div>' +
            '<div class="ce-tour-card" role="dialog" aria-modal="true" aria-labelledby="ceTourTitle" tabindex="-1">' +
            '  <span class="ce-tour-arrow" aria-hidden="true"></span>' +
            '  <p class="ce-tour-count"></p>' +
            '  <h3 class="ce-tour-title" id="ceTourTitle"></h3>' +
            '  <p class="ce-tour-text"></p>' +
            '  <div class="ce-tour-actions">' +
            '    <button type="button" class="ce-tour-skip">Skip tour</button>' +
            '    <span class="ce-tour-spacer"></span>' +
            '    <button type="button" class="ce-tour-btn ce-tour-back">Back</button>' +
            '    <button type="button" class="ce-tour-btn ce-tour-next">Next</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(root);
        spot = root.querySelector('.ce-tour-spot');
        card = root.querySelector('.ce-tour-card');
        arrow = root.querySelector('.ce-tour-arrow');
        titleEl = root.querySelector('.ce-tour-title');
        textEl = root.querySelector('.ce-tour-text');
        countEl = root.querySelector('.ce-tour-count');
        backBtn = root.querySelector('.ce-tour-back');
        nextBtn = root.querySelector('.ce-tour-next');
        skipBtn = root.querySelector('.ce-tour-skip');
        backBtn.addEventListener('click', function () { go(index - 1); });
        nextBtn.addEventListener('click', function () { index >= steps.length - 1 ? end() : go(index + 1); });
        skipBtn.addEventListener('click', end);
        root.querySelector('.ce-tour-block').addEventListener('click', function () { /* clicks on the page are ignored while touring */ });
    }

    // ---------------------------------------------------------------- showing a step
    function go(i) {
        if (i < 0 || i >= steps.length) return;
        index = i;
        var step = steps[i];
        var el = find(step);
        if (!el) {                                   // it disappeared: move on in the direction we were going
            steps.splice(i, 1);
            if (!steps.length) { end(); return; }
            go(Math.min(i, steps.length - 1));
            return;
        }
        current = { step: step, el: el };

        titleEl.textContent = step.title;
        textEl.textContent = step.text;
        countEl.textContent = (i + 1) + ' of ' + steps.length;
        backBtn.hidden = i === 0;
        nextBtn.textContent = i === steps.length - 1 ? 'Done' : 'Next';

        var r = el.getBoundingClientRect();
        var tall = r.height > window.innerHeight * 0.6;
        // the top bar stays on screen while the page scrolls, so anything under it counts as out of view too
        var bar = document.querySelector('.top-header');
        var topInset = bar ? Math.max(0, bar.getBoundingClientRect().bottom) : 0;
        var offscreen = r.top < topInset + 12 || r.bottom > window.innerHeight - 12;
        if (offscreen || tall) {
            el.style.scrollMarginTop = (topInset + 16) + 'px';
            el.scrollIntoView({ block: tall ? 'start' : 'center', inline: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
            setTimeout(place, reduce ? 0 : 380);
        }
        place();
        nextBtn.focus({ preventScroll: true });
        card.classList.remove('is-in'); void card.offsetWidth; card.classList.add('is-in');
    }

    function place() {
        if (!current) return;
        var el = current.el;
        if (!document.body.contains(el)) { var again = find(current.step); if (!again) return; current.el = el = again; }
        var r = el.getBoundingClientRect();
        var vw = window.innerWidth, vh = window.innerHeight;

        // the light on the thing we are pointing at
        var x = Math.max(4, r.left - PAD), y = Math.max(4, r.top - PAD);
        var w = Math.min(vw - 8 - x, r.width + PAD * 2), h = Math.min(vh - 8 - y, r.height + PAD * 2);
        spot.style.left = x + 'px'; spot.style.top = y + 'px'; spot.style.width = w + 'px'; spot.style.height = h + 'px';

        // the card: first the side asked for (or the one with room), then the others
        var cw = card.offsetWidth, ch = card.offsetHeight, gap = 14, m = 10;
        var room = { bottom: vh - (y + h) - m, top: y - m, right: vw - (x + w) - m, left: x - m };
        var need = { bottom: ch + gap, top: ch + gap, right: cw + gap, left: cw + gap };
        var order = ['bottom', 'top', 'right', 'left'];
        if (current.step.side) order.unshift(current.step.side);
        var side = null;
        for (var i = 0; i < order.length; i++) { if (room[order[i]] >= need[order[i]]) { side = order[i]; break; } }

        var left, top;
        if (!side) {                                  // no room beside it: sit over the lower part of the screen
            side = 'none';
            left = (vw - cw) / 2;
            top = Math.min(vh - ch - m, Math.max(m, y + h + gap > vh - ch ? vh - ch - m : y + h + gap));
        } else if (side === 'bottom' || side === 'top') {
            left = x + w / 2 - cw / 2;
            top = side === 'bottom' ? y + h + gap : y - ch - gap;
        } else {
            top = y + h / 2 - ch / 2;
            left = side === 'right' ? x + w + gap : x - cw - gap;
        }
        left = Math.max(m, Math.min(vw - cw - m, left));
        top = Math.max(m, Math.min(vh - ch - m, top));
        card.style.left = left + 'px'; card.style.top = top + 'px';
        card.setAttribute('data-side', side);

        // the little pointer, kept under the middle of the thing we point at
        if (side === 'bottom' || side === 'top') {
            arrow.style.left = Math.max(18, Math.min(cw - 18, x + w / 2 - left)) + 'px'; arrow.style.top = '';
        } else if (side === 'left' || side === 'right') {
            arrow.style.top = Math.max(18, Math.min(ch - 18, y + h / 2 - top)) + 'px'; arrow.style.left = '';
        }
    }

    function onMove() {
        if (raf) return;
        raf = requestAnimationFrame(function () { raf = 0; place(); });
    }

    function onKey(e) {
        if (!root) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); end(); }
        else if (e.key === 'ArrowRight') { e.preventDefault(); e.stopPropagation(); index >= steps.length - 1 ? end() : go(index + 1); }
        else if (e.key === 'ArrowLeft') { e.preventDefault(); e.stopPropagation(); go(index - 1); }
        else if (e.key === 'Tab') {                 // keep the keyboard inside the card
            var btns = [skipBtn, backBtn, nextBtn].filter(function (b) { return !b.hidden; });
            var at = btns.indexOf(document.activeElement);
            e.preventDefault();
            btns[(at + (e.shiftKey ? btns.length - 1 : 1)) % btns.length].focus();
        }
    }

    // ---------------------------------------------------------------- start / end
    function run(list) {
        steps = usable(list);
        if (!steps.length) {
            if (typeof showToast === 'function') showToast('There is no tour for this page yet.', 'info');
            return false;
        }
        returnFocus = document.activeElement;
        build();
        root.classList.add('is-open');
        document.addEventListener('keydown', onKey, true);
        window.addEventListener('resize', onMove);
        window.addEventListener('scroll', onMove, true);
        // the first spot starts as a point at the middle, so it opens by growing out of the screen
        spot.style.transition = 'none';
        spot.style.left = window.innerWidth / 2 + 'px'; spot.style.top = window.innerHeight / 2 + 'px';
        spot.style.width = '0px'; spot.style.height = '0px';
        void spot.offsetWidth;
        spot.style.transition = '';
        go(0);
        return true;
    }

    function end() {
        if (!root) return;
        root.classList.remove('is-open');
        document.removeEventListener('keydown', onKey, true);
        window.removeEventListener('resize', onMove);
        window.removeEventListener('scroll', onMove, true);
        current = null;
        if (returnFocus && returnFocus.focus) { try { returnFocus.focus({ preventScroll: true }); } catch (e) { /* ignore */ } }
    }

    function start(which) {
        if (root && root.classList.contains('is-open')) return;
        var pageSteps = DATA.pages[pageKey()] || [];
        var list;
        if (which === 'shell') list = DATA.shell;
        else if (which === 'welcome') list = pageSteps.concat(DATA.shell);
        else list = pageSteps.length ? pageSteps : DATA.shell;    // the page's own tour, or the menu tour if it has none
        run(list);
    }

    function isHome() { var k = pageKey(); return k === 'borrower/dashboard' || k === 'manage/dashboard'; }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#tourBtn, [data-start-tour]');
        if (!btn) return;
        e.preventDefault();
        start(btn.getAttribute('data-start-tour') || 'page');
    });

    function ready() {
        var params = new URLSearchParams(location.search);
        if (params.get('tour')) {
            params.delete('tour');
            var q = params.toString();
            history.replaceState(null, '', location.pathname + (q ? '?' + q : '') + location.hash);
            setTimeout(function () { start('page'); }, 600);
            return;
        }
        // first visit to the home page: a welcome tour, once per person on this browser
        if (isHome()) {
            var key = 'ce-tour-seen:' + userId();
            try {
                if (!localStorage.getItem(key)) {
                    localStorage.setItem(key, '1');
                    setTimeout(function () { if (!document.querySelector('.modal-overlay.open')) start('welcome'); }, 900);
                }
            } catch (err) { /* storage blocked: no automatic tour */ }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();

    window.CETour = { start: start, end: end };
})();
