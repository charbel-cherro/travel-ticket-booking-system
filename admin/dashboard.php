<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="page">
  <div class="page-header">
    <h2>Admin Dashboard</h2>
    <p class="muted">Manage destinations, bookings, insurance, and payments.</p>
  </div>

  <div class="grid-3">
    <div class="panel">
      <h3>Destinations</h3>
      <p>Add/update/delete destinations.</p>
      <a class="btn-dark" href="manage-destinations.php">Manage</a>
    </div>

    <div class="panel">
      <h3>Bookings</h3>
      <p>View and manage bookings.</p>
      <a class="btn-dark" href="manage-bookings.php">Manage</a>
    </div>

    <div class="panel">
      <h3>Insurance</h3>
      <p>Manage insurance options.</p>
      <a class="btn-dark" href="manage-insurance.php">Manage</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>