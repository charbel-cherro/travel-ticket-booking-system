<?php
include __DIR__ . '/config.php';
include_once __DIR__ . '/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = current_user();
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$hideHeaderAuth = str_contains($currentPath, '/auth/login.php') || str_contains($currentPath, '/auth/policy.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LebaneseAirline</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="navbar">
  <div class="nav-container">
    <a class="logo" href="<?= BASE_URL ?>/index.php">LebaneseAirline</a>

    <nav class="nav-links">
      <ul>
        <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
        <li><a href="<?= BASE_URL ?>/user/booking.php">Book</a></li>
        <?php if ($user): ?>
          <li><a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a></li>
        <?php endif; ?>
        <?php if (is_admin()): ?>
          <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="nav-right">
      <?php if ($user): ?>
        <span class="nav-user-chip"><?= htmlspecialchars($user['name']) ?></span>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-signin">Logout</a>
      <?php elseif (!$hideHeaderAuth): ?>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-signin">Sign in</a>
      <?php endif; ?>
    </div>
  </div>
</header>
