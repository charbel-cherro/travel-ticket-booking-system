<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/flight-data.php';
require_admin();

$message = '';
$editingFlight = null;
$flights = get_all_flights();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_flight') {
        $flightId = (int)($_POST['flight_id'] ?? 0);
        $flight = [
            'id' => $flightId > 0 ? $flightId : next_flight_id(),
            'name' => trim($_POST['name'] ?? ''),
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'from' => trim($_POST['from'] ?? ''),
            'to' => trim($_POST['to'] ?? ''),
            'date' => trim($_POST['date'] ?? ''),
            'departure' => trim($_POST['departure'] ?? ''),
            'arrival' => trim($_POST['arrival'] ?? ''),
            'stops' => max(0, (int)($_POST['stops'] ?? 0)),
            'economy_price' => (float)($_POST['economy_price'] ?? 0),
            'business_price' => (float)($_POST['business_price'] ?? 0),
            'first_price' => (float)($_POST['first_price'] ?? 0),
            'status' => trim($_POST['status'] ?? 'active'),
        ];

        $updated = false;
        foreach ($flights as $index => $existing) {
            if ((int)$existing['id'] === $flight['id']) {
                $flights[$index] = $flight;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $flights[] = $flight;
        }
        save_all_flights($flights);
        $message = $updated ? 'Flight updated successfully.' : 'Flight added successfully.';
        $flights = get_all_flights();
    }

    if ($action === 'cancel_flight') {
        $id = (int)($_POST['flight_id'] ?? 0);
        foreach ($flights as $index => $existing) {
            if ((int)$existing['id'] === $id) {
                $flights[$index]['status'] = 'cancelled';
            }
        }
        save_all_flights($flights);
        $message = 'Flight cancelled successfully.';
        $flights = get_all_flights();
    }
}

if (isset($_GET['edit'])) {
    $editingFlight = get_flight_by_id((int)$_GET['edit']);
}
?>
<section class="page admin-layout">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Flights</h2>
    <p class="muted">Each flight now carries separate prices for economy, business, and first class. This lets the admin define the exact price shown during booking.</p>
  </div>

  <?php if ($message): ?>
    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="booking-grid admin-grid">
    <div class="panel modern-card">
      <h3><?= $editingFlight ? 'Edit flight' : 'Add new flight' ?></h3>
      <form method="POST" class="form">
        <input type="hidden" name="action" value="save_flight">
        <input type="hidden" name="flight_id" value="<?= (int)($editingFlight['id'] ?? 0) ?>">

        <div class="form-group">
          <label>Flight name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($editingFlight['name'] ?? '') ?>" placeholder="Beirut–Paris" required>
        </div>

        <div class="form-group">
          <label>Flight code</label>
          <input type="text" name="code" value="<?= htmlspecialchars($editingFlight['code'] ?? '') ?>" placeholder="LA203" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>From</label>
            <input type="text" name="from" value="<?= htmlspecialchars($editingFlight['from'] ?? '') ?>" placeholder="Beirut" required>
          </div>
          <div class="form-group">
            <label>To</label>
            <input type="text" name="to" value="<?= htmlspecialchars($editingFlight['to'] ?? '') ?>" placeholder="Paris" required>
          </div>
          <div class="form-group">
            <label>Stops</label>
            <input type="number" name="stops" min="0" value="<?= htmlspecialchars((string)($editingFlight['stops'] ?? 0)) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($editingFlight['date'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Departure time</label>
            <input type="time" name="departure" value="<?= htmlspecialchars($editingFlight['departure'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Arrival time</label>
            <input type="time" name="arrival" value="<?= htmlspecialchars($editingFlight['arrival'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Economy price</label>
            <input type="number" name="economy_price" min="0" step="0.01" value="<?= htmlspecialchars((string)($editingFlight['economy_price'] ?? ($editingFlight['price'] ?? ''))) ?>" required>
          </div>
          <div class="form-group">
            <label>Business price</label>
            <input type="number" name="business_price" min="0" step="0.01" value="<?= htmlspecialchars((string)($editingFlight['business_price'] ?? '')) ?>" required>
          </div>
          <div class="form-group">
            <label>First class price</label>
            <input type="number" name="first_price" min="0" step="0.01" value="<?= htmlspecialchars((string)($editingFlight['first_price'] ?? '')) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active" <?= (($editingFlight['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
              <option value="cancelled" <?= (($editingFlight['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>
        </div>

        <button class="btn-primary" type="submit"><?= $editingFlight ? 'Update flight' : 'Add flight' ?></button>
      </form>
    </div>

    <div class="panel modern-card table-wrap">
      <h3>Current flights</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Date</th>
            <th>Time</th>
            <th>Stops</th>
            <th>Economy</th>
            <th>Business</th>
            <th>First</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($flights as $flight): ?>
            <tr>
              <td><?= htmlspecialchars($flight['name']) ?></td>
              <td><?= htmlspecialchars($flight['code']) ?></td>
              <td><?= htmlspecialchars($flight['date']) ?></td>
              <td><?= htmlspecialchars($flight['departure']) ?> - <?= htmlspecialchars($flight['arrival']) ?></td>
              <td><?= (int)$flight['stops'] === 0 ? 'Direct' : (int)$flight['stops'] . ' stop(s)' ?></td>
              <td>$<?= number_format((float)$flight['economy_price'], 0) ?></td>
              <td>$<?= number_format((float)$flight['business_price'], 0) ?></td>
              <td>$<?= number_format((float)$flight['first_price'], 0) ?></td>
              <td><span class="badge <?= $flight['status'] === 'cancelled' ? 'danger' : 'ok' ?>"><?= htmlspecialchars(ucfirst($flight['status'])) ?></span></td>
              <td class="action-stack">
                <a class="btn-small" href="manage-flights.php?edit=<?= (int)$flight['id'] ?>">Edit</a>
                <form method="POST">
                  <input type="hidden" name="action" value="cancel_flight">
                  <input type="hidden" name="flight_id" value="<?= (int)$flight['id'] ?>">
                  <button type="submit" class="btn-small btn-danger-outline">Cancel</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
