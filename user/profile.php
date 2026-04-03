<?php
include __DIR__ . '/../includes/header.php';
require_login();
$user = current_user();
?>
<section class="page">
  <div class="page-header">
    <h2>My Profile</h2>
    <p class="muted">Your account information.</p>
  </div>

  <div class="panel modern-card">
    <form class="form">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="<?= htmlspecialchars($user['name'] ?? '') ?>" readonly>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Member Since</label>
          <input type="text" value="<?= htmlspecialchars(substr((string)($user['created_at'] ?? ''), 0, 10)) ?>" readonly>
        </div>
      </div>
    </form>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
