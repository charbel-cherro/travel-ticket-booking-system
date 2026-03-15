<?php
include __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// $_SESSION['user_id']=1;
// $_SESSION['role']='admin'; or user to acess user dashboard
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>LebaneseAirline</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="navbar">
  <div class="nav-container">

    <div class="logo">
      <h1>LebaneseAirline</h1>
    </div>

    <nav class="nav-links">
      <ul>
        <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
        <li><a href="<?= BASE_URL ?>/user/booking.php">Book</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a></li>
        <?php endif; ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
        <?php endif; ?>

      </ul>
    </nav>

    <div class="nav-right">

      <?php if (isset($_SESSION['user_id'])): ?>
          <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-signin">Logout</a>
      <?php else: ?>
          <a href="<?= BASE_URL ?>/auth/login.php" class="btn-signin">Sign In</a>
      <?php endif; ?>

    </div>

  </div>
</header>