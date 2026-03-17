<?php
include __DIR__ . '/../includes/header.php';

$error = flash_message('flash_error');
$success = flash_message('flash_success');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = find_user_by_email($email);

    if (!$user || !password_verify($password, $user['password'])) {
        $error = 'Invalid email or password.';
    } 
    else {

        login_user($user);

        $_SESSION['flash_success'] = 'Welcome back, ' . ($user['name'] ?? 'traveler') . '.';

        // ⭐ FIX ADMIN REDIRECT HERE
        if (($user['role'] ?? 'user') === 'admin') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/user/dashboard.php');
        }

        exit();
    }
}
?>
<div class="auth-page">
  <div class="auth-container card-soft">
    <h1 class="brand-title">LebaneseAirline</h1>
    <h2>Welcome back</h2>
    <p class="muted">Sign in to manage your bookings, view flight codes, and continue your trip planning.</p>

    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="" method="POST" class="auth-form">
      <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>

      <div class="row-between auth-agree-row">
        <label class="checkbox-inline">
          <input type="checkbox" required>
          <span>I agree to the <a href="<?= BASE_URL ?>/auth/policy.php" class="link">Terms and Privacy Policy</a></span>
        </label>
        <a href="forgot-password.php" class="link-muted">Forgot password?</a>
      </div>

      <button class="btn-primary" type="submit">Sign in</button>
    </form>

    

    <p class="auth-switch">Don’t you have an account? <a class="link" href="register.php">Sign up</a></p>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
