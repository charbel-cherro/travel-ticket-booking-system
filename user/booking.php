<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/flight-data.php';
require_login();

$allFlights = get_all_flights(false);
$selectedFlightId = isset($_GET['flight_id']) ? (int)$_GET['flight_id'] : 0;
$selectedFlight = $selectedFlightId ? get_flight_by_id($selectedFlightId) : ($allFlights[0] ?? null);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flightId = (int)($_POST['flight_id'] ?? 0);
    $flight = get_flight_by_id($flightId);
    if (!$flight) {
        $_SESSION['flash_error'] = 'Please choose a valid flight.';
        header('Location: ' . BASE_URL . '/user/booking.php');
        exit();
    }

    $tripType = trim($_POST['ticketType'] ?? 'oneway');
    $classType = trim($_POST['class'] ?? 'economy');
    $passengers = max(1, (int)($_POST['seats'] ?? 1));
    $insurance = (float)($_POST['insurance'] ?? 0);
    $outboundSeat = trim($_POST['seatNumber'] ?? '');
    $returnSeat = trim($_POST['returnSeatNumber'] ?? '');
    $payment = trim($_POST['payment'] ?? 'Credit Card');

    $basePrice = (float)$flight['price'] * $passengers;
    if ($tripType === 'round') {
        $basePrice *= 2;
    }
    if ($tripType === 'multi') {
        $segments = max(1, count($_POST['multiFrom'] ?? []));
        $basePrice *= $segments;
    }
    if ($classType === 'business') {
        $basePrice += 250 * $passengers;
    }
    if ($classType === 'first') {
        $basePrice += 500 * $passengers;
    }
    $total = $basePrice + $insurance;

    $booking = add_booking([
        'user_id' => (int)$_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'Traveler',
        'user_email' => $_SESSION['user_email'] ?? '',
        'route' => $flight['from'] . ' → ' . $flight['to'],
        'flight_name' => $flight['name'],
        'flight_code' => $flight['code'],
        'flight_time' => $flight['departure'] . ' - ' . $flight['arrival'],
        'date' => $flight['date'],
        'trip_type' => $tripType,
        'class' => $classType,
        'passengers' => $passengers,
        'seat_number' => $outboundSeat,
        'return_seat_number' => $returnSeat,
        'insurance' => $insurance,
        'payment' => $payment,
        'status' => 'Confirmed',
        'total' => $total
    ]);

    $_SESSION['flash_success'] = 'Booking #' . $booking['id'] . ' has been confirmed.';
    header('Location: ' . BASE_URL . '/user/my-bookings.php');
    exit();
}
?>
<section class="page">
  <div class="page-header split-header">
    <div>
      <span class="eyebrow">Booking flow</span>
      <h2>Book a flight</h2>
      <p class="muted">Choose the trip type first, then select your flight, class, and seats in the right order.</p>
    </div>
  </div>

  <form class="booking-grid booking-shell" action="" method="POST" id="bookingForm">
    <div class="booking-main">
      <div class="panel modern-card section-block">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 1</span>
            <h3>Choose trip type</h3>
          </div>
        </div>

        <div class="trip-type-grid">
          <label class="choice-card active-choice">
            <input type="radio" name="ticketType" value="oneway" checked>
            <strong>One-way</strong>
            <span>Choose one outbound seat</span>
          </label>
          <label class="choice-card">
            <input type="radio" name="ticketType" value="round">
            <strong>Round-trip</strong>
            <span>Choose outbound and return seats</span>
          </label>
          <label class="choice-card">
            <input type="radio" name="ticketType" value="multi">
            <strong>Multi-city</strong>
            <span>Choose one seat per segment</span>
          </label>
        </div>
      </div>

      <div class="panel modern-card section-block">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 2</span>
            <h3>Select a flight</h3>
          </div>
          <p class="muted small-inline">Flight code is shown on every option.</p>
        </div>

        <div class="flight-selection-list">
          <?php foreach ($allFlights as $flight): ?>
            <label class="flight-option <?= $selectedFlight && (int)$selectedFlight['id'] === (int)$flight['id'] ? 'selected-flight' : '' ?>" data-flight-option>
              <input
                type="radio"
                name="flight_id"
                value="<?= (int)$flight['id'] ?>"
                <?= $selectedFlight && (int)$selectedFlight['id'] === (int)$flight['id'] ? 'checked' : '' ?>
                data-name="<?= htmlspecialchars($flight['name']) ?>"
                data-code="<?= htmlspecialchars($flight['code']) ?>"
                data-from="<?= htmlspecialchars($flight['from']) ?>"
                data-to="<?= htmlspecialchars($flight['to']) ?>"
                data-date="<?= htmlspecialchars($flight['date']) ?>"
                data-departure="<?= htmlspecialchars($flight['departure']) ?>"
                data-arrival="<?= htmlspecialchars($flight['arrival']) ?>"
                data-price="<?= htmlspecialchars((string)$flight['price']) ?>"
              >
              <div class="flight-option-header">
                <div>
                  <strong><?= htmlspecialchars($flight['name']) ?></strong>
                  <div class="muted"><?= htmlspecialchars($flight['from']) ?> → <?= htmlspecialchars($flight['to']) ?></div>
                </div>
                <span class="pill"><?= htmlspecialchars($flight['code']) ?></span>
              </div>
              <div class="flight-option-grid">
                <span><strong>Date:</strong> <?= htmlspecialchars($flight['date']) ?></span>
                <span><strong>Time:</strong> <?= htmlspecialchars($flight['departure']) ?> - <?= htmlspecialchars($flight['arrival']) ?></span>
                <span><strong>From:</strong> $<?= number_format($flight['price'], 0) ?></span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel modern-card section-block gated-section" id="classSeatSection">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 3</span>
            <h3>Choose class and seats</h3>
          </div>
          <span class="lock-note" id="classSeatLock">Class and seat selection depend on your trip type.</span>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Class</label>
            <select id="classType" name="class" data-requires-trip>
              <option value="economy">Economy</option>
              <option value="business">Business (+$250)</option>
              <option value="first">First Class (+$500)</option>
            </select>
          </div>

          <div class="form-group">
            <label>Passengers</label>
            <select id="seatsSelect" name="seats" data-requires-trip>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
            </select>
          </div>

          <div class="form-group">
            <label>Insurance</label>
            <select id="insuranceSelect" name="insurance">
              <option value="0">None</option>
              <option value="15">Basic +15</option>
              <option value="30">Premium +30</option>
            </select>
          </div>
        </div>

        <div class="seat-legend">
          <span><i class="seat-swatch available"></i> Available</span>
          <span><i class="seat-swatch taken"></i> Taken</span>
          <span><i class="seat-swatch selected"></i> Selected</span>
        </div>

        <div class="seat-layout-wrap">
          <div>
            <h4 id="outboundSeatTitle">Outbound seat</h4>
            <p class="muted small-inline" id="seatZoneNote">Economy seats are shown by default.</p>
            <div id="seatMapOutbound" class="seat-map"></div>
            <input type="hidden" name="seatNumber" id="seatNumber">
          </div>

          <div id="returnSeatContainer" class="return-seat-panel hidden">
            <h4>Return seat</h4>
            <p class="muted small-inline">Choose a second seat because you selected a round-trip.</p>
            <div id="seatMapReturn" class="seat-map"></div>
            <input type="hidden" name="returnSeatNumber" id="returnSeatNumber">
          </div>
        </div>
      </div>

      <div class="panel modern-card section-block" id="multiCityContainer" style="display:none;">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 4</span>
            <h3>Multi-city segments</h3>
          </div>
        </div>

        <div id="multiCitySegments">
          <div class="form-row multi-segment">
            <div class="form-group">
              <label>From</label>
              <input type="text" name="multiFrom[]" placeholder="Beirut">
            </div>
            <div class="form-group">
              <label>To</label>
              <input type="text" name="multiTo[]" placeholder="Paris">
            </div>
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="multiDate[]">
            </div>
          </div>
        </div>
        <button type="button" class="btn-secondary" id="addSegmentBtn">+ Add segment</button>
      </div>
    </div>

    <aside class="booking-sidebar">
      <div class="panel modern-card sticky-card">
        <h3>Booking summary</h3>
        <div class="summary-list">
          <div><span>Trip type</span><strong id="sumTripType">One-way</strong></div>
          <div><span>Flight</span><strong id="sumFlightName"><?= htmlspecialchars($selectedFlight['name'] ?? 'Select a flight') ?></strong></div>
          <div><span>Flight code</span><strong id="sumFlightCode"><?= htmlspecialchars($selectedFlight['code'] ?? '-') ?></strong></div>
          <div><span>Date</span><strong id="sumFlightDate"><?= htmlspecialchars($selectedFlight['date'] ?? '-') ?></strong></div>
          <div><span>Flight time</span><strong id="sumFlightTime"><?= isset($selectedFlight) ? htmlspecialchars($selectedFlight['departure'] . ' - ' . $selectedFlight['arrival']) : '-' ?></strong></div>
          <div><span>Class</span><strong id="sumClass">Economy</strong></div>
          <div><span>Passenger(s)</span><strong id="sumSeats">1</strong></div>
          <div><span>Outbound seat</span><strong id="sumOutboundSeat">Not selected</strong></div>
          <div id="summaryReturnSeatRow" class="hidden"><span>Return seat</span><strong id="sumReturnSeat">Not selected</strong></div>
          <div><span>Base fare</span><strong id="sumFlight">$<?= number_format((float)($selectedFlight['price'] ?? 0), 0) ?></strong></div>
          <div><span>Insurance</span><strong id="sumInsurance">$0</strong></div>
          <div class="summary-total"><span>Total</span><strong id="sumTotal">$<?= number_format((float)($selectedFlight['price'] ?? 0), 0) ?></strong></div>
        </div>

        <div class="form-row stacked-summary">
          <div class="form-group">
            <label>Payment method</label>
            <select name="payment">
              <option>Credit Card</option>
              <option>Debit Card</option>
              <option>PayPal</option>
              <option>Cash</option>
            </select>
          </div>
        </div>

        <button class="btn-primary full-width" type="submit">Confirm booking</button>
      </div>
    </aside>
  </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
