/**
 * Borrower Equipment Catalog: save to watchlist + reserve modal.
 * Loaded by public/borrower/catalog.php.
 * Expects: APP_BASE, openModal, closeModal, setBtnText, showToast.
 */

// ---- Save / unsave to the watchlist ----
function paintSaveBtn(btn) {
    const saved = btn.dataset.saved === '1';
    btn.querySelector('.save-label').textContent = saved ? 'Saved' : 'Save';
    btn.style.background = saved ? '#eff6ff' : '';
    btn.style.borderColor = saved ? '#2563eb' : '';
    btn.style.color = saved ? '#2563eb' : '';
}

document.querySelectorAll('.save-btn').forEach(btn => {
    paintSaveBtn(btn);
    btn.addEventListener('click', async () => {
        const wasSaved = btn.dataset.saved === '1';
        const fd = new FormData();
        fd.append('catalog_id', btn.dataset.catalogId);
        btn.disabled = true;
        try {
            const res = await fetch(APP_BASE + '/api/wishlist/' + (wasSaved ? 'remove' : 'add') + '.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                btn.dataset.saved = wasSaved ? '0' : '1';
                paintSaveBtn(btn);
                showToast(wasSaved ? 'Removed from saved items.' : 'Saved to your watchlist.', 'success');
            } else {
                showToast(data.message || 'Could not update saved items.', 'error');
            }
        } catch (err) {
            showToast('Server communication error', 'error');
        } finally {
            btn.disabled = false;
        }
    });
});

// Both settings come from config.php through data-* attributes on the reserve form.
const reserveForm = document.getElementById('reserve-form');
const PICKUP_WINDOW_DAYS = parseInt(reserveForm.dataset.pickupWindowDays, 10);
const LOAN_DUE_TIME = reserveForm.dataset.loanDueTime;
let currentMaxDays = 1;

const toLocalDate = (d) =>
    d.getFullYear() + '-' +
    String(d.getMonth() + 1).padStart(2, '0') + '-' +
    String(d.getDate()).padStart(2, '0');

// Keep the days field inside 1..max for this model, and show the return date.
function clampDays() {
    const input = document.getElementById('res_days');
    let n = parseInt(input.value, 10);
    if (isNaN(n) || n < 1) n = 1;
    if (n > currentMaxDays) n = currentMaxDays;
    return n;
}

function updatePickupPreview() {
    const input = document.getElementById('res_days');
    const preview = document.getElementById('res_pickup_preview');
    const startVal = document.getElementById('res_start_at').value;
    const raw = parseInt(input.value, 10);

    if (!isNaN(raw) && raw > currentMaxDays) {
        input.value = currentMaxDays;
        showToast(`This equipment can only be borrowed for up to ${currentMaxDays} day(s).`, 'error');
    }
    const n = clampDays();
    document.getElementById('res_days_minus').disabled = n <= 1;
    document.getElementById('res_days_plus').disabled = n >= currentMaxDays;

    if (!startVal) { preview.textContent = ''; return; }
    // Pick-up window = opens on the START DATE you chose and stays open for PICKUP_WINDOW_DAYS,
    // never past the loan's last moment. It is separate from the loan length (days you borrow).
    // Same rule as Reservation::pickupWindow() on the server, so reserving for next week means
    // picking up next week.
    const [y, m, d] = startVal.split('-').map(Number);
    const [dueH, dueM] = LOAN_DUE_TIME.split(':').map(Number);
    const opens = new Date(y, m - 1, d);
    const windowEnd = new Date(y, m - 1, d + PICKUP_WINDOW_DAYS);
    const loanEnd = new Date(y, m - 1, d + n, dueH, dueM);
    const closes = windowEnd < loanEnd ? windowEnd : loanEnd;
    const fmt = (dt) => dt.toLocaleString(undefined,
        { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    preview.innerHTML = 'Pick-up window: <strong>' + fmt(opens) + ' &ndash; ' + fmt(closes) + '</strong>' +
        '<br><span class="return-preview-note">The window starts on your start date and lasts ' + PICKUP_WINDOW_DAYS + ' days. Collect it from the Lab Desk within it, or the reservation is cancelled automatically.</span>';
}

document.getElementById('res_days_minus').addEventListener('click', () => {
    document.getElementById('res_days').value = Math.max(1, clampDays() - 1);
    updatePickupPreview();
});
document.getElementById('res_days_plus').addEventListener('click', () => {
    document.getElementById('res_days').value = Math.min(currentMaxDays, clampDays() + 1);
    updatePickupPreview();
});
document.getElementById('res_days').addEventListener('input', updatePickupPreview);
document.getElementById('res_days').addEventListener('blur', () => {
    document.getElementById('res_days').value = clampDays();
    updatePickupPreview();
});
document.getElementById('res_start_at').addEventListener('change', updatePickupPreview);

function openReserveModal(catalogId, modelName, maxDays, lateFineRate) {
    document.getElementById('res_catalog_id').value = catalogId;
    document.getElementById('res-model-name').textContent = modelName;
    document.getElementById('res-max-days').textContent = `Up to ${maxDays} Days`;
    const fineRow = document.getElementById('res-fine-row');
    fineRow.style.display = lateFineRate > 0 ? 'flex' : 'none';
    document.getElementById('res-late-fine').textContent = '\u20B1' + Number(lateFineRate).toFixed(2) + ' per day';

    // Build dates from local parts: toISOString() is UTC, so for users ahead
    // of UTC (e.g. Philippines, UTC+8) it returns *yesterday* in the morning
    // and the server then rejects the start date as "in the past".
    currentMaxDays = Math.max(1, maxDays);
    const startInput = document.getElementById('res_start_at');
    const today = toLocalDate(new Date());
    startInput.min = today;
    startInput.value = today;

    const daysInput = document.getElementById('res_days');
    daysInput.max = currentMaxDays;
    daysInput.value = 1;

    // Quick-pick chips: 1 day, a middle option, and the model's maximum.
    const picks = [...new Set([1, Math.ceil(currentMaxDays / 2), currentMaxDays])];
    const chips = document.getElementById('res_day_chips');
    chips.innerHTML = '';
    picks.forEach(n => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = n === currentMaxDays && picks.length > 1 ? `${n} days (max)` : `${n} day${n === 1 ? '' : 's'}`;
        b.addEventListener('click', () => { daysInput.value = n; updatePickupPreview(); });
        chips.appendChild(b);
    });

    updatePickupPreview();

    openModal('reserveModal');
}

// AJAX Create Reservation
document.getElementById('reserve-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('reserve-submit-btn');
    btn.disabled = true;
    setBtnText(btn, 'Submitting...');

    document.getElementById('res_days').value = clampDays();
    const formData = new FormData(this);
    try {
        const res = await fetch(APP_BASE + '/api/reservation/create.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Reservation submitted for staff approval!', 'success');
            closeModal('reserveModal');
            setTimeout(() => window.location.href = APP_BASE + '/public/borrower/dashboard.php', 900);
        } else {
            showToast(data.message || 'Could not submit reservation', 'error');
        }
    } catch (err) {
        showToast('Server communication error', 'error');
    } finally {
        btn.disabled = false;
        setBtnText(btn, 'Submit Request');
    }
});

// ---- Reserve buttons (replaces the inline on* attributes that used to be in the PHP) ----
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-reserve]');
    if (!btn || btn.disabled) return;
    openReserveModal(
        parseInt(btn.dataset.reserve, 10),
        btn.dataset.modelName,
        parseInt(btn.dataset.maxDays, 10),
        parseFloat(btn.dataset.lateFine)
    );
});
