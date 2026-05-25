<?php
include __DIR__ . '/../includes/header.php';
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_insurance') {
            $insuranceId = (int)($_POST['insurance_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if ($name === '') {
                $error = 'Insurance type is required.';
            } elseif ($price < 0) {
                $error = 'Price cannot be negative.';
            } elseif ($description === '') {
                $error = 'Description is required.';
            } else {
                $pdo = db();
                $pdo->beginTransaction();

                if ($insuranceId > 0) {
                    // Update existing option
                    $stmt = $pdo->prepare("
                        UPDATE insurance_options
                        SET name = :name, price = :price, description = :description
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':price' => $price,
                        ':description' => $description,
                        ':id' => $insuranceId,
                    ]);

                    // Keep compatibility table synced
                    $compatStmt = $pdo->prepare("
                        INSERT INTO insurance (insurance_id, insurance_type, insurance_price)
                        VALUES (:id, :name, :price)
                        ON DUPLICATE KEY UPDATE
                            insurance_type = VALUES(insurance_type),
                            insurance_price = VALUES(insurance_price)
                    ");
                    $compatStmt->execute([
                        ':id' => $insuranceId,
                        ':name' => $name,
                        ':price' => $price,
                    ]);

                    $message = 'Insurance option updated successfully.';
                } else {
                    // Add new option
                    $stmt = $pdo->prepare("
                        INSERT INTO insurance_options (name, price, description)
                        VALUES (:name, :price, :description)
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':price' => $price,
                        ':description' => $description,
                    ]);

                    $newId = (int)$pdo->lastInsertId();

                    // Keep compatibility table synced
                    $compatStmt = $pdo->prepare("
                        INSERT INTO insurance (insurance_id, insurance_type, insurance_price)
                        VALUES (:id, :name, :price)
                        ON DUPLICATE KEY UPDATE
                            insurance_type = VALUES(insurance_type),
                            insurance_price = VALUES(insurance_price)
                    ");
                    $compatStmt->execute([
                        ':id' => $newId,
                        ':name' => $name,
                        ':price' => $price,
                    ]);

                    $message = 'Insurance option added successfully.';
                }

                $pdo->commit();
            }
        }

        if ($action === 'delete_insurance') {
            $deleteId = (int)($_POST['insurance_id'] ?? 0);

            if ($deleteId > 0) {
                $pdo = db();
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("DELETE FROM insurance_options WHERE id = :id");
                $stmt->execute([':id' => $deleteId]);

                $compatStmt = $pdo->prepare("DELETE FROM insurance WHERE insurance_id = :id");
                $compatStmt->execute([':id' => $deleteId]);

                $pdo->commit();

                $message = 'Insurance option removed successfully.';
            }
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Database error: ' . $e->getMessage();
    }
}

$options = get_all_insurance_options();

$editingOption = null;
if (isset($_GET['edit'])) {
    foreach ($options as $option) {
        if ((int)$option['id'] === (int)$_GET['edit']) {
            $editingOption = $option;
            break;
        }
    }
}
?>
<section class="page">
  <div class="page-header">
    <span class="eyebrow">Admin area</span>
    <h2>Manage Insurance</h2>
    <p class="muted">All current insurance options are listed below, and the admin can add new ones or update existing plans.</p>
  </div>

  <?php if ($message): ?>
    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="booking-grid admin-grid">
    <div class="panel modern-card">
      <h3><?= $editingOption ? 'Edit insurance option' : 'Add insurance option' ?></h3>
      <form method="POST" class="form">
        <input type="hidden" name="action" value="save_insurance">
        <input type="hidden" name="insurance_id" value="<?= (int)($editingOption['id'] ?? 0) ?>">

        <div class="form-group">
          <label>Insurance type</label>
          <input type="text" name="name" value="<?= htmlspecialchars($editingOption['name'] ?? '') ?>" placeholder="Premium Plus" required>
        </div>

        <div class="form-group">
          <label>Price</label>
          <input type="number" name="price" min="0" step="0.01" value="<?= htmlspecialchars((string)($editingOption['price'] ?? 0)) ?>" required>
        </div>

        <div class="form-group">
          <label>Description</label>
          <input type="text" name="description" value="<?= htmlspecialchars($editingOption['description'] ?? '') ?>" placeholder="Medical support and baggage cover" required>
        </div>

        <button class="btn-primary" type="submit"><?= $editingOption ? 'Update option' : 'Add option' ?></button>
      </form>
    </div>

    <div class="panel modern-card table-wrap">
      <h3>Available insurance options</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Description</th>
            <th>Price</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($options as $option): ?>
            <tr>
              <td><?= htmlspecialchars($option['name']) ?></td>
              <td><?= htmlspecialchars($option['description']) ?></td>
              <td>$<?= number_format((float)$option['price'], 0) ?></td>
              <td class="action-stack">
                <a class="btn-small" href="manage-insurance.php?edit=<?= (int)$option['id'] ?>">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this insurance option?');">
                  <input type="hidden" name="action" value="delete_insurance">
                  <input type="hidden" name="insurance_id" value="<?= (int)$option['id'] ?>">
                  <button type="submit" class="btn-small btn-danger-outline">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($options)): ?>
            <tr>
              <td colspan="4">No insurance options found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
