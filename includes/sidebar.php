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

    <!-- Student Profile -->
    <div class="user-profile"
        style="background: #1e293b; margin: 0 1.25rem 1.5rem 1.25rem; padding: 1rem; border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div
                style="width: 36px; height: 36px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">
                JP
            </div>
            <div>
                <h4 style="margin: 0; color: white; font-size: 0.9rem; font-weight: 600;">Justin Peralta</h4>
                <p style="margin: 0; color: #d2ddec; font-size: 0.75rem;">Student &bull; 2026-10-12</p>
            </div>
        </div>
        <a href="#" class="btn btn-sm"
            style="display: block; text-align: center; background: #334155; color: #cbd5e1; text-decoration: none; border-radius: 6px;"
            onclick="event.preventDefault(); openModal('logoutModal');">Log Out</a>
    </div>

    <!-- Navigation Links -->
    <nav class="sidebar-nav">
        <div class="nav-section">
            <h5
                style="color: #64748b; font-size: 0.7rem; margin: 0 1.5rem 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                Navigation</h5>
            <ul style="list-style: none; padding: 0; margin: 0;">

                <!-- Home -->
                <li>
                    <a href="/campusequip/public/borrower/home.php"
                        style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; margin: 0 1rem 0.25rem 1rem; border-radius: 6px; text-decoration: none; transition: all 0.2s; color: <?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'white' : '#cbd5e1' ?>; background: <?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? '#2563eb' : 'transparent' ?>;">
                        <i class="ph ph-house" style="font-size: 1.25rem;"></i>
                        <span style="font-size: 0.95rem; font-weight: 500;">Home</span>
                    </a>
                </li>

                <!-- Equipment Catalog -->
                <li>
                    <a href="/campusequip/public/borrower/catalog.php"
                        style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; margin: 0 1rem 0.25rem 1rem; border-radius: 6px; text-decoration: none; transition: all 0.2s; color: <?= (basename($_SERVER['PHP_SELF']) == 'catalog.php') ? 'white' : '#cbd5e1' ?>; background: <?= (basename($_SERVER['PHP_SELF']) == 'catalog.php') ? '#2563eb' : 'transparent' ?>;">
                        <i class="ph ph-book-open" style="font-size: 1.25rem;"></i>
                        <span style="font-size: 0.95rem; font-weight: 500;">Equipment Catalog</span>
                    </a>
                </li>

                <!-- Saved Item -->
                <li>
                    <a href="/campusequip/public/borrower/wishlist.php"
                        style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; margin: 0 1rem 0.25rem 1rem; border-radius: 6px; text-decoration: none; transition: all 0.2s; color: <?= (basename($_SERVER['PHP_SELF']) == 'wishlist.php') ? 'white' : '#cbd5e1' ?>; background: <?= (basename($_SERVER['PHP_SELF']) == 'wishlist.php') ? '#2563eb' : 'transparent' ?>;">
                        <i class="ph ph-bookmark" style="font-size: 1.25rem;"></i>
                        <span style="font-size: 0.95rem; font-weight: 500;">Saved Item</span>
                    </a>
                </li>

                <!-- Borrowing History -->
                <li>
                    <a href="/campusequip/public/borrower/my_requests.php"
                        style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; margin: 0 1rem 0.25rem 1rem; border-radius: 6px; text-decoration: none; transition: all 0.2s; color: <?= (basename($_SERVER['PHP_SELF']) == 'my_requests.php') ? 'white' : '#cbd5e1' ?>; background: <?= (basename($_SERVER['PHP_SELF']) == 'my_requests.php') ? '#2563eb' : 'transparent' ?>;">
                        <i class="ph ph-clock-counter-clockwise" style="font-size: 1.25rem;"></i>
                        <span style="font-size: 0.95rem; font-weight: 500;">Borrowing History</span>
                    </a>
                </li>

            </ul>
        </div>
    </nav>
</aside>