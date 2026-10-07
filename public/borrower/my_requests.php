<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin', 'staff']);

$pageTitle = 'Borrowing & Requests';
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

                <!-- Interactive Tabs -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                    <button id="tab-borrowed" onclick="switchTab('borrowed')"
                        style="display: flex; align-items: center; gap: 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.75rem 1.25rem; cursor: pointer; transition: 0.2s;">
                        <span
                            style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #2563eb; color: white; font-weight: 700; font-size: 0.85rem;">1</span>
                        <span style="font-weight: 600; font-size: 0.9rem; color: #1e3a8a;">Currently Borrowed</span>
                    </button>

                    <button id="tab-pending" onclick="switchTab('pending')"
                        style="display: flex; align-items: center; gap: 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1.25rem; cursor: pointer; transition: 0.2s;">
                        <span
                            style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 0.85rem;">2</span>
                        <span style="font-weight: 600; font-size: 0.9rem; color: #334155;">Pending Request</span>
                    </button>

                    <button id="tab-history" onclick="switchTab('history')"
                        style="display: flex; align-items: center; gap: 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1.25rem; cursor: pointer; transition: 0.2s;">
                        <span
                            style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 0.85rem;">12</span>
                        <span style="font-weight: 600; font-size: 0.9rem; color: #334155;">History</span>
                    </button>
                </div>

                <!-- Two-Column Grid -->
                <!-- Tab Content Sections -->
                <div id="content-borrowed" style="display: block;">
                    <h4
                        style="font-size: 0.85rem; font-weight: 700; color: #334155; letter-spacing: 0.05em; margin-bottom: 1rem; text-transform: uppercase;">
                        Currently in Your Possession</h4>

                    <!-- Card 1 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Sony Alpha
                                Camera 4K</h3>
                            <span
                                style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px;">Currently
                                Borrowed</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; margin-bottom: 1rem;">
                            <div><span style="color: #64748b;">Serial Number:</span> SN-CAM-002</div>
                            <div><span style="color: #64748b;">Request Code:</span> #REQ-2026-002</div>
                            <div><span style="color: #64748b;">Borrowed Date:</span> Sept 16, 2026 9:00 AM</div>
                            <div><span style="color: #64748b;">Due Date:</span> Sept 19, 2026 9:00 AM</div>
                        </div>
                        <div
                            style="display: flex; align-items: center; gap: 0.5rem; background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; padding: 0.6rem 0.85rem; border-radius: 6px; font-size: 0.8rem;">
                            <i class="ph ph-info" style="font-size: 1rem;"></i>
                            <span>Present your physical Student ID at the Lab Desk upon return.</span>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Sony Alpha
                                Camera 4K</h3>
                            <span
                                style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px;">Currently
                                Borrowed</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; margin-bottom: 1rem;">
                            <div><span style="color: #64748b;">Serial Number:</span> SN-CAM-002</div>
                            <div><span style="color: #64748b;">Request Code:</span> #REQ-2026-002</div>
                            <div><span style="color: #64748b;">Borrowed Date:</span> Sept 16, 2026 9:00 AM</div>
                            <div><span style="color: #64748b;">Due Date:</span> Sept 19, 2026 9:00 AM</div>
                        </div>
                        <div
                            style="display: flex; align-items: center; gap: 0.5rem; background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; padding: 0.6rem 0.85rem; border-radius: 6px; font-size: 0.8rem;">
                            <i class="ph ph-info" style="font-size: 1rem;"></i>
                            <span>Present your physical Student ID at the Lab Desk upon return.</span>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Sony Alpha
                                Camera 4K</h3>
                            <span
                                style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px;">Currently
                                Borrowed</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; margin-bottom: 1rem;">
                            <div><span style="color: #64748b;">Serial Number:</span> SN-CAM-002</div>
                            <div><span style="color: #64748b;">Request Code:</span> #REQ-2026-002</div>
                            <div><span style="color: #64748b;">Borrowed Date:</span> Sept 16, 2026 9:00 AM</div>
                            <div><span style="color: #64748b;">Due Date:</span> Sept 19, 2026 9:00 AM</div>
                        </div>
                        <div
                            style="display: flex; align-items: center; gap: 0.5rem; background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; padding: 0.6rem 0.85rem; border-radius: 6px; font-size: 0.8rem;">
                            <i class="ph ph-info" style="font-size: 1rem;"></i>
                            <span>Present your physical Student ID at the Lab Desk upon return.</span>
                        </div>
                    </div>
                </div>

                <div id="content-pending" style="display: none;">
                    <h4
                        style="font-size: 0.85rem; font-weight: 700; color: #334155; letter-spacing: 0.05em; margin-bottom: 1rem; text-transform: uppercase;">
                        Awaiting Staff Sign-Off</h4>

                    <!-- Card 1 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Dell XPS 15
                                Laptop</h3>
                            <span
                                style="background: #fef08a; color: #854d0e; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px;">Pending
                                Request</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; margin-bottom: 1.25rem;">
                            <div><span style="color: #64748b;">Serial Number:</span> SN-CAM-002</div>
                            <div><span style="color: #64748b;">Request Code:</span> #REQ-2026-003</div>
                            <div><span style="color: #64748b;">Request Window:</span> Sept 16, 2026 9:00 AM</div>
                            <div><span style="color: #64748b;">Purpose:</span> Capstone Presentation</div>
                            <div><span style="color: #64748b;">Status:</span> Awaiting Staff Approval</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: #94a3b8;">ID validation required at pickup.</span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-rect"
                                onclick="openModal('cancelModal')">Cancel Request</button>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Dell XPS 15
                                Laptop</h3>
                            <span
                                style="background: #fef08a; color: #854d0e; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px;">Pending
                                Request</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; margin-bottom: 1.25rem;">
                            <div><span style="color: #64748b;">Serial Number:</span> SN-CAM-002</div>
                            <div><span style="color: #64748b;">Request Code:</span> #REQ-2026-003</div>
                            <div><span style="color: #64748b;">Request Window:</span> Sept 16, 2026 9:00 AM</div>
                            <div><span style="color: #64748b;">Purpose:</span> Capstone Presentation</div>
                            <div><span style="color: #64748b;">Status:</span> Awaiting Staff Approval</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: #94a3b8;">ID validation required at pickup.</span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-rect"
                                onclick="openModal('cancelModal')">Cancel Request</button>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="item-card bg-white"
                        style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Dell XPS 15
                                Laptop</h3>
                            <span
                                style="background: #fef08a; color: #854d0e; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px;">Pending
                                Request</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; margin-bottom: 1.25rem;">
                            <div><span style="color: #64748b;">Serial Number:</span> SN-CAM-002</div>
                            <div><span style="color: #64748b;">Request Code:</span> #REQ-2026-003</div>
                            <div><span style="color: #64748b;">Request Window:</span> Sept 16, 2026 9:00 AM</div>
                            <div><span style="color: #64748b;">Purpose:</span> Capstone Presentation</div>
                            <div><span style="color: #64748b;">Status:</span> Awaiting Staff Approval</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: #94a3b8;">ID validation required at pickup.</span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-rect"
                                onclick="openModal('cancelModal')">Cancel Request</button>
                        </div>
                    </div>
                </div>

                <div id="content-history" style="display: none;">
                    <h4
                        style="font-size: 0.85rem; font-weight: 700; color: #334155; letter-spacing: 0.05em; margin-bottom: 1rem; text-transform: uppercase;">
                        Past Borrowing History</h4>
                    <div
                        style="padding: 3rem; text-align: center; background: white; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b;">
                        <i class="ph ph-clock-counter-clockwise"
                            style="font-size: 2.5rem; margin-bottom: 0.5rem; color: #94a3b8;"></i>
                        <p style="margin: 0;">Your past reservations will appear here.</p>
                    </div>
                </div>

            </div>

            <!-- Cancel Request Modal -->
            <div class="modal-overlay" id="cancelModal">
                <div class="modal" style="max-width: 28rem;">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">Cancel Request</h2>
                        </div>
                        <button type="button" class="modal-close" onclick="closeModal('cancelModal')">
                            <i class="ph ph-x" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this pending request? You will lose your place in the queue.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline btn-rect" onclick="closeModal('cancelModal')">Keep
                            Request</button>
                        <button type="button" class="btn btn-danger btn-rect" onclick="closeModal('cancelModal')">Yes,
                            Cancel</button>
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
                        <button type="button" class="modal-close" onclick="closeModal('notificationModal')">
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

            <!-- Modal Logic Script -->
            <script>
                function openModal(id) {
                    var modal = document.getElementById(id);
                    if (modal) {
                        modal.classList.add('open');
                    }
                }

                function closeModal(id) {
                    var modal = document.getElementById(id);
                    if (modal) {
                        modal.classList.remove('open');
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var overlays = document.querySelectorAll('.modal-overlay');
                    overlays.forEach(function (overlay) {
                        overlay.addEventListener('click', function (e) {
                            if (e.target === overlay) {
                                closeModal(overlay.id);
                            }
                        });
                    });
                });

                function switchTab(tabId) {
                    // 1. Hide all content sections
                    document.getElementById('content-borrowed').style.display = 'none';
                    document.getElementById('content-pending').style.display = 'none';
                    document.getElementById('content-history').style.display = 'none';

                    // 2. Reset all buttons to default styling
                    const tabs = ['tab-borrowed', 'tab-pending', 'tab-history'];
                    tabs.forEach(id => {
                        const btn = document.getElementById(id);
                        btn.style.background = '#ffffff';
                        btn.style.borderColor = '#e2e8f0';
                        btn.children[0].style.background = '#eff6ff';
                        btn.children[0].style.color = '#2563eb';
                        btn.children[1].style.color = '#334155';
                    });

                    // 3. Show selected content
                    document.getElementById('content-' + tabId).style.display = 'block';

                    // 4. Apply "Active" styling to the clicked button
                    const activeBtn = document.getElementById('tab-' + tabId);
                    activeBtn.style.background = '#eff6ff';
                    activeBtn.style.borderColor = '#bfdbfe';
                    activeBtn.children[0].style.background = '#2563eb';
                    activeBtn.children[0].style.color = 'white';
                    activeBtn.children[1].style.color = '#1e3a8a';
                }
            </script>
            <!-- Confirm Cancel Modal -->
            <div class="modal-overlay" id="cancelModal">
                <div class="modal" style="max-width: 28rem; border-radius: 12px; overflow: hidden; background: white;">

                    <!-- Header -->
                    <div class="modal-header"
                        style="border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Confirm Action</h2>
                        <button class="modal-close" onclick="closeModal('cancelModal')"
                            style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: 0.2s;">
                            <i class="ph ph-x" style="font-size: 1.1rem;"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body" style="padding: 1.5rem;">
                        <div
                            style="background: #f8fafc; border-radius: 8px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                            <i class="ph ph-warning" style="color: #ef4444; font-size: 1.25rem;"></i>
                            <span style="font-size: 0.9rem; color: #334155;">Are you sure you want to cancel your
                                pending request?</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer"
                        style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; background: #ffffff;">
                        <button type="button" onclick="closeModal('cancelModal')"
                            style="padding: 0.6rem 1.25rem; border: 1px solid #cbd5e1; background: white; color: #334155; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">Back</button>
                        <button type="button" onclick="closeModal('cancelModal')"
                            style="padding: 0.6rem 1.25rem; background: #dc2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">Confirm
                            Cancel</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>