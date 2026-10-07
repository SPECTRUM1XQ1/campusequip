<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Saved Items';
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

            <div style="padding: 1.5rem 2rem;">

                <!-- Watchlist Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #0f172a;">Your Personal Watchlist
                    </h2>
                    <div style="display: flex; gap: 1rem;">
                        <button class="btn btn-outline btn-rect"
                            style="padding: 0.5rem 1.25rem; font-weight: 600;">Clear All</button>
                        <button class="btn btn-primary btn-rect"
                            style="padding: 0.5rem 1.25rem; font-weight: 600;">Select Items</button>
                    </div>
                </div>

                <!-- Two-Column Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

                    <!-- Left Column: OUT OF SERVICE -->
                    <div>
                        <h4
                            style="font-size: 0.85rem; font-weight: 700; color: #64748b; letter-spacing: 0.05em; margin-bottom: 1rem; text-transform: uppercase;">
                            Out of Service / Under Repair
                        </h4>

                        <!-- Card 1 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: none;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                                    Asus Vivobook Lapto...</h3>
                                <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Max window: 1
                                    Day •<br>Academic Use Only</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span
                                    style="background: #fee2e2; color: #ef4444; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">Maintenance</span>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #2563eb; color: #2563eb; background: transparent; font-weight: 600;"
                                    onclick="notifyMe()">Notify</button>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #fecaca; color: #ef4444; background: transparent; padding: 0.4rem 0.5rem;"
                                    onclick="openModal('removeWishlistModal')">
                                    <i class="ph ph-trash" style="font-size: 1.1rem;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: none;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                                    Asus Vivobook Lapto...</h3>
                                <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Max window: 1
                                    Day •<br>Academic Use Only</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span
                                    style="background: #fee2e2; color: #ef4444; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">Maintenance</span>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #2563eb; color: #2563eb; background: transparent; font-weight: 600;"
                                    onclick="notifyMe()">Notify</button>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #fecaca; color: #ef4444; background: transparent; padding: 0.4rem 0.5rem;"
                                    onclick="openModal('removeWishlistModal')">
                                    <i class="ph ph-trash" style="font-size: 1.1rem;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: none;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                                    Asus Vivobook Lapto...</h3>
                                <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Max window: 1
                                    Day •<br>Academic Use Only</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span
                                    style="background: #fee2e2; color: #ef4444; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">Maintenance</span>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #2563eb; color: #2563eb; background: transparent; font-weight: 600;"
                                    onclick="notifyMe()">Notify</button>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #fecaca; color: #ef4444; background: transparent; padding: 0.4rem 0.5rem;"
                                    onclick="openModal('removeWishlistModal')">
                                    <i class="ph ph-trash" style="font-size: 1.1rem;"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: AVAILABLE NOW -->
                    <div>
                        <h4
                            style="font-size: 0.85rem; font-weight: 700; color: #64748b; letter-spacing: 0.05em; margin-bottom: 1rem; text-transform: uppercase;">
                            Available Now For Dispatch
                        </h4>

                        <!-- Card 1 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: none;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                                    Sony Alpha Cinema C...</h3>
                                <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Max window: 3
                                    Days •<br>Equipment Kit</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">4
                                    Available</span>
                                <button class="btn btn-sm btn-primary btn-rect" style="font-weight: 600;"
                                    onclick="openModal('reserveModal')">Reserve</button>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #fecaca; color: #ef4444; background: transparent; padding: 0.4rem 0.5rem;"
                                    onclick="openModal('removeWishlistModal')">
                                    <i class="ph ph-trash" style="font-size: 1.1rem;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: none;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                                    Dell XPS 15 Laptop</h3>
                                <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Max window: 3
                                    Days •<br>Equipment Kit</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">4
                                    Available</span>
                                <button class="btn btn-sm btn-primary btn-rect" style="font-weight: 600;"
                                    onclick="openModal('reserveModal')">Reserve</button>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #fecaca; color: #ef4444; background: transparent; padding: 0.4rem 0.5rem;"
                                    onclick="openModal('removeWishlistModal')">
                                    <i class="ph ph-trash" style="font-size: 1.1rem;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: none;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                                    Sony Alpha Cinema C...</h3>
                                <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Max window: 3
                                    Days •<br>Equipment Kit</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">4
                                    Available</span>
                                <button class="btn btn-sm btn-primary btn-rect" style="font-weight: 600;"
                                    onclick="openModal('reserveModal')">Reserve</button>
                                <button class="btn btn-sm btn-rect"
                                    style="border: 1px solid #fecaca; color: #ef4444; background: transparent; padding: 0.4rem 0.5rem;"
                                    onclick="openModal('removeWishlistModal')">
                                    <i class="ph ph-trash" style="font-size: 1.1rem;"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Confirm Remove Modal -->
            <div class="modal-overlay" id="removeWishlistModal">
                <div class="modal" style="max-width: 28rem; border-radius: 12px; overflow: hidden; background: white;">

                    <!-- Header -->
                    <div class="modal-header"
                        style="border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Confirm Action</h2>
                        <button class="modal-close" onclick="closeModal('removeWishlistModal')"
                            style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: 0.2s;">
                            <i class="ph ph-x" style="font-size: 1.1rem;"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body" style="padding: 1.5rem;">
                        <div
                            style="background: #f8fafc; border-radius: 8px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                            <i class="ph ph-warning" style="color: #ef4444; font-size: 1.25rem;"></i>
                            <span style="font-size: 0.9rem; color: #334155;">Are you sure you want to remove this
                                item?</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer"
                        style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; background: #ffffff;">
                        <button type="button" onclick="closeModal('removeWishlistModal')"
                            style="padding: 0.6rem 1.25rem; border: 1px solid #cbd5e1; background: white; color: #334155; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">Keep</button>
                        <button type="button" onclick="closeModal('removeWishlistModal')"
                            style="padding: 0.6rem 1.25rem; background: #dc2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">Confirm
                            Remove</button>
                    </div>
                </div>
            </div>

            <!-- Reserve Equipment Modal -->
            <div class="modal-overlay" id="reserveModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Reserve Equipment</h2>
                            <p class="modal-subtitle" style="margin-top: 0.25rem;">Select your borrowing dates.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('reserveModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div style="margin-bottom: 1rem;">
                            <label
                                style="display: block; font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 600;">Pickup
                                Date</label>
                            <input type="date"
                                style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 600;">Return
                                Date</label>
                            <input type="date"
                                style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline btn-rect"
                            onclick="closeModal('reserveModal')">Cancel</button>
                        <button type="button" class="btn btn-primary btn-rect" onclick="submitReservation()">Submit
                            Request</button>
                    </div>
                </div>
            </div>

            <!-- Notification Modal (Bell) -->
            <div class="modal-overlay" id="notificationModal">
                <div class="modal" style="max-width: 28rem; position: absolute; top: 4rem; right: 2rem; margin: 0;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Notifications</h2>
                        </div>
                        <button class="modal-close" onclick="closeModal('notificationModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 0;">
                        <div style="padding: 1rem; border-bottom: 1px solid #e2e8f0; display: flex; gap: 1rem;">
                            <div
                                style="width: 8px; height: 8px; background-color: #2563eb; border-radius: 50%; margin-top: 0.4rem;">
                            </div>
                            <div>
                                <p style="margin: 0; font-size: 0.9rem; font-weight: 500;">Request Approved</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #64748b;">Your request for
                                    DELL XPS 15 LAPTOP has been approved.</p>
                                <span style="font-size: 0.7rem; color: #94a3b8; display: block; margin-top: 0.5rem;">2
                                    hours ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scripts -->
            <script>
                function openModal(id) {
                    var overlay = document.getElementById(id);
                    if (overlay) {
                        // Force the white box to show, overriding any global script that hid it
                        var innerModal = overlay.querySelector('.modal');
                        if (innerModal) {
                            innerModal.style.display = 'block';
                        }
                        overlay.classList.add('open');
                    }
                }

                function closeModal(id) {
                    var overlay = document.getElementById(id);
                    if (overlay) {
                        overlay.classList.remove('open');
                    }
                }

                function submitReservation() {
                    alert("Success! Your reservation request has been submitted to the staff.");
                    closeModal('reserveModal');
                }

                function notifyMe() {
                    alert("You will be notified via email as soon as this item is back in service.");
                }

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('.modal-overlay').forEach(o => {
                        o.addEventListener('click', (e) => { if (e.target === o) closeModal(o.id); });
                    });
                });
            </script>

        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>