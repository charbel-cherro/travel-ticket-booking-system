<?php
include __DIR__ . '/../includes/header.php';
require_admin();

$message = '';
$destinations = get_all_destinations();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_destination') {
        $destination = [
            'id' => (int)($_POST['destination_id'] ?? 0) ?: next_destination_id(),
            'city' => trim($_POST['city'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'airport_code' => strtoupper(trim($_POST['airport_code'] ?? '')),
        ];

        $updated = false;
        foreach ($destinations as $index => $existing) {
            if ((int)$existing['id'] === (int)$destination['id']) {
                $destinations[$index] = $destination;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $destinations[] = $destination;
        }
        save_all_destinations($destinations);
        $destinations = get_all_destinations();
        $message = $updated ? 'Airport updated successfully.' : 'Airport added successfully.';
    }

    if ($action === 'delete_destination') {
        $deleteId = (int)($_POST['destination_id'] ?? 0);
        $destinations = array_values(array_filter($destinations, fn($destination) => (int)$destination['id'] !== $deleteId));
        save_all_destinations($destinations);
        $destinations = get_all_destinations();
        $message = 'Airport removed successfully.';
    }
}

$editingDestination = null;
if (isset($_GET['edit'])) {
    foreach ($destinations as $destination) {
        if ((int)$destination['id'] === (int)$_GET['edit']) {
            $editingDestination = $destination;
            break;
        }
    }
}
?>
<section class="page">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Destinations</h2>
    <p class="muted">This page is now limited to airport and destination data only. Seats and prices are managed in <strong>Manage Flights</strong>, because they depend on the specific flight and aircraft configuration.</p>
  </div>

  <?php if ($message): ?>
    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="booking-grid admin-grid">
    <div class="panel modern-card">
      <h3><?= $editingDestination ? 'Edit airport' : 'Add airport' ?></h3>
      <form method="POST" class="form">
        <input type="hidden" name="action" value="save_destination">
        <input type="hidden" name="destination_id" value="<?= (int)($editingDestination['id'] ?? 0) ?>">

        <div class="form-group">
          <label>City</label>
          <input type="text" name="city" value="<?= htmlspecialchars($editingDestination['city'] ?? '') ?>" placeholder="Paris" required>
        </div>

        <div class="form-group">
          <label>Country</label>
          <input type="text" name="country" value="<?= htmlspecialchars($editingDestination['country'] ?? '') ?>" placeholder="France" required>
        </div>

        <div class="form-group">
          <label>Airport code</label>
          <input type="text" name="airport_code" value="<?= htmlspecialchars($editingDestination['airport_code'] ?? '') ?>" placeholder="CDG" maxlength="3" required>
        </div>

        <button class="btn-primary" type="submit"><?= $editingDestination ? 'Update airport' : 'Add airport' ?></button>
      </form>
    </div>

    <div class="panel modern-card table-wrap">
      <div class="admin-note">
        <strong>Purpose of this page:</strong> keep a clean list of airports and destinations available for route creation. Pricing, stops, seat class prices, and availability belong to each individual flight, so they are handled on the flight management page.
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>City</th>
            <th>Country</th>
            <th>Airport code</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($destinations as $destination): ?>
            <tr>
              <td><?= htmlspecialchars($destination['city']) ?></td>
              <td><?= htmlspecialchars($destination['country']) ?></td>
              <td><span class="tag-inline"><?= htmlspecialchars($destination['airport_code']) ?></span></td>
              <td class="action-stack">
                <a class="btn-small" href="manage-destinations.php?edit=<?= (int)$destination['id'] ?>">Edit</a>
                <form method="POST">
                  <input type="hidden" name="action" value="delete_destination">
                  <input type="hidden" name="destination_id" value="<?= (int)$destination['id'] ?>">
                  <button class="btn-small btn-danger-outline" type="submit">Delete</button>
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
