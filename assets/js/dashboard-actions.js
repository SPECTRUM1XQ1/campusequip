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

  document.querySelectorAll('button').forEach(button => {
    if (button.textContent.trim() === 'Cancel') {
      button.addEventListener('click', function () {
        const requestCard = this.closest('.item-card') || this.parentElement.parentElement;
        if (requestCard) {
          requestCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          requestCard.style.opacity = '0';
          requestCard.style.transform = 'scale(0.95)';
          setTimeout(() => { requestCard.style.display = 'none'; }, 300);
        }
        if (typeof showToast === 'function') showToast('Request cancelled', 'info');
      });
    }
  });
});
