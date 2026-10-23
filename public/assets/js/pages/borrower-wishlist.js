/**
 * Saved Items page: remove / clear / select, reserve a saved item.
 * Loaded by public/borrower/wishlist.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

// ---- Remove / Clear All (one confirm modal, different action) ----
let pendingRemove = null; // { message, run: async () => response }

function askRemove(message, run) {
    pendingRemove = { message, run };
    document.getElementById('remove-message').textContent = message;
    openModal('removeWishlistModal');
}

document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        askRemove('Remove ' + btn.dataset.model + ' from your saved items?', () => {
            const fd = new FormData();
            fd.append('catalog_id', btn.dataset.id);
            return postForm(APP_BASE + '/api/wishlist/remove.php', fd);
        });
    });
});

const clearBtn = document.getElementById('clear-all-btn');
if (clearBtn) {
    clearBtn.addEventListener('click', () => {
        askRemove('Remove ALL items from your saved items?', () => postForm(APP_BASE + '/api/wishlist/clear.php', new FormData()));
    });
}

document.getElementById('remove-confirm-btn').addEventListener('click', async function () {
    if (!pendingRemove) return;
    this.disabled = true;
    try {
        const data = await pendingRemove.run();
        if (data.success) {
            showToast('Saved items updated.', 'success');
            closeModal('removeWishlistModal');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message || 'Could not update your saved items.', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        this.disabled = false;
    }
});

// ---- Select Items: tick several, then remove them together ----
const selectBtn = document.getElementById('select-btn');
const selectCancelBtn = document.getElementById('select-cancel-btn');
let selecting = false;

function selectedIds() {
    return Array.from(document.querySelectorAll('.select-check:checked')).map(c => c.value);
}

function refreshSelectUi() {
    document.querySelectorAll('.select-box').forEach(b => b.style.display = selecting ? 'flex' : '');
    selectCancelBtn.hidden = !selecting;
    clearBtn.style.display = selecting ? 'none' : '';
    if (!selecting) {
        selectBtn.textContent = 'Select Items';
        selectBtn.className = 'btn btn-primary btn-rect';
        document.querySelectorAll('.select-check').forEach(c => c.checked = false);
    } else {
        const n = selectedIds().length;
        selectBtn.textContent = 'Remove Selected (' + n + ')';
        selectBtn.className = 'btn btn-danger btn-rect';
    }
}

if (selectBtn) {
    selectBtn.addEventListener('click', () => {
        if (!selecting) {
            selecting = true;
            refreshSelectUi();
            return;
        }
        const ids = selectedIds();
        if (ids.length === 0) {
            showToast('Tick at least one item to remove.', 'info');
            return;
        }
        askRemove('Remove ' + ids.length + (ids.length === 1 ? ' item' : ' items') + ' from your saved items?', () => {
            const fd = new FormData();
            ids.forEach(id => fd.append('catalog_ids[]', id));
            return postForm(APP_BASE + '/api/wishlist/remove.php', fd);
        });
    });
    selectCancelBtn.addEventListener('click', () => { selecting = false; refreshSelectUi(); });
    document.querySelectorAll('.select-check').forEach(c => c.addEventListener('change', refreshSelectUi));
}

// ---- Reserve a saved item ----
const toLocalDate = (d) =>
    d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

document.querySelectorAll('.reserve-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('res_catalog_id').value = btn.dataset.id;
        document.getElementById('res-model-name').textContent = btn.dataset.model;
        document.getElementById('res-max-days').textContent = 'Up to ' + btn.dataset.days + ' Days';

        // Built from local date parts: toISOString() is UTC and can return yesterday.
        const now = new Date();
        const next = new Date(now);
        next.setDate(next.getDate() + 1);
        const start = document.getElementById('res_start_at');
        const end = document.getElementById('res_end_at');
        start.min = toLocalDate(now);
        start.value = toLocalDate(now);
        end.min = toLocalDate(next);
        end.value = toLocalDate(next);
        openModal('reserveModal');
    });
});

document.getElementById('reserve-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('reserve-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Submitting...');
    try {
        const data = await postForm(APP_BASE + '/api/reservation/create.php', new FormData(this));
        if (data.success) {
            showToast('Reservation submitted for staff approval!', 'success');
            closeModal('reserveModal');
            setTimeout(() => window.location.href = APP_BASE + '/public/borrower/my_requests.php#pending', 900);
        } else {
            showToast(data.message || 'Could not submit reservation', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Submit');
    }
});
