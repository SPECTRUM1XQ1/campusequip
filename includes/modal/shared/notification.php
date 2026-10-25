<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div class="modal-overlay notif-drawer-overlay" id="notificationModal">
    <div class="modal m-shared-notification-s1">
        <div class="modal-header">
            <div>
                <h2 class="modal-title m-shared-notification-s2">Notifications</h2>
                <p class="modal-subtitle m-shared-notification-s3" id="notifSubtitle">Loading...</p>
            </div>
            <button type="button" class="modal-close" aria-label="Close" data-close-modal="notificationModal">
                <i class="ph ph-x m-shared-notification-s4"></i>
            </button>
        </div>
        <div class="modal-body notif-list" id="notifList"></div>
    </div>
</div>
