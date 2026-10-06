<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/Database.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Dashboard Overview';

// Real counts — one quick query per card. If your tables genuinely have no
// rows yet (no test reservations/checkouts/items entered), these will
// correctly show 0 too — that's accurate, not a bug.
// Temporary mock data for UI design (bypassing the database)
$pendingRequests = 23;
$activeLoans = 13;
$overdueItems = 3;
$availableEquipment = 148;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>
</head>

<body>
    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <!-- Stat cards (same order as Figma) -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Total Items</span>
                        <h3 class="stat-value"><?= (int) $availableEquipment ?></h3>
                    </div>
                    <div class="stat-icon primary">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 3 3 8l9 5 9-5-9-5Z" />
                            <path d="m3 13 9 5 9-5" />
                            <path d="m3 17.5 9 5 9-5" opacity=".5" />
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Active Checkouts</span>
                        <h3 class="stat-value"><?= (int) $activeLoans ?></h3>
                    </div>
                    <div class="stat-icon success">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 11a8 8 0 0 0-14.5-4M4 5v3h3" />
                            <path d="M4 13a8 8 0 0 0 14.5 4M20 19v-3h-3" />
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Overdue Items</span>
                        <h3 class="stat-value"><?= (int) $overdueItems ?></h3>
                    </div>
                    <div class="stat-icon danger">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 4 2.5 20h19L12 4Z" />
                            <path d="M12 10v4M12 17v.01" />
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-details">
                        <span class="stat-label">Pending Requests</span>
                        <h3 class="stat-value"><?= (int) $pendingRequests ?></h3>
                    </div>
                    <div class="stat-icon warning">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
                            <path d="M14 3v5h5M9 13h6M9 17h6" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">

                <!-- LEFT COLUMN -->
                <div>
                    <div class="section-header">
                        <h3 class="section-title">Pending Reservation Requests</h3>
                        <span class="section-subtitle">3 requests waiting approval</span>
                    </div>

                    <!-- Request Card 1 -->
                    <div class="request-card">
                        <div class="request-meta">
                            <div>
                                <p class="meta-label">Borrower</p>
                                <h4 class="meta-value">Justin Peralta</h4>
                                <p class="meta-sub">09515825198</p>
                            </div>
                            <div>
                                <p class="meta-label">Equipment Requested</p>
                                <h4 class="meta-value">Sony Alpha Camera</h4>
                                <p class="meta-sub">Serial: SN-10201</p>
                            </div>
                            <div>
                                <p class="meta-label">Timeline</p>
                                <h4 class="meta-value">7 Days</h4>
                                <p class="meta-sub">Requested: 2026-12-11</p>
                            </div>
                        </div>
                        <div class="request-actions">
                            <button class="btn-decline">Decline</button>
                            <button class="btn-approve">Approve</button>
                        </div>
                    </div>

                    <!-- Request Card 2 -->
                    <div class="request-card">
                        <div class="request-meta">
                            <div>
                                <p class="meta-label">Borrower</p>
                                <h4 class="meta-value">Emma Watson</h4>
                                <p class="meta-sub">09123456789</p>
                            </div>
                            <div>
                                <p class="meta-label">Equipment Requested</p>
                                <h4 class="meta-value">Dell XPS Laptop</h4>
                                <p class="meta-sub">Serial: SN-40502</p>
                            </div>
                            <div>
                                <p class="meta-label">Timeline</p>
                                <h4 class="meta-value">3 Days</h4>
                                <p class="meta-sub">Requested: 2026-12-11</p>
                            </div>
                        </div>
                        <div class="request-actions">
                            <button class="btn-decline">Decline</button>
                            <button class="btn-approve">Approve</button>
                        </div>
                    </div>

                    <!-- Equipment Availability Breakdown -->
                    <div class="card-container">
                        <h3 class="section-title" style="margin-bottom: 1.25rem;">Equipment Availability Breakdown</h3>

                        <div class="availability-row">
                            <div class="availability-head">
                                <span class="availability-name">Laptop (Dell XPS)</span>
                                <span class="availability-meta">Available: 10/30 • Maint: 2</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: 33%;"></div>
                            </div>
                        </div>

                        <div class="availability-row">
                            <div class="availability-head">
                                <span class="availability-name">Projector (Epson)</span>
                                <span class="availability-meta">Available: 8/12 • Maint: 0</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: 66%;"></div>
                            </div>
                        </div>

                        <div class="availability-row">
                            <div class="availability-head">
                                <span class="availability-name">Sony Alpha Camera</span>
                                <span class="availability-meta">Available: 4/10 • Maint: 1</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: 40%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <div class="card-container">
                        <h3 class="section-title" style="margin-bottom: 1rem;">Recent Logs</h3>

                        <div class="log-item">
                            <div>
                                <h4 class="log-id">REQ-101</h4>
                                <p class="log-name">Justin Peralta</p>
                                <p class="log-note">Returned Unit</p>
                            </div>
                            <span class="log-date">2026-12-11</span>
                        </div>

                        <div class="log-item">
                            <div>
                                <h4 class="log-id">REQ-102</h4>
                                <p class="log-name">Emma Watson</p>
                                <p class="log-note">Approved Request</p>
                            </div>
                            <span class="log-date">2026-12-11</span>
                        </div>

                        <div class="log-item">
                            <div>
                                <h4 class="log-id">REQ-088</h4>
                                <p class="log-name">Aris Santos</p>
                                <p class="log-note">Checked Out</p>
                            </div>
                            <span class="log-date">2026-12-10</span>
                        </div>

                        <div class="log-item">
                            <div>
                                <h4 class="log-id">REQ-087</h4>
                                <p class="log-name">Chloe Mendez</p>
                                <p class="log-note">Returned Unit</p>
                            </div>
                            <span class="log-date">2026-12-10</span>
                        </div>

                        <div class="log-item">
                            <div>
                                <h4 class="log-id">REQ-085</h4>
                                <p class="log-name">James Cruz</p>
                                <p class="log-note">Checked Out</p>
                            </div>
                            <span class="log-date">2026-12-09</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>