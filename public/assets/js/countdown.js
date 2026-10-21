/**
 * Live pick-up countdowns.
 *
 * <span class="pickup-countdown" data-open="2026-10-16T00:00:00" data-close="2026-10-19T00:00:00">
 *   data-on-expire="remove"     remove the surrounding .request-card when the window ends (staff pages)
 *   data-expired-text="..."     text to show once the window has ended
 *
 * <span class="due-countdown" data-due="2026-10-19T17:00:00">   (Active Returns cards)
 *   counts down to the due date ("Due in 2d 04:12:30", amber under 6 hours), then shows how long the
 *   item is overdue ("Overdue by 1 day 4h") and marks the card as overdue for the filter pills.
 *
 * The date values have no timezone, so the browser reads them as local time.
 */
(function () {
    const els = Array.from(document.querySelectorAll('.pickup-countdown[data-open][data-close]'));
    const dueEls = Array.from(document.querySelectorAll('.due-countdown[data-due]'));
    if (!els.length && !dueEls.length) return;

    const pad = (n) => String(n).padStart(2, '0');

    function span(ms) {
        let s = Math.max(0, Math.floor(ms / 1000));
        const d = Math.floor(s / 86400); s %= 86400;
        const h = Math.floor(s / 3600);  s %= 3600;
        const m = Math.floor(s / 60);    s %= 60;
        return (d > 0 ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
    }

    // How long past the due date: "1 day 4h", "5h 12m", "12 min".
    function overdueText(ms) {
        const mins = Math.floor(ms / 60000);
        const d = Math.floor(mins / 1440);
        const h = Math.floor((mins % 1440) / 60);
        const m = mins % 60;
        if (d >= 1) return d + (d === 1 ? ' day' : ' days') + (h ? ' ' + h + 'h' : '');
        if (h >= 1) return h + 'h ' + m + 'm';
        if (m >= 1) return m + ' min';
        return 'less than a minute';
    }

    // Take an expired card off the staff list and keep the "N ready" count honest.
    function removeCard(el) {
        const card = el.closest('.request-card');
        if (!card) return;
        const box = card.parentElement;
        card.remove();

        const left = box.querySelectorAll('.request-card').length;
        const counter = document.querySelector('[data-ready-count]');
        if (counter) counter.textContent = left + ' ready';
        if (left === 0) {
            const empty = document.createElement('div');
            empty.className = 'card-container text-muted';
            empty.style.cssText = 'text-align: center; padding: 2rem;';
            empty.textContent = 'No items pending handover.';
            box.appendChild(empty);
        }
    }

    function tickDue(now) {
        dueEls.forEach((el) => {
            const due = new Date(el.dataset.due).getTime();
            el.classList.remove('is-open', 'is-soon', 'is-expired');

            if (now < due) {
                const left = due - now;
                el.classList.add(left <= 6 * 3600 * 1000 ? 'is-soon' : 'is-open');
                el.textContent = 'Due in ' + span(left);
            } else {
                el.classList.add('is-expired');
                el.textContent = 'Overdue by ' + overdueText(now - due);
                // The card just turned overdue while the page was open: keep the filter pills honest.
                const card = el.closest('.request-card');
                if (card && card.dataset.tags !== 'overdue') card.dataset.tags = 'overdue';
            }
        });
    }

    function tick() {
        const now = Date.now();
        tickDue(now);
        els.forEach((el) => {
            if (!el.isConnected) return;
            const open = new Date(el.dataset.open).getTime();
            const close = new Date(el.dataset.close).getTime();
            el.classList.remove('is-open', 'is-soon', 'is-expired');

            if (now < open) {
                el.textContent = 'Opens in ' + span(open - now);
            } else if (now < close) {
                const left = close - now;
                el.classList.add(left <= 6 * 3600 * 1000 ? 'is-soon' : 'is-open');
                el.textContent = 'Closes in ' + span(left);
            } else if (el.dataset.onExpire === 'remove') {
                removeCard(el);
            } else {
                el.classList.add('is-expired');
                el.textContent = el.dataset.expiredText || 'Pick-up window ended';
            }
        });
    }

    tick();
    setInterval(tick, 1000);
})();
