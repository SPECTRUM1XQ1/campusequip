/**
 * Borrower home: cancel a pending reservation.
 * Loaded by public/borrower/dashboard.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

async function cancelReservation(reservationId) {
    if (!confirm("Are you sure you want to cancel this pending reservation?")) return;

    const formData = new FormData();
    formData.append('reservation_id', reservationId);

    try {
        const res = await fetch(APP_BASE + '/api/reservation/cancel.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Reservation request cancelled', 'info');
            const el = document.getElementById('borrower-req-' + reservationId);
            if (el) el.remove();
            if (window.refreshFilters) window.refreshFilters();
        } else {
            showToast(data.message || 'Could not cancel reservation', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    }
}

// ---- Cancel-request buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-cancel-reservation]');
    if (btn) cancelReservation(parseInt(btn.dataset.cancelReservation, 10));
});
