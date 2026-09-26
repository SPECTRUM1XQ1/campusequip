<?php
// Expects $_SESSION to already be started (auth_check.php does this).
// $currentPage is used to highlight the active nav link.
$currentPage = basename($_SERVER['PHP_SELF']);

$fullName = $_SESSION['full_name'] ?? 'User';
$role     = $_SESSION['role'] ?? 'borrower';

// Initials for the avatar circle, e.g. "Aian Rosario" -> "AR"
$nameParts = preg_split('/\s+/', trim($fullName));
$initials  = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));

$isStaff = in_array($role, ['staff', 'admin'], true);

$navItems = $isStaff
    ? [
        ['label' => 'Dashboard',          'icon' => 'ph-house',                     'href' => '/campusequip/admin/dashboard.php'],
        ['label' => 'Request & Pending',  'icon' => 'ph-hourglass',                 'href' => '/campusequip/admin/requests.php'],
        ['label' => 'Checkout & Return',  'icon' => 'ph-arrows-left-right',         'href' => '/campusequip/admin/checkouts.php'],
        ['label' => 'Transaction Log',    'icon' => 'ph-clock-counter-clockwise',   'href' => '/campusequip/admin/logs.php'],
      ]
    : [
        ['label' => 'Home',               'icon' => 'ph-house',            'href' => '/campusequip/borrower/dashboard.php'],
        ['label' => 'Equipment Catalog',  'icon' => 'ph-book-open',        'href' => '/campusequip/borrower/catalog.php'],
        ['label' => 'Saved Item',         'icon' => 'ph-bookmark-simple',  'href' => '/campusequip/borrower/wishlist.php'],
        ['label' => 'Borrowing History',  'icon' => 'ph-clock-counter-clockwise', 'href' => '/campusequip/borrower/my_requests.php'],
      ];

// Admin-only extra links, appended after the shared staff items.
if ($role === 'admin') {
    $navItems[] = ['label' => 'User Management', 'icon' => 'ph-users',    'href' => '/campusequip/admin/users.php'];
    $navItems[] = ['label' => 'System Settings',  'icon' => 'ph-gear',    'href' => '/campusequip/admin/settings.php'];
}
?>
<aside class="sidebar" id="sidebar">
    <div style="display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; padding: 24px; color: white;">
        <span class="campus-equip-logo">
            <span class="chip-icon"></span>
        </span>
        CampusEquip
    </div>

    <div class="user-profile-card">
        <div class="user-info">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-details">
                <h4><?= htmlspecialchars($fullName) ?></h4>
                <p><?= htmlspecialchars(ucfirst($role)) ?></p>
            </div>
        </div>
        <a href="/campusequip/auth/logout.php" class="btn-logout">Logout</a>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Navigation</div>
        <?php foreach ($navItems as $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>"
               class="nav-link <?= basename($item['href']) === $currentPage ? 'active' : '' ?>">
                <i class="ph <?= htmlspecialchars($item['icon']) ?>"></i> <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
