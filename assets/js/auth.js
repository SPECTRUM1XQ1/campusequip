document.addEventListener('DOMContentLoaded', () => {
  const loginForm  = document.getElementById('login-form');
  const signupForm = document.getElementById('signup-form');

  if (loginForm)  loginForm.addEventListener('submit', handleLogin);
  if (signupForm) signupForm.addEventListener('submit', handleSignup);
});

async function handleLogin(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  submitBtn.disabled = true;

  const formData = new FormData(form);
  formData.append('action', 'login');

  try {
    const res = await fetch('/campusequip/api/auth_handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('Signed in successfully', 'success');
      // Route by role — matches the folders in the project structure.
      const destinations = {
        borrower: '/campusequip/borrower/dashboard.php',
        staff:    '/campusequip/admin/dashboard.php',
        admin:    '/campusequip/admin/dashboard.php',
      };
      setTimeout(() => { window.location.href = destinations[data.role] || '/campusequip/index.php'; }, 600);
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
    const res = await fetch('/campusequip/api/auth_handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast(data.message, 'success');
      setTimeout(() => { window.location.href = '/campusequip/auth/login.php'; }, 1000);
    } else {
      showToast(data.message, 'error');
      submitBtn.disabled = false;
    }
  } catch (err) {
    showToast('Could not reach the server. Please try again.', 'error');
    submitBtn.disabled = false;
  }
}
