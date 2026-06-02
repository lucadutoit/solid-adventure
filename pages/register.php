<?php
require_once __DIR__ . '/../php/config/database.php';
if (isLoggedIn()) {
    header('Location: /swift-swap/pages/dashboard.php');
    exit;
}
$title = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body style="background:var(--gray-100)">

  <div class="auth-wrap">
    <div class="auth-box">
      <div class="auth-logo"><a href="/swift-swap/" style="text-decoration:none;color:inherit">Swift<span>Swap</span></a></div>

      <div class="auth-card">
        <h1 class="auth-title">Create your account</h1>
        <div id="auth-alert"></div>

        <form id="register-form" novalidate>
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="johndoe123" required autocomplete="username"
                   minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
            <div class="form-hint">Letters, numbers, and underscores only.</div>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Min. 8 characters" required autocomplete="new-password" minlength="8">
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-accent btn-block btn-lg" style="margin-top:0.5rem">Create account</button>
        </form>
      </div>

      <div class="auth-footer">
        Already have an account? <a href="/swift-swap/pages/login.php">Log in</a>
      </div>
    </div>
  </div>

  <script src="/swift-swap/js/main.js"></script>
  <script src="/swift-swap/js/auth.js"></script>
</body>
</html>
