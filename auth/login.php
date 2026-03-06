<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-page">
  <div class="auth-container">
    <h1 class="brand">LebaneseAirline</h1>
    <h2>Welcome Back</h2>
    <p class="muted">Sign in to manage your bookings</p>

    <form action="../user/dashboard.php" method="GET">
      <input type="email" placeholder="Email Address" required>
      <input type="password" placeholder="Password" required>

      <div class="row-between">
        <label class="checkbox"><input type="checkbox"> Remember me</label>
        <a href="forgot-password.php">Forgot password?</a>
      </div>

      <button class="btn-primary" type="submit">Sign In</button>
    </form>

    <div class="divider"></div>

    <a class="btn-secondary" href="register.php">Create Account</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>