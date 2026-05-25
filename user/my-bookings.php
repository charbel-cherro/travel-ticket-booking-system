<?php
include __DIR__ . '/../includes/header.php';
require_login();

function user_status_label(string $status): string {
    if ($status === 'Confirmed') {
        return 'Booked';
    }

    if ($status === 'Pending') {
        return 'Pending approval';
    }

    if ($status === 'Rejected') {
        return 'Rejected';
    }

    if ($status === 'Cancelled') {
        return 'Cancelled';
    }

    return $status;
}

function user_status_badge_class(string $status): string {
    if ($status === 'Confirmed') {
        return 'ok';
    }

    if ($status === 'Rejected' || $status === 'Cancelled') {
        return 'danger';
    }

    return '';
}

function user_success_message(string $message): string {
    $message = str_replace('has been confirmed', 'has been booked', $message);
    $message = str_replace('confirmed', 'booked', $message);

    return $message;
}

$success = flash_message('flash_success');
$bookings = get_bookings_for_user((int)$_SESSION['user_id']);
?>

<section class="page">
  <div class="page-header">
    <h2>My Bookings</h2>
    <p class="muted">View your flight booking history.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert-success"><?= htmlspecialchars(user_success_message($success)) ?></div>
  <?php endif; ?>

  <div class="panel table-wrap modern-card">
    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Route</th>
          <th>Passengers</th>
          <th>Flight code</th>
          <th>Flight time</th>
          <th>Date</th>
          <th>Seats</th>
          <th>Bags</th>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>

      <tbody>
        <?php if (!$bookings): ?>
          <tr>
            <td colspan="10">No bookings found yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($bookings as $booking): ?>
            <?php
              $status = $booking['status'] ?? 'Pending';
              $statusLabel = user_status_label($status);
              $badgeClass = user_status_badge_class($status);
            ?>

            <tr>
              <td>#<?= (int)$booking['id'] ?></td>

              <td><?= htmlspecialchars($booking['route']) ?></td>

              <td>
                <?php if (!empty($booking['passenger_names']) && is_array($booking['passenger_names'])): ?>
                  <?= htmlspecialchars(implode(', ', $booking['passenger_names'])) ?>
                <?php else: ?>
                  <?= (int)($booking['passengers'] ?? 1) ?> traveler(s)
                <?php endif; ?>
              </td>

              <td><?= htmlspecialchars($booking['flight_code']) ?></td>

              <td><?= htmlspecialchars($booking['flight_time']) ?></td>

              <td><?= htmlspecialchars($booking['date']) ?></td>

              <td>
                <?= htmlspecialchars($booking['seat_number'] ?: '-') ?>

                <?php if (!empty($booking['return_seat_number'])): ?>
                  / <?= htmlspecialchars($booking['return_seat_number']) ?>
                <?php endif; ?>
              </td>

              <td>
                Hand: <?= (int)($booking['hand_bags'] ?? 0) ?>,
                Checked: <?= (int)($booking['checked_bags'] ?? 0) ?>
              </td>

              <td>
                <span class="badge <?= htmlspecialchars($badgeClass) ?>">
                  <?= htmlspecialchars($statusLabel) ?>
                </span>
              </td>

              <td>$<?= number_format((float)$booking['total'], 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>