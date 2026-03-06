<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="page">
  <div class="page-header">
    <h2>Manage Bookings (UI only)</h2>
  </div>

  <div class="panel">
    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>User</th>
          <th>Route</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>#1021</td>
          <td>charbel@gmail.com</td>
          <td>BEY → CDG</td>
          <td>2026-03-12</td>
          <td><span class="badge ok">Confirmed</span></td>
          <td><button class="btn-small">Cancel</button></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>