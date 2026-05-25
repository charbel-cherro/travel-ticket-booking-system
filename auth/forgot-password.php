<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mailer.php';

$message = '';
$error = '';

function ensure_password_reset_columns(): void {
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

function find_user_by_email_for_reset(string $email): ?array {
    $stmt = db()->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function save_password_reset_token(int $userId, string $tokenHash, string $expiresAt): void {
    $stmt = db()->prepare("
        UPDATE users
        SET reset_token_hash = :token_hash,
            reset_token_expires_at = :expires_at
        WHERE id = :id
    ");

    $stmt->execute([
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
        ':id' => $userId,
    ]);
}

function send_password_reset_email_to_user(string $toEmail, string $toName, string $resetLink): bool {
    $safeName = htmlspecialchars($toName ?: 'Traveler', ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    $subject = 'Reset your LebaneseAirline password';

    $html = "
        <h2>Password reset request</h2>
        <p>Hi {$safeName},</p>
        <p>We received a request to reset your LebaneseAirline account password.</p>
        <p>
            <a href='{$safeLink}' style='display:inline-block;padding:12px 18px;background:#0f4778;color:#ffffff;text-decoration:none;border-radius:8px;'>
                Reset password
            </a>
        </p>
        <p>If the button does not work, copy and paste this link into your browser:</p>
        <p>{$safeLink}</p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this, you can ignore this email.</p>
        <p>LebaneseAirline</p>
    ";

    return send_app_email($toEmail, $toName, $subject, $html);
}

try {
    ensure_password_reset_columns();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $user = find_user_by_email_for_reset($email);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                save_password_reset_token((int)$user['id'], $tokenHash, $expiresAt);

                $resetLink = 'http://localhost' . BASE_URL . '/auth/reset-password.php?token=' . urlencode($token);

                send_password_reset_email_to_user(
                    $user['email'],
                    $user['name'] ?? 'Traveler',
                    $resetLink
                );
            }

            $message = 'If this email exists in our system, a reset link has been sent.';
        }
    }
} catch (Throwable $e) {
    $error = 'Password reset error: ' . $e->getMessage();
}
?>

<style>
  .auth-page-fixed {
    min-height: calc(100vh - 180px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
  }

  .auth-card-fixed {
    width: 100%;
    max-width: 520px;
    background: #ffffff;
    border: 1px solid #dbe4ef;
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
  }

  .auth-card-fixed h1 {
    margin: 0 0 12px;
    color: #0f4778;
    font-size: 32px;
  }

  .auth-card-fixed h2 {
    margin: 0 0 8px;
    font-size: 26px;
    color: #0f172a;
  }

  .auth-card-fixed .muted {
    color: #64748b;
    margin-bottom: 20px;
  }

  .auth-card-fixed .form-group {
    margin-bottom: 16px;
  }

  .auth-card-fixed label {
    display: block;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .auth-card-fixed input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #d5e0ee;
    border-radius: 12px;
    font-size: 16px;
  }

  .auth-card-fixed .btn-primary {
    width: 100%;
    margin-top: 4px;
  }

  .auth-card-fixed .auth-link {
    text-align: center;
    margin-top: 18px;
  }
</style>

<section class="auth-page-fixed">
  <div class="auth-card-fixed">
    <h1>LebaneseAirline</h1>
    <h2>Reset Password</h2>
    <p class="muted">Enter your email to receive a reset link.</p>

    <?php if ($message): ?>
      <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form">
      <div class="form-group">
        <label>Email address</label>
        <input
          type="email"
          name="email"
          placeholder="Enter your email"
          required
        >
      </div>

      <button class="btn-primary" type="submit">
        Send Reset Link
      </button>
    </form>

    <p class="auth-link">
      Remember your password?
      <a href="<?= BASE_URL ?>/auth/login.php">Sign In</a>
    </p>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>