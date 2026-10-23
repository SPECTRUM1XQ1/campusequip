/**
 * Handover modal + AJAX submit.
 * Used by: public/staff/handover.php and public/manage/checkouts.php.
 * Expects (from html-head / footer): APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

function openHandoverModal(id, borrower, idNumber, item, due) {
    document.getElementById('handover_reservation_id').value = id;
    document.getElementById('ho-borrower').textContent = borrower;
    document.getElementById('ho-idnumber').textContent = idNumber;
    document.getElementById('ho-item').textContent = item;
    document.getElementById('ho-due').textContent = due;
    openModal('handoverModal');
}

// AJAX Handover Submission
document.getElementById('handover-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('ho-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Processing...');

    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/checkout/handover.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Equipment handed over to student!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Handover failed', 'error');
        }
    } catch (err) {
        showToast('Server error during handover', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Handover');
    }
});

// ---- Hand Over buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-handover]');
    if (!btn) return;
    openHandoverModal(parseInt(btn.dataset.handover, 10), btn.dataset.borrower, btn.dataset.idNumber, btn.dataset.item, btn.dataset.due);
});
