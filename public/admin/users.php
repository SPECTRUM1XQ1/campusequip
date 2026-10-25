<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../src/User.php';
requireRole('admin');

$pageTitle = 'User Management';
$pageStyles = ['pages/admin-users'];

$users      = (new User())->listAll();
$totalUsers = count($users);
$myId       = (int) $_SESSION['user_id'];

// Each staff member does exactly ONE job (shown as radio buttons).
$jobs = [
    'can_approve_requests' => 'Approve / decline requests',
    'can_confirm_pickup'   => 'Handover (confirm pickup)',
    'can_inspect_returns'  => 'Return (inspect returned items)',
    'can_manage_catalog'   => 'Manage equipment catalog',
];

function userFullName(array $u): string
{
    return trim(preg_replace('/\s+/', ' ', $u['first_name'] . ' ' . ($u['middle_name'] ?? '') . ' ' . $u['last_name']));
}

// Which single job (if any) this user has.
function userJob(array $u, array $jobs): string
{
    foreach ($jobs as $flag => $label) {
        if ((int) $u[$flag] === 1) {
            return $flag;
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>

<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/navigation/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/navigation/topbar.php'; ?>

            <section class="requests-page">
                <p class="page-note">Manage roles and assign each staff member their job</p>

                <div class="requests-toolbar">
                    <p class="results-count">
                        Showing <strong id="visible-count"><?= $totalUsers ?></strong>
                        of <strong id="total-count"><?= $totalUsers ?></strong> users
                    </p>

                    <div class="filter-group" role="group" aria-label="Filter users by role"
                         data-filter-group data-items="#users-tbody tr[data-tags]" data-page-size="10" data-pager="#users-pager" data-pager-always data-empty="#filter-empty-row"
                         data-visible="#visible-count" data-total="#total-count">
                        <span class="filter-label">Role:</span>
                        <button type="button" class="filter-pill active" data-filter="all">All</button>
                        <button type="button" class="filter-pill" data-filter="admin">Admin</button>
                        <button type="button" class="filter-pill" data-filter="staff">Staff</button>
                        <button type="button" class="filter-pill" data-filter="borrower">Borrower</button>
                    </div>
                </div>

                <div class="table-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Student ID / Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Job</th>
                                <th scope="col" class="col-action">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <?php foreach ($users as $user):
                                $fullName = userFullName($user);
                                $job      = userJob($user, $jobs);
                                $isSelf   = ((int) $user['user_id'] === $myId);
                            ?>
                                <tr data-tags="<?= htmlspecialchars($user['role']) ?>">
                                    <td class="cell-strong">
                                        <?= htmlspecialchars($fullName) ?>
                                        <?php if ($isSelf): ?><span class="text-muted admin-users-s1"> (you)</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['id_number'] ?: '—') ?>
                                        <span class="admin-users-s2"><?= htmlspecialchars($user['email']) ?></span>
                                    </td>
                                    <td class="cell-strong"><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'staff'): ?>
                                            <?php if ($job !== ''): ?>
                                                <span class="status-badge approved"><?= htmlspecialchars($jobs[$job]) ?></span>
                                            <?php else: ?>
                                                <span class="status-badge pending">Not assigned</span>
                                            <?php endif; ?>
                                        <?php elseif ($user['role'] === 'admin'): ?>
                                            <span class="text-muted">All jobs</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-action">
                                        <div class="row-actions">
                                            <?= edit_button([
                                    'id' => (int) $user['user_id'],
                                    'name' => $fullName,
                                    'contact' => $user['id_number'] ?: $user['email'],
                                    'role' => $user['role'],
                                    'job' => $job,
                                    'self' => $isSelf ? '1' : '0',
                                ], 'edit-user-btn') ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="filter-empty-row" style="display:none;">
                                <td colspan="5" class="text-muted admin-users-s3">
                                    No users match this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager" id="users-pager"></div>
            </section>

            <!-- Edit User Modal -->
<?php include __DIR__ . '/../../includes/modal/admin/users-edit.php'; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/navigation/footer.php'; ?>
    <script src="<?= APP_BASE ?>/public/assets/js/filters.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/filters.js') ?: 1 ?>"></script>

    <script src="<?= APP_BASE ?>/public/assets/js/pages/admin-users.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/pages/admin-users.js') ?: 1 ?>"></script>
</body>

</html>
