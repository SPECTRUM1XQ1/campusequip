<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('borrower');

$pageTitle = 'Saved Items';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/html-head.php'; ?>
</head>
<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/topbar.php'; ?>

            <!--
                TEMPORARY DATA. Replace with a query joining wishlist_items
                to equipment_catalog for $_SESSION['user_id'] once
                api/catalog/wishlist.php exists.
            -->
            <div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 1000px;">

                <div class="section-header">
                    <h3 class="section-title">My Saved Items</h3>
                    <span class="section-subtitle">Review your equipment and select dates before confirming</span>
                </div>

                <div class="card-container">

                    <div class="list-item" style="padding: 1.5rem 0;">
                        <div style="display: flex; gap: 1.5rem; align-items: center;">
                            <div class="equipment-image"
                                style="width: 80px; height: 80px; border-radius: 8px; font-size: 2.5rem; min-height: auto;">
                                <i class="ph ph-laptop"></i>
                            </div>
                            <div class="list-info">
                                <span class="equipment-category">Laptops</span>
                                <h5 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Dell XPS 15 Workstation</h5>
                                <div style="display: flex; gap: 0.5rem; align-items: center; font-size: 0.875rem;">
                                    <span style="color: var(--text-muted); font-weight: 600;">Pick-up:</span>
                                    <input type="date"
                                        style="border: 1px solid var(--border-color); border-radius: 4px; padding: 0.25rem; font-family: inherit;">
                                    <span
                                        style="color: var(--text-muted); font-weight: 600; margin-left: 0.5rem;">Return:</span>
                                    <input type="date"
                                        style="border: 1px solid var(--border-color); border-radius: 4px; padding: 0.25rem; font-family: inherit;">
                                </div>
                            </div>
                        </div>
                        <div class="card-actions card-actions-right">
                            <button class="btn btn-outline-danger">Remove</button>
                        </div>
                    </div>

                    <div class="list-item" style="padding: 1.5rem 0;">
                        <div style="display: flex; gap: 1.5rem; align-items: center;">
                            <div class="equipment-image"
                                style="width: 80px; height: 80px; border-radius: 8px; font-size: 2.5rem; min-height: auto;">
                                <i class="ph ph-circuitry"></i>
                            </div>
                            <div class="list-info">
                                <span class="equipment-category">Kits</span>
                                <h5 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Arduino Uno Basic Kit</h5>
                                <div style="display: flex; gap: 0.5rem; align-items: center; font-size: 0.875rem;">
                                    <span style="color: var(--text-muted); font-weight: 600;">Pick-up:</span>
                                    <input type="date"
                                        style="border: 1px solid var(--border-color); border-radius: 4px; padding: 0.25rem; font-family: inherit;">
                                    <span
                                        style="color: var(--text-muted); font-weight: 600; margin-left: 0.5rem;">Return:</span>
                                    <input type="date"
                                        style="border: 1px solid var(--border-color); border-radius: 4px; padding: 0.25rem; font-family: inherit;">
                                </div>
                            </div>
                        </div>
                        <div class="card-actions card-actions-right">
                            <button class="btn btn-outline-danger">Remove</button>
                        </div>
                    </div>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                        <div>
                            <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--text-main);">Total Items: 2
                            </h4>
                            <p style="font-size: 0.875rem; color: var(--text-muted);">Subject to staff approval upon
                                request.</p>
                        </div>
                        <button class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">Confirm
                            Reservation</button>
                    </div>

                </div>

                <div class="section-header" style="margin-top: 2rem;">
                    <h3 class="section-title">Approved Requests (Ready to Pick Up)</h3>
                </div>

                <div class="item-card bg-white"
                    style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h5>DELL XPS 15 LAPTOP (SN-1002)</h5>
                        <p style="margin-bottom: 0;">Approve Date: Sept 12, 2026 • Duration: 3 Days</p>
                    </div>
                    <button class="btn btn-primary">Pick Up Item</button>
                </div>

                <div class="section-header" style="margin-top: 2rem;">
                    <h3 class="section-title">My Wishlist</h3>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="item-card bg-white"
                        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h5>Advanced Research Chemistry Kit #1</h5>
                            <p style="margin-bottom: 0;">Max Duration: 7 Days</p>
                        </div>
                        <div class="card-actions card-actions-right">
                            <button class="btn btn-sm btn-outline">Remove</button>
                            <button class="btn btn-sm btn-primary">Reserve</button>
                        </div>
                    </div>

                    <div class="item-card bg-white"
                        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h5>Advanced Research Chemistry Kit #2</h5>
                            <p style="margin-bottom: 0;">Max Duration: 7 Days</p>
                        </div>
                        <div class="card-actions card-actions-right">
                            <button class="btn btn-sm btn-outline">Remove</button>
                            <button class="btn btn-sm btn-primary">Reserve</button>
                        </div>
                    </div>

                    <div class="item-card bg-white"
                        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h5>Advanced Research Chemistry Kit #3</h5>
                            <p style="margin-bottom: 0;">Max Duration: 7 Days</p>
                        </div>
                        <div class="card-actions card-actions-right">
                            <button class="btn btn-sm btn-outline">Remove</button>
                            <button class="btn btn-sm btn-primary">Reserve</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
