<header class="top-header">
    <div class="header-title">
        <button class="mobile-menu-btn" id="menuBtn">
            <i class="ph ph-list"></i>
        </button>
        <span><?= htmlspecialchars($pageTitle ?? '') ?></span>
    </div>

    <div class="header-controls">
        <select>
            <option>Category</option>
            <option>Laptops</option>
            <option>Kits</option>
            <option>Books</option>
        </select>

        <div class="search-bar">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" placeholder="Search Item...">
        </div>

        <button class="btn-icon">
            <i class="ph ph-bell"></i>
        </button>
    </div>
</header>
