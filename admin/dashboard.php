<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/Database.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Dashboard Overview';

// Real counts — one quick query per card. If your tables genuinely have no
// rows yet (no test reservations/checkouts/items entered), these will
// correctly show 0 too — that's accurate, not a bug.
$db = Database::getConnection();

$pendingRequests = $db->query(
    "SELECT COUNT(*) FROM reservations WHERE status = 'pending'"
)->fetchColumn();

$activeLoans = $db->query(
    "SELECT COUNT(*) FROM checkouts WHERE returned_at IS NULL"
)->fetchColumn();

$overdueItems = $db->query(
    "SELECT COUNT(*) FROM checkouts WHERE returned_at IS NULL AND due_at < NOW()"
)->fetchColumn();

$availableEquipment = $db->query(
    "SELECT COUNT(*) FROM serialized_items WHERE status = 'available'"
)->fetchColumn();
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
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon warning">⏳</div>
                    <div class="stat-details">
                        <span class="stat-label">Pending Requests</span>
                        <h3 class="stat-value"><?= (int) $pendingRequests ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon primary">📦</div>
                    <div class="stat-details">
                        <span class="stat-label">Active Loans</span>
                        <h3 class="stat-value"><?= (int) $activeLoans ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon danger">⚠️</div>
                    <div class="stat-details">
                        <span class="stat-label">Overdue Items</span>
                        <h3 class="stat-value"><?= (int) $overdueItems ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">✅</div>
                    <div class="stat-details">
                        <span class="stat-label">Available Equipment</span>
                        <h3 class="stat-value"><?= (int) $availableEquipment ?></h3>
                    </div>
                </div>
            </div>
            <!-- Recent Requests Table Card -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
                
                <!-- LEFT COLUMN -->
                <div>
                    <!-- Pending Reservation Requests Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; font-size: 16px; color: #111c43;">Pending Reservation Requests</h3>
                        <span style="font-size: 12px; color: #a0aec0;">3 requests waiting approval</span>
                    </div>

                    <!-- Request Card 1 (Justin Peralta) -->
                    <div style="background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Borrower</p>
                            <h4 style="margin: 3px 0; font-size: 14px; color: #111c43;">Justin Peralta</h4>
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">09515825198</p>
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Equipment Requested</p>
                            <h4 style="margin: 3px 0; font-size: 14px; color: #111c43;">Sony Alpha Camera</h4>
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Serial: SN-10201</p>
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Timeline</p>
                            <h4 style="margin: 3px 0; font-size: 14px; color: #111c43;">7 Days</h4>
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Requested: 2026-12-11</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button style="background: #fff; border: 1px solid #ff3333; color: #ff3333; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Decline</button>
                            <button style="background: #00cc44; border: none; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Approve</button>
                        </div>
                    </div>

                    <!-- Request Card 2 (Emma Watson) -->
                    <div style="background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Borrower</p>
                            <h4 style="margin: 3px 0; font-size: 14px; color: #111c43;">Emma Watson</h4>
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">09123456789</p>
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Equipment Requested</p>
                            <h4 style="margin: 3px 0; font-size: 14px; color: #111c43;">Dell XPS Laptop</h4>
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Serial: SN-40502</p>
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Timeline</p>
                            <h4 style="margin: 3px 0; font-size: 14px; color: #111c43;">3 Days</h4>
                            <p style="margin: 0; font-size: 12px; color: #a0aec0;">Requested: 2026-12-11</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button style="background: #fff; border: 1px solid #ff3333; color: #ff3333; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Decline</button>
                            <button style="background: #00cc44; border: none; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Approve</button>
                        </div>
                    </div>

                    <!-- Equipment Availability Breakdown -->
                    <div style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                        <h3 style="margin: 0 0 20px 0; font-size: 16px; color: #111c43;">Equipment Availability Breakdown</h3>
                        
                        <!-- Laptop Progress -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; font-weight: 600; color: #111c43;">Laptop (Dell XPS)</span>
                                <span style="font-size: 12px; color: #a0aec0;">Available: 10/30 • Maint: 2</span>
                            </div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 3px; width: 100%;">
                                <div style="background: #4318ff; width: 33%; height: 100%; border-radius: 3px;"></div>
                            </div>
                        </div>

                        <!-- Projector Progress -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; font-weight: 600; color: #111c43;">Projector (Epson)</span>
                                <span style="font-size: 12px; color: #a0aec0;">Available: 8/12 • Maint: 0</span>
                            </div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 3px; width: 100%;">
                                <div style="background: #4318ff; width: 66%; height: 100%; border-radius: 3px;"></div>
                            </div>
                        </div>

                        <!-- Camera Progress -->
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 13px; font-weight: 600; color: #111c43;">Sony Alpha Camera</span>
                                <span style="font-size: 12px; color: #a0aec0;">Available: 4/10 • Maint: 1</span>
                            </div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 3px; width: 100%;">
                                <div style="background: #4318ff; width: 40%; height: 100%; border-radius: 3px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <!-- Recent Logs Card -->
                    <div style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); height: 100%;">
                        <h3 style="margin: 0 0 20px 0; font-size: 16px; color: #111c43;">Recent Logs</h3>
                        
                        <!-- Log Item 1 -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f4f7fe; padding-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0; font-size: 13px; color: #4318ff;">REQ-101</h4>
                                <p style="margin: 3px 0; font-size: 13px; font-weight: 600; color: #111c43;">Justin Peralta</p>
                                <p style="margin: 0; font-size: 12px; color: #a0aec0;">Returned Unit</p>
                            </div>
                            <span style="font-size: 12px; color: #a0aec0;">2026-12-11</span>
                        </div>

                        <!-- Log Item 2 -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f4f7fe; padding-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0; font-size: 13px; color: #4318ff;">REQ-102</h4>
                                <p style="margin: 3px 0; font-size: 13px; font-weight: 600; color: #111c43;">Emma Watson</p>
                                <p style="margin: 0; font-size: 12px; color: #a0aec0;">Approved Request</p>
                            </div>
                            <span style="font-size: 12px; color: #a0aec0;">2026-12-11</span>
                        </div>

                        <!-- Log Item 3 -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f4f7fe; padding-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0; font-size: 13px; color: #4318ff;">REQ-088</h4>
                                <p style="margin: 3px 0; font-size: 13px; font-weight: 600; color: #111c43;">Aris Santos</p>
                                <p style="margin: 0; font-size: 12px; color: #a0aec0;">Checked Out</p>
                            </div>
                            <span style="font-size: 12px; color: #a0aec0;">2026-12-10</span>
                        </div>

                        <!-- Log Item 4 -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f4f7fe; padding-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0; font-size: 13px; color: #4318ff;">REQ-087</h4>
                                <p style="margin: 3px 0; font-size: 13px; font-weight: 600; color: #111c43;">Chloe Mendez</p>
                                <p style="margin: 0; font-size: 12px; color: #a0aec0;">Returned Unit</p>
                            </div>
                            <span style="font-size: 12px; color: #a0aec0;">2026-12-10</span>
                        </div>

                        <!-- Log Item 5 -->
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <h4 style="margin: 0; font-size: 13px; color: #4318ff;">REQ-085</h4>
                                <p style="margin: 3px 0; font-size: 13px; font-weight: 600; color: #111c43;">James Cruz</p>
                                <p style="margin: 0; font-size: 12px; color: #a0aec0;">Checked Out</p>
                            </div>
                            <span style="font-size: 12px; color: #a0aec0;">2026-12-09</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
