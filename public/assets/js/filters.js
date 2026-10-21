/**
 * List tools: filter pills + search + pagination, shared by many pages.
 *
 * Put these attributes on any element (a pill group, a section, or a hidden div):
 *   data-filter-group   (or data-list)  marks it as a list
 *   data-items="CSS"    the rows/cards this list controls                (required)
 *   data-page-size="10" rows per page; leave out for no paging
 *   data-pager="#id"    empty element where Prev / Next is drawn         (needed for paging)
 *   data-pager-always   keep Previous / Next visible even when everything fits on one page
 *   data-empty="#id"    element shown when items exist but none match
 *   data-visible="#id"  element whose text becomes "how many rows are shown on this page"
 *   data-total-matches  (optional) the total counts only items matching the active pill
 *   data-total="#id"    element whose text becomes "how many items in total"
 *
 * Pills (optional): <button class="filter-pill" data-filter="x"> inside the element.
 * An item matches a pill when its data-tags (space separated) contains x; "all" matches everything.
 *
 * The search box in the top bar drives every list on the page through
 * CEList.setSearch(text). Items are matched on their visible text.
 */
(function () {
    const lists = [];
    let searchText = '';

    function makeButton(label, disabled, onClick) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-outline btn-sm btn-rect';
        b.textContent = label;
        b.disabled = disabled;
        b.addEventListener('click', onClick);
        return b;
    }

    function setText(selector, value) {
        if (!selector) return;
        const el = document.querySelector(selector);
        if (el) el.textContent = value;
    }

    function apply(list) {
        const el = list.el;
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
        const start = size > 0 ? (list.page - 1) * size : 0;
        const shown = new Set(size > 0 ? matches.slice(start, start + size) : matches);

        items.forEach(function (item) {
            item.style.display = shown.has(item) ? '' : 'none';
        });

        setText(el.dataset.visible, shown.size);
        setText(el.dataset.total, el.dataset.totalMatches !== undefined ? matches.length : items.length);

        if (el.dataset.empty) {
            const empty = document.querySelector(el.dataset.empty);
            if (empty) empty.style.display = (items.length > 0 && matches.length === 0) ? '' : 'none';
        }

        drawPager(list, matches.length, size, pages, start);
    }

    function drawPager(list, matchCount, size, pages, start) {
        if (!list.el.dataset.pager) return;
        const pager = document.querySelector(list.el.dataset.pager);
        if (!pager) return;
        pager.textContent = '';
        if (size <= 0 || matchCount === 0) return;
        if (matchCount <= size && list.el.dataset.pagerAlways === undefined) return;

        const info = document.createElement('span');
        info.className = 'pager-info';
        info.textContent = 'Showing ' + (start + 1) + '–' + Math.min(start + size, matchCount) + ' of ' + matchCount;

        const nav = document.createElement('div');
        nav.className = 'pager-nav';
        const label = document.createElement('span');
        label.className = 'pager-page';
        label.textContent = 'Page ' + list.page + ' of ' + pages;
        nav.append(
            makeButton('Previous', list.page <= 1, function () { list.page--; apply(list); }),
            label,
            makeButton('Next', list.page >= pages, function () { list.page++; apply(list); })
        );

        pager.append(info, nav);
    }

    function init() {
        document.querySelectorAll('[data-filter-group], [data-list]').forEach(function (el) {
            const list = { el: el, page: 1 };
            lists.push(list);

            el.addEventListener('click', function (e) {
                const pill = e.target.closest('.filter-pill');
                if (!pill || !el.contains(pill)) return;
                el.querySelectorAll('.filter-pill').forEach(function (p) { p.classList.remove('active'); });
                pill.classList.add('active');
                list.page = 1;
                apply(list);
            });

            apply(list);
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    window.CEList = {
        count: function () { return lists.length; },
        setSearch: function (text) {
            searchText = (text || '').toLowerCase().trim();
            lists.forEach(function (list) { list.page = 1; apply(list); });
        },
        refresh: function () { lists.forEach(apply); }
    };

    // Older name, kept so existing pages keep working (call after rows are removed/added).
    window.refreshFilters = window.CEList.refresh;
})();
