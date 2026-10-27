/**
 * Admin > Export Records (the transaction log).
 *  - Filters apply by themselves (no Apply button): the results block is reloaded in place with fetch(),
 *    so the filter panel never closes or resets while the admin is working.
 *  - Borrower and Equipment are styled dropdowns (dropdown.js); Status is a row of choices (pills), like the
 *    filters on the other pages.
 *  - The panel's open/closed state is remembered for this browser tab.
 *  - The table is cut into pages in the browser by the shared pager (filters.js): Previous / Next, page dots,
 *    swipe. After each reload the new table is handed to CEList.scan().
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
    const statusInput = document.getElementById('f-status');
    const statusPills = document.getElementById('exportStatusPills');
    const OPEN_KEY = 'ce_export_filters_open';
    let controller = null;

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

    // ---------- Status choices (pills) ----------
    function showStatus() {
        statusPills.querySelectorAll('.filter-pill').forEach(p => {
            const on = p.dataset.value === statusInput.value;
            p.classList.toggle('active', on);
            p.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }
    statusPills.addEventListener('click', e => {
        const pill = e.target.closest('.filter-pill');
        if (!pill || pill.dataset.value === statusInput.value) return;
        statusInput.value = pill.dataset.value;
        showStatus();
        statusInput.dispatchEvent(new Event('change', { bubbles: true }));   // same path as every other filter
    });
    showStatus();

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
        toggle.classList.toggle('has-filters', n > 0);
        clearBtn.hidden = n === 0;
        excelBtn.href = '?' + query({ format: 'xlsx' }).toString();
        printBtn.href = '?' + query({ print: '1' }).toString();
    }

    // ---------- Reload the results block in place ----------
    async function load() {
        const url = '?' + query().toString();

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
            if (window.CEList) window.CEList.scan();      // the new table gets its pager, dots and swipe
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
    form.addEventListener('change', () => { swapDatesIfBackwards(); syncChrome(); load(); });
    form.addEventListener('submit', e => { e.preventDefault(); load(); });

    clearBtn.addEventListener('click', e => {
        e.preventDefault();
        form.reset();
        Array.from(form.elements).forEach(el => { if (el.name) el.value = ''; });
        showStatus();
        if (window.CEDropdown) window.CEDropdown.sync(form);
        syncChrome();
        load();
    });

    syncChrome();
})();
