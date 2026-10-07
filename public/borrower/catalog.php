<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Equipment Catalog';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/../../includes/html-head.php'; ?>

    <!-- Override global script interference for modals -->
    <style>
        .modal-overlay.open .modal {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
    </style>
</head>

<body>

    <div class="dashboard-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>

            <!-- The padding wrapper keeps everything aligned -->
            <div style="padding: 1.5rem 2rem;">

                <h3 style="font-size: 1.15rem; font-weight: 600; color: #0f172a; margin-top: 0; margin-bottom: 1.5rem;">
                    Browse All Academic Equipment</h3>

                <!-- Six-Card Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">

                    <!-- Card 1 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: none; display: flex; flex-direction: column;">
                        <div style="padding: 1.25rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a;">Sony Alpha
                                    Camera 4K</h3>
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">4
                                    Available</span>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Category: Camera &bull; Max
                                Duration: 3 Days</p>
                        </div>
                        <div
                            style="border-top: 1px solid #e2e8f0; padding: 1.25rem; display: flex; gap: 0.75rem; align-items: center;">
                            <button type="button" class="btn btn-primary btn-rect"
                                style="font-weight: 600; padding: 0.5rem 1.5rem;"
                                onclick="openModal('reserveModal')">Reserve Now</button>
                            <button type="button" class="btn btn-outline btn-rect"
                                style="padding: 0.5rem 0.75rem; transition: 0.2s;" onclick="toggleSave(this)">
                                <i class="ph ph-bookmark" style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: none; display: flex; flex-direction: column;">
                        <div style="padding: 1.25rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a;">Dell XPS 15
                                    Workstation</h3>
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">2
                                    Available</span>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Category: Laptop &bull; Max
                                Duration: 7 Days</p>
                        </div>
                        <div
                            style="border-top: 1px solid #e2e8f0; padding: 1.25rem; display: flex; gap: 0.75rem; align-items: center;">
                            <button type="button" class="btn btn-primary btn-rect"
                                style="font-weight: 600; padding: 0.5rem 1.5rem;"
                                onclick="openModal('reserveModal')">Reserve Now</button>
                            <button type="button" class="btn btn-outline btn-rect"
                                style="padding: 0.5rem 0.75rem; transition: 0.2s;" onclick="toggleSave(this)">
                                <i class="ph ph-bookmark" style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: none; display: flex; flex-direction: column;">
                        <div style="padding: 1.25rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a;">Microbiology
                                    Microscope v2</h3>
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">1
                                    Available</span>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Category: Lab Gear &bull; Max
                                Duration: 5 Days</p>
                        </div>
                        <div
                            style="border-top: 1px solid #e2e8f0; padding: 1.25rem; display: flex; gap: 0.75rem; align-items: center;">
                            <button type="button" class="btn btn-primary btn-rect"
                                style="font-weight: 600; padding: 0.5rem 1.5rem;"
                                onclick="openModal('reserveModal')">Reserve Now</button>
                            <button type="button" class="btn btn-outline btn-rect"
                                style="padding: 0.5rem 0.75rem; transition: 0.2s;" onclick="toggleSave(this)">
                                <i class="ph ph-bookmark" style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: none; display: flex; flex-direction: column;">
                        <div style="padding: 1.25rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a;">MacBook Pro
                                    M3 Max</h3>
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">8
                                    Available</span>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Category: Laptop &bull; Max
                                Duration: 3 Days</p>
                        </div>
                        <div
                            style="border-top: 1px solid #e2e8f0; padding: 1.25rem; display: flex; gap: 0.75rem; align-items: center;">
                            <button type="button" class="btn btn-primary btn-rect"
                                style="font-weight: 600; padding: 0.5rem 1.5rem;"
                                onclick="openModal('reserveModal')">Reserve Now</button>
                            <button type="button" class="btn btn-outline btn-rect"
                                style="padding: 0.5rem 0.75rem; transition: 0.2s;" onclick="toggleSave(this)">
                                <i class="ph ph-bookmark" style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card 5 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: none; display: flex; flex-direction: column;">
                        <div style="padding: 1.25rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a;">GoPro Hero
                                    12 Action Cam</h3>
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">6
                                    Available</span>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Category: Camera &bull; Max
                                Duration: 2 Days</p>
                        </div>
                        <div
                            style="border-top: 1px solid #e2e8f0; padding: 1.25rem; display: flex; gap: 0.75rem; align-items: center;">
                            <button type="button" class="btn btn-primary btn-rect"
                                style="font-weight: 600; padding: 0.5rem 1.5rem;"
                                onclick="openModal('reserveModal')">Reserve Now</button>
                            <button type="button" class="btn btn-outline btn-rect"
                                style="padding: 0.5rem 0.75rem; transition: 0.2s;" onclick="toggleSave(this)">
                                <i class="ph ph-bookmark" style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: none; display: flex; flex-direction: column;">
                        <div style="padding: 1.25rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a;">Arduino
                                    Robotics Toolkit</h3>
                                <span
                                    style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 9999px;">12
                                    Available</span>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Category: Robotics &bull; Max
                                Duration: 10 Days</p>
                        </div>
                        <div
                            style="border-top: 1px solid #e2e8f0; padding: 1.25rem; display: flex; gap: 0.75rem; align-items: center;">
                            <button type="button" class="btn btn-primary btn-rect"
                                style="font-weight: 600; padding: 0.5rem 1.5rem;"
                                onclick="openModal('reserveModal')">Reserve Now</button>
                            <button type="button" class="btn btn-outline btn-rect"
                                style="padding: 0.5rem 0.75rem; transition: 0.2s;" onclick="toggleSave(this)">
                                <i class="ph ph-bookmark" style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Reserve Equipment Modal -->
            <!-- Reserve Equipment Modal -->
            <div class="modal-overlay" id="reserveModal">
                <div class="modal" style="max-width: 32rem; border-radius: 12px; overflow: hidden; background: white;">

                    <!-- Header -->
                    <div class="modal-header"
                        style="border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">New Reservation
                            Request</h2>
                        <button class="modal-close" onclick="closeModal('reserveModal')"
                            style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: 0.2s;">
                            <i class="ph ph-x" style="font-size: 1.1rem;"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body" style="padding: 1.5rem;">
                        <!-- Equipment Info Box -->
                        <div style="background: #f8fafc; border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.85rem; color: #64748b;">Equipment Name</span>
                                <span style="font-size: 0.85rem; font-weight: 600; color: #0f172a;">Sony Alpha Camera
                                    4K</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-size: 0.85rem; color: #64748b;">Max Duration</span>
                                <span style="font-size: 0.85rem; font-weight: 600; color: #0f172a;">Up to 7 Days</span>
                            </div>
                        </div>

                        <!-- Date Inputs -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                            <div>
                                <label
                                    style="display: block; font-size: 0.85rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">Start
                                    Date</label>
                                <input type="date"
                                    style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; color: #64748b; outline: none;">
                            </div>
                            <div>
                                <label
                                    style="display: block; font-size: 0.85rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">End
                                    Date</label>
                                <input type="date"
                                    style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; color: #64748b; outline: none;">
                            </div>
                        </div>

                        <!-- Purpose Textarea -->
                        <div style="margin-bottom: 1.25rem;">
                            <label
                                style="display: block; font-size: 0.85rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">Purpose</label>
                            <textarea placeholder="Describe how you will use this equipment..."
                                style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; height: 80px; resize: none; outline: none; font-family: inherit; box-sizing: border-box;"></textarea>
                        </div>

                        <!-- Checkbox -->
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="agreeTerms" checked
                                style="width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer;">
                            <label for="agreeTerms" style="font-size: 0.85rem; color: #475569; cursor: pointer;">I agree
                                to return on time and assume responsibility for damages.</label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer"
                        style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; background: #ffffff;">
                        <button type="button" onclick="closeModal('reserveModal')"
                            style="padding: 0.6rem 1.25rem; border: 1px solid #cbd5e1; background: white; color: #334155; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">Cancel</button>
                        <button type="button" onclick="submitReservation()"
                            style="padding: 0.6rem 1.25rem; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">Submit
                            Request</button>
                    </div>
                </div>
            </div>

            <!-- Scripts -->
            <script>
                function openModal(id) {
                    var overlay = document.getElementById(id);
                    if (overlay) {
                        var innerModal = overlay.querySelector('.modal');
                        if (innerModal) innerModal.style.display = 'block';
                        overlay.classList.add('open');
                    }
                }

                function closeModal(id) {
                    var overlay = document.getElementById(id);
                    if (overlay) overlay.classList.remove('open');
                }

                function submitReservation() {
                    alert("Success! Your reservation request has been submitted to the staff.");
                    closeModal('reserveModal');
                }

                // Interactive Toggle for the Save Bookmark Button
                function toggleSave(btn) {
                    let icon = btn.querySelector('i');
                    icon.classList.toggle('ph-fill');

                    if (icon.classList.contains('ph-fill')) {
                        icon.style.color = '#2563eb';
                        btn.style.borderColor = '#bfdbfe';
                        btn.style.background = '#eff6ff';
                    } else {
                        icon.style.color = '';
                        btn.style.borderColor = '';
                        btn.style.background = 'transparent';
                    }
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