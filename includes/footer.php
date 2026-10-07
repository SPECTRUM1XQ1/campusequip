<!-- Logout Confirmation Modal -->
<div class="modal-overlay" id="logoutModal">
    <div class="modal" style="max-width: 28rem;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Confirm Logout</h2>
            </div>
            <button class="modal-close" onclick="closeModal('logoutModal')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-main); font-size: 0.95rem;">Are you sure you want to log out of your account?
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-rect" onclick="closeModal('logoutModal')">Cancel</button>
            <a href="/campusequip/public/logout.php" class="btn btn-danger btn-rect"
                style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Yes,
                Log Out</a>
        </div>
    </div>
</div>

<script src="/campusequip/public/assets/js/snackbar.js"></script>
<script src="/campusequip/public/assets/js/sidebar.js"></script>
<script src="/campusequip/public/assets/js/dashboard-actions.js"></script>
<script src="/campusequip/public/assets/js/snackbar.js"></script>
<script src="/campusequip/public/assets/js/sidebar.js"></script>
<script src="/campusequip/public/assets/js/dashboard-actions.js"></script>