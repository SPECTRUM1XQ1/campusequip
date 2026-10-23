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
<aside class="sidebar" id="sidebar">
    <div
        class="ui-sidebar-admin-s1">
        <span class="app-logo">
            <span class="app-logo-mark"></span>
        </span>
        LendLab
    </div>

    <div class="user-profile-card">
        <div class="user-info">
            <div class="avatar">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div class="user-details">
                <h4>
                    <?= htmlspecialchars($fullName) ?>
                </h4>
                <p>
                    <?= htmlspecialchars(ucfirst($role)) ?>
                </p>
            </div>
        </div>
        <a href="#" class="btn btn-sm ui-sidebar-admin-s2"
           
            data-open-modal="logoutModal">
            <i class="ph ph-sign-out" aria-hidden="true"></i><span class="btn-text">Log Out</span>
        </a>
    </div>

    <nav class="nav-section">
        <?php foreach ($navGroups as $group): ?>
            <div class="nav-group">
                <div class="nav-label">
                    <p>
                        <?= htmlspecialchars($group['title']) ?>
                    </p>
                </div>
                <?php foreach ($group['items'] as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>"
                        class="nav-link <?= basename($item['href']) === $currentPage ? 'active' : '' ?>">
                        <i class="ph <?= htmlspecialchars($item['icon']) ?>"></i>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>