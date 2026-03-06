<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="page">

  <div class="page-header">
    <h2>My Bookings</h2>
    <p class="muted">View your flight booking history.</p>
  </div>

  <div class="panel table-wrap">

    <table class="table">

      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Route</th>
          <th>Date</th>
          <th>Seats</th>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>

      <tbody>

        <tr>
          <td>#1021</td>
          <td>BEY → CDG</td>
          <td>2026-03-12</td>
          <td>1</td>
          <td><span class="badge ok">Confirmed</span></td>
          <td>$420</td>
        </tr>

        <tr>
          <td>#1016</td>
          <td>DXB → HND</td>
          <td>2026-03-02</td>
          <td>2</td>
          <td><span class="badge wait">Pending</span></td>
          <td>$980</td>
        </tr>

        <tr>
          <td>#1008</td>
          <td>LHR → FCO</td>
          <td>2026-02-20</td>
          <td>1</td>
          <td><span class="badge ok">Confirmed</span></td>
          <td>$420</td>
        </tr>

      </tbody>

    </table>

  </div>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>