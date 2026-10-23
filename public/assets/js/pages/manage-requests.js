/**
 * Requests & Pending page: approve / decline modals.
 * Loaded by public/manage/requests.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

function openApproveModal(id, borrower, model, btn) {
    document.getElementById('approve_reservation_id').value = id;
    document.getElementById('approve-summary-id').textContent = 'REQ-' + id;
    document.getElementById('approve-summary-borrower').textContent = borrower;
    document.getElementById('approve-summary-model').textContent = model;

    const unitSelect = document.getElementById('assign_item_id');
    unitSelect.innerHTML = '';

    const units = JSON.parse(btn.getAttribute('data-units') || '[]');
    if (units.length === 0) {
        unitSelect.innerHTML = '<option value="" disabled selected>No available units in stock!</option>';
        document.getElementById('approve-submit-btn').disabled = true;
    } else {
        document.getElementById('approve-submit-btn').disabled = false;
        units.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.item_id;
            opt.textContent = `SN: ${u.serial_number} (${u.storage_location || 'Main Desk'})`;
            unitSelect.appendChild(opt);
        });
    }
    openModal('approveModal');
}

function openDeclineModal(id, borrower, model) {
    document.getElementById('decline_reservation_id').value = id;
    document.getElementById('decline-summary-id').textContent = 'REQ-' + id;
    document.getElementById('decline-summary-borrower').textContent = borrower;
    document.getElementById('decline-summary-model').textContent = model;
    document.getElementById('decline-form').reset(); // clears radios + custom text
    document.getElementById('decline_reservation_id').value = id; // reset() also clears hidden inputs
    syncDeclineReason();
    openModal('declineModal');
}

// The textarea only appears for "Other".
function syncDeclineReason() {
    const choice = document.querySelector('input[name="decline_choice"]:checked');
    const isOther = choice && choice.value === '__other__';
    const field = document.getElementById('decline-other-field');
    field.style.display = isOther ? 'block' : 'none';
    if (isOther) document.getElementById('decline_other').focus();
}
document.querySelectorAll('input[name="decline_choice"]').forEach(r => r.addEventListener('change', syncDeclineReason));

// AJAX Approve Request
document.getElementById('approve-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('approve-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Processing...');

    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/reservation/approve.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Reservation approved successfully!', 'success');
            const rowId = 'req-row-' + formData.get('reservation_id');
            const row = document.getElementById(rowId);
            if (row) row.remove();
            window.refreshFilters();
            closeModal('approveModal');
        } else {
            showToast(data.message || 'Approval failed', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Approve');
    }
});

// AJAX Decline Request
document.getElementById('decline-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Work out the single reason string to send.
    const choice = this.querySelector('input[name="decline_choice"]:checked');
    if (!choice) {
        showToast('Please select a reason for declining.', 'error');
        return;
    }
    const reason = (choice.value === '__other__')
        ? document.getElementById('decline_other').value.trim()
        : choice.value;
    if (reason === '') {
        showToast('Please type the reason for declining.', 'error');
        return;
    }
    document.getElementById('decline_reason').value = reason;

    const btn = document.getElementById('decline-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Processing...');

    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/reservation/decline.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Reservation declined and borrower notified.', 'info');
            const rowId = 'req-row-' + formData.get('reservation_id');
            const row = document.getElementById(rowId);
            if (row) row.remove();
            window.refreshFilters();
            closeModal('declineModal');
        } else {
            showToast(data.message || 'Decline failed', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Decline');
    }
});

// ---- Approve / Decline buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const approve = e.target.closest('[data-approve]');
    if (approve) {
        openApproveModal(parseInt(approve.dataset.approve, 10), approve.dataset.borrower, approve.dataset.model, approve);
        return;
    }
    const decline = e.target.closest('[data-decline]');
    if (decline) openDeclineModal(parseInt(decline.dataset.decline, 10), decline.dataset.borrower, decline.dataset.model);
});
