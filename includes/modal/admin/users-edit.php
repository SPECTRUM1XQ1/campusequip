<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="editUserModal">
    <div class="modal m-admin-users-edit-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Edit User</h2>
                <p class="modal-subtitle" id="edit-user-subtitle"></p>
            </div>
            <button type="button" class="modal-close" data-close-modal="editUserModal">&times;</button>
        </div>

        <form id="editUserForm">
            <input type="hidden" id="edit_user_id">
            <div class="modal-body">
                <div class="modal-field">
                    <label class="modal-label" for="edit_role">Role</label>
                    <select class="input-field" id="edit_role">
                        <option value="borrower">Borrower</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                    <p class="text-muted" id="edit-self-note" style="display:none; font-size:0.75rem; margin-top:0.35rem;">
                        You can't change your own role.
                    </p>
                </div>

                <!-- Only for Staff: radio buttons, so only ONE job can be picked -->
                <div class="modal-field" id="job-field" style="display:none;" role="radiogroup" aria-labelledby="job-label">
                    <label class="modal-label" id="job-label">Staff job * (choose one)</label>
                    <?php foreach ($jobs as $flag => $label): ?>
                        <label class="option-row">
                            <input type="radio" name="job" value="<?= $flag ?>"> <?= htmlspecialchars($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-rect" data-close-modal="editUserModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary btn-rect" id="edit-save-btn"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span class="btn-text">Save</span></button>
            </div>
        </form>
    </div>
</div>
