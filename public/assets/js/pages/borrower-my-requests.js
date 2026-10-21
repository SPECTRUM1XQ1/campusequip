/**
 * Borrowing & Requests page: tabs + cancel-request modal.
 * Loaded by public/borrower/my_requests.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

function switchTab(tabId) {
    ['borrowed', 'pending', 'history'].forEach(id => {
        document.getElementById('content-' + id).hidden = (id !== tabId);
        const btn = document.getElementById('tab-' + id);
        btn.classList.toggle('active', id === tabId);
        btn.setAttribute('aria-selected', id === tabId ? 'true' : 'false');
    });
}
switchTab('borrowed');

// Open the tab named in the URL (e.g. my_requests.php#pending).
const startTab = location.hash.replace('#', '');
if (['borrowed', 'pending', 'history'].includes(startTab)) {
    switchTab(startTab);
}

// Cancel a pending / approved request
let cancelId = null;
document.querySelectorAll('.cancel-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        cancelId = btn.dataset.id;
        document.getElementById('cancel-message').textContent =
            'Are you sure you want to cancel your request for ' + btn.dataset.model + '?';
        openModal('cancelModal');
    });
});

document.getElementById('cancel-confirm-btn').addEventListener('click', async function () {
    if (!cancelId) return;
    this.disabled = true;
    this.textContent = 'Cancelling...';

    const fd = new FormData();
    fd.append('reservation_id', cancelId);
    try {
        const res = await fetch(APP_BASE + '/api/reservation/cancel.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Request cancelled.', 'success');
            closeModal('cancelModal');
            setTimeout(() => { location.hash = 'history'; location.reload(); }, 700);
        } else {
            showToast(data.message || 'Could not cancel the request.', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        this.disabled = false;
        this.textContent = 'Yes, Cancel';
    }
});

// ---- Tab buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const tab = e.target.closest('[data-tab]');
    if (tab) switchTab(tab.dataset.tab);
});
