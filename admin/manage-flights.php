<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/flight-data.php';
include __DIR__ . '/../includes/mailer.php';
require_admin();

$message = '';
$error = '';
$editingFlight = null;

if (!defined('DEFAULT_ECONOMY_SEATS')) {
    define('DEFAULT_ECONOMY_SEATS', 10);
}

if (!defined('DEFAULT_BUSINESS_SEATS')) {
    define('DEFAULT_BUSINESS_SEATS', 3);
}

if (!defined('DEFAULT_FIRST_SEATS')) {
    define('DEFAULT_FIRST_SEATS', 2);
}

if (!defined('MAX_TOTAL_SEATS')) {
    define('MAX_TOTAL_SEATS', 250);
}

function ensure_flight_columns(): void {
    $columns = [
        'arrival_date' => "ALTER TABLE flights ADD COLUMN arrival_date DATE NULL AFTER flight_date",
        'seat_count' => "ALTER TABLE flights ADD COLUMN seat_count INT NOT NULL DEFAULT 15 AFTER first_price",
        'economy_seats' => "ALTER TABLE flights ADD COLUMN economy_seats INT NOT NULL DEFAULT 10 AFTER seat_count",
        'business_seats' => "ALTER TABLE flights ADD COLUMN business_seats INT NOT NULL DEFAULT 3 AFTER economy_seats",
        'first_seats' => "ALTER TABLE flights ADD COLUMN first_seats INT NOT NULL DEFAULT 2 AFTER business_seats",
    ];

    foreach ($columns as $column => $sql) {
        $stmt = db()->query("SHOW COLUMNS FROM flights LIKE " . db()->quote($column));
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exists) {
            db()->exec($sql);
        }
    }

    db()->exec("UPDATE flights SET arrival_date = flight_date WHERE arrival_date IS NULL");
}

function admin_flight_row_to_array(array $row): array {
    $economySeats = (int)($row['economy_seats'] ?? DEFAULT_ECONOMY_SEATS);
    $businessSeats = (int)($row['business_seats'] ?? DEFAULT_BUSINESS_SEATS);
    $firstSeats = (int)($row['first_seats'] ?? DEFAULT_FIRST_SEATS);
    $seatCount = (int)($row['seat_count'] ?? ($economySeats + $businessSeats + $firstSeats));

    return [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'code' => $row['code'],
        'from' => $row['from_city'],
        'to' => $row['to_city'],
        'date' => $row['flight_date'],
        'arrival_date' => $row['arrival_date'] ?? $row['flight_date'],
        'departure' => substr((string)$row['departure_time'], 0, 5),
        'arrival' => substr((string)$row['arrival_time'], 0, 5),
        'stops' => (int)$row['stops'],
        'economy_price' => (float)$row['economy_price'],
        'business_price' => (float)$row['business_price'],
        'first_price' => (float)$row['first_price'],
        'seat_count' => $seatCount,
        'economy_seats' => $economySeats,
        'business_seats' => $businessSeats,
        'first_seats' => $firstSeats,
        'status' => $row['status'],
    ];
}

