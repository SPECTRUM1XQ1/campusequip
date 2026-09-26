<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('borrower');

$pageTitle = 'Equipment Catalog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>
<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <!--
                TEMPORARY DATA. Replace this grid with a loop over
                Equipment::search() results (equipment_catalog + a count of
                available serialized_items per model) once that class exists.
            -->
            <div class="catalog-grid">

                <div class="equipment-card">
                    <div class="equipment-image">
                        <i class="ph ph-laptop"></i>
                    </div>
                    <div class="equipment-details">
                        <span class="equipment-category">Laptops</span>
                        <h3 class="equipment-title">Dell XPS 15 Workstation</h3>
                        <div class="equipment-status">
                            <span class="status-dot"></span> 4 Available
                        </div>
                        <button class="btn btn-primary" style="width: 100%; margin-top: auto;">View Details</button>
                    </div>
                </div>

                <div class="equipment-card">
                    <div class="equipment-image">
                        <i class="ph ph-camera"></i>
                    </div>
                    <div class="equipment-details">
                        <span class="equipment-category">Media</span>
                        <h3 class="equipment-title">Sony Alpha Camera 4K</h3>
                        <div class="equipment-status">
                            <span class="status-dot"></span> 16 Available
                        </div>
                        <button class="btn btn-primary" style="width: 100%; margin-top: auto;">View Details</button>
                    </div>
                </div>

                <div class="equipment-card">
                    <div class="equipment-image">
                        <i class="ph ph-circuitry"></i>
                    </div>
                    <div class="equipment-details">
                        <span class="equipment-category">Kits</span>
                        <h3 class="equipment-title">Arduino Uno Basic Kit</h3>
                        <div class="equipment-status">
                            <span class="status-dot"></span> 25 Available
                        </div>
                        <button class="btn btn-primary" style="width: 100%; margin-top: auto;">View Details</button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
