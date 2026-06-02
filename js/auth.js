/* Swift Swap — Auth page logic */

document.addEventListener('DOMContentLoaded', () => {
  const loginForm    = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');

  // ── Login ──────────────────────────────────────────────────
  if (loginForm) {
    loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      clearAlert('auth-alert');
      const btn = loginForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Logging in…';

      try {
        const data = await API.post('/auth/login.php', {
          email:    loginForm.email.value.trim(),
          password: loginForm.password.value,
        });
        Auth.save(data.user);
        location.href = '/swift-swap/pages/dashboard.php';
      } catch (err) {
        showAlert('auth-alert', err.message);
        btn.disabled = false;
        btn.textContent = 'Log in';
      }
    });
  }

  // ── Register ───────────────────────────────────────────────
  if (registerForm) {
    registerForm.addEventListener('submit', async e => {
      e.preventDefault();
      clearAlert('auth-alert');

      const password = registerForm.password.value;
      const confirm  = registerForm.confirm_password.value;
      if (password !== confirm) {
        showAlert('auth-alert', 'Passwords do not match.');
        return;
      }

      const btn = registerForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Creating account…';

      try {
        const data = await API.post('/auth/register.php', {
          username: registerForm.username.value.trim(),
          email:    registerForm.email.value.trim(),
          password,
        });
        Auth.save(data.user);
        location.href = '/swift-swap/pages/dashboard.php';
      } catch (err) {
        showAlert('auth-alert', err.message);
        btn.disabled = false;
        btn.textContent = 'Create account';
      }
    });
  }
});
