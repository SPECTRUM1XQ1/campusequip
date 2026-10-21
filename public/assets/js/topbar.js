/**
 * Top bar: page search (liveSearch) and the notification bell.
 * Loaded by includes/footer.php. The signed-in user id and role come from data-user-id / data-is-staff
 * on #bellBtn (see includes/topbar.php), so this file contains no PHP.
 */

// ---------- Page search ----------
// Pages with a paged list (assets/js/filters.js) are searched through it;
// other pages (catalog, saved items...) just hide the non-matching cards.
function liveSearch() {
    const text = document.getElementById('searchInput').value.toLowerCase().trim();

    if (window.CEList && CEList.count() > 0) {
        CEList.setSearch(text);
        return;
    }
    document.querySelectorAll('.item-card').forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(text) ? '' : 'none';
    });
}

// ---------- Notifications ----------
(function () {
    const bellBtn = document.getElementById('bellBtn');
    const SEEN_KEY = 'ce_notif_seen_' + (bellBtn.dataset.userId || '0');
    const IS_STAFF = bellBtn.dataset.isStaff === '1';
    let items = [];

    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const subtitle = document.getElementById('notifSubtitle');

    const lastSeen = () => parseInt(localStorage.getItem(SEEN_KEY) || '0', 10);
    const unreadCount = () => items.filter(i => i.ts > lastSeen()).length;

    function timeAgo(ts) {
        const diff = Math.floor(Date.now() / 1000) - ts;
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
        if (diff < 7 * 86400) return Math.floor(diff / 86400) + ' day(s) ago';
        return new Date(ts * 1000).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function paintBadge() {
        const n = unreadCount();
        badge.textContent = n > 9 ? '9+' : n;
        badge.hidden = n === 0;
    }

    function render() {
        const seen = lastSeen();
        const n = unreadCount();
        subtitle.textContent = items.length === 0
            ? 'Nothing new right now.'
            : (n === 0 ? 'You are all caught up.' : 'You have ' + n + ' unread update' + (n === 1 ? '' : 's') + '.');

        list.textContent = '';
        if (items.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'notif-empty';
            empty.textContent = IS_STAFF
                ? 'New requests, ready pick-ups, overdue returns and recent activity will show up here.'
                : 'Updates about your requests and equipment will show up here.';
            list.appendChild(empty);
            return;
        }

        items.forEach(item => {
            const row = document.createElement(item.href ? 'a' : 'div');
            row.className = 'notif-item notif-' + item.tone + (item.ts > seen ? ' notif-unread' : '');
            if (item.href) row.href = item.href;

            const icon = document.createElement('i');
            icon.className = 'ph ' + item.icon + ' notif-icon';
            icon.setAttribute('aria-hidden', 'true');

            const body = document.createElement('div');
            const title = document.createElement('h4');
            title.textContent = item.title;
            const msg = document.createElement('p');
            msg.textContent = item.message;
            const when = document.createElement('span');
            when.className = 'notif-time';
            when.textContent = timeAgo(item.ts);
            body.append(title, msg, when);

            row.append(icon, body);
            list.appendChild(row);
        });
    }

    async function load() {
        try {
            const res = await fetch(APP_BASE + '/api/notifications/list.php', { cache: 'no-store' });
            const data = await res.json();
            if (data.success) {
                items = data.items;
                paintBadge();
                if (document.getElementById('notificationModal').classList.contains('open')) render();
            }
        } catch (e) { /* offline or logged out: keep the old list */ }
    }

    // Opening the bell shows what is unread; closing it marks everything as read.
    bellBtn.addEventListener('click', () => { render(); load(); });

    document.addEventListener('DOMContentLoaded', () => {
        const originalClose = window.closeModal;
        window.closeModal = function (id) {
            originalClose(id);
            if (id === 'notificationModal' && items.length) {
                localStorage.setItem(SEEN_KEY, String(Math.max(...items.map(i => i.ts))));
                paintBadge();
            }
        };
    });

    load();
    setInterval(() => { if (document.visibilityState === 'visible') load(); }, 60000);
})();

// ---- Search box (replaces the inline on* attributes that used to be in the PHP) ----
const searchInputEl = document.getElementById('searchInput');
if (searchInputEl) searchInputEl.addEventListener('keyup', liveSearch);
