<?php
include __DIR__ . '/../includes/header.php';

$message = '';
$error = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$user = null;

function ensure_password_reset_columns_for_reset_page(): void {
    $columns = [
        'reset_token_hash' => "ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(64) NULL AFTER password",
        'reset_token_expires_at' => "ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL AFTER reset_token_hash",
    ];

    foreach ($columns as $column => $sql) {
        $stmt = db()->query("SHOW COLUMNS FROM users LIKE " . db()->quote($column));
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exists) {
            db()->exec($sql);
        }
    }
}

function find_user_by_reset_token(string $token): ?array {
    if ($token === '') {
        return null;
    }

    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare("
        SELECT id, name, email, reset_token_expires_at
        FROM users
        WHERE reset_token_hash = :token_hash
        LIMIT 1
    ");
    $stmt->execute([':token_hash' => $tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    if (empty($user['reset_token_expires_at']) || strtotime($user['reset_token_expires_at']) < time()) {
        return null;
    }

    return $user;
}

function update_user_password_and_clear_token(int $userId, string $newPassword): void {
    $stmt = db()->prepare("
        UPDATE users
        SET password = :password,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE id = :id
    ");

    $stmt->execute([
        ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);
}

try {
    ensure_password_reset_columns_for_reset_page();

    $user = find_user_by_reset_token($token);

    if (!$user && $token !== '') {
        $error = 'This reset link is invalid or expired. Please request a new reset link.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$user) {
            $error = 'This reset link is invalid or expired. Please request a new reset link.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            update_user_password_and_clear_token((int)$user['id'], $password);
            $message = 'Your password has been reset successfully. You can now sign in.';
            $token = '';
            $user = null;
        }
    }
} catch (Throwable $e) {
    $error = 'Password reset error: ' . $e->getMessage();
}
?>

<section class="auth-section">
  <div class="auth-card">
    <h1>LebaneseAirline</h1>
    <h2>Create New Password</h2>

    <?php if ($message): ?>
      <div class="alert-success"><?= htmlspecialchars($message) ?></div>
      <p><a class="btn-primary" href="<?= BASE_URL ?>/auth/login.php">Go to Sign In</a></p>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($user): ?>
        <p class="muted">Enter a new password for <?= htmlspecialchars($user['email']) ?>.</p>

        <form method="POST" class="form">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <div class="form-group">
            <label>New password</label>
            <input
              type="password"
              name="password"
              placeholder="New password"
              required
            >
          </div>

          <div class="form-group">
            <label>Confirm new password</label>
            <input
              type="password"
              name="confirm_password"
              placeholder="Confirm new password"
              required
            >
          </div>

          <button class="btn-primary" type="submit">
            Reset Password
          </button>
        </form>
      <?php else: ?>
        <p><a href="<?= BASE_URL ?>/auth/forgot-password.php">Request a new reset link</a></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
