document.addEventListener('DOMContentLoaded', () => {
  const loginForm  = document.getElementById('login-form');
  const signupForm = document.getElementById('signup-form');

  if (loginForm)  loginForm.addEventListener('submit', handleLogin);
  if (signupForm) signupForm.addEventListener('submit', handleSignup);

  setupFieldRules();
  setupEmailHint();
  setupPageSwitch();
});

/*
 * Motion helpers (styles: pages/auth.css).
 *   setupPageSwitch  the Sign Up / Sign In link slides the card out, then opens the other page, where the card slides in
 *   setBusy          the button shows a spinner while the request runs, and a tick when it worked
 *   shake            the card shakes once when something has to be fixed
 */
function setupPageSwitch() {
  const card = document.getElementById('auth-container');
  if (!card) return;
  document.querySelectorAll('a.link-text[href]').forEach(link => {
    link.addEventListener('click', e => {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      const toSignup = /signup\.php/.test(link.getAttribute('href'));
      try { sessionStorage.setItem('ce-auth-dir', toSignup ? 'forward' : 'back'); } catch (err) { /* ignore */ }
      card.style.setProperty('--auth-out', toSignup ? '-32px' : '32px');
      card.classList.add('is-leaving');
      setTimeout(() => { window.location.href = link.href; }, 230);
    });
  });

  // "Back to home": the card slides away, the brand panel fades, and the landing page opens
  document.querySelectorAll('a.auth-back-home[href]').forEach(link => {
    link.addEventListener('click', e => {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      card.style.setProperty('--auth-out', '32px');
      card.classList.add('is-leaving');
      link.classList.add('is-going');
      const wrapper = document.querySelector('.login-wrapper');
      if (wrapper) wrapper.classList.add('is-exiting');
      setTimeout(() => { window.location.href = link.href; }, 300);
    });
  });
}

function setBusy(btn, state) {                         // state: 'loading' | 'success' | null
  btn.classList.toggle('is-loading', state === 'loading');
  btn.classList.toggle('is-success', state === 'success');
  btn.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
}

function shake() {
  const card = document.getElementById('auth-container');
  if (!card) return;
  card.classList.remove('is-shaking');
  void card.offsetWidth;                               // restart the animation if it is still running
  card.classList.add('is-shaking');
  setTimeout(() => card.classList.remove('is-shaking'), 450);
}

function leaveUp(then) {
  const card = document.getElementById('auth-container');
  if (card) card.classList.add('is-leaving-up');
  setTimeout(then, 320);
}

/*
 * Live input rules (the server checks the same things again in src/Validation.php):
 *   data-letters-only  names: letters, single spaces, hyphen, apostrophe, period
 *   data-id-format     PHINMA ID: digits only, dashes added automatically -> 03-01-2526-049080
 *   data-ph-phone      Philippine mobile number: 11 digits, 09XXXXXXXXX
 *   data-phinma-email  lower case, no spaces
 *   data-no-space      no spaces at all (password, login box)
 * Every field also has a maxlength in the markup.
 */
function setupFieldRules() {
  const on = (selector, handler) => document.querySelectorAll(selector).forEach(handler);

  on('input[data-letters-only]', input => {
    input.addEventListener('input', () => {
      const cleaned = input.value
        .replace(/[^\p{L} .'’\-]/gu, '')
        .replace(/^\s+/, '')
        .replace(/\s{2,}/g, ' ');
      if (cleaned !== input.value) input.value = cleaned;
    });
    // Tidy trailing spaces when leaving the box.
    input.addEventListener('blur', () => { input.value = input.value.trim(); });
  });

  on('input[data-no-space]', input => {
    input.addEventListener('keydown', e => { if (e.key === ' ') e.preventDefault(); });
    input.addEventListener('input', () => {
      const cleaned = input.value.replace(/\s/g, '');
      if (cleaned !== input.value) input.value = cleaned;
    });
  });

  on('input[data-phinma-email]', input => {
    input.addEventListener('input', () => {
      const cleaned = input.value.replace(/\s/g, '').toLowerCase();
      if (cleaned !== input.value) input.value = cleaned;
    });
  });

  on('input[data-id-format]', input => {
    let lastDigits = '';
    input.addEventListener('input', e => {
      let digits = input.value.replace(/\D/g, '').slice(0, 14);
      // Backspace over a dash removes the digit before it instead of getting stuck.
      if (e.inputType === 'deleteContentBackward' && digits === lastDigits && digits.length) {
        digits = digits.slice(0, -1);
      }
      lastDigits = digits;
      input.value = formatId(digits);
    });
  });

  on('input[data-ph-phone]', input => {
    input.addEventListener('input', () => {
      const digits = input.value.replace(/\D/g, '').slice(0, 11);
      if (digits !== input.value) input.value = digits;
    });
    // +63 917 123 4567 / 9171234567 pasted in: turn it into 09171234567.
    input.addEventListener('paste', e => {
      const text = (e.clipboardData || window.clipboardData).getData('text');
      e.preventDefault();
      input.value = normalizePhone(text).slice(0, 11);
    });
    input.addEventListener('blur', () => { input.value = normalizePhone(input.value).slice(0, 11); });
  });
}

function formatId(digits) {
    if (digits.length > 11) {
        // New format (14 digits): 03-01-2526-049080 -> 2-2-4-6
        const parts = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8), digits.slice(8, 14)];
        return parts.filter(Boolean).join('-');
    } else {
        // Old format (11 digits): 03-1920-03027 -> 2-4-5
        const parts = [digits.slice(0, 2), digits.slice(2, 6), digits.slice(6, 11)];
        return parts.filter(Boolean).join('-');
    }
}

