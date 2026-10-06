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
                ['label' => 'Dashboard', 'icon' => 'ph-house', 'href' => '/campusequip/public/admin/dashboard.php'],
            ],
        ],
        [
            'title' => 'Operation',
            'items' => [
                ['label' => 'Pending & Request', 'icon' => 'ph-hourglass', 'href' => '/campusequip/public/admin/requests.php'],
                ['label' => 'Check-In & Return', 'icon' => 'ph-arrows-left-right', 'href' => '/campusequip/public/admin/checkouts.php'],
            ],
        ],
        [
            'title' => 'Inventory',
            'items' => [
                ['label' => 'Equipment Catalog', 'icon' => 'ph-book-open', 'href' => '/campusequip/public/admin/catalog.php'],
                ['label' => 'Serialized Items', 'icon' => 'ph-barcode', 'href' => '/campusequip/public/admin/serialized.php'],
                ['label' => 'Transaction Logs', 'icon' => 'ph-clock-counter-clockwise', 'href' => '/campusequip/public/admin/logs.php'],
            ],
        ],
    ];

    // Admin-only category, appended after the shared staff groups.
    if ($role === 'admin') {
        $navGroups[] = [
            'title' => 'Administration',
            'items' => [
                ['label' => 'User Management', 'icon' => 'ph-users', 'href' => '/campusequip/public/admin/users.php'],
                ['label' => 'System Settings', 'icon' => 'ph-gear', 'href' => '/campusequip/public/admin/settings.php'],
            ],
        ];
    }
} else {
    // Borrowers keep a single "Main Menu" group.
    $navGroups = [
        [
            'title' => 'Main Menu',
            'items' => [
                ['label' => 'Home', 'icon' => 'ph-house', 'href' => '/campusequip/public/borrower/dashboard.php'],
                ['label' => 'Equipment Catalog', 'icon' => 'ph-book-open', 'href' => '/campusequip/public/borrower/catalog.php'],
                ['label' => 'Saved Item', 'icon' => 'ph-bookmark-simple', 'href' => '/campusequip/public/borrower/wishlist.php'],
                ['label' => 'Borrowing History', 'icon' => 'ph-clock-counter-clockwise', 'href' => '/campusequip/public/borrower/my_requests.php'],
            ],
        ],
    ];
}
?>
<aside class="sidebar" id="sidebar">
    <div
        style="display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; padding: 24px; color: white;">
        <span class="campus-equip-logo">
            <span class="chip-icon">`</span>
        </span>
        Campus Equip
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
        <a href="#" class="btn btn-sm"
            style="display: block; text-align: center; background: #334155; color: #cbd5e1; text-decoration: none; border-radius: 6px;"
            onclick="event.preventDefault(); openModal('logoutModal');">
            Log Out
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