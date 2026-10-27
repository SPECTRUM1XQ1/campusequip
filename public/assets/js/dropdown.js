/**
 * Styled dropdown. Add data-ce-dropdown to a <select> and it becomes a button + a list of choices
 * (with a search box when there are many choices, a tick on the chosen one, keyboard control).
 * The <select> stays in the page and keeps the value, so forms and "change" listeners work unchanged.
 *
 *   CEDropdown.init(root)   turn the selects inside root into dropdowns (done automatically on load)
 *   CEDropdown.sync(root)   re-read the selects (call after setting select.value from code, e.g. after a form reset)
 */
(function () {
    var SEARCH_FROM = 8;          // show the search box from this many choices
    var openOne = null;
    var uid = 0;

    function make(tag, cls) {
        var el = document.createElement(tag);
        if (cls) el.className = cls;
        return el;
    }

    function icon(name) {
        var i = make('i', 'ph ' + name);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    function textOf(opt) { return opt.text.replace(/\s+/g, ' ').trim(); }

    function build(select) {
        if (select._ceDd) return;
        uid++;

        var wrap = make('div', 'ce-dd');
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('ce-dd-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        var btn = make('button', 'ce-dd-btn');
        btn.type = 'button';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        var label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        if (label) {
            if (!label.id) label.id = 'ce-dd-label-' + uid;
            btn.setAttribute('aria-labelledby', label.id);
            label.addEventListener('click', function (e) { e.preventDefault(); btn.focus(); });
        }
        var text = make('span', 'ce-dd-text');
        btn.append(text, icon('ph-caret-down ce-dd-caret'));

        var panel = make('div', 'ce-dd-panel');
        panel.hidden = true;
        var search = null;
        if (select.options.length >= SEARCH_FROM) {
            var box = make('div', 'ce-dd-search');
            search = make('input');
            search.type = 'text';
            search.placeholder = 'Search...';
            search.setAttribute('aria-label', 'Search choices');
            search.autocomplete = 'off';
            box.append(icon('ph-magnifying-glass'), search);
            panel.appendChild(box);
        }
        var list = make('ul', 'ce-dd-list');
        list.setAttribute('role', 'listbox');
        list.tabIndex = -1;
        var empty = make('p', 'ce-dd-empty');
        empty.textContent = 'No matches';
        empty.hidden = true;
        panel.append(list, empty);

        var items = [];
        Array.prototype.forEach.call(select.options, function (opt) {
            var li = make('li', 'ce-dd-opt' + (opt.value === '' ? ' is-placeholder' : ''));
            li.setAttribute('role', 'option');
            li.dataset.value = opt.value;
            var t = make('span', 'ce-dd-opt-text');
            t.textContent = textOf(opt);
            t.title = t.textContent;
            li.append(t, icon('ph-check ce-dd-check'));
            li.addEventListener('mousedown', function (e) { e.preventDefault(); });          // keep focus where it is
            li.addEventListener('click', function () { choose(li.dataset.value); });
            li.addEventListener('mousemove', function () { setActive(li, false); });
            list.appendChild(li);
            items.push(li);
        });

        wrap.append(btn, panel);

        var dd = { select: select, wrap: wrap, btn: btn, panel: panel, list: list, search: search, items: items, text: text, empty: empty };
        select._ceDd = dd;

        function visible() { return items.filter(function (li) { return !li.hidden; }); }

        function setActive(li, scroll) {
            items.forEach(function (x) { x.classList.toggle('is-active', x === li); });
            if (li && scroll) li.scrollIntoView({ block: 'nearest' });
        }

        function choose(value) {
            if (select.value !== value) {
                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            sync(select);
            close(true);
        }

        function filter() {
            var q = search ? search.value.trim().toLowerCase() : '';
            items.forEach(function (li) { li.hidden = q !== '' && li.textContent.toLowerCase().indexOf(q) === -1; });
            var shown = visible();
            empty.hidden = shown.length > 0;
            setActive(shown[0] || null, true);
        }

        function open() {
            if (select.disabled || openOne === dd) return;
            if (openOne) openOne.close(false);
            if (search) { search.value = ''; filter(); }
            panel.hidden = false;
            wrap.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            // open upward when there is no room below
            var r = btn.getBoundingClientRect();
            var need = panel.offsetHeight + 16;
            wrap.classList.toggle('is-up', window.innerHeight - r.bottom < need && r.top > need);
            var chosen = items.filter(function (li) { return li.dataset.value === select.value; })[0];
            setActive(chosen || visible()[0] || null, true);
            if (search) search.focus();
            openOne = dd;
        }

        function close(returnFocus) {
            if (panel.hidden) return;
            panel.hidden = true;
            wrap.classList.remove('is-open', 'is-up');
            btn.setAttribute('aria-expanded', 'false');
            if (openOne === dd) openOne = null;
            if (returnFocus) btn.focus();
        }

        function move(step) {
            var shown = visible();
            if (!shown.length) return;
            var at = shown.indexOf(items.filter(function (li) { return li.classList.contains('is-active'); })[0]);
            var next = at === -1 ? (step > 0 ? 0 : shown.length - 1) : Math.min(shown.length - 1, Math.max(0, at + step));
            setActive(shown[next], true);
        }

        function onKey(e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Home') { e.preventDefault(); setActive(visible()[0] || null, true); }
            else if (e.key === 'End') { e.preventDefault(); var s = visible(); setActive(s[s.length - 1] || null, true); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                var a = items.filter(function (li) { return li.classList.contains('is-active') && !li.hidden; })[0];
                if (a) choose(a.dataset.value);
            }
            else if (e.key === 'Escape') { e.preventDefault(); close(true); }
            else if (e.key === 'Tab') { close(false); }
        }

        btn.addEventListener('click', function () { panel.hidden ? open() : close(true); });
        btn.addEventListener('keydown', function (e) {
            if (!panel.hidden) { onKey(e); return; }
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
        });
        panel.addEventListener('keydown', onKey);
        if (search) search.addEventListener('input', filter);
        select.addEventListener('change', function () { sync(select); });

        dd.close = close;
        sync(select);
    }

    function sync(select) {
        var dd = select._ceDd;
        if (!dd) return;
        var opt = select.options[select.selectedIndex];
        var label = opt ? textOf(opt) : '';
        dd.text.textContent = label;
        dd.btn.dataset.empty = select.value === '' ? '1' : '0';
        dd.btn.title = label.length > 24 ? label : '';
        dd.btn.disabled = select.disabled;
        dd.items.forEach(function (li) {
            var on = li.dataset.value === select.value;
            li.classList.toggle('is-selected', on);
            li.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function each(root, fn) {
        (root || document).querySelectorAll('select[data-ce-dropdown]').forEach(fn);
    }

    document.addEventListener('pointerdown', function (e) {
        if (openOne && !openOne.wrap.contains(e.target)) openOne.close(false);
    });

    window.CEDropdown = {
        init: function (root) { each(root, build); },
        sync: function (root) { each(root, sync); }
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { window.CEDropdown.init(); });
    else window.CEDropdown.init();
})();
