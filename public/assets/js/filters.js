/**
 * List tools: filter pills + search + pagination, shared by many pages.
 *
 * Put these attributes on any element (a pill group, a section, or a hidden div):
 *   data-filter-group   (or data-list)  marks it as a list
 *   data-items="CSS"    the rows/cards this list controls                (required)
 *   data-page-size="10" rows per page; leave out for no paging
 *   data-pager="#id"    empty element where the pager is drawn           (needed for paging)
 *   data-pager-always   keep the pager visible even when everything fits on one page
 *   data-pager-style="swipe"  swipe-only paging: no Previous / Next buttons (used by the borrower Equipment Catalog)
 *   data-empty="#id"    element shown when items exist but none match
 *   data-visible="#id"  element whose text becomes "how many rows are shown on this page"
 *   data-total-matches  (optional) the total counts only items matching the active pill
 *   data-total="#id"    element whose text becomes "how many items in total"
 *
 * Pills (optional): <button class="filter-pill" data-filter="x"> inside the element.
 * An item matches a pill when its data-tags (space separated) contains x; "all" matches everything.
 *
 * Paging feel (every paged list in the system):
 *   - Previous / Next buttons (unless data-pager-style="swipe"), plus page dots you can tap
 *   - swipe the list left / right to change page: a two-finger swipe on a laptop trackpad (the list moves with your
 *     fingers; swipe one way for the next page, the other way for the previous one), a finger on a touch screen, or a
 *     mouse drag on card lists. The list resists at the first / last page and springs back if you let go too early
 *     On a table wider than its box the swipe scrolls the table first; the page only changes with a NEW swipe made
 *     once the table is already at its left / right end (a swipe that was scrolling never flips the page itself)
 *   - a small "Swipe" hint under the list (always for swipe-only lists, on touch screens for the others), and a
 *     one-time nudge the first time a swipeable list is on screen
 *   - the new page slides in from the side you swiped toward (CSS: .ce-in-next / .ce-in-prev / .ce-in-fade in shared/motion.css)
 *   - swipe-only lists also answer to the Left / Right arrow keys when the list has focus
 *
 * The search box in the top bar drives every list on the page through
 * CEList.setSearch(text). Items are matched on their visible text.
 */