function admin_get_all_flights(): array {
    $stmt = db()->query("SELECT * FROM flights ORDER BY flight_date ASC, departure_time ASC, id ASC");
    return array_map('admin_flight_row_to_array', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function admin_get_flight_by_id(int $id): ?array {
    $stmt = db()->prepare("SELECT * FROM flights WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? admin_flight_row_to_array($row) : null;
}

function admin_save_flight(array $flight): bool {
    $isUpdate = (int)$flight['id'] > 0;

    if ($isUpdate) {
        $stmt = db()->prepare("
            UPDATE flights
            SET
                name = :name,
                code = :code,
                from_city = :from_city,
                to_city = :to_city,
                flight_date = :flight_date,
                arrival_date = :arrival_date,
                departure_time = :departure_time,
                arrival_time = :arrival_time,
                stops = :stops,
                economy_price = :economy_price,
                business_price = :business_price,
                first_price = :first_price,
                seat_count = :seat_count,
                economy_seats = :economy_seats,
                business_seats = :business_seats,
                first_seats = :first_seats,
                status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => (int)$flight['id'],
            ':name' => $flight['name'],
            ':code' => $flight['code'],
            ':from_city' => $flight['from'],
            ':to_city' => $flight['to'],
            ':flight_date' => $flight['date'],
            ':arrival_date' => $flight['arrival_date'],
            ':departure_time' => $flight['departure'],
            ':arrival_time' => $flight['arrival'],
            ':stops' => (int)$flight['stops'],
            ':economy_price' => (float)$flight['economy_price'],
            ':business_price' => (float)$flight['business_price'],
            ':first_price' => (float)$flight['first_price'],
            ':seat_count' => (int)$flight['seat_count'],
            ':economy_seats' => (int)$flight['economy_seats'],
            ':business_seats' => (int)$flight['business_seats'],
            ':first_seats' => (int)$flight['first_seats'],
            ':status' => $flight['status'],
        ]);

        return true;
    }

    $stmt = db()->prepare("
        INSERT INTO flights
        (
            name,
            code,
            from_city,
            to_city,
            flight_date,
            arrival_date,
            departure_time,
            arrival_time,
            stops,
            economy_price,
            business_price,
            first_price,
            seat_count,
            economy_seats,
            business_seats,
            first_seats,
            status
        )
        VALUES
        (
            :name,
            :code,
            :from_city,
            :to_city,
            :flight_date,
            :arrival_date,
            :departure_time,
            :arrival_time,
            :stops,
            :economy_price,
            :business_price,
            :first_price,
            :seat_count,
            :economy_seats,
            :business_seats,
            :first_seats,
            :status
        )
    ");

    $stmt->execute([
        ':name' => $flight['name'],
        ':code' => $flight['code'],
        ':from_city' => $flight['from'],
        ':to_city' => $flight['to'],
        ':flight_date' => $flight['date'],
        ':arrival_date' => $flight['arrival_date'],
        ':departure_time' => $flight['departure'],
        ':arrival_time' => $flight['arrival'],
        ':stops' => (int)$flight['stops'],
        ':economy_price' => (float)$flight['economy_price'],
        ':business_price' => (float)$flight['business_price'],
        ':first_price' => (float)$flight['first_price'],
        ':seat_count' => (int)$flight['seat_count'],
        ':economy_seats' => (int)$flight['economy_seats'],
        ':business_seats' => (int)$flight['business_seats'],
        ':first_seats' => (int)$flight['first_seats'],
        ':status' => $flight['status'],
    ]);

    return false;
}

function admin_cancel_flight(int $id): void {
    $stmt = db()->prepare("UPDATE flights SET status = 'cancelled' WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

try {
    ensure_flight_columns();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_flight') {
            $flightId = (int)($_POST['flight_id'] ?? 0);

            $status = trim($_POST['status'] ?? 'active');
            if (!in_array($status, ['active', 'cancelled'], true)) {
                $status = 'active';
            }

            $economySeats = max(0, (int)($_POST['economy_seats'] ?? DEFAULT_ECONOMY_SEATS));
            $businessSeats = max(0, (int)($_POST['business_seats'] ?? DEFAULT_BUSINESS_SEATS));
            $firstSeats = max(0, (int)($_POST['first_seats'] ?? DEFAULT_FIRST_SEATS));

            $totalSeats = $economySeats + $businessSeats + $firstSeats;

            if ($totalSeats === 0) {
                $economySeats = DEFAULT_ECONOMY_SEATS;
                $businessSeats = DEFAULT_BUSINESS_SEATS;
                $firstSeats = DEFAULT_FIRST_SEATS;
                $totalSeats = $economySeats + $businessSeats + $firstSeats;
            }

            $flight = [
                'id' => $flightId,
                'name' => trim($_POST['name'] ?? ''),
                'code' => strtoupper(trim($_POST['code'] ?? '')),
                'from' => trim($_POST['from'] ?? ''),
                'to' => trim($_POST['to'] ?? ''),
                'date' => trim($_POST['date'] ?? ''),
                'arrival_date' => trim($_POST['arrival_date'] ?? ($_POST['date'] ?? '')),
                'departure' => trim($_POST['departure'] ?? ''),
                'arrival' => trim($_POST['arrival'] ?? ''),
                'stops' => max(0, (int)($_POST['stops'] ?? 0)),
                'economy_price' => (float)($_POST['economy_price'] ?? 0),
                'business_price' => (float)($_POST['business_price'] ?? 0),
                'first_price' => (float)($_POST['first_price'] ?? 0),
                'seat_count' => $totalSeats,
                'economy_seats' => $economySeats,
                'business_seats' => $businessSeats,
                'first_seats' => $firstSeats,
                'status' => $status,
            ];

            if (
                $flight['name'] === '' ||
                $flight['code'] === '' ||
                $flight['from'] === '' ||
                $flight['to'] === '' ||
                $flight['date'] === '' ||
                $flight['arrival_date'] === '' ||
                $flight['departure'] === '' ||
                $flight['arrival'] === ''
            ) {
                $error = 'Please fill in all required flight fields.';
            } elseif (strtotime($flight['arrival_date']) < strtotime($flight['date'])) {
                $error = 'Arrival date cannot be before departure date.';
            } elseif ($totalSeats > MAX_TOTAL_SEATS) {
                $error = 'The total number of seats cannot be more than ' . MAX_TOTAL_SEATS . '.';
            } else {
                $updated = admin_save_flight($flight);

                $message = $updated ? 'Flight updated successfully.' : 'Flight added successfully.';

                if (!$updated) {
                    $sentCount = send_new_flight_email_to_all_users($flight);
                    $message .= ' Email sent to ' . $sentCount . ' user(s).';
                }
            }
        }

        if ($action === 'cancel_flight') {
            $id = (int)($_POST['flight_id'] ?? 0);
            if ($id > 0) {
                admin_cancel_flight($id);
                $message = 'Flight cancelled successfully.';
            }
        }
    }

    if (isset($_GET['edit'])) {
        $editingFlight = admin_get_flight_by_id((int)$_GET['edit']);
    }

    $flights = admin_get_all_flights();
} catch (Throwable $e) {
    $flights = [];
    $error = 'Database error: ' . $e->getMessage();
}
?>

<section class="page admin-layout">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Flights</h2>
    <p class="muted">
      Add flights, choose prices, set departure/arrival dates, and define how many Economy, Business, and First Class seats each flight has.
    </p>
  </div>

  <?php if ($message): ?>
    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="booking-grid admin-grid">
    <div class="panel modern-card">
      <h3><?= $editingFlight ? 'Edit flight' : 'Add new flight' ?></h3>

      <form method="POST" class="form">
        <input type="hidden" name="action" value="save_flight">
        <input type="hidden" name="flight_id" value="<?= (int)($editingFlight['id'] ?? 0) ?>">

        <div class="form-group">
          <label>Flight name</label>
          <input
            type="text"
            name="name"
            value="<?= htmlspecialchars($editingFlight['name'] ?? '') ?>"
            placeholder="Beirut–Paris"
            required
          >
        </div>

        <div class="form-group">
          <label>Flight code</label>
          <input
            type="text"
            name="code"
            value="<?= htmlspecialchars($editingFlight['code'] ?? '') ?>"
            placeholder="LA203"
            required
          >
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>From</label>
            <input
              type="text"
              name="from"
              value="<?= htmlspecialchars($editingFlight['from'] ?? '') ?>"
              placeholder="Beirut"
              required
            >
          </div>

          <div class="form-group">
            <label>To</label>
            <input
              type="text"
              name="to"
              value="<?= htmlspecialchars($editingFlight['to'] ?? '') ?>"
              placeholder="Paris"
              required
            >
          </div>

          <div class="form-group">
            <label>Stops</label>
            <input
              type="number"
              name="stops"
              min="0"
              value="<?= htmlspecialchars((string)($editingFlight['stops'] ?? 0)) ?>"
              required
            >
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Departure date</label>
            <input
              type="date"
              name="date"
              value="<?= htmlspecialchars($editingFlight['date'] ?? '') ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>Arrival date</label>
            <input
              type="date"
              name="arrival_date"
              value="<?= htmlspecialchars($editingFlight['arrival_date'] ?? ($editingFlight['date'] ?? '')) ?>"
              required
            >
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Departure time</label>
            <input
              type="time"
              name="departure"
              value="<?= htmlspecialchars($editingFlight['departure'] ?? '') ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>Arrival time</label>
            <input
              type="time"
              name="arrival"
              value="<?= htmlspecialchars($editingFlight['arrival'] ?? '') ?>"
              required
            >
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Economy price</label>
            <input
              type="number"
              name="economy_price"
              min="0"
              step="0.01"
              value="<?= htmlspecialchars((string)($editingFlight['economy_price'] ?? '')) ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>Business price</label>
            <input
              type="number"
              name="business_price"
              min="0"
              step="0.01"
              value="<?= htmlspecialchars((string)($editingFlight['business_price'] ?? '')) ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>First class price</label>
            <input
              type="number"
              name="first_price"
              min="0"
              step="0.01"
              value="<?= htmlspecialchars((string)($editingFlight['first_price'] ?? '')) ?>"
              required
            >
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Economy seats</label>
            <input
              type="number"
              name="economy_seats"
              min="0"
              max="250"
              value="<?= htmlspecialchars((string)($editingFlight['economy_seats'] ?? DEFAULT_ECONOMY_SEATS)) ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>Business seats</label>
            <input
              type="number"
              name="business_seats"
              min="0"
              max="250"
              value="<?= htmlspecialchars((string)($editingFlight['business_seats'] ?? DEFAULT_BUSINESS_SEATS)) ?>"
              required
            >
          </div>

          <div class="form-group">
            <label>First class seats</label>
            <input
              type="number"
              name="first_seats"
              min="0"
              max="250"
              value="<?= htmlspecialchars((string)($editingFlight['first_seats'] ?? DEFAULT_FIRST_SEATS)) ?>"
              required
            >
          </div>
        </div>

        <p class="muted">
          Default total seats: 15. Maximum total seats: 250.
        </p>

        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active" <?= (($editingFlight['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>
                Active
              </option>
              <option value="cancelled" <?= (($editingFlight['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>
                Cancelled
              </option>
            </select>
          </div>
        </div>

        <button class="btn-primary" type="submit">
          <?= $editingFlight ? 'Update flight' : 'Add flight' ?>
        </button>
      </form>
    </div>

    <div class="panel modern-card table-wrap">
      <h3>Current flights</h3>

      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Route</th>
            <th>Departure date</th>
            <th>Arrival date</th>
            <th>Time</th>
            <th>Stops</th>
            <th>Economy seats</th>
            <th>Business seats</th>
            <th>First seats</th>
            <th>Total seats</th>
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
              <td><?= htmlspecialchars($flight['from']) ?> → <?= htmlspecialchars($flight['to']) ?></td>
              <td><?= htmlspecialchars($flight['date']) ?></td>
              <td><?= htmlspecialchars($flight['arrival_date']) ?></td>
              <td><?= htmlspecialchars($flight['departure']) ?> - <?= htmlspecialchars($flight['arrival']) ?></td>
              <td>
                <?= (int)$flight['stops'] === 0 ? 'Direct' : (int)$flight['stops'] . ' stop(s)' ?>
              </td>
              <td><?= (int)$flight['economy_seats'] ?></td>
              <td><?= (int)$flight['business_seats'] ?></td>
              <td><?= (int)$flight['first_seats'] ?></td>
              <td><?= (int)$flight['seat_count'] ?></td>
              <td>$<?= number_format((float)$flight['economy_price'], 0) ?></td>
              <td>$<?= number_format((float)$flight['business_price'], 0) ?></td>
              <td>$<?= number_format((float)$flight['first_price'], 0) ?></td>
              <td>
                <span class="badge <?= $flight['status'] === 'cancelled' ? 'danger' : 'ok' ?>">
                  <?= htmlspecialchars(ucfirst($flight['status'])) ?>
                </span>
              </td>
              <td class="action-stack">
                <a class="btn-small" href="manage-flights.php?edit=<?= (int)$flight['id'] ?>">
                  Edit
                </a>

                <form method="POST" onsubmit="return confirm('Cancel this flight?');">
                  <input type="hidden" name="action" value="cancel_flight">
                  <input type="hidden" name="flight_id" value="<?= (int)$flight['id'] ?>">
                  <button type="submit" class="btn-small btn-danger-outline">
                    Cancel
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($flights)): ?>
            <tr>
              <td colspan="16">No flights found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>