/**
 * Serialize Item page: add unit / edit unit modals.
 * Loaded by public/manage/serialized.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

// ---- Edit unit ----
const EDITABLE_STATUSES = ['available', 'maintenance', 'retired'];
const statusLabel = s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ');

function openEditUnitModal(btn) {
    const d = btn.dataset;
    document.getElementById('eu_item_id').value = d.itemId;
    document.getElementById('eu-model-name').textContent = d.modelName;
    document.getElementById('eu_serial').value  = d.serial;
    document.getElementById('eu_storage').value = d.storage;
    document.getElementById('eu_notes').value   = d.notes;

    // Out on loan: serial + status are locked (storage/notes can still be corrected).
    const onLoan = (d.status === 'checked_out');
    const statusLocked = !EDITABLE_STATUSES.includes(d.status);
    document.getElementById('eu_serial').readOnly = onLoan;

    const sel = document.getElementById('eu_status');
    sel.replaceChildren();
    (statusLocked ? [d.status] : EDITABLE_STATUSES).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.textContent = statusLabel(s);
        opt.selected = (s === d.status);
        sel.appendChild(opt);
    });
    sel.disabled = statusLocked; // a disabled field is not submitted, so the server keeps the status

    const note = document.getElementById('eu-lock-note');
    note.style.display = onLoan ? 'block' : 'none';
    note.textContent = onLoan ? 'This unit is checked out. Serial number and status are locked until it is returned.' : '';

    openModal('editUnitModal');
}

document.getElementById('edit-unit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('edit-unit-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Saving...');
    try {
        const res = await fetch(APP_BASE + '/api/catalog/edit_unit.php', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.success) {
            showToast('Unit updated!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to update unit', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Save Changes');
    }
});

document.getElementById('add-unit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('add-unit-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Saving...');

    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/catalog/add_unit.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Unit successfully registered!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to register unit', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Register Unit');
    }
});

// ---- Edit-unit buttons and model filter (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-unit-action="edit"]');
    if (btn) openEditUnitModal(btn);
});

document.addEventListener('change', (e) => {
    const select = e.target.closest('[data-filter-catalog]');
    if (select) {
        location.href = 'serialized.php' + (select.value ? '?catalog_id=' + encodeURIComponent(select.value) : '');
    }
});