(function () {
    const lists = [];
    let searchText = '';

    const reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const coarse = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);

    // ---------------------------------------------------------------- small helpers

    function makeButton(label, icon, iconFirst, disabled, onClick) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-outline btn-sm btn-rect';
        const i = document.createElement('i');
        i.className = 'ph ' + icon;
        i.setAttribute('aria-hidden', 'true');
        const t = document.createElement('span');
        t.textContent = label;
        if (iconFirst) b.append(i, t); else b.append(t, i);
        b.disabled = disabled;
        b.addEventListener('click', onClick);
        return b;
    }

    function setText(selector, value) {
        if (!selector) return;
        const el = document.querySelector(selector);
        if (el) el.textContent = value;
    }

    function isSwipeOnly(list) {
        return list.el.dataset.pagerStyle === 'swipe';
    }

    // ---------------------------------------------------------------- page-change animation

    function animateIn(shownItems, kind) {
        const classes = ['ce-in-next', 'ce-in-prev', 'ce-in-fade'];
        shownItems.forEach(function (it) { it.classList.remove.apply(it.classList, classes); });
        void document.body.offsetWidth;   // restart the animation if it is already running
        shownItems.forEach(function (it, i) {
            it.style.setProperty('--ce-i', i);
            it.classList.add('ce-in-' + kind);
            it.addEventListener('animationend', function done(e) {
                if (e.target !== it || String(e.animationName).indexOf('ce-page') !== 0) return;
                it.classList.remove.apply(it.classList, classes);
                it.removeEventListener('animationend', done);
            });
        });
    }

    // ---------------------------------------------------------------- core

    function apply(list, mode) {
        const el = list.el;
        if (mode !== 'next' && mode !== 'prev' && list.zone) { list.zone.style.minHeight = ''; list.maxH = 0; }
        const active = el.querySelector('.filter-pill.active');
        const filter = active ? active.dataset.filter : 'all';
        const items = Array.from(document.querySelectorAll(el.dataset.items));

        const matches = items.filter(function (item) {
            const tags = (item.dataset.tags || '').split(/\s+/);
            const tagOk = filter === 'all' || tags.includes(filter);
            const textOk = searchText === '' || item.textContent.toLowerCase().includes(searchText);
            return tagOk && textOk;
        });

        const size = parseInt(el.dataset.pageSize || '0', 10);
        const pages = size > 0 ? Math.max(1, Math.ceil(matches.length / size)) : 1;
        list.page = Math.min(Math.max(list.page, 1), pages);
        list.pages = pages;
        const start = size > 0 ? (list.page - 1) * size : 0;
        const shownList = size > 0 ? matches.slice(start, start + size) : matches;
        const shown = new Set(shownList);

        items.forEach(function (item) {
            item.style.display = shown.has(item) ? '' : 'none';
        });

        setText(el.dataset.visible, shown.size);
        setText(el.dataset.total, el.dataset.totalMatches !== undefined ? matches.length : items.length);

        if (el.dataset.empty) {
            const empty = document.querySelector(el.dataset.empty);
            if (empty) empty.style.display = (items.length > 0 && matches.length === 0) ? '' : 'none';
        }

        // Animate only when what is on screen really changed (typing in search must not flicker the list).
        const order = new Map(items.map(function (it, i) { return [it, i]; }));
        const sig = shownList.map(function (it) { return order.get(it); }).join(',');
        const firstRun = list.sig === undefined;
        const changed = sig !== list.sig;
        list.sig = sig;
        if (!firstRun && !reduceMotion && mode && (mode === 'next' || mode === 'prev' || changed)) {
            animateIn(shownList, (mode === 'next' || mode === 'prev') ? mode : 'fade');
        }

        if (size > 0 && items.length) prepareSwipe(list, items);
        holdHeight(list);
        drawPager(list, matches.length, size, pages, start);
    }

    // A short last page must not shrink the swipe area: the pointer would end up outside it and the next swipe would
    // do nothing. So the area keeps the height of the tallest page seen (reset whenever the filter / search changes).
    // Lists that scroll inside a fixed-height layout (Handover / Returns) are left alone.
    function holdHeight(list) {
        const zone = list.zone;
        if (!zone || zone.closest('.main-content--fixed')) return;
        list.maxH = Math.max(list.maxH || 0, zone.offsetHeight);
        if (list.maxH > 0) zone.style.minHeight = list.maxH + 'px';
    }

    function goTo(list, page) {
        page = Math.min(Math.max(page, 1), list.pages || 1);
        if (page === list.page) return;
        const dir = page > list.page ? 'next' : 'prev';
        list.page = page;
        apply(list, dir);
    }

    // ---------------------------------------------------------------- pager (buttons + dots + swipe hint)

    function drawPager(list, matchCount, size, pages, start) {
        if (!list.el.dataset.pager) return;
        const pager = document.querySelector(list.el.dataset.pager);
        if (!pager) return;
        pager.textContent = '';
        if (size <= 0 || matchCount === 0) return;
        if (matchCount <= size && list.el.dataset.pagerAlways === undefined) return;

        const swipeOnly = isSwipeOnly(list);
        pager.classList.toggle('pager--swipe', swipeOnly);

        const info = document.createElement('span');
        info.className = 'pager-info';
        info.setAttribute('aria-live', 'polite');
        info.textContent = 'Showing ' + (start + 1) + '–' + Math.min(start + size, matchCount) + ' of ' + matchCount;

        const nav = document.createElement('div');
        nav.className = 'pager-nav';

        if (!swipeOnly) {
            nav.append(makeButton('Previous', 'ph-caret-left', true, list.page <= 1, function () { goTo(list, list.page - 1); }));
        }

        if (pages > 8) {
            const label = document.createElement('span');
            label.className = 'pager-page';
            label.textContent = 'Page ' + list.page + ' of ' + pages;
            nav.append(label);
        } else {
            const dots = document.createElement('div');
            dots.className = 'pager-dots';
            dots.setAttribute('role', 'group');
            dots.setAttribute('aria-label', 'Page ' + list.page + ' of ' + pages);
            for (let p = 1; p <= pages; p++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'pager-dot' + (p === list.page ? ' is-on' : '');
                dot.setAttribute('aria-label', 'Go to page ' + p);
                if (p === list.page) dot.setAttribute('aria-current', 'page');
                dot.addEventListener('click', function () { goTo(list, p); });
                dots.append(dot);
            }
            nav.append(dots);
        }

        if (!swipeOnly) {
            nav.append(makeButton('Next', 'ph-caret-right', false, list.page >= pages, function () { goTo(list, list.page + 1); }));
        }

        pager.append(info, nav);

        // "Swipe" hint: always for swipe-only lists, on touch screens for the others (CSS hides it otherwise).
        if (pages > 1) {
            const hint = document.createElement('span');
            hint.className = 'pager-swipe';
            hint.setAttribute('aria-hidden', 'true');
            hint.innerHTML = '<i class="ph ph-arrows-left-right"></i>';
            hint.append(document.createTextNode('Swipe'));
            pager.append(hint);
        }
    }

    // ---------------------------------------------------------------- swipe

    function zoneFor(items) {
        const first = items[0];
        if (!first) return null;
        if (first.matches('tr')) return first.closest('.table-card') || first.closest('table');
        return first.parentElement;
    }

    function prepareSwipe(list, items) {
        const zone = zoneFor(items);
        if (!zone || list.zone === zone) return;
        list.zone = zone;

        const isTable = items[0].matches('tr');
        const swipeOnly = isSwipeOnly(list);
        zone.classList.add('ce-swipe-zone');
        zone.classList.toggle('ce-swipe-zone--table', isTable);
        zone.classList.toggle('ce-swipe-zone--only', swipeOnly);

        if (swipeOnly) {
            if (!zone.hasAttribute('tabindex')) zone.setAttribute('tabindex', '0');
            zone.setAttribute('role', 'group');
            zone.setAttribute('aria-label', 'Swipe or use the left and right arrow keys to change page');
            zone.addEventListener('keydown', function (e) {
                if (e.target !== zone) return;
                if (e.key === 'ArrowRight') { e.preventDefault(); goTo(list, list.page + 1); }
                if (e.key === 'ArrowLeft')  { e.preventDefault(); goTo(list, list.page - 1); }
            });
        }

        attachSwipe(list, zone, isTable);
        nudgeOnce(list, zone, swipeOnly);
    }

    function attachSwipe(list, zone, isTable) {
        let st = null;
        let suppressClick = false;

        zone.addEventListener('pointerdown', function (e) {
            if ((list.pages || 1) <= 1) return;
            if (e.pointerType === 'mouse' && (e.button !== 0 || isTable)) return;       // tables: no mouse drag (text selection)
            if (e.target.closest('input, select, textarea, label')) return;
            if (e.pointerType === 'mouse' && e.target.closest('button, a')) return;
            if (isTable && zone.scrollWidth > zone.clientWidth + 2) return;             // a table that scrolls sideways keeps its own scrolling
            st = { id: e.pointerId, x: e.clientX, y: e.clientY, t: performance.now(), drag: false, dx: 0 };
        });

        zone.addEventListener('pointermove', function (e) {
            if (!st || e.pointerId !== st.id) return;
            const dx = e.clientX - st.x;
            const dy = e.clientY - st.y;
            if (!st.drag) {
                if (Math.abs(dy) > 10 && Math.abs(dy) > Math.abs(dx)) { st = null; return; }          // a vertical scroll
                if (Math.abs(dx) < 8 || Math.abs(dx) < Math.abs(dy) * 1.4) return;
                st.drag = true;
                zone.classList.add('ce-dragging');
                try { zone.setPointerCapture(e.pointerId); } catch (err) { /* not fatal */ }
            }
            st.dx = dx;
            const atEdge = (dx < 0 && list.page >= list.pages) || (dx > 0 && list.page <= 1);
            zone.style.transform = 'translateX(' + (dx * (atEdge ? 0.25 : 0.6)) + 'px)';    // follows the finger, stiff at the ends
        });

        function end(e) {
            if (!st || e.pointerId !== st.id) return;
            const s = st;
            st = null;
            if (!s.drag) return;

            zone.classList.remove('ce-dragging');
            suppressClick = true;
            setTimeout(function () { suppressClick = false; }, 60);

            const v = s.dx / Math.max(1, performance.now() - s.t);       // px per ms
            const threshold = Math.min(120, zone.clientWidth * 0.18);
            let target = list.page;
            if ((s.dx < -threshold || (v < -0.5 && s.dx < -24)) && list.page < list.pages) target = list.page + 1;
            else if ((s.dx > threshold || (v > 0.5 && s.dx > 24)) && list.page > 1) target = list.page - 1;

            if (target !== list.page) {
                zone.style.transition = 'none';
                zone.style.transform = '';
                goTo(list, target);
                requestAnimationFrame(function () { zone.style.transition = ''; });
            } else {
                zone.style.transform = '';                                // the CSS transition springs it back
            }
        }
        zone.addEventListener('pointerup', end);
        zone.addEventListener('pointercancel', end);

        // ---- Two-finger trackpad swipe (horizontal wheel events) ----
        // Swipe one way = next page, the other way = previous page. Mostly-vertical scrolling is left alone.
        //
        // A table that is wider than its box scrolls sideways first, so every column can be read. The page only changes
        // with a NEW swipe that starts when the table is already at its end in that direction. A swipe that was
        // scrolling the table never carries on into a page change, however far or fast it goes: it stops at the
        // limit, and the next swipe (fingers lifted, momentum finished) flips the page.
        //
        // A swipe leaves "momentum" events behind; they are ignored until things go quiet,
        // so one swipe never skips several pages.
        let wAcc = 0, wLocked = false, wLockedAt = 0, wTimer = null;
        let gLast = 0, gScrolls = false;
        const GESTURE_GAP = 220;                       // ms of quiet that separates one swipe from the next

        // After a page change the swipe is "used up": further events (a long swipe, the momentum tail) are ignored until
        // the trackpad has been quiet for a moment AND the page change has had time to finish.
        function wheelSettle() {
            wTimer = null;
            if (wLocked) {
                const left = 600 - (performance.now() - wLockedAt);
                if (left > 0) { wTimer = setTimeout(wheelSettle, left); return; }
                wLocked = false;
                wAcc = 0;
                return;
            }
            if (wAcc !== 0) {                                   // a small, unfinished swipe: spring back
                wAcc = 0;
                zone.classList.remove('ce-dragging');
                zone.style.transform = '';
            }
        }

        function wheelReset() {
            if (wAcc !== 0) { wAcc = 0; zone.classList.remove('ce-dragging'); zone.style.transform = ''; }
        }

        zone.addEventListener('wheel', function (e) {
            if ((list.pages || 1) <= 1) return;
            // Only a mostly-sideways gesture counts; up / down scrolling and shift + wheel stay with the browser.
            const dx = e.deltaX;
            if (dx === 0 || Math.abs(dx) <= Math.abs(e.deltaY) || e.shiftKey) return;

            // Is this event part of the swipe that is already going on, or the start of a new one?
            const now = performance.now();
            const fresh = now - gLast > GESTURE_GAP;
            gLast = now;

            const hiddenWidth = isTable ? zone.scrollWidth - zone.clientWidth : 0;
            const scrollable = hiddenWidth > 2;
            let canScroll = false;
            if (scrollable) {
                const atEnd = zone.scrollLeft >= hiddenWidth - 1;
                const atStart = zone.scrollLeft <= 0;
                canScroll = dx > 0 ? !atEnd : !atStart;
                if (fresh) gScrolls = canScroll;               // does this swipe begin by scrolling the table?
                else if (canScroll) gScrolls = true;
            } else {
                gScrolls = false;
            }

            if (canScroll && !wLocked) {                       // the table still has hidden columns this way:
                wheelReset();                                   // let the browser scroll it
                return;
            }
            if (scrollable && gScrolls && !wLocked) {          // this swipe just reached the limit:
                wheelReset();                                   // stop here, do not flip in the same swipe
                e.preventDefault();                             // (also keeps the browser's back / forward swipe away)
                return;
            }

            e.preventDefault();                                  // also stops the browser's two-finger "back / forward" swipe

            clearTimeout(wTimer);
            wTimer = setTimeout(wheelSettle, wLocked ? 260 : 140);   // locked: wait for a longer quiet gap
            if (wLocked) return;

            wAcc += dx;
            const atEdge = (wAcc > 0 && list.page >= list.pages) || (wAcc < 0 && list.page <= 1);
            zone.classList.add('ce-dragging');
            zone.style.transform = 'translateX(' + (-wAcc * (atEdge ? 0.2 : 0.5)) + 'px)';

            // a deliberate pull is needed on a table that scrolls, so a stray touch at its end does not flip the page
            const threshold = scrollable ? 140 : Math.min(110, zone.clientWidth * 0.12);
            if (!atEdge && Math.abs(wAcc) > threshold) {
                const forward = wAcc > 0;
                const target = list.page + (forward ? 1 : -1);
                wAcc = 0;
                wLocked = true;
                wLockedAt = performance.now();
                zone.classList.remove('ce-dragging');
                zone.style.transition = 'none';
                zone.style.transform = '';
                goTo(list, target);
                if (isTable) zone.scrollLeft = forward ? 0 : zone.scrollWidth;   // the new page starts at its near edge
                requestAnimationFrame(function () { zone.style.transition = ''; });
            }
        }, { passive: false });

        // a drag must not count as a click on a card or button
        zone.addEventListener('click', function (e) {
            if (suppressClick) { e.preventDefault(); e.stopPropagation(); }
        }, true);
        zone.addEventListener('dragstart', function (e) { e.preventDefault(); });
    }

    // One gentle shove the first time a swipeable list is on screen (swipe-only lists everywhere, others on touch screens).
    function nudgeOnce(list, zone, swipeOnly) {
        if (reduceMotion || !('IntersectionObserver' in window)) return;
        if (!swipeOnly && !coarse) return;
        const key = 'ce-nudge:' + location.pathname + ':' + (list.el.dataset.pager || '');
        try { if (sessionStorage.getItem(key)) return; } catch (err) { /* storage blocked: nudge anyway */ }

        const io = new IntersectionObserver(function (entries) {
            if (!entries.some(function (en) { return en.isIntersecting; })) return;
            io.disconnect();
            if ((list.pages || 1) <= 1) return;
            try { sessionStorage.setItem(key, '1'); } catch (err) { /* ignore */ }
            zone.classList.add('ce-nudge');
            setTimeout(function () { zone.classList.remove('ce-nudge'); }, 1600);
        }, { threshold: 0.4 });
        io.observe(zone);
    }

    // ---------------------------------------------------------------- init

    let preselected = false;

    // Set up every list on the page that is not set up yet, and forget lists that were removed from the page.
    // Runs on load; call CEList.scan() again after a part of the page was replaced (e.g. Export Records results).
    function scan() {
        for (let i = lists.length - 1; i >= 0; i--) {
            if (!lists[i].el.isConnected) lists.splice(i, 1);
        }

        // A link like fines.php?filter=unpaid opens the list with that pill already selected.
        const wanted = new URLSearchParams(window.location.search).get('filter');

        document.querySelectorAll('[data-filter-group], [data-list]').forEach(function (el) {
            if (el._ceList) return;
            const list = { el: el, page: 1, pages: 1 };
            el._ceList = list;
            lists.push(list);

            if (wanted && !preselected) {
                const pill = Array.from(el.querySelectorAll('.filter-pill')).find(function (p) { return p.dataset.filter === wanted; });
                if (pill) {
                    el.querySelectorAll('.filter-pill').forEach(function (p) { p.classList.remove('active'); });
                    pill.classList.add('active');
                    preselected = true;
                }
            }

            el.addEventListener('click', function (e) {
                const pill = e.target.closest('.filter-pill');
                if (!pill || !el.contains(pill)) return;
                el.querySelectorAll('.filter-pill').forEach(function (p) { p.classList.remove('active'); });
                pill.classList.add('active');
                list.page = 1;
                apply(list, 'fade');
            });

            apply(list);
        });
    }

    document.addEventListener('DOMContentLoaded', scan);

    window.CEList = {
        count: function () { return lists.length; },
        scan: scan,
        setSearch: function (text) {
            searchText = (text || '').toLowerCase().trim();
            lists.forEach(function (list) { list.page = 1; apply(list, 'fade'); });
        },
        refresh: function () { lists.forEach(function (list) { apply(list); }); }
    };

    // Older name, kept so existing pages keep working (call after rows are removed/added).
    window.refreshFilters = window.CEList.refresh;
})();
