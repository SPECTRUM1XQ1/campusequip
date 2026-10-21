/**
 * Return modal (condition, damage notes, missing accessories) + AJAX submit.
 * Used by: public/staff/return.php and public/manage/checkouts.php.
 * Data: the accessories of every model are read from <script type="application/json" id="accessories-data">
 * that the PHP page prints (catalog_id => [{accessory_id, accessory_name}]).
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

// Accessories that ship with each model: { catalog_id: [{accessory_id, accessory_name}, ...] }
const ACCESSORIES_BY_MODEL = JSON.parse(document.getElementById('accessories-data').textContent || '{}');

function renderMissingAccessories(catalogId) {
    const list = document.getElementById('missing-accessories-list');
    const missingRadio = document.querySelector('input[name="return_condition"][value="missing_accessories"]');
    const accessories = ACCESSORIES_BY_MODEL[catalogId] || [];
    list.replaceChildren();

    if (accessories.length === 0) {
        // Nothing registered for this model, so there is nothing to tick.
        missingRadio.disabled = true;
        missingRadio.parentElement.title = 'No accessories are registered for this model (add them in Equipment Catalog).';
        const p = document.createElement('p');
        p.className = 'text-muted';
        p.textContent = 'No accessories are registered for this model.';
        list.appendChild(p);
        return;
    }

    missingRadio.disabled = false;
    missingRadio.parentElement.removeAttribute('title');
    accessories.forEach(a => {
        const label = document.createElement('label');
        label.className = 'option-row';
        const box = document.createElement('input');
        box.type = 'checkbox';
        box.name = 'missing_accessories[]';
        box.value = a.accessory_id;
        label.appendChild(box);
        label.appendChild(document.createTextNode(' ' + a.accessory_name)); // text node: no HTML injection
        list.appendChild(label);
    });
}

// Each condition shows only its own extra input.
function syncReturnFields() {
    const cond = document.querySelector('input[name="return_condition"]:checked').value;
    document.getElementById('damage-notes-field').style.display = (cond === 'damaged') ? 'block' : 'none';
    document.getElementById('missing-accessories-field').style.display = (cond === 'missing_accessories') ? 'block' : 'none';
}

function openReturnModal(id, borrower, item, isOverdue, catalogId) {
    // The card may have turned overdue after the page loaded, so trust the live countdown too.
    isOverdue = isOverdue || !!document.querySelector('#return-card-' + id + ' .due-countdown.is-expired');
    document.getElementById('return-form').reset(); // clear the previous return's choices
    renderMissingAccessories(catalogId);
    syncReturnFields();
    document.getElementById('return_checkout_id').value = id;
    document.getElementById('ret-borrower').textContent = borrower;
    document.getElementById('ret-item').textContent = item;
    document.getElementById('ret-status-badge').innerHTML = isOverdue 
        ? '<span class="status-badge declined">Overdue Return</span>' 
        : '<span class="status-badge approved">On-Time Return</span>';
    openModal('returnModal');
}

document.querySelectorAll('input[name="return_condition"]').forEach(radio => {
    radio.addEventListener('change', syncReturnFields);
});

// AJAX Return Submission
document.getElementById('return-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const cond = this.querySelector('input[name="return_condition"]:checked').value;
    if (cond === 'damaged' && document.getElementById('damage_notes').value.trim() === '') {
        showToast('Please describe what happened to the item.', 'error');
        return;
    }
    if (cond === 'missing_accessories' && !this.querySelector('input[name="missing_accessories[]"]:checked')) {
        showToast('Select at least one missing accessory.', 'error');
        return;
    }

    const btn = document.getElementById('ret-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Processing...');

    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/checkout/return.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.late_fine ? data.message : 'Equipment return successfully recorded!', data.late_fine ? 'info' : 'success');
            setTimeout(() => location.reload(), data.late_fine ? 1800 : 800);
        } else {
            showToast(data.message || 'Return processing failed', 'error');
        }
    } catch (err) {
        showToast('Server error during return', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Complete Return');
    }
});

// ---- Process Return buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-return]');
    if (!btn) return;
    openReturnModal(
        parseInt(btn.dataset.return, 10),
        btn.dataset.borrower,
        btn.dataset.item,
        parseInt(btn.dataset.overdue, 10),
        parseInt(btn.dataset.catalog, 10)
    );
});
