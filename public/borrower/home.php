<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Home Overview';
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
                TEMPORARY DATA BELOW.
                Everything in this dashboard-grid is placeholder markup until
                Reservation.php / Equipment.php are built. Swap each block for
                a real query against reservations / checkouts / wishlist_items
                for $_SESSION['user_id'] when that's ready.
            -->
            <div class="dashboard-grid">

                <div class="left-column">
                    <!-- Request Pending -->
                    <div class="section-header">
                        <h3 class="section-title">Request Pending</h3>
                        <span class="section-subtitle">Awaiting staff approval</span>
                    </div>

                    <div class="pending-grid"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <!-- Pending Card 1 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; box-shadow: none; border-radius: 8px; padding: 1.25rem;">
                            <h5 style="margin-bottom: 0.25rem; font-size: 1rem; color: #0f172a;">Science Book (Biology)
                            </h5>
                            <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.85rem;">Duration: Sept 12 - Sept
                                15, 2026</p>
                            <div style="display: flex; justify-content: flex-start; gap: 0.5rem; align-items: center;">
                                <span
                                    style="background-color: #fef08a; color: #854d0e; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Pending
                                    Request</span>

                                <!-- CLEANED UP BUTTON -->
                                <button type="button" class="btn-cancel-outline"
                                    onclick="prepareCancel(this)">Cancel</button>
                            </div>
                        </div>

                        <!-- Pending Card 2 -->
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; box-shadow: none; border-radius: 8px; padding: 1.25rem;">
                            <h5 style="margin-bottom: 0.25rem; font-size: 1rem; color: #0f172a;">Science Book (Biology)
                            </h5>
                            <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.85rem;">Duration: Sept 12 - Sept
                                15, 2026</p>
                            <div style="display: flex; justify-content: flex-start; gap: 0.5rem; align-items: center;">
                                <span
                                    style="background-color: #fef08a; color: #854d0e; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Pending
                                    Request</span>

                                <!-- CLEANED UP BUTTON -->
                                <button type="button" class="btn-cancel-outline"
                                    onclick="prepareCancel(this)">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Requests -->
                    <div class="section-header">
                        <h3 class="section-title">Approved Requests (Ready to Pick Up)</h3>
                    </div>

                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; box-shadow: none; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h5>DELL XPS 15 LAPTOP (SN-1002)</h5>
                            <p style="margin-bottom: 0; color: #64748b; font-size: 0.85rem;">Approve Date: Sept 12, 2026
                                • Duration: 3 Days</p>
                        </div>
                        <button class="btn btn-primary btn-rect" onclick="openModal('pickupModal')">Pick Up
                            Item</button>
                    </div>

                    <!-- My Wishlist -->
                    <div class="section-header">
                        <h3 class="section-title">My Wishlist</h3>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; box-shadow: none; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h5>Advanced Research Chemistry Kit #1</h5>
                                <p style="margin-bottom: 0; color: #64748b; font-size: 0.85rem;">Max Duration: 7 Days
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-sm btn-outline btn-rect"
                                    onclick="openModal('removeWishlistModal')">Remove</button>
                                <button class="btn btn-sm btn-primary btn-rect"
                                    onclick="openModal('reserveModal')">Reserve</button>
                            </div>
                        </div>

                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; box-shadow: none; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h5>Advanced Research Chemistry Kit #2</h5>
                                <p style="margin-bottom: 0; color: #64748b; font-size: 0.85rem;">Max Duration: 7 Days
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-sm btn-outline btn-rect">Remove</button>
                                <button class="btn btn-sm btn-primary btn-rect">Reserve</button>
                            </div>
                        </div>

                        <div class="item-card bg-white"
                            style="border: 1px solid #e2e8f0; box-shadow: none; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h5>Advanced Research Chemistry Kit #3</h5>
                                <p style="margin-bottom: 0; color: #64748b; font-size: 0.85rem;">Max Duration: 7 Days
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-sm btn-outline btn-rect">Remove</button>
                                <button class="btn btn-sm btn-primary btn-rect">Reserve</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="right-column">
                    <!-- Available Items -->
                    <div class="section-header">
                        <h3 class="section-title">Available Items</h3>
                        <a href="/campusequip/public/borrower/catalog.php" class="section-link"
                            style="font-weight: 600; text-decoration: none; color: #2563eb;">View All</a>
                    </div>

                    <div class="card-container bg-white"
                        style="border: 1px solid #e2e8f0; padding: 1.25rem; border-radius: 8px;">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem;">
                                <div>
                                    <h5 style="margin-bottom: 0.2rem; font-size: 0.95rem;">Sony Alpha Camera 4K</h5>
                                    <p class="text-success"
                                        style="margin: 0; font-weight: 600; font-size: 0.75rem; color: #16a34a;">16
                                        Available</p>
                                </div>
                                <button class="btn btn-sm btn-outline btn-rect"
                                    onclick="openModal('viewItemModal')">View</button>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem;">
                                <div>
                                    <h5 style="margin-bottom: 0.2rem; font-size: 0.95rem;">Dell XPS 15 Workstation</h5>
                                    <p class="text-success"
                                        style="margin: 0; font-weight: 600; font-size: 0.75rem; color: #16a34a;">4
                                        Available</p>
                                </div>
                                <button class="btn btn-sm btn-outline btn-rect">View</button>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem;">
                                <div>
                                    <h5 style="margin-bottom: 0.2rem; font-size: 0.95rem;">Arduino Uno Basic Kit</h5>
                                    <p class="text-success"
                                        style="margin: 0; font-weight: 600; font-size: 0.75rem; color: #16a34a;">25
                                        Available</p>
                                </div>
                                <button class="btn btn-sm btn-outline btn-rect">View</button>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem;">
                                <div>
                                    <h5 style="margin-bottom: 0.2rem; font-size: 0.95rem;">Asus Vivobook Laptop</h5>
                                    <p class="text-success"
                                        style="margin: 0; font-weight: 600; font-size: 0.75rem; color: #16a34a;">9
                                        Available</p>
                                </div>
                                <button class="btn btn-sm btn-outline btn-rect">View</button>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem;">
                                <div>
                                    <h5 style="margin-bottom: 0.2rem; font-size: 0.95rem;">Biology Lab Pipette Set</h5>
                                    <p class="text-success"
                                        style="margin: 0; font-weight: 600; font-size: 0.75rem; color: #16a34a;">30
                                        Available</p>
                                </div>
                                <button class="btn btn-sm btn-outline btn-rect">View</button>
                            </div>
                        </div>
                    </div>

                    <!-- Picked Up Item -->
                    <div class="section-header" style="margin-top: 1.5rem;">
                        <h3 class="section-title">Picked Up Item</h3>
                    </div>

                    <div class="item-card bg-white" style="border: 1px solid #e2e8f0; box-shadow: none;">
                        <h5>Science Book (Physics)</h5>
                        <p style="margin-bottom: 0.25rem; color: #64748b; font-size: 0.85rem;">Checkout: Sept 14, 2026
                        </p>
                        <p style="margin-bottom: 0; color: #64748b; font-size: 0.85rem;">Return due: Sept 21, 2026</p>
                    </div>
                </div>
            </div>

            <!-- 1. Cancel Request Modal -->
            <div class="modal-overlay" id="cancelModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Cancel Request</h2>
                        </div>
                        <button class="modal-close" onclick="closeModal('cancelModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this pending request? You will lose your place in the queue.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('cancelModal')">Keep
                            Request</button>
                        <button type="button" onclick="executeCancel()"
                            style="background: #dc2626; color: white; border: none; border-radius: 6px; padding: 0.6rem 1.25rem; font-weight: 600; cursor: pointer;">Yes,
                            Cancel</button>
                    </div>
                </div>
            </div>

            <!-- 2. Pick Up Item Modal -->
            <div class="modal-overlay" id="pickupModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Confirm Pickup</h2>
                        </div>
                        <button class="modal-close" onclick="closeModal('pickupModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Please present your Student ID to the equipment desk staff to claim your approved item.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('pickupModal')">Close</button>
                        <button class="btn btn-primary btn-rect" onclick="closeModal('pickupModal')">Mark as Picked
                            Up</button>
                    </div>
                </div>
            </div>

            <!-- 3. Remove from Wishlist Modal -->
            <div class="modal-overlay" id="removeWishlistModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Remove Item</h2>
                        </div>
                        <button class="modal-close" onclick="closeModal('removeWishlistModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Remove this item from your wishlist?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect"
                            onclick="closeModal('removeWishlistModal')">Cancel</button>
                        <button class="btn btn-danger btn-rect"
                            onclick="closeModal('removeWishlistModal')">Remove</button>
                    </div>
                </div>
            </div>

            <!-- 4. Reserve Item Modal -->
            <div class="modal-overlay" id="reserveModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Reserve Equipment</h2>
                            <p class="modal-subtitle">Select your borrowing dates.</p>
                        </div>
                        <button class="modal-close" onclick="closeModal('reserveModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
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
                        <button class="btn btn-outline btn-rect" onclick="closeModal('reserveModal')">Cancel</button>
                        <button class="btn btn-primary btn-rect" onclick="closeModal('reserveModal')">Submit
                            Request</button>
                    </div>
                </div>
            </div>

            <!-- 5. View Item Details Modal -->
            <div class="modal-overlay" id="viewItemModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Item Details</h2>
                        </div>
                        <button class="modal-close" onclick="closeModal('viewItemModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h4 style="margin-bottom: 0.5rem;">Specifications</h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">Includes carrying case,
                            standard charger, and original manual. Limit 1 per student.</p>
                        <span class="text-success"
                            style="font-weight: 600; font-size: 0.85rem; color: #16a34a;">Currently in stock at Main
                            Library Desk</span>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline btn-rect" onclick="closeModal('viewItemModal')">Close</button>
                        <button class="btn btn-primary btn-rect" onclick="closeModal('viewItemModal')">Reserve</button>
                    </div>
                </div>
            </div>

            <!-- Notification Modal -->
            <div class="modal-overlay" id="notificationModal">
                <div class="modal" style="max-width: 28rem; position: absolute; top: 4rem; right: 2rem; margin: 0;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Notifications</h2>
                        </div>
                        <button class="modal-close" onclick="closeModal('notificationModal')">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 0;">
                        <!-- Fake Notification 1 -->
                        <div style="padding: 1rem; border-bottom: 1px solid #e2e8f0; display: flex; gap: 1rem;">
                            <div
                                style="width: 8px; height: 8px; background-color: #2563eb; border-radius: 50%; margin-top: 0.4rem;">
                            </div>
                            <div>
                                <p style="margin: 0; font-size: 0.9rem; font-weight: 500;">Request Approved</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #64748b;">Your request for
                                    DELL XPS 15 LAPTOP has been approved. Please pick it up.</p>
                                <span style="font-size: 0.7rem; color: #94a3b8; display: block; margin-top: 0.5rem;">2
                                    hours ago</span>
                            </div>
                        </div>
                        <!-- Fake Notification 2 -->
                        <div style="padding: 1rem; display: flex; gap: 1rem; opacity: 0.7;">
                            <div style="width: 8px; height: 8px; border-radius: 50%; margin-top: 0.4rem;"></div>
                            <div>
                                <p style="margin: 0; font-size: 0.9rem; font-weight: 500;">Return Reminder</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #64748b;">Your Science Book
                                    (Physics) is due tomorrow.</p>
                                <span style="font-size: 0.7rem; color: #94a3b8; display: block; margin-top: 0.5rem;">1
                                    day ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Logic Script -->
            <script>
                function openModal(modalId) {
                    document.getElementById(modalId).classList.add('open');
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).classList.remove('open');
                }

                // Close modal when clicking the dark background overlay
                document.addEventListener('DOMContentLoaded', () => {
                    const overlays = document.querySelectorAll('.modal-overlay');
                    overlays.forEach(overlay => {
                        overlay.addEventListener('click', (e) => {
                            if (e.target === overlay) {
                                closeModal(overlay.id);
                            }
                        });
                    });
                });

                // Variable to remember which card we are trying to cancel
                let itemToCancel = null;

                function prepareCancel(buttonElement) {
                    // Block other scripts from hijacking this click
                    let e = window.event;
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }

                    // Find the card and open the modal
                    itemToCancel = buttonElement.closest('.item-card');
                    openModal('cancelModal');
                }

                function executeCancel() {
                    // Block other scripts
                    let e = window.event;
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }

                    // Close the modal instantly
                    closeModal('cancelModal');

                    // Apply the smooth shrink/fade animation
                    if (itemToCancel) {
                        itemToCancel.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        itemToCancel.style.opacity = '0';
                        itemToCancel.style.transform = 'scale(0.95)';

                        // Wait 300ms for the animation to finish before removing it from the layout
                        setTimeout(() => {
                            itemToCancel.style.display = 'none';
                        }, 300);
                    }

                    // Fire the toast notification!
                    if (typeof showToast === 'function') {
                        showToast('Request cancelled', 'info');
                    }

                    // Clear memory for the next time
                    itemToCancel = null;
                }
            </script>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>