/**
 * Generic snackbar/toast. Doesn't know or care why it's being shown —
 * call it from any page's JS after any action.
 *
 * Usage: showToast('Reservation approved', 'success');
 * type: 'success' | 'error' | 'info'  (default: 'info')
 */
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('snackbar-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'snackbar-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `snackbar snackbar--${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  // trigger CSS transition
  requestAnimationFrame(() => toast.classList.add('snackbar--visible'));

  setTimeout(() => {
    toast.classList.remove('snackbar--visible');
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  }, duration);
}
