<?php include __DIR__ . '/../includes/header.php';
require_admin(); ?>

<section class="page">
  <div class="page-header split-header">
    <div>
      <span class="eyebrow">Admin area</span>
      <h2>Admin Dashboard</h2>
      <p class="muted">Manage flights, bookings, destinations, and insurance from a cleaner control panel.</p>
    </div>
  </div>

  <div class="cards-grid cols-4">
    <div class="panel modern-card">
      <h3>Flights</h3>
      <p>Add flights with both flight name and flight code, then edit date and time later.</p>
      <a class="btn-dark" href="manage-flights.php">Manage flights</a>
    </div>
    <div class="panel modern-card">
      <h3>Bookings</h3>
      <p>View flight time and booking details in a clearer table.</p>
      <a class="btn-dark" href="manage-bookings.php">Manage bookings</a>
    </div>
    <div class="panel modern-card">
      <h3>Destinations</h3>
      <p>Maintain available destinations and pricing.</p>
      <a class="btn-dark" href="manage-destinations.php">Manage destinations</a>
    </div>
    <div class="panel modern-card">
      <h3>Insurance</h3>
      <p>Update insurance options offered during checkout.</p>
      <a class="btn-dark" href="manage-insurance.php">Manage insurance</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
