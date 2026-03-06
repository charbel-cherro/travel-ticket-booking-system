<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="page">
  <div class="page-header">
    <h2>Manage Destinations (UI only)</h2>
    <p class="muted">Add, update, delete destinations.</p>
  </div>

  <div class="panel">
    <div class="form">
      <div class="form-row">
        <div class="form-group">
          <label>City</label>
          <input type="text" placeholder="e.g., Paris">
        </div>
        <div class="form-group">
          <label>Country</label>
          <input type="text" placeholder="e.g., France">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Price</label>
          <input type="number" placeholder="e.g., 420">
        </div>
        <div class="form-group">
          <label>Available Seats</label>
          <input type="number" placeholder="e.g., 30">
        </div>
      </div>

      <button class="btn-primary" type="button">Add Destination</button>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>