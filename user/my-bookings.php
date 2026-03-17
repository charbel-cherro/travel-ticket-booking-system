<?php
include __DIR__ . '/../includes/header.php';
require_login();
$success = flash_message('flash_success');
$bookings = get_bookings_for_user((int)$_SESSION['user_id']);
?>
<section class="page">
  <div class="page-header">
    <h2>My Bookings</h2>
    <p class="muted">View your flight booking history.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="panel table-wrap modern-card">
    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Route</th>
          <th>Flight code</th>
          <th>Flight time</th>
          <th>Date</th>
          <th>Seat(s)</th>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$bookings): ?>
          <tr><td colspan="8">No bookings found yet.</td></tr>
        <?php else: ?>
          <?php foreach ($bookings as $booking): ?>
            <tr>
              <td>#<?= (int)$booking['id'] ?></td>
              <td><?= htmlspecialchars($booking['route']) ?></td>
              <td><?= htmlspecialchars($booking['flight_code']) ?></td>
              <td><?= htmlspecialchars($booking['flight_time']) ?></td>
              <td><?= htmlspecialchars($booking['date']) ?></td>
              <td>
                <?= htmlspecialchars($booking['seat_number']) ?>
                <?php if (!empty($booking['return_seat_number'])): ?>
                  / <?= htmlspecialchars($booking['return_seat_number']) ?>
                <?php endif; ?>
              </td>
              <td><span class="badge ok"><?= htmlspecialchars($booking['status']) ?></span></td>
              <td>$<?= number_format((float)$booking['total'], 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
