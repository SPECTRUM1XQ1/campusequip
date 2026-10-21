<header class="top-header">
    <div class="header-title">
        <button type="button" class="mobile-menu-btn" id="menuBtn" aria-label="Open menu">
            <i class="ph ph-list"></i>
        </button>
        <span><?= htmlspecialchars($pageTitle ?? '') ?></span>
    </div>

    <div class="header-controls">
        <div class="search-bar">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search Item..." aria-label="Search this page">
        </div>

        <button type="button" class="btn-icon bell-btn" id="bellBtn" aria-label="Notifications" data-open-modal="notificationModal"
            data-user-id="<?= (int) ($_SESSION['user_id'] ?? 0) ?>"
            data-is-staff="<?= in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true) ? '1' : '0' ?>">
            <i class="ph ph-bell-ringing"></i>
            <span class="notif-badge" id="notifBadge" hidden>0</span>
        </button>
    </div>
</header>

<!-- Notification Modal (filled from api/notifications/list.php) -->
<?php include __DIR__ . '/../modal/shared/notification.php'; ?>