function normalizePhone(value) {
  const digits = value.replace(/\D/g, '');
  if (digits.length === 12 && digits.startsWith('63')) return '0' + digits.slice(2);
  if (digits.length === 10 && digits.startsWith('9')) return '0' + digits;
  return digits;
}

// Shows what the person's PHINMA email should look like, built from the name they typed:
// first 2 letters of first name + first 2 of middle name . last name . up @phinmaed.com
function setupEmailHint() {
  const form = document.getElementById('signup-form');
  const hint = document.getElementById('email-hint');
  if (!form || !hint) return;

  const letters = v => v.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z]/g, '');
  const update = () => {
    const first = letters(form.first_name.value);
    const middle = letters(form.middle_name.value);
    const last = letters(form.last_name.value);
    if (first.length >= 2 && last.length >= 2) {
      hint.textContent = 'Your PHINMA email should look like ' + first.slice(0, 2) + middle.slice(0, 2) + '.' + last + '.up@phinmaed.com';
    } else {
      hint.textContent = 'Your PHINMA email, like juca.peralta.up@phinmaed.com.';
    }
  };
  ['first_name', 'middle_name', 'last_name'].forEach(n => form[n].addEventListener('input', update));
}

// Same rules as src/Validation.php. Returns { field, message } for the first problem, or null.
function checkSignup(form) {
  const v = name => form[name].value.trim();
  const NAME = /^\p{L}[\p{L} .'’\-]*$/u;
  const letterCount = s => (s.match(/\p{L}/gu) || []).length;

  const nameRules = [
    ['last_name', 'Last name', 30, true],
    ['first_name', 'First name', 20, true],
    ['middle_name', 'Middle name', 20, false],
  ];
  for (const [field, label, max, required] of nameRules) {
    const value = v(field);
    if (value === '') { if (required) return { field, message: label + ' is required.' }; continue; }
    if (value.length > max) return { field, message: label + ' must be ' + max + ' characters or fewer.' };
    if (!NAME.test(value)) return { field, message: label + ' can only contain letters.' };
    if (required && letterCount(value) < 2) return { field, message: label + ' must have at least 2 letters.' };
  }

  if (!/^(\d{2}-\d{2}-\d{4}-\d{6}|\d{2}-\d{4}-\d{5})$/.test(v('id_number'))) {
    return { field: 'id_number', message: 'Student / Employee ID must look like 03-01-2526-049080 or 03-1920-03027.' };
  }
  if (!/^09\d{9}$/.test(v('phone'))) {
    return { field: 'phone', message: 'Phone number must be a Philippine mobile number like 09171234567.' };
  }

  const email = v('email').toLowerCase();
  if (email.length > 50 || !/^[a-z]{2,4}\.[a-z]+[0-9]{0,3}\.up@phinmaed\.com$/.test(email)) {
    return { field: 'email', message: 'Use your PHINMA email, like juca.peralta.up@phinmaed.com.' };
  }

  const pw = form.password.value;
  if (/\s/.test(pw)) return { field: 'password', message: 'Password cannot contain spaces.' };
  if (pw.length < 8) return { field: 'password', message: 'Password must be at least 8 characters.' };
  if (pw.length > 64) return { field: 'password', message: 'Password must be 64 characters or fewer.' };
  if (pw !== form.confirm_password.value) return { field: 'confirm_password', message: 'Passwords do not match.' };
  return null;
}

async function handleLogin(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');

  if (/\s/.test(form.password.value)) {
    showToast('Password cannot contain spaces.', 'error');
    shake();
    form.password.focus();
    return;
  }
  submitBtn.disabled = true;
  setBusy(submitBtn, 'loading');

  const formData = new FormData(form);
  formData.append('action', 'login');

  try {
    const res = await fetch(APP_BASE + '/api/auth/auth_handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('Signed in successfully', 'success');
      setBusy(submitBtn, 'success');
      // Route by role — matches the folders in the project structure.
      const destinations = {
        borrower: APP_BASE + '/public/borrower/dashboard.php',
        staff:    APP_BASE + '/public/manage/dashboard.php',
        admin:    APP_BASE + '/public/manage/dashboard.php',
      };
      const go = destinations[data.role] || APP_BASE + '/public/index.php';
      setTimeout(() => leaveUp(() => { window.location.href = go; }), 350);
    } else {
      showToast(data.message, 'error');
      setBusy(submitBtn, null);
      shake();
      submitBtn.disabled = false;
    }
  } catch (err) {
    showToast('Could not reach the server. Please try again.', 'error');
    setBusy(submitBtn, null);
    shake();
    submitBtn.disabled = false;
  }
}

async function handleSignup(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');

  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  const problem = checkSignup(form);
  if (problem) {
    showToast(problem.message, 'error');
    form[problem.field].classList.add('is-invalid');
    shake();
    form[problem.field].focus();
    return;
  }
  submitBtn.disabled = true;
  setBusy(submitBtn, 'loading');

  const formData = new FormData(form);
  formData.append('action', 'signup');

  try {
    const res = await fetch(APP_BASE + '/api/auth/auth_handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast(data.message, 'success');
      setBusy(submitBtn, 'success');
      try { sessionStorage.setItem('ce-auth-dir', 'back'); } catch (err) { /* ignore */ }
      setTimeout(() => leaveUp(() => { window.location.href = APP_BASE + '/public/login/login.php'; }), 700);
    } else {
      showToast(data.message, 'error');
      setBusy(submitBtn, null);
      shake();
      submitBtn.disabled = false;
    }
  } catch (err) {
    showToast('Could not reach the server. Please try again.', 'error');
    setBusy(submitBtn, null);
    shake();
    submitBtn.disabled = false;
  }
}
