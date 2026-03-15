<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: /travel-booking-system/auth/login.php");
    exit();
}

$from = $_GET['from'] ?? 'Beirut';
$to = $_GET['to'] ?? 'Paris';
$basePrice = isset($_GET['price']) ? (float)$_GET['price'] : 420;

include __DIR__ . '/../includes/header.php';
?>


<section class="page">

<div class="page-header">
<h2>Book a Flight</h2>
<p class="muted">Choose destination, insurance, and payment method</p>
</div>

<div class="booking-grid">

<div class="panel">

<h3>Flight Details</h3>

<form class="form" action="../user/dashboard.php" method="GET">

<input type="hidden" id="basePriceValue" value="<?= $basePrice ?>">


<div class="form-row">

<div class="form-group">
<label>From</label>
<select id="fromSelect" name="from">
<option value="Beirut" <?= $from==='Beirut'?'selected':'' ?>>Beirut</option>
<option value="Dubai" <?= $from==='Dubai'?'selected':'' ?>>Dubai</option>
<option value="London" <?= $from==='London'?'selected':'' ?>>London</option>
</select>
</div>

<div class="form-group">
<label>To</label>
<select id="toSelect" name="to">
<option value="Paris" <?= $to==='Paris'?'selected':'' ?>>Paris</option>
<option value="Tokyo" <?= $to==='Tokyo'?'selected':'' ?>>Tokyo</option>
<option value="Rome" <?= $to==='Rome'?'selected':'' ?>>Rome</option>
<option value="London" <?= $to==='London'?'selected':'' ?>>London</option>
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
<div class="panel mt">
    <h3>Select Your Seat</h3>

    <div id="seatMap" class="seat-map"></div>

    <input type="hidden" name="seatNumber" id="seatNumber" required>
</div>


<!-- CLASS -->
<div class="form-group">
<label>Class</label>
<select id="classType" name="class">
<option value="economy">Economy</option>
<option value="business">Business (+$250)</option>
<option value="first">First Class (+$500)</option>
</select>
</div>


<div class="form-row">

<div class="form-group">
<label>Ticket Type</label>
<select id="ticketType" name="ticketType">
<option value="oneway">One Way</option>
<option value="round">Round Trip</option>
<option value="multi">Multi-City</option>
</select>
</div>

<div class="form-group" id="returnDateGroup" style="display:none;">
<label>Return Date</label>
<input type="date" name="returnDate">
</div>

</div>


<div id="multiCityContainer" style="display:none;">
<div class="panel mt">

<h3>Multi-City Flights</h3>

<div id="multiCitySegments">

<div class="form-row multi-segment">

<div class="form-group">
<label>From</label>
<select name="multiFrom[]">
<option>Beirut</option>
<option>Dubai</option>
<option>London</option>
<option>Doha</option>
<option>Istanbul</option>
<option>Paris</option>
</select>
</div>

<div class="form-group">
<label>To</label>
<select name="multiTo[]">
<option>Paris</option>
<option>Tokyo</option>
<option>Rome</option>
<option>London</option>
<option>Doha</option>
<option>Istanbul</option>
</select>
</div>

<div class="form-group">
<label>Date</label>
<input type="date" name="multiDate[]">
</div>

</div>

</div>

<button type="button" class="btn-dark" id="addSegmentBtn">+ Add Flight</button>

</div>
</div>


<div class="form-row">

<div class="form-group">
<label>Departure</label>
<input type="time" id="departureTime" name="departureTime" readonly>
</div>

<div class="form-group">
<label>Arrival</label>
<input type="time" id="arrivalTime" name="arrivalTime" readonly>
</div>

<div class="form-group">
<label>Duration</label>
<input type="text" id="flightDuration" readonly>
</div>

</div>


<div class="form-row">

<div class="form-group">
<label>Flight Type</label>
<select id="flightType" name="flightType">
<option value="direct">Direct</option>
<option value="escale">Layover (-$50)</option>
</select>
</div>

<div class="form-group" id="escaleCityGroup" style="display:none;">
<label>Layover City</label>
<select name="escaleCity">
<option>Doha</option>
<option>Istanbul</option>
<option>Dubai</option>
<option>Frankfurt</option>
</select>
</div>

</div>


<div class="form-row">

<div class="form-group">
<label>Insurance</label>
<select id="insuranceSelect" name="insurance">
<option value="0">None</option>
<option value="15">Basic +15</option>
<option value="30">Premium +30</option>
</select>
</div>

<div class="form-group">
<label>Payment</label>
<select name="payment">
<option>Credit Card</option>
<option>Debit Card</option>
<option>PayPal</option>
<option>Cash</option>
</select>
</div>

</div>


<div class="panel mt">
<h3>Select Your Seat</h3>

<div id="seatMap" class="seat-map"></div>

<input type="hidden" name="seatNumber" id="seatNumber">
</div>

<button class="btn-primary" type="submit">Confirm Booking</button>

</form>

</div>


<div>

<div class="panel">

<h3>Summary</h3>

<div class="summary">

<div class="sum-row">
<span>Flight</span>
<strong id="sumFlight">$<?= $basePrice ?></strong>
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
<strong id="sumTotal">$<?= $basePrice ?></strong>
</div>

</div>

</div>

</div>

</div>

</section>

<script src="../assets/js/script.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
