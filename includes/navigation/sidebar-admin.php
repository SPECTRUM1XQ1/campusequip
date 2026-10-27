<?php
// Expects $_SESSION to already be started (auth_check.php does this).
// $currentPage is used to highlight the active nav link.
$currentPage = basename($_SERVER['PHP_SELF']);

$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'borrower';

// Initials for the avatar circle, e.g. "Aian Rosario" -> "AR"
$nameParts = preg_split('/\s+/', trim($fullName));
$initials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));

$isStaff = in_array($role, ['staff', 'admin'], true);

// The role shown in the account block (and its colour: see .sidebar[data-role] in shared/sidebar.css).
$roleLabels = ['admin' => 'Administrator', 'staff' => 'Staff', 'borrower' => 'Borrower'];
$roleKey = isset($roleLabels[$role]) ? $role : 'borrower';

// Navigation is grouped: each group has a 'title' (the category header)
// and an 'items' list of links.
if ($isStaff) {
    $navGroups = [
        [
            'title' => 'Main Menu',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'ph-house', 'href' => APP_BASE . '/public/manage/dashboard.php'],
            ],
        ],
        [
            'title' => 'Operation',
            'items' => [
                ['label' => 'Pending & Request', 'icon' => 'ph-hourglass', 'href' => APP_BASE . '/public/manage/requests.php', 'perm' => 'can_approve_requests'],
                ['label' => 'Check-In & Return', 'icon' => 'ph-arrows-left-right', 'href' => APP_BASE . '/public/manage/checkouts.php', 'only' => 'admin'],
                ['label' => 'Handover', 'icon' => 'ph-package', 'href' => APP_BASE . '/public/staff/handover.php', 'perm' => 'can_confirm_pickup', 'only' => 'staff'],
                ['label' => 'Process Return', 'icon' => 'ph-arrow-u-down-left', 'href' => APP_BASE . '/public/staff/return.php', 'perm' => 'can_inspect_returns', 'only' => 'staff'],
                ['label' => 'Fines & Penalties', 'icon' => 'ph-receipt', 'href' => APP_BASE . '/public/manage/fines.php', 'perm' => 'can_inspect_returns'],
            ],
        ],
        [
            'title' => 'Inventory',
            'items' => [
                ['label' => 'Equipment Catalog', 'icon' => 'ph-book-open', 'href' => APP_BASE . '/public/manage/catalog.php', 'perm' => 'can_manage_catalog'],
                ['label' => 'Serialized Items', 'icon' => 'ph-barcode', 'href' => APP_BASE . '/public/manage/serialized.php', 'perm' => 'can_manage_catalog'],
                ['label' => 'Transaction Logs', 'icon' => 'ph-clock-counter-clockwise', 'href' => APP_BASE . '/public/manage/logs.php'],
            ],
        ],
    ];

    // Admin-only category, appended after the shared staff groups.
    if ($role === 'admin') {
        $navGroups[] = [
            'title' => 'Administration',
            'items' => [
                ['label' => 'User Management', 'icon' => 'ph-users', 'href' => APP_BASE . '/public/admin/users.php'],
                ['label' => 'Export Records', 'icon' => 'ph-export', 'href' => APP_BASE . '/public/admin/export.php'],
                ['label' => 'System Settings', 'icon' => 'ph-gear', 'href' => APP_BASE . '/public/admin/settings.php'],
            ],
        ];
    }
} else {
    // Borrowers keep a single "Main Menu" group.
    $navGroups = [
        [
            'title' => 'Main Menu',
            'items' => [
                ['label' => 'Home', 'icon' => 'ph-house', 'href' => APP_BASE . '/public/borrower/dashboard.php'],
                ['label' => 'Equipment Catalog', 'icon' => 'ph-book-open', 'href' => APP_BASE . '/public/borrower/catalog.php'],
                ['label' => 'Saved Item', 'icon' => 'ph-bookmark-simple', 'href' => APP_BASE . '/public/borrower/wishlist.php'],
                ['label' => 'Borrowing History', 'icon' => 'ph-clock-counter-clockwise', 'href' => APP_BASE . '/public/borrower/my_requests.php'],
                ['label' => 'Penalties & Fines', 'icon' => 'ph-receipt', 'href' => APP_BASE . '/public/borrower/fines.php'],
            ],
        ],
    ];
}

// Staff only see the pages their single job needs (admins see everything).
foreach ($navGroups as $gi => $group) {
    $navGroups[$gi]['items'] = array_values(array_filter($group['items'], function ($item) use ($role) {
        if (isset($item['only']) && $item['only'] !== $role) {
            return false; // e.g. admins get the combined page, staff get their single-job page
        }
        return !isset($item['perm']) || hasPermission($item['perm']);
    }));
}
$navGroups = array_values(array_filter($navGroups, fn($g) => !empty($g['items'])));
?>
<aside class="sidebar" id="sidebar" data-role="<?= htmlspecialchars($roleKey) ?>">
    <a class="sidebar-brand" href="<?= htmlspecialchars($navGroups[0]['items'][0]['href'] ?? '#') ?>" aria-label="LendLab home">
        <span class="app-logo">
            <span class="app-logo-mark"></span>
        </span>
        <span class="sidebar-brand-name">LendLab</span>
    </a>

    <nav class="nav-section" aria-label="Main navigation">
        <?php foreach ($navGroups as $group): ?>
            <div class="nav-group">
                <div class="nav-label">
                    <p>
                        <?= htmlspecialchars($group['title']) ?>
                    </p>
                </div>
                <?php foreach ($group['items'] as $item): ?>
                    <?php $isActive = basename($item['href']) === $currentPage; ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>"
                        class="nav-link <?= $isActive ? 'active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                        <i class="ph <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-account">
        <div class="account-card">
            <div class="avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
            <div class="user-details">
                <h4><?= htmlspecialchars($fullName) ?></h4>
                <div class="role-chip"><?= htmlspecialchars($roleLabels[$roleKey]) ?></div>
            </div>
        </div>
        <button type="button" class="logout-btn" data-open-modal="logoutModal">
            <i class="ph ph-sign-out" aria-hidden="true"></i><span>Log out</span>
        </button>
    </div>
</aside>
