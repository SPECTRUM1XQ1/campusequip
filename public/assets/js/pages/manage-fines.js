/**
 * Fines & Penalties page (staff/admin): mark paid, waive, charge.
 * Loaded by public/manage/fines.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

async function finish(data, successMessage) {
    if (data.success) {
        showToast(successMessage, 'success');
        setTimeout(() => location.reload(), 700);
    } else {
        showToast(data.message || 'Something went wrong.', 'error');
    }
}

// ---- Mark paid ----
let payId = null;
document.querySelectorAll('.pay-btn').forEach(btn => btn.addEventListener('click', () => {
    payId = btn.dataset.id;
    document.getElementById('pay-message').textContent = 'Record that ' + btn.dataset.label + ' has been paid?';
    openModal('payModal');
}));
document.getElementById('pay-confirm-btn').addEventListener('click', async function () {
    if (!payId) return;
    this.disabled = true;
    try {
        const fd = new FormData();
        fd.append('fine_id', payId);
        await finish(await postForm(APP_BASE + '/api/fine/pay.php', fd), 'Payment recorded.');
    } catch (e) { showToast('Server communication error', 'error'); }
    this.disabled = false;
});

// ---- Waive (admin) ----
let waiveId = null;
document.querySelectorAll('.waive-btn').forEach(btn => btn.addEventListener('click', () => {
    waiveId = btn.dataset.id;
    document.getElementById('waive-message').textContent = 'Waive ' + btn.dataset.label + '? The borrower will no longer owe it.';
    document.getElementById('waive_reason').value = '';
    openModal('waiveModal');
}));
document.getElementById('waive-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('waive-submit-btn');
    btn.disabled = true;
    try {
        const fd = new FormData(this);
        fd.append('fine_id', waiveId);
        await finish(await postForm(APP_BASE + '/api/fine/waive.php', fd), 'Fine waived.');
    } catch (err) { showToast('Server communication error', 'error'); }
    btn.disabled = false;
});

// ---- Charge ----
document.getElementById('charge-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('charge-submit-btn');
    btn.disabled = true;
    try {
        await finish(await postForm(APP_BASE + '/api/fine/charge.php', new FormData(this)), 'Fine charged.');
    } catch (err) { showToast('Server communication error', 'error'); }
    btn.disabled = false;
});

// ---- Charge amount: at most 5 digits before the decimal point and 2 after ----
document.getElementById('charge_amount').addEventListener('input', function () {
    const m = this.value.match(/^(\d{0,5})(\d*)(\.\d{0,2})?/);
    if (m && this.value !== (m[1] + (m[3] || ''))) this.value = m[1] + (m[3] || '');
});
