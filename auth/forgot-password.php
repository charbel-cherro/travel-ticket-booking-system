<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-page">
  <div class="auth-container">

    <h1 class="brand">LebaneseAirline</h1>
    <h2>Reset Password</h2>
    <p class="muted">Enter your email to receive a reset link.</p>

    <form>

      <input 
        type="email" 
        placeholder="Email Address"
        required
      >

      <button class="btn-primary">
        Send Reset Link
      </button>

    </form>

    <div class="divider"></div>

    <p class="small">
      Remember your password?
      <a class="link" href="login.php">Sign In</a>
    </p>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>