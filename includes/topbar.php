<header class="top-header">
    <div class="header-title">
        <button class="mobile-menu-btn" id="menuBtn">
            <i class="ph ph-list"></i>
        </button>
        <span><?= htmlspecialchars($pageTitle ?? '') ?></span>
    </div>

    <div class="header-controls">
        <select>
            <option>Category</option>
            <option>Laptops</option>
            <option>Kits</option>
            <option>Books</option>
        </select>

        <div class="search-bar">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search Item..." onkeyup="liveSearch()">
        </div>

        <button class="btn-icon" onclick="openModal('notificationModal')"
            style="background-color: #f8fafc; border: none; border-radius: 50%; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #475569; transition: background-color 0.2s;">
            <i class="ph ph-bell-ringing" style="font-size: 1.25rem;"></i>
        </button>
    </div>
</header>

<!-- Notification Modal -->
<div class="modal-overlay" id="notificationModal">
    <div class="modal" style="max-width: 26rem;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title" style="font-size: 1.1rem;">Notifications</h2>
                <p class="modal-subtitle" style="margin-top: 0.25rem;">You have 1 unread update.</p>
            </div>
            <button class="modal-close" onclick="closeModal('notificationModal')">
                <i class="ph ph-x" style="font-size: 1.25rem;"></i>
            </button>
        </div>
        <div class="modal-body">

            <!-- Unread Notification -->
            <div
                style="padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; margin-bottom: 0.75rem;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <i class="ph ph-check-circle" style="color: #2563eb; font-size: 1.5rem; margin-top: 0.1rem;"></i>
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0; font-size: 0.9rem; color: #1e3a8a;">Request Approved</h4>
                        <p style="margin: 0; font-size: 0.8rem; color: #475569; line-height: 1.4;">Your request for the
                            <strong>Dell XPS 15 Workstation</strong> was approved by staff. It is now ready for pickup
                            at the lab desk.
                        </p>
                        <span
                            style="display: block; margin-top: 0.5rem; font-size: 0.7rem; color: #94a3b8; font-weight: 600;">JUST
                            NOW</span>
                    </div>
                </div>
            </div>

            <!-- Read Notification -->
            <div style="padding: 1rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <i class="ph ph-warning-circle" style="color: #64748b; font-size: 1.5rem; margin-top: 0.1rem;"></i>
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0; font-size: 0.9rem; color: #334155;">Return Reminder</h4>
                        <p style="margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4;">Your <strong>Sony
                                Alpha Camera 4K</strong> is due back tomorrow by 9:00 AM.</p>
                        <span
                            style="display: block; margin-top: 0.5rem; font-size: 0.7rem; color: #cbd5e1; font-weight: 600;">2
                            DAYS AGO</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function liveSearch() {
        // Get what the user typed and convert to lowercase
        let filter = document.getElementById('searchInput').value.toLowerCase();

        // Find all item cards on whatever page we are currently on
        let cards = document.querySelectorAll('.item-card');

        // Loop through each card
        cards.forEach(card => {
            // Grab all the text inside the card (title, serial number, etc.)
            let cardText = card.innerText.toLowerCase();

            // If the typed text is found in the card, show it. Otherwise, hide it.
            if (cardText.includes(filter)) {
                card.style.display = ''; // Restores its original CSS layout (block or flex)
            } else {
                card.style.display = 'none'; // Hides it
            }
        });
    } // <-- Properly closes the liveSearch function

    // Start the independent global event listener for the Escape key
    document.addEventListener('keydown', function (event) {
        // Check if the pressed key is "Escape"
        if (event.key === 'Escape') {
            // Find any modal that currently has the 'open' class
            let openModal = document.querySelector('.modal-overlay.open');

            // If one exists, grab its ID and pass it to your existing close function
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
</script>