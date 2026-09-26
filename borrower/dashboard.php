<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('borrower');

$pageTitle = 'Home Overview';
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
                TEMPORARY DATA BELOW.
                Everything in this dashboard-grid is placeholder markup until
                Reservation.php / Equipment.php are built. Swap each block for
                a real query against reservations / checkouts / wishlist_items
                for $_SESSION['user_id'] when that's ready.
            -->
            <div class="dashboard-grid">

                <div class="left-column">

                    <div class="section-header">
                        <h3 class="section-title">Request Pending</h3>
                        <span class="section-subtitle">Awaiting staff approval</span>
                    </div>

                    <div class="pending-grid mb-6" style="margin-bottom: 1.5rem;">
                        <div class="item-card bg-white">
                            <h5>Science Book (Biology)</h5>
                            <p>Duration: Sept 12 - Sept 15, 2026</p>
                            <div class="card-actions">
                                <span class="badge-warning">Pending Request</span>
                                <button class="btn btn-sm btn-outline-danger">Cancel</button>
                            </div>
                        </div>
                        <div class="item-card bg-white">
                            <h5>Science Book (Biology)</h5>
                            <p>Duration: Sept 12 - Sept 15, 2026</p>
                            <div class="card-actions">
                                <span class="badge-warning">Pending Request</span>
                                <button class="btn btn-sm btn-outline-danger">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <div class="section-header">
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

                    <div class="section-header">
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

                <div class="right-column">

                    <div class="section-header">
                        <h3 class="section-title">Available Items</h3>
                        <a href="/campusequip/borrower/catalog.php" class="section-link">View All</a>
                    </div>

                    <div class="card-container">
                        <div class="list-item">
                            <div class="list-info">
                                <h5>Sony Alpha Camera 4K</h5>
                                <p class="text-success">16 Available</p>
                            </div>
                            <button class="btn btn-sm btn-outline">View</button>
                        </div>
                        <div class="list-item">
                            <div class="list-info">
                                <h5>Dell XPS 15 Workstation</h5>
                                <p class="text-success">4 Available</p>
                            </div>
                            <button class="btn btn-sm btn-outline">View</button>
                        </div>
                        <div class="list-item">
                            <div class="list-info">
                                <h5>Arduino Uno Basic Kit</h5>
                                <p class="text-success">25 Available</p>
                            </div>
                            <button class="btn btn-sm btn-outline">View</button>
                        </div>
                        <div class="list-item">
                            <div class="list-info">
                                <h5>Asus Vivobook Laptop</h5>
                                <p class="text-success">9 Available</p>
                            </div>
                            <button class="btn btn-sm btn-outline">View</button>
                        </div>
                        <div class="list-item">
                            <div class="list-info">
                                <h5>Biology Lab Pipette Set</h5>
                                <p class="text-success">30 Available</p>
                            </div>
                            <button class="btn btn-sm btn-outline">View</button>
                        </div>
                    </div>

                    <div class="section-header" style="margin-top: 2rem;">
                        <h3 class="section-title">Picked Up Item</h3>
                    </div>

                    <div class="item-card bg-white">
                        <h5>Science Book (Physics)</h5>
                        <p style="margin-bottom: 0.25rem;">Checkout: Sept 14, 2026</p>
                        <p style="margin-bottom: 0;">Return due: Sept 21, 2026</p>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
