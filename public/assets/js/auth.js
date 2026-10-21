document.addEventListener('DOMContentLoaded', () => {
  const loginForm  = document.getElementById('login-form');
  const signupForm = document.getElementById('signup-form');

  if (loginForm)  loginForm.addEventListener('submit', handleLogin);
  if (signupForm) signupForm.addEventListener('submit', handleSignup);

  // Fields marked data-digits-only (ID number, phone) accept numbers only:
  // anything else typed or pasted is removed straight away.
  document.querySelectorAll('input[data-digits-only]').forEach(input => {
    input.addEventListener('input', () => {
      const cleaned = input.value.replace(/\D/g, '');
      if (cleaned !== input.value) input.value = cleaned;
    });
  });
});

async function handleLogin(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  submitBtn.disabled = true;

  const formData = new FormData(form);
  formData.append('action', 'login');

  try {
    const res = await fetch(APP_BASE + '/api/auth/auth_handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('Signed in successfully', 'success');
      // Route by role — matches the folders in the project structure.
      const destinations = {
        borrower: APP_BASE + '/public/borrower/dashboard.php',
        staff:    APP_BASE + '/public/manage/dashboard.php',
        admin:    APP_BASE + '/public/manage/dashboard.php',
      };
      setTimeout(() => { window.location.href = destinations[data.role] || APP_BASE + '/public/index.php'; }, 600);
    } else {
      showToast(data.message, 'error');
      submitBtn.disabled = false;
    }
  } catch (err) {
    showToast('Could not reach the server. Please try again.', 'error');
    submitBtn.disabled = false;
  }
}

async function handleSignup(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  submitBtn.disabled = true;

  const formData = new FormData(form);
  formData.append('action', 'signup');

  try {
    const res = await fetch(APP_BASE + '/api/auth/auth_handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast(data.message, 'success');
      setTimeout(() => { window.location.href = APP_BASE + '/public/login/login.php'; }, 1000);
    } else {
      showToast(data.message, 'error');
      submitBtn.disabled = false;
    }
  } catch (err) {
    showToast('Could not reach the server. Please try again.', 'error');
    submitBtn.disabled = false;
  }
}
