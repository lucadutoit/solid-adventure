<?php
require_once __DIR__ . '/../php/config/database.php';
if (isLoggedIn()) {
    header('Location: /swift-swap/pages/dashboard.php');
    exit;
}
$title = 'Log In';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body style="background:var(--gray-100)">

  <div class="auth-wrap">
    <div class="auth-box">
      <div class="auth-logo"><a href="/swift-swap/" style="text-decoration:none;color:inherit">Swift<span>Swap</span></a></div>

      <div class="auth-card">
        <h1 class="auth-title">Welcome back</h1>
        <div id="auth-alert"></div>

        <form id="login-form" novalidate>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:0.5rem">Log in</button>
        </form>
      </div>

      <div class="auth-footer">
        Don't have an account? <a href="/swift-swap/pages/register.php">Sign up for free</a>
      </div>
    </div>
  </div>

  <script src="/swift-swap/js/main.js"></script>
  <script src="/swift-swap/js/auth.js"></script>
</body>
</html>
