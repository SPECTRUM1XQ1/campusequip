/**
 * CampusEquip Global Dashboard Utilities
 * Modals: open/close, Escape + backdrop close, and keyboard accessibility
 * (dialog roles, focus moves into the dialog, Tab stays inside, focus returns on close).
 */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const modalOpeners = {};

function focusableIn(modal) {
    return Array.from(modal.querySelectorAll(FOCUSABLE)).filter(el => el.offsetParent !== null);
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modalOpeners[modalId] = document.activeElement;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');

    // Move focus into the dialog: first form field, otherwise first button that isn't "close".
    requestAnimationFrame(() => {
        const items = focusableIn(modal);
        const target = items.find(el => /^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName))
            || items.find(el => !el.classList.contains('modal-close'))
            || items[0];
        if (target) target.focus();
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');

    // Give focus back to whatever opened the dialog.
    const opener = modalOpeners[modalId];
    if (opener && document.body.contains(opener) && typeof opener.focus === 'function') {
        opener.focus();
    }
    delete modalOpeners[modalId];
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.setAttribute('aria-hidden', 'true');

        // Screen readers: announce each box as a dialog named by its title.
        const box = overlay.querySelector('.modal');
        if (box) {
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');
            const title = box.querySelector('.modal-title');
            if (title) {
                if (!title.id) title.id = overlay.id + '-title';
                box.setAttribute('aria-labelledby', title.id);
            }
        }

        // Click on the dark backdrop closes it.
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });

    // Icon-only buttons need a name for screen readers.
    document.querySelectorAll('.modal-close:not([aria-label])').forEach(btn => btn.setAttribute('aria-label', 'Close'));
    document.querySelectorAll('button:not([aria-label])').forEach(btn => {
        if (btn.textContent.trim() === '' && btn.title) btn.setAttribute('aria-label', btn.title);
    });

    document.addEventListener('keydown', (e) => {
        const activeModal = document.querySelector('.modal-overlay.open');
        if (!activeModal) return;

        if (e.key === 'Escape') {
            closeModal(activeModal.id);
            return;
        }

        // Keep Tab / Shift+Tab inside the open dialog.
        if (e.key === 'Tab') {
            const items = focusableIn(activeModal);
            if (items.length === 0) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });
});

// ---- Modal open/close + print buttons: data-open-modal="id", data-close-modal="id", data-print (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open-modal]');
    if (opener) {
        e.preventDefault();
        openModal(opener.dataset.openModal);
        return;
    }
    const closer = e.target.closest('[data-close-modal]');
    if (closer) {
        closeModal(closer.dataset.closeModal);
        return;
    }
    if (e.target.closest('[data-print]')) window.print();
});
