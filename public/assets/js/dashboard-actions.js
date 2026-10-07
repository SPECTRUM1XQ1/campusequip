/**
 * TEMPORARY: these buttons only update the page visually right now.
 * Once api/reservations/create.php (Reserve) and cancel.php (Cancel) exist,
 * replace the bodies below with fetch() calls, same pattern as auth.js,
 * and show the result with showToast() instead of just re-styling the button.
 */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('button').forEach(button => {
    if (button.textContent.trim() === 'Reserve') {
      button.addEventListener('click', function () {
        this.textContent = 'Requested';
        this.style.backgroundColor = '#10b981';
        this.style.color = '#ffffff';
        this.style.border = 'none';
        this.style.pointerEvents = 'none';
        if (typeof showToast === 'function') showToast('Reservation request sent', 'success');
      });
    }
  });
});
// Opens the modal by adding the 'open' class
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('open');
  }
}

// Closes the modal by removing the 'open' class
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('open');
  }
}