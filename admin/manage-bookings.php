<?php
include __DIR__ . '/../includes/header.php';
require_admin();
$bookings = get_all_bookings();
usort($bookings, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
?>
<section class="page">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Bookings</h2>
  </div>

  <div class="panel table-wrap modern-card">
    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>User</th>
          <th>Route</th>
          <th>Flight code</th>
          <th>Flight time</th>
          <th>Date</th>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$bookings): ?>
          <tr><td colspan="8">No bookings have been created yet.</td></tr>
        <?php else: ?>
          <?php foreach ($bookings as $booking): ?>
            <tr>
              <td>#<?= (int)$booking['id'] ?></td>
              <td><?= htmlspecialchars($booking['user_email'] ?: $booking['user_name']) ?></td>
              <td><?= htmlspecialchars($booking['route']) ?></td>
              <td><?= htmlspecialchars($booking['flight_code']) ?></td>
              <td><?= htmlspecialchars($booking['flight_time']) ?></td>
              <td><?= htmlspecialchars($booking['date']) ?></td>
              <td><span class="badge <?= strtolower($booking['status']) === 'confirmed' ? 'ok' : 'wait' ?>"><?= htmlspecialchars($booking['status']) ?></span></td>
              <td>$<?= number_format((float)$booking['total'], 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
