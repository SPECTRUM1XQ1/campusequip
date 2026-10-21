<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay" id="logoutModal">
    <div class="modal m-shared-logout-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Confirm Logout</h2>
            </div>
            <button class="modal-close" data-close-modal="logoutModal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p class="m-shared-logout-s2">Are you sure you want to log out of your account?
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-rect" data-close-modal="logoutModal"><i class="ph ph-x" aria-hidden="true"></i><span class="btn-text">Cancel</span></button>
            <a href="<?= APP_BASE ?>/public/login/logout.php" class="btn btn-danger btn-rect m-shared-logout-s3"
               ><i class="ph ph-sign-out" aria-hidden="true"></i><span class="btn-text">Yes,
                Log Out</span></a>
        </div>
    </div>
</div>
