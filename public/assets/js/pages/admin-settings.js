/**
 * System Settings page: database backup button.
 * Loaded by public/admin/settings.php.
 * Expects: snackbar container from footer.php.
 */

function triggerBackup() {
    const container = document.getElementById('snackbar-container');
    const snackbar = document.createElement('div');
    snackbar.className = 'snackbar snackbar--success';
    snackbar.textContent = 'Database backup package is downloading...';

    container.appendChild(snackbar);

    // Trigger reflow to ensure the CSS transition works
    setTimeout(() => {
        snackbar.classList.add('snackbar--visible');
    }, 10);

    // Remove the snackbar after 3 seconds
    setTimeout(() => {
        snackbar.classList.remove('snackbar--visible');
        setTimeout(() => snackbar.remove(), 250);
    }, 3000);
}

// ---- Backup button (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-backup]')) triggerBackup();
});
