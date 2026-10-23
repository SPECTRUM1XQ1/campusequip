/**
 * System Settings page: saves the announcements, desk info and maintenance switch.
 * Loaded by public/admin/settings.php.
 * Expects: APP_BASE, setBtnText, showToast.
 */

const settingsForm = document.getElementById('settings-form');

// POST the form and always tell the admin what went wrong, instead of a vague error:
// a 404 means api/settings/save.php was not copied, a 500 means a PHP error (see apache/logs/error.log).
async function saveSettings(fd) {
    const res = await fetch(APP_BASE + '/api/settings/save.php', { method: 'POST', body: fd });
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        const hint = res.status === 404
            ? 'api/settings/save.php was not found. Check that the api/settings folder was copied.'
            : 'The server answered with an error (HTTP ' + res.status + '). Check apache/logs/error.log.';
        return { success: false, message: hint };
    }
}

settingsForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn = document.getElementById('settings-save-btn');
    const maintenanceOn = document.getElementById('maintenance_mode').checked;

    const fd = new FormData();
    fd.append('announcement', document.getElementById('announcement').value);
    fd.append('announcement_staff', document.getElementById('announcement_staff').value);
    fd.append('desk_hours', document.getElementById('desk_hours').value);
    fd.append('desk_contact', document.getElementById('desk_contact').value);
    fd.append('maintenance_mode', maintenanceOn ? '1' : '0');
    fd.append('maintenance_message', document.getElementById('maintenance_message').value);

    btn.disabled = true;
    setBtnText(btn, 'Saving...');

    try {
        const r = await saveSettings(fd);
        if (!r.success) {
            showToast(r.message || 'Could not save the settings.', 'error', 8000);
            return;
        }
        showToast(maintenanceOn ? 'Saved. Maintenance mode is ON.' : 'Settings saved.', 'success');
        // Reload so the form shows exactly what was stored (e.g. an empty maintenance message becomes the default).
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        showToast('Could not reach the server. Is XAMPP (Apache and MySQL) running?', 'error', 8000);
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Save Changes');
    }
});
