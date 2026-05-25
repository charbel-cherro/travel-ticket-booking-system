<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mailer.php';
require_admin();

$message = '';
$error = '';

function admin_update_booking_status(int $bookingId, string $newStatus): void {
    $allowedStatuses = ['Pending', 'Confirmed', 'Rejected', 'Cancelled'];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Invalid booking status.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $bookingId,
        ]);

        if ($newStatus === 'Confirmed') {
            if (table_exists('payments') && column_exists('payments', 'booking_id')) {
                $paymentStmt = $pdo->prepare("
                    UPDATE payments
                    SET payment_status = 'Paid'
                    WHERE booking_id = :booking_id
                ");
                $paymentStmt->execute([':booking_id' => $bookingId]);
            }
        }

        if ($newStatus === 'Rejected' || $newStatus === 'Cancelled') {
            /*
              If the admin says No / Cancelled, release the seats.
              This allows another customer to choose those seats again.
            */
            if (table_exists('booking_seats') && column_exists('booking_seats', 'booking_id')) {
                $seatStmt = $pdo->prepare("DELETE FROM booking_seats WHERE booking_id = :booking_id");
                $seatStmt->execute([':booking_id' => $bookingId]);
            }

            if (table_exists('payments') && column_exists('payments', 'booking_id')) {
                $paymentStmt = $pdo->prepare("
                    UPDATE payments
                    SET payment_status = :payment_status
                    WHERE booking_id = :booking_id
                ");
                $paymentStmt->execute([
                    ':payment_status' => $newStatus,
                    ':booking_id' => $bookingId,
                ]);
            }
        }

        if ($newStatus === 'Pending') {
            if (table_exists('payments') && column_exists('payments', 'booking_id')) {
                $paymentStmt = $pdo->prepare("
                    UPDATE payments
                    SET payment_status = 'Pending'
                    WHERE booking_id = :booking_id
                ");
                $paymentStmt->execute([':booking_id' => $bookingId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function admin_find_booking_by_id(int $bookingId): ?array {
    foreach (get_all_bookings() as $booking) {
        if ((int)$booking['id'] === $bookingId) {
            return $booking;
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_status') {
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');

            if ($bookingId <= 0) {
                $error = 'Invalid booking ID.';
            } else {
                admin_update_booking_status($bookingId, $newStatus);

                $updatedBooking = admin_find_booking_by_id($bookingId);

                if ($updatedBooking) {
                    $emailSent = send_booking_decision_email($updatedBooking);

                    if ($emailSent) {
                        $message = 'Booking status updated successfully. Decision email sent to the customer.';
                    } else {
                        $message = 'Booking status updated successfully, but the email could not be sent. Check SMTP settings or Apache error log.';
                    }
                } else {
                    $message = 'Booking status updated successfully.';
                }
            }
        }
    } catch (Throwable $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

$bookings = get_all_bookings();

function status_badge_class(string $status): string {
    $status = strtolower($status);

    if ($status === 'confirmed') {
        return 'ok';
    }

    if ($status === 'rejected' || $status === 'cancelled') {
        return 'danger';
    }

    return 'warning';
}

function admin_status_label(string $status): string {
    if ($status === 'Confirmed') {
        return 'Confirmed';
    }

    if ($status === 'Rejected') {
        return 'Rejected';
    }

    if ($status === 'Cancelled') {
        return 'Cancelled';
    }

    return 'Pending';
}
?>

<section class="page admin-layout">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Bookings</h2>
    <p class="muted">
      Review customer booking requests, approve or reject them, and automatically email the customer with the admin decision.
    </p>
  </div>

  <?php if ($message): ?>
    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="panel modern-card table-wrap">
    <h3>Customer bookings</h3>

    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Customer</th>
          <th>Route</th>
          <th>Flight</th>
          <th>Date</th>
          <th>Class</th>
          <th>Passengers</th>
          <th>Seat(s)</th>
          <th>Total</th>
          <th>Status</th>
          <th>Admin decision</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <?php $currentStatus = $booking['status'] ?? 'Pending'; ?>

          <tr>
            <td>#<?= (int)$booking['id'] ?></td>

            <td>
              <strong><?= htmlspecialchars($booking['user_name'] ?: 'Customer') ?></strong><br>
              <small><?= htmlspecialchars($booking['user_email'] ?: '-') ?></small>
            </td>

            <td><?= htmlspecialchars($booking['route']) ?></td>

            <td>
              <strong><?= htmlspecialchars($booking['flight_code']) ?></strong><br>
              <small><?= htmlspecialchars($booking['flight_name']) ?></small>
            </td>

            <td><?= htmlspecialchars($booking['date']) ?></td>

            <td><?= htmlspecialchars(ucfirst($booking['class'])) ?></td>

            <td>
              <?= (int)$booking['passengers'] ?><br>
              <?php if (!empty($booking['passenger_names']) && is_array($booking['passenger_names'])): ?>
                <small><?= htmlspecialchars(implode(', ', $booking['passenger_names'])) ?></small>
              <?php endif; ?>
            </td>

            <td>
              <?= htmlspecialchars($booking['seat_number'] ?: '-') ?>

              <?php if (!empty($booking['return_seat_number'])): ?>
                <br><small>Return: <?= htmlspecialchars($booking['return_seat_number']) ?></small>
              <?php endif; ?>
            </td>

            <td>$<?= number_format((float)$booking['total'], 0) ?></td>

            <td>
              <span class="badge <?= htmlspecialchars(status_badge_class($currentStatus)) ?>">
                <?= htmlspecialchars(admin_status_label($currentStatus)) ?>
              </span>
            </td>

            <td>
              <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">

                <select name="status" required>
                  <option value="Pending" <?= $currentStatus === 'Pending' ? 'selected' : '' ?>>
                    Pending
                  </option>
                  <option value="Confirmed" <?= $currentStatus === 'Confirmed' ? 'selected' : '' ?>>
                    Confirm: Yes
                  </option>
                  <option value="Rejected" <?= $currentStatus === 'Rejected' ? 'selected' : '' ?>>
                    Confirm: No
                  </option>
                  <option value="Cancelled" <?= $currentStatus === 'Cancelled' ? 'selected' : '' ?>>
                    Cancelled
                  </option>
                </select>

                <button type="submit" class="btn-small">
                  Save
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($bookings)): ?>
          <tr>
            <td colspan="11">No bookings found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
