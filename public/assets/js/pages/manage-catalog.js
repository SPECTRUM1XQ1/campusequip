/**
 * Staff/admin Equipment Catalog: accessories manager, edit model, archive/restore, add category.
 * Loaded by public/manage/catalog.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

// ---- Accessories manager ----
// { catalog_id: [{accessory_id, accessory_name}, ...] } kept in memory and updated after each API call
const ACCESSORIES = JSON.parse(document.getElementById('accessories-data').textContent || '{}');
let accCatalogId = null;

function openAccessoriesModal(btn) {
    accCatalogId = parseInt(btn.dataset.catalogId, 10);
    document.getElementById('acc-model-name').textContent = btn.dataset.modelName;
    document.getElementById('acc-new-name').value = '';
    renderAccessoryList();
    openModal('accessoriesModal');
}

function renderAccessoryList() {
    const list = document.getElementById('acc-list');
    const items = ACCESSORIES[accCatalogId] || [];
    list.replaceChildren();

    if (items.length === 0) {
        const p = document.createElement('p');
        p.className = 'text-muted';
        p.textContent = 'No accessories listed yet.';
        list.appendChild(p);
    }

    items.forEach(a => {
        const row = document.createElement('div');
        row.className = 'option-row';
        row.style.cursor = 'default';

        const name = document.createElement('span');
        name.textContent = a.accessory_name; // textContent: no HTML injection
        row.appendChild(name);

        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-outline btn-sm btn-rect';
        del.style.marginLeft = 'auto';
        del.textContent = 'Remove';
        del.addEventListener('click', () => removeAccessory(a.accessory_id));
        row.appendChild(del);

        list.appendChild(row);
    });

    document.querySelectorAll('.acc-count[data-catalog-id="' + accCatalogId + '"]')
        .forEach(el => el.textContent = items.length);
}

document.getElementById('add-accessory-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = document.getElementById('acc-new-name');
    const btn = document.getElementById('acc-add-btn');
    const name = input.value.trim();
    if (name === '') return;

    btn.disabled = true;
    const formData = new FormData();
    formData.append('catalog_id', accCatalogId);
    formData.append('accessory_name', name);
    try {
        const res = await fetch(APP_BASE + '/api/catalog/add_accessory.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            const list = ACCESSORIES[accCatalogId] = ACCESSORIES[accCatalogId] || [];
            list.push({ accessory_id: data.accessory_id, accessory_name: name });
            list.sort((a, b) => a.accessory_name.localeCompare(b.accessory_name));
            input.value = '';
            renderAccessoryList();
            input.focus();
        } else {
            showToast(data.message || 'Could not add accessory', 'error');
        }
    } catch (err) {
        showToast('Server error while adding accessory', 'error');
    } finally {
        btn.disabled = false;
    }
});

async function removeAccessory(accessoryId) {
    const formData = new FormData();
    formData.append('accessory_id', accessoryId);
    try {
        const res = await fetch(APP_BASE + '/api/catalog/delete_accessory.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            ACCESSORIES[accCatalogId] = (ACCESSORIES[accCatalogId] || []).filter(a => a.accessory_id !== accessoryId);
            renderAccessoryList();
        } else {
            showToast(data.message || 'Could not remove accessory', 'error');
        }
    } catch (err) {
        showToast('Server error while removing accessory', 'error');
    }
}

function openEditModal(btn) {
    const d = btn.dataset;
    document.getElementById('edit_catalog_id').value    = d.catalogId;
    document.getElementById('edit_model_name').value    = d.modelName;
    document.getElementById('edit_category_id').value   = d.categoryId;
    document.getElementById('edit_max_loan_days').value = d.maxLoanDays;
    document.getElementById('edit_late_fine_rate').value = d.lateFineRate;
    document.getElementById('edit_description').value   = d.description;
    openModal('editModal');
}

document.getElementById('edit-model-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('edit-model-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Saving...');
    try {
        const res = await fetch(APP_BASE + '/api/catalog/update_model.php', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.success) {
            showToast('Equipment model updated!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to update model', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Save');
    }
});

// ---- Archive / restore ----
async function setArchived(btn, archive) {
    const name = btn.dataset.modelName;
    const question = archive
        ? 'Archive "' + name + '"?\n\nIt will be hidden from borrowers and cannot be reserved. You can restore it later.'
        : 'Restore "' + name + '" so borrowers can reserve it again?';
    if (!confirm(question)) return;

    const formData = new FormData();
    formData.append('catalog_id', btn.dataset.catalogId);
    formData.append('archived', archive ? '1' : '0');
    btn.disabled = true;
    try {
        const res = await fetch(APP_BASE + '/api/catalog/archive_model.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(archive ? 'Model archived.' : 'Model restored.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Could not change the archive status', 'error');
            btn.disabled = false;
        }
    } catch (err) {
        showToast('Server communication error', 'error');
        btn.disabled = false;
    }
}

// AJAX Add Model
document.getElementById('add-model-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('add-model-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Saving...');

    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/catalog/add_model.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Equipment model created successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to save model', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Save');
    }
});

// Add a new category, then reload so the dropdowns include it.
document.getElementById('category-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('category-submit-btn');
    btn.disabled = true;
    try {
        const res = await fetch(APP_BASE + '/api/catalog/add_category.php', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.success) {
            showToast('Category added.', 'success');
            closeModal('categoryModal');
            setTimeout(() => location.reload(), 700);
        } else {
            showToast(data.message || 'Could not add the category.', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    }
    btn.disabled = false;
});

// ---- Card action buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-catalog-action]');
    if (!btn) return;
    switch (btn.dataset.catalogAction) {
        case 'accessories': openAccessoriesModal(btn); break;
        case 'edit':        openEditModal(btn); break;
        case 'archive':     setArchived(btn, true); break;
        case 'restore':     setArchived(btn, false); break;
    }
});
