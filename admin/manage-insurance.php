<?php
include __DIR__ . '/../includes/header.php';
require_admin();

$message = '';
$options = get_all_insurance_options();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_insurance') {
        $option = [
            'id' => (int)($_POST['insurance_id'] ?? 0) ?: next_insurance_id(),
            'name' => trim($_POST['name'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
        ];

        $updated = false;
        foreach ($options as $index => $existing) {
            if ((int)$existing['id'] === (int)$option['id']) {
                $options[$index] = $option;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $options[] = $option;
        }
        save_all_insurance_options($options);
        $options = get_all_insurance_options();
        $message = $updated ? 'Insurance option updated successfully.' : 'Insurance option added successfully.';
    }

    if ($action === 'delete_insurance') {
        $deleteId = (int)($_POST['insurance_id'] ?? 0);
        $options = array_values(array_filter($options, fn($option) => (int)$option['id'] !== $deleteId));
        save_all_insurance_options($options);
        $options = get_all_insurance_options();
        $message = 'Insurance option removed successfully.';
    }
}

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

  <div class="booking-grid admin-grid">
    <div class="panel modern-card">
      <h3><?= $editingOption ? 'Edit insurance option' : 'Add insurance option' ?></h3>
      <form method="POST" class="form">
        <input type="hidden" name="action" value="save_insurance">
        <input type="hidden" name="insurance_id" value="<?= (int)($editingOption['id'] ?? 0) ?>">

        <div class="form-group">
          <label>Insurance type</label>
          <input type="text" name="name" value="<?= htmlspecialchars($editingOption['name'] ?? '') ?>" placeholder="Premium" required>
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
                <form method="POST">
                  <input type="hidden" name="action" value="delete_insurance">
                  <input type="hidden" name="insurance_id" value="<?= (int)$option['id'] ?>">
                  <button type="submit" class="btn-small btn-danger-outline">Delete</button>
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
