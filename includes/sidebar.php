<?php
// Get current page for active link highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// User Session Data
$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'borrower';

// Initials for the avatar circle 
$nameParts = preg_split('/\s+/', trim($fullName));
$initials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));

// Navigation Logic
$isStaff = in_array($role, ['staff', 'admin'], true);

$navItems = $isStaff ? [
    ['label' => 'Dashboard', 'icon' => 'ph-house', 'href' => '/campusequip/public/admin/dashboard.php'],
    ['label' => 'Request & Pending', 'icon' => 'ph-hourglass', 'href' => '/campusequip/public/admin/requests.php'],
    ['label' => 'Checkout & Return', 'icon' => 'ph-arrows-left-right', 'href' => '/campusequip/public/admin/checkouts.php'],
    ['label' => 'Serialize Item', 'icon' => 'ph-arrows-left-right', 'href' => '/campusequip/public/admin/checkouts.php'],
    ['label' => 'Equipment Catalog', 'icon' => 'ph-arrows-left-right', 'href' => '/campusequip/public/admin/checkouts.php'],
    ['label' => 'Transaction Log', 'icon' => 'ph-clock-counter-clockwise', 'href' => '/campusequip/public/admin/logs.php'],
] : [
    ['label' => 'Home', 'icon' => 'ph-house', 'href' => '/campusequip/public/borrower/dashboard.php'],
    ['label' => 'Equipment Catalog', 'icon' => 'ph-book-open', 'href' => '/campusequip/public/borrower/catalog.php'],
    ['label' => 'Saved Item', 'icon' => 'ph-bookmark-simple', 'href' => '/campusequip/public/borrower/wishlist.php'],
    ['label' => 'Borrowing History', 'icon' => 'ph-clock-counter-clockwise', 'href' => '/campusequip/public/borrower/my_requests.php'],
];

// Admin-only extra links
if ($role === 'admin') {
    $navItems[] = ['label' => 'User Management', 'icon' => 'ph-users', 'href' => '/campusequip/public/admin/users.php'];
    $navItems[] = ['label' => 'System Settings', 'icon' => 'ph-gear', 'href' => '/campusequip/public/admin/settings.php'];
}
?>

<aside class="sidebar" id="sidebar"
    style="background-color: #0f172a; height: 100vh; border-right: 1px solid #1e293b; color: white; display: flex; flex-direction: column;">

    <!-- Logo Header -->
    <div class="sidebar-header" style="padding: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <div
            style="background: #2563eb; color: white; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            <i class="ph ph-cpu" style="font-size: 1.25rem;"></i>
        </div>
        <span style="color: white; font-weight: 700; font-size: 1.25rem;">CampusEquip</span>
    </div>

    <!-- Dynamic User Profile -->
    <div class="user-profile"
        style="background: #1e293b; margin: 0 1.25rem 1.5rem 1.25rem; padding: 1rem; border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div
                style="width: 36px; height: 36px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div>
                <h4 style="margin: 0; color: white; font-size: 0.9rem; font-weight: 600;">
                    <?= htmlspecialchars($fullName) ?>
                </h4>
                <p style="margin: 0; color: #d2ddec; font-size: 0.75rem;">
                    <?= htmlspecialchars(ucfirst($role)) ?>
                </p>
            </div>
        </div>
        <a href="#" class="btn btn-sm"
            style="display: block; text-align: center; background: #334155; color: #cbd5e1; text-decoration: none; border-radius: 6px;"
            onclick="event.preventDefault(); openModal('logoutModal');">Log Out</a>
    </div>

    <!-- Dynamic Navigation Links -->
    <nav class="sidebar-nav">
        <div class="nav-section">
            <h5
                style="color: #64748b; font-size: 0.7rem; margin: 0 1.5rem 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                Navigation
            </h5>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($navItems as $item): ?>
                    <li>
                        <a href="<?= htmlspecialchars($item['href']) ?>"
                            style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; margin: 0 1rem 0.25rem 1rem; border-radius: 6px; text-decoration: none; transition: all 0.2s; color: <?= (basename($item['href']) == $currentPage) ? 'white' : '#cbd5e1' ?>; background: <?= (basename($item['href']) == $currentPage) ? '#2563eb' : 'transparent' ?>;">
                            <i class="ph <?= htmlspecialchars($item['icon']) ?>" style="font-size: 1.25rem;"></i>
                            <span style="font-size: 0.95rem; font-weight: 500;">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</aside>