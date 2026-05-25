<?php
include __DIR__ . '/../includes/header.php';

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (find_user_by_email($email)) {
        $error = 'An account with this email already exists.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must contain at least 6 characters.';
    } else {
        $user = create_user($name, $email, $password, 'user');
        login_user($user);
        $_SESSION['flash_success'] = 'Your account has been created successfully.';
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit();
    }
}
?>
<div class="auth-page">
  <div class="auth-container card-soft">
    <h1 class="brand-title">LebaneseAirline</h1>
    <h2>Create your account</h2>
    <p class="muted">Sign up first so you can book flights, choose seats, and manage your trips in one place.</p>

    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="registerForm" action="" method="POST" class="auth-form">
      <div class="form-group">
        <label>Full name</label>
        <input type="text" name="name" placeholder="Enter your full name" value="<?= htmlspecialchars($name) ?>" required>
      </div>

      <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" id="password" placeholder="Create a password" required>
      </div>

      <div class="form-group">
        <label>Confirm password</label>
        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm your password" required>
      </div>

      <div class="terms">
        <label class="checkbox-inline">
          <input type="checkbox" required>
          <span>I agree to the <a href="<?= BASE_URL ?>/auth/policy.php" class="link">Terms and Privacy Policy</a></span>
        </label>
      </div>

      <button class="btn-primary" type="submit">Create my account</button>
    </form>

    <p class="auth-switch">Already have an account? <a class="link" href="login.php">Sign in</a></p>
  </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e){
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;
  if(password !== confirmPassword){
    alert('Password does not match.');
    e.preventDefault();
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
