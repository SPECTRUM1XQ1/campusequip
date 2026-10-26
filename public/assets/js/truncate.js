/**
 * Tooltip for cut text: when the mouse rests on something that is shown with "..." (see css/shared/truncate.css),
 * the full text appears as the browser tooltip. Nothing is measured up front, so lists that show new pages,
 * filters and search results all work without extra code. Loaded by includes/navigation/footer.php.
 */
(function () {
    var CUT = [
        '.text-truncate', '.text-clamp-2', '.text-clamp-3',
        '.meta-value', '.meta-sub', '.item-card h5', '.fine-stat-value', '.fines-board-amount', '.fines-board-stat strong',
        '.saved-card-info h3', '.saved-card-info p', '.borrower-catalog-s7', '.borrower-catalog-s8', '.borrower-catalog-s9',
        '.loan-card-title', '.loan-row .value', '.catalog-item-card .section-title',
        '.user-details h4', '.user-details p', '.results-count', '.data-table td'
    ].join(',');

    document.addEventListener('mouseover', function (e) {
        var t = e.target;
        if (!t || !t.closest) return;

        // A closed dropdown: its tooltip is the choice that is selected.
        var sel = t.closest('select.input-field');
        if (sel) {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.text.length > 18) sel.title = opt.text.trim(); else sel.removeAttribute('title');
            return;
        }

        var el = t.closest(CUT);
        if (!el) return;

        var cut = el.scrollWidth > el.clientWidth + 1 || el.scrollHeight > el.clientHeight + 1;
        if (cut) {
            if (!el.title || el.dataset.ceTip === '1') {
                el.title = el.innerText.replace(/\s*\n\s*/g, ' · ').trim();
                el.dataset.ceTip = '1';
            }
        } else if (el.dataset.ceTip === '1') {       // the text changed and fits now: drop our tooltip
            el.removeAttribute('title');
            delete el.dataset.ceTip;
        }
    }, true);
})();
