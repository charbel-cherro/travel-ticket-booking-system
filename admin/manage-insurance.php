<?php include __DIR__ . '/../includes/header.php';
require_admin(); ?>

<section class="page">
  <div class="page-header">
    <h2>Manage Insurance (UI only)</h2>
  </div>

  <div class="panel">
    <div class="form-row">
      <div class="form-group">
        <label>Insurance Type</label>
        <input type="text" placeholder="e.g., Premium">
      </div>
      <div class="form-group">
        <label>Price</label>
        <input type="number" placeholder="e.g., 30">
      </div>
    </div>
    <button class="btn-primary" type="button">Add Insurance</button>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>