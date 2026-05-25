<?php
include __DIR__ . '/../includes/header.php';
require_login();
function dashboard_status_label(string $status): string {
    if ($status === 'Confirmed') {
        return 'Booked';
    }

    if ($status === 'Pending') {
        return 'Pending approval';
    }

    return $status;
}

function dashboard_status_badge_class(string $status): string {
    if ($status === 'Confirmed') {
        return 'ok';
    }

    if ($status === 'Rejected' || $status === 'Cancelled') {
        return 'danger';
    }

    if ($status === 'Pending') {
        return 'warning';
    }

    return '';
}
$success = flash_message('flash_success');
$bookings = get_bookings_for_user((int)$_SESSION['user_id']);
?>
<section class="page">
  <div class="page-header">
    <h2>User Dashboard</h2>
    <p class="muted">Welcome back. Manage your bookings and start a new trip.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="cards-grid cols-3">
    <div class="panel">
      <h3>New Booking</h3>
      <p>Search flights and book your next trip.</p>
      <a class="btn-dark" href="booking.php">Book a Flight</a>
    </div>

    <div class="panel">
      <h3>My Bookings</h3>
      <p>View your confirmed trips and booking details.</p>
      <a class="btn-dark" href="<?= BASE_URL ?>/user/my-bookings.php">View Bookings</a>
    </div>

    <div class="panel">
      <h3>Account</h3>
      <p>Review your profile details.</p>
      <a class="btn-dark" href="<?= BASE_URL ?>/user/profile.php">View Profile</a>
    </div>
  </div>

  <div class="table-wrap panel modern-card">
    <h3 class="mt">Recent Bookings</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Route</th>
          <th>Flight code</th>
          <th>Date</th>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$bookings): ?>
          <tr><td colspan="6">No bookings yet.</td></tr>
        <?php else: ?>
          <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
            <tr>
              <td>#<?= (int)$booking['id'] ?></td>
              <td><?= htmlspecialchars($booking['route']) ?></td>
              <td><?= htmlspecialchars($booking['flight_code']) ?></td>
              <td><?= htmlspecialchars($booking['date']) ?></td>
              <td><?php
  $status = $booking['status'] ?? 'Pending';
  $statusLabel = dashboard_status_label($status);
  $badgeClass = dashboard_status_badge_class($status);
?>

<span class="badge <?= htmlspecialchars($badgeClass) ?>">
  <?= htmlspecialchars($statusLabel) ?>
</span></td>
              <td>$<?= number_format((float)$booking['total'], 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
