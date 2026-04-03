<?php include __DIR__ . '/../includes/header.php';
require_admin(); ?>

<section class="page">
  <div class="page-header split-header">
    <div>
      <span class="eyebrow">Admin area</span>
      <h2>Admin Dashboard</h2>
    </div>
  </div>

  <div class="cards-grid cols-4">
    <div class="panel modern-card">
      <h3>Flights</h3>
      <p>Add flights, separate class prices, stops, dates, and schedule details.</p>
      <a class="btn-dark" href="manage-flights.php">Manage flights</a>
    </div>
    <div class="panel modern-card">
      <h3>Bookings</h3>
      <p>View passenger names, baggage details, and booking totals in one place.</p>
      <a class="btn-dark" href="manage-bookings.php">Manage bookings</a>
    </div>
    <div class="panel modern-card">
      <h3>Destinations</h3>
      <p>Maintain the airport list used for route setup. Seats and prices are handled per flight.</p>
      <a class="btn-dark" href="manage-destinations.php">Manage destinations</a>
    </div>
    <div class="panel modern-card">
      <h3>Insurance</h3>
      <p>Display current insurance plans and add new options for checkout.</p>
      <a class="btn-dark" href="manage-insurance.php">Manage insurance</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
