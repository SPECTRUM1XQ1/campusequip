/**
 * Admin > Export Records.
 *  - Filters apply by themselves (no Apply button): the results block is reloaded in place with fetch(),
 *    so the filter panel never closes or resets while the admin is working.
 *  - The panel's open/closed state is remembered for this browser tab.
 *  - Pagination links reload the results block the same way.
 *  - Print opens the printable report in a new tab; Excel always downloads every match.
 * Without JavaScript the page still works: changing a filter is just a normal GET request.
 */
(function () {
    const form = document.getElementById('exportFilters');
    const results = document.getElementById('exportResults');
    if (!form || !results) return;

    const toggle = document.getElementById('exportFilterToggle');
    const countBadge = document.getElementById('exportFilterCount');
    const clearBtn = document.getElementById('exportClear');
    const excelBtn = document.getElementById('exportExcel');
    const printBtn = document.getElementById('exportPrint');
    const OPEN_KEY = 'ce_export_filters_open';
    let controller = null;
    let page = 1;

    // ---------- Filter panel open / close (remembered) ----------
    function setOpen(open) {
        form.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        try { sessionStorage.setItem(OPEN_KEY, open ? '1' : '0'); } catch (e) { /* storage blocked: fine */ }
    }
    let saved = null;
    try { saved = sessionStorage.getItem(OPEN_KEY); } catch (e) { /* ignore */ }
    setOpen(saved !== '0');
    toggle.addEventListener('click', () => setOpen(form.hidden));

    // Closed dropdowns cut long names with "..."; show the full name as a tooltip.
    function setTitles() {
        form.querySelectorAll('select').forEach(sel => {
            const opt = sel.options[sel.selectedIndex];
            sel.title = opt ? opt.text : '';
        });
    }
    setTitles();
    form.addEventListener('change', setTitles);

    // ---------- Build the query string from the form ----------
    function query(extra) {
        const params = new URLSearchParams();
        new FormData(form).forEach((value, key) => {
            if (String(value).trim() !== '') params.set(key, value);
        });
        Object.keys(extra || {}).forEach(k => params.set(k, extra[k]));
        return params;
    }

    function activeCount() {
        let n = 0;
        new FormData(form).forEach(value => { if (String(value).trim() !== '') n++; });
        return n;
    }

    function syncChrome() {
        const n = activeCount();
        countBadge.textContent = n;
        countBadge.hidden = n === 0;
        clearBtn.hidden = n === 0;
        excelBtn.href = '?' + query({ format: 'xlsx' }).toString();
        printBtn.href = '?' + query({ print: '1' }).toString();
    }

    // ---------- Reload the results block in place ----------
    async function load(pageNo) {
        page = pageNo || 1;
        const params = query(page > 1 ? { page: page } : {});
        const url = '?' + params.toString();

        if (controller) controller.abort();
        controller = new AbortController();
        results.classList.add('is-loading');

        try {
            const res = await fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'fetch' } });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('exportResults');
            if (!fresh) throw new Error('bad response');
            results.innerHTML = fresh.innerHTML;
            history.replaceState(null, '', url === '?' ? location.pathname : url);
            syncChrome();
        } catch (e) {
            if (e.name === 'AbortError') return;
            window.location.href = url; // fall back to a normal page load
        } finally {
            results.classList.remove('is-loading');
        }
    }

    // ---------- Auto-apply ----------
    function swapDatesIfBackwards() {
        const from = form.elements.from, to = form.elements.to;
        if (from.value && to.value && from.value > to.value) {
            const t = from.value; from.value = to.value; to.value = t;
        }
    }
    form.addEventListener('change', () => { swapDatesIfBackwards(); syncChrome(); load(1); });
    form.addEventListener('submit', e => { e.preventDefault(); load(1); });

    clearBtn.addEventListener('click', e => {
        e.preventDefault();
        form.reset();
        Array.from(form.elements).forEach(el => { if (el.name) el.value = ''; });
        syncChrome();
        load(1);
    });

    // ---------- Pagination ----------
    results.addEventListener('click', e => {
        const link = e.target.closest('a[data-page]');
        if (!link) return;
        e.preventDefault();
        load(parseInt(link.dataset.page, 10) || 1);
        const top = document.getElementById('exportPage');
        if (top) top.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    syncChrome();
})();
