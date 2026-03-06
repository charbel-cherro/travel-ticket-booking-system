<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$basePrice = isset($_GET['price']) ? (float)$_GET['price'] : 420;
?>

<section class="page">
  <div class="page-header">
    <h2>Book a Flight</h2>
    <p class="muted">Choose destination, insurance, and payment method</p>
  </div>

  <div class="booking-grid">

    <!-- BOOKING FORM -->
    <div class="panel">
      <h3>Flight Details</h3>

      <form class="form" action="../user/dashboard.php" method="GET">

        <!-- send base price -->
        <input type="hidden" name="price" value="<?= $basePrice ?>">

        <div class="form-row">

          <div class="form-group">
            <label>From</label>
            <select id="fromSelect" name="from">
              <option value="Beirut" <?= $from === 'Beirut' ? 'selected' : '' ?>>Beirut (BEY)</option>
              <option value="Dubai" <?= $from === 'Dubai' ? 'selected' : '' ?>>Dubai (DXB)</option>
              <option value="London" <?= $from === 'London' ? 'selected' : '' ?>>London (LHR)</option>
            </select>
          </div>

          <div class="form-group">
            <label>To</label>
            <select id="toSelect" name="to">
              <option value="Paris" <?= $to === 'Paris' ? 'selected' : '' ?>>Paris (CDG)</option>
              <option value="Tokyo" <?= $to === 'Tokyo' ? 'selected' : '' ?>>Tokyo (HND)</option>
              <option value="Rome" <?= $to === 'Rome' ? 'selected' : '' ?>>Rome (FCO)</option>
              <option value="London" <?= $to === 'London' ? 'selected' : '' ?>>London (LHR)</option>
            </select>
          </div>

        </div>

        <div class="form-row">

          <div class="form-group">
            <label>Travel Date</label>
            <input type="date" name="date" required>
          </div>

          <div class="form-group">
            <label>Seats</label>
            <select id="seatsSelect" name="seats">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
            </select>
          </div>

        </div>

        <div class="form-row">

          <div class="form-group">
            <label>Insurance</label>
            <select id="insuranceSelect" name="insurance">
              <option value="0">No Insurance</option>
              <option value="15">Basic Insurance (+$15)</option>
              <option value="30">Premium Insurance (+$30)</option>
            </select>
          </div>

          <div class="form-group">
            <label>Payment Method</label>
            <select name="payment">
              <option>Credit Card</option>
              <option>Debit Card</option>
              <option>PayPal</option>
              <option>Cash (On Arrival)</option>
            </select>
          </div>

        </div>

        <button class="btn-primary" type="submit">
          Confirm Booking
        </button>

      </form>
    </div>

    <!-- SUMMARY PANEL -->
    <div class="panel">

      <h3>Summary</h3>

      <div class="summary">

        <div class="sum-row">
          <span>Flight</span>
          <strong id="sumFlight">$<?= number_format($basePrice, 0) ?></strong>
        </div>

        <div class="sum-row">
          <span>Insurance</span>
          <strong id="sumInsurance">$0</strong>
        </div>

        <div class="sum-row">
          <span>Seats</span>
          <strong id="sumSeats">1</strong>
        </div>

        <div class="sum-row total">
          <span>Total</span>
          <strong id="sumTotal">$<?= number_format($basePrice, 0) ?></strong>
        </div>

      </div>

    </div>

  </div>
</section>

<script>

const basePrice = <?= json_encode($basePrice) ?>;

const seatsSelect = document.getElementById('seatsSelect');
const insuranceSelect = document.getElementById('insuranceSelect');

const sumFlight = document.getElementById('sumFlight');
const sumInsurance = document.getElementById('sumInsurance');
const sumSeats = document.getElementById('sumSeats');
const sumTotal = document.getElementById('sumTotal');

function updateSummary() {

  const seats = parseInt(seatsSelect.value) || 1;
  const insurance = parseFloat(insuranceSelect.value) || 0;

  const flightCost = basePrice * seats;
  const total = flightCost + insurance;

  sumFlight.textContent = '$' + flightCost;
  sumInsurance.textContent = '$' + insurance;
  sumSeats.textContent = seats;
  sumTotal.textContent = '$' + total;
}

seatsSelect.addEventListener('change', updateSummary);
insuranceSelect.addEventListener('change', updateSummary);

updateSummary();

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>