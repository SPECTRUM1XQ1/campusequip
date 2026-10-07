<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'User Management';
$comingSoonMessage = 'Managing accounts and staff permissions is next on the build list.';

// Temporary mock data for UI design — backend dev: replace with a DB query.
// 'status' must be 'Active' or 'Suspended'; 'role' is Admin | Staff | Borrower.
$totalUsers = 12;
$users = [
    ['id' => 1, 'name' => 'Admin Staff', 'contact' => 'admin@campusequip.edu', 'role' => 'Admin', 'status' => 'Active'],
    ['id' => 2, 'name' => 'Santos', 'contact' => 'santos@campusequip.edu', 'role' => 'Staff', 'status' => 'Active'],
    ['id' => 3, 'name' => 'Cruz', 'contact' => 'cruz@campusequip.edu', 'role' => 'Staff', 'status' => 'Active'],
    ['id' => 4, 'name' => 'Justin Peralta', 'contact' => '2023-10451', 'role' => 'Borrower', 'status' => 'Active'],
    ['id' => 5, 'name' => 'Chloe Mendez', 'contact' => 'chloe.mendez@student.edu', 'role' => 'Borrower', 'status' => 'Suspended'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>

<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <section class="requests-page">
                <p class="page-note">Manage accounts, roles and access for all CampusEquip users</p>

                <!-- Toolbar: count + role filters + add button -->
                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong>1</strong> to <strong><?= count($users) ?></strong>
                        of <strong><?= (int) $totalUsers ?></strong> users
                    </p>

                    <div class="filter-group" role="group" aria-label="Filter users by role">
                        <span class="filter-label">Role:</span>
                        <button type="button" class="filter-pill active">All</button>
                        <button type="button" class="filter-pill">Admin</button>
                        <button type="button" class="filter-pill">Staff</button>
                        <button type="button" class="filter-pill">Borrower</button>
                        <button type="button" class="btn btn-primary btn-sm btn-rect"
                            onclick="openModal('addUserModal')">+ Add User</button>
                    </div>
                </div>

                <!-- Users table -->
                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Student ID / Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Account Status</th>
                                <th scope="col" class="col-action">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="cell-strong"><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['contact']) ?></td>
                                    <td class="cell-strong"><?= htmlspecialchars($user['role']) ?></td>
                                    <td>
                                        <span
                                            class="status-badge <?= $user['status'] === 'Active' ? 'approved' : 'declined' ?>">
                                            <?= htmlspecialchars($user['status']) ?>
                                        </span>
                                    </td>
                                    <td class="col-action">
                                        <div class="row-actions">
                                            <button type="button" class="link-text" data-id="<?= (int) $user['id'] ?>"
                                                onclick="openModal('editUserModal')">Edit</button>

                                            <button type="button" class="btn-decline" data-id="<?= (int) $user['id'] ?>"
                                                onclick="openModal('deleteUserModal')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- Add User Modal -->
            <div class="modal-overlay" id="addUserModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Add New User</h2>
                            <p class="modal-subtitle">Create a new account and assign a role.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('addUserModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-field">
                            <label class="modal-label">Full Name</label>
                            <input type="text" class="input-field" placeholder="e.g. Jane Doe">
                        </div>
                        <div class="modal-field">
                            <label class="modal-label">Email Address</label>
                            <input type="email" class="input-field" placeholder="jane@campusequip.com">
                        </div>
                        <div class="grid-row grid-cols-2">
                            <div class="modal-field">
                                <label class="modal-label">Role</label>
                                <select class="input-field">
                                    <option>Borrower</option>
                                    <option>Staff</option>
                                    <option>Admin</option>
                                </select>
                            </div>
                            <div class="modal-field">
                                <label class="modal-label">ID Number</label>
                                <input type="text" class="input-field" placeholder="CE-2026-XXXX">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('addUserModal')">Cancel</button>
                        <button class="btn btn-primary btn-rect" onclick="closeModal('addUserModal')">Create
                            User</button>
                    </div>
                </div>
            </div>

            <!-- Modal & Filter Toggle Logic -->
            <script>
                // Modal Functions
                function openModal(modalId) {
                    document.getElementById(modalId).classList.add('open');
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).classList.remove('open');
                }

                document.addEventListener('DOMContentLoaded', () => {
                    // Visual toggle for the Role filter pills
                    const filterPills = document.querySelectorAll('.filter-group .filter-pill');
                    filterPills.forEach(pill => {
                        pill.addEventListener('click', function () {
                            // Remove active class from all pills
                            filterPills.forEach(p => p.classList.remove('active'));
                            // Add active class to the clicked pill
                            this.classList.add('active');
                        });
                    });

                    // Close modal when clicking the dark overlay background
                    const overlays = document.querySelectorAll('.modal-overlay');
                    overlays.forEach(overlay => {
                        overlay.addEventListener('click', (e) => {
                            if (e.target === overlay) closeModal(overlay.id);
                        });
                    });
                });
            </script>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal">
        <div class="modal" style="max-width: 32rem;">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Edit User Details</h2>
                </div>
                <button class="modal-close" onclick="closeModal('editUserModal')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <!-- Justin will handle the form submission -->
                <form id="editUserForm" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label
                            style="display: block; font-size: 0.85rem; margin-bottom: 0.25rem; color: var(--text-muted);">Full
                            Name</label>
                        <input type="text" class="form-input"
                            style="width: 100%; padding: 0.5rem; border: 1px solid #334155; border-radius: 4px; background: transparent; color: white;"
                            placeholder="e.g. Justin Peralta">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 0.85rem; margin-bottom: 0.25rem; color: var(--text-muted);">Role</label>
                        <select class="form-input"
                            style="width: 100%; padding: 0.5rem; border: 1px solid #334155; border-radius: 4px; background: #1e293b; color: white;">
                            <option value="borrower">Borrower</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline btn-rect" onclick="closeModal('editUserModal')">Cancel</button>
                <button class="btn btn-primary btn-rect"
                    onclick="closeModal('editUserModal'); showToast('User updated successfully', 'success');">Save
                    Changes</button>
            </div>
        </div>
    </div>
    <!-- Delete User Modal -->
    <div class="modal-overlay" id="deleteUserModal">
        <div class="modal" style="max-width: 28rem;">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Delete User</h2>
                </div>
                <button class="modal-close" onclick="closeModal('deleteUserModal')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="color: var(--text-main); font-size: 0.95rem;">Are you sure you want to delete this user? This
                    action cannot be undone and will remove their access to CampusEquip.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline btn-rect" onclick="closeModal('deleteUserModal')">Cancel</button>
                <!-- Justin will attach his PHP delete execution here -->
                <button class="btn btn-danger btn-rect">Yes, Delete User</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>