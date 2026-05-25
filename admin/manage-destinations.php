<?php
include __DIR__ . '/../includes/header.php';
require_admin();

$message = '';
$error = '';
$editingDestination = null;

function admin_get_all_destinations(): array {
    $stmt = db()->query("SELECT id, city, country, airport_code FROM destinations ORDER BY city ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function admin_get_destination_by_id(int $id): ?array {
    $stmt = db()->prepare("SELECT id, city, country, airport_code FROM destinations WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);
    return $destination ?: null;
}

function admin_save_destination(int $id, string $city, string $country, string $airportCode): bool {
    $city = trim($city);
    $country = trim($country);
    $airportCode = strtoupper(trim($airportCode));

    if ($city === '' || $country === '' || $airportCode === '') {
        throw new InvalidArgumentException('City, country, and airport code are required.');
    }

    if (!preg_match('/^[A-Z]{2,5}$/', $airportCode)) {
        throw new InvalidArgumentException('Airport code must be 2 to 5 letters, for example SYD, MEL, CDG.');
    }

    if ($id > 0) {
        $stmt = db()->prepare(
            "UPDATE destinations
             SET city = :city, country = :country, airport_code = :airport_code
             WHERE id = :id"
        );
        $stmt->execute([
            ':city' => $city,
            ':country' => $country,
            ':airport_code' => $airportCode,
            ':id' => $id,
        ]);
        return true;
    }

    $stmt = db()->prepare(
        "INSERT INTO destinations (city, country, airport_code)
         VALUES (:city, :country, :airport_code)"
    );
    $stmt->execute([
        ':city' => $city,
        ':country' => $country,
        ':airport_code' => $airportCode,
    ]);
    return false;
}

function admin_delete_destination(int $id): void {
    if ($id <= 0) {
        throw new InvalidArgumentException('Invalid airport ID.');
    }

    $stmt = db()->prepare("DELETE FROM destinations WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_destination') {
            $id = (int)($_POST['destination_id'] ?? 0);
            $city = $_POST['city'] ?? '';
            $country = $_POST['country'] ?? '';
            $airportCode = $_POST['airport_code'] ?? '';

            $updated = admin_save_destination($id, $city, $country, $airportCode);
            $message = $updated ? 'Airport updated successfully.' : 'Airport added successfully.';
        }

        if ($action === 'delete_destination') {
            $id = (int)($_POST['destination_id'] ?? 0);
            admin_delete_destination($id);
            $message = 'Airport deleted successfully.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['edit'])) {
    $editingDestination = admin_get_destination_by_id((int)$_GET['edit']);
}

$destinations = admin_get_all_destinations();
?>

<section class="page admin-layout">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Airports</h2>
    <p class="muted">Add, edit, or delete airport destinations used by the booking system.</p>
  </div>

  <?php if ($message): ?>
    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="booking-grid admin-grid">
    <div class="panel modern-card">
      <h3><?= $editingDestination ? 'Edit airport' : 'Add airport' ?></h3>

      <form method="POST" class="form">
        <input type="hidden" name="action" value="save_destination">
        <input type="hidden" name="destination_id" value="<?= (int)($editingDestination['id'] ?? 0) ?>">

        <div class="form-group">
          <label>City</label>
          <input
            type="text"
            name="city"
            value="<?= htmlspecialchars($editingDestination['city'] ?? '') ?>"
            placeholder="Beirut"
            required
          >
        </div>

        <div class="form-group">
          <label>Country</label>
          <input
            type="text"
            name="country"
            value="<?= htmlspecialchars($editingDestination['country'] ?? '') ?>"
            placeholder="LEBANON"
            required
          >
        </div>

        <div class="form-group">
          <label>Airport code</label>
          <input
            type="text"
            name="airport_code"
            value="<?= htmlspecialchars($editingDestination['airport_code'] ?? '') ?>"
            placeholder="BEY"
            maxlength="5"
            required
          >
        </div>

        <button class="btn-primary" type="submit">
          <?= $editingDestination ? 'Update airport' : 'Add airport' ?>
        </button>

        <?php if ($editingDestination): ?>
          <a class="btn-small" href="manage-destinations.php">Cancel edit</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="panel modern-card table-wrap">
      <h3>Available airports</h3>

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
              <td><span class="badge"><?= htmlspecialchars($destination['airport_code']) ?></span></td>
              <td class="action-stack">
                <a class="btn-small" href="manage-destinations.php?edit=<?= (int)$destination['id'] ?>">Edit</a>

                <form method="POST" onsubmit="return confirm('Delete this airport?');">
                  <input type="hidden" name="action" value="delete_destination">
                  <input type="hidden" name="destination_id" value="<?= (int)$destination['id'] ?>">
                  <button type="submit" class="btn-small btn-danger-outline">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($destinations)): ?>
            <tr>
              <td colspan="4">No airports found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>