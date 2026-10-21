/**
 * User Management page: edit role / job modal.
 * Loaded by public/admin/users.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

const roleSelect = document.getElementById('edit_role');
const jobField   = document.getElementById('job-field');
let original = { role: '', job: '' };

// The job radios only make sense for Staff.
function syncJobField() {
    jobField.style.display = (roleSelect.value === 'staff') ? 'block' : 'none';
}
roleSelect.addEventListener('change', syncJobField);

document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const d = btn.dataset;
        original = { role: d.role, job: d.job };

        document.getElementById('edit_user_id').value = d.id;
        document.getElementById('edit-user-subtitle').textContent = d.name + ' · ' + d.contact;
        roleSelect.value = d.role;

        // You can't change your own role (avoids locking yourself out).
        roleSelect.disabled = (d.self === '1');
        document.getElementById('edit-self-note').style.display = (d.self === '1') ? 'block' : 'none';

        document.querySelectorAll('input[name="job"]').forEach(r => { r.checked = (r.value === d.job); });
        syncJobField();
        openModal('editUserModal');
    });
});

document.getElementById('editUserForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const userId = document.getElementById('edit_user_id').value;
    const role   = roleSelect.value;
    const chosen = document.querySelector('input[name="job"]:checked');
    const job    = chosen ? chosen.value : '';

    if (role === 'staff' && job === '') {
        showToast('Please choose one job for this staff member.', 'error');
        return;
    }

    const roleChanged = !roleSelect.disabled && role !== original.role;
    const jobChanged  = role === 'staff' && job !== original.job;
    if (!roleChanged && !jobChanged) {
        closeModal('editUserModal');
        return;
    }

    const btn = document.getElementById('edit-save-btn');
    btn.disabled = true;
    setBtnText(btn, 'Saving...');

    try {
        if (roleChanged) {
            const fd = new FormData();
            fd.append('user_id', userId);
            fd.append('role', role);
            const r = await postForm(APP_BASE + '/api/users/update_role.php', fd);
            if (!r.success) { showToast(r.message || 'Could not change the role.', 'error'); return; }
        }
        if (role === 'staff' && (jobChanged || roleChanged)) {
            const fd = new FormData();
            fd.append('user_id', userId);
            fd.append('job', job);
            const r = await postForm(APP_BASE + '/api/users/update_permission.php', fd);
            if (!r.success) { showToast(r.message || 'Could not save the job.', 'error'); return; }
        }
        showToast('User updated successfully', 'success');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Save Changes');
    }
});
