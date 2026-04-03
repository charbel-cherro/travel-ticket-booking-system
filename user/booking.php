<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/flight-data.php';
require_login();

$allFlights = get_all_flights(false);
$destinations = get_all_destinations();
$insuranceOptions = get_all_insurance_options();

$fromOptions = [];
$toOptions = [];
$dateOptions = [];
foreach ($allFlights as $flight) {
    $fromOptions[$flight['from']] = true;
    $toOptions[$flight['to']] = true;
    $dateOptions[$flight['date']] = true;
}
ksort($fromOptions);
ksort($toOptions);
ksort($dateOptions);

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
    $passengers = max(1, (int)($_POST['passengers'] ?? 1));
    $insuranceId = (int)($_POST['insurance_option_id'] ?? 1);
    $insuranceCost = 0.0;
    $insuranceName = 'None';
    foreach ($insuranceOptions as $option) {
        if ((int)$option['id'] === $insuranceId) {
            $insuranceCost = (float)($option['price'] ?? 0);
            $insuranceName = $option['name'] ?? 'Insurance';
            break;
        }
    }

    $outboundSeat = trim($_POST['seatNumber'] ?? '');
    $returnSeat = trim($_POST['returnSeatNumber'] ?? '');
    $payment = trim($_POST['payment'] ?? 'Credit Card');
    $handBags = max(0, (int)($_POST['hand_bags'] ?? 0));
    $checkedBags = max(0, (int)($_POST['checked_bags'] ?? 0));
    $bagFee = ($handBags * 20) + ($checkedBags * 45);

    $passengerNames = array_values(array_filter(array_map('trim', $_POST['passenger_names'] ?? []), fn($name) => $name !== ''));
    if (count($passengerNames) < $passengers) {
        $_SESSION['flash_error'] = 'Please add the full name for each passenger.';
        header('Location: ' . BASE_URL . '/user/booking.php?flight_id=' . $flightId);
        exit();
    }

    if ($outboundSeat === '') {
        $_SESSION['flash_error'] = 'Please choose an outbound seat.';
        header('Location: ' . BASE_URL . '/user/booking.php?flight_id=' . $flightId);
        exit();
    }

    if ($tripType === 'round' && $returnSeat === '') {
        $_SESSION['flash_error'] = 'Please choose a return seat for a round-trip booking.';
        header('Location: ' . BASE_URL . '/user/booking.php?flight_id=' . $flightId);
        exit();
    }

    $baseFare = flight_price_for_class($flight, $classType) * $passengers;
    if ($tripType === 'round') {
        $baseFare *= 2;
    }
    if ($tripType === 'multi') {
        $segments = max(1, count($_POST['multiFrom'] ?? []));
        $baseFare *= $segments;
    }

    $total = $baseFare + $insuranceCost + $bagFee;

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
        'passenger_names' => $passengerNames,
        'seat_number' => $outboundSeat,
        'return_seat_number' => $returnSeat,
        'insurance' => $insuranceCost,
        'insurance_name' => $insuranceName,
        'hand_bags' => $handBags,
        'checked_bags' => $checkedBags,
        'bag_fee' => $bagFee,
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
  <?php if ($error = flash_message('flash_error')): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="page-header split-header">
    <div>
      <span class="eyebrow">Booking flow</span>
      <h2>Book a flight</h2>
      <p class="muted">Start with departure, destination, and date. Then review only the matching trips.</p>
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
            <span>One outbound flight</span>
          </label>
          <label class="choice-card">
            <input type="radio" name="ticketType" value="round">
            <strong>Round-trip</strong>
            <span>Outbound flight with return seat</span>
          </label>
          <label class="choice-card">
            <input type="radio" name="ticketType" value="multi">
            <strong>Multi-city</strong>
            <span>Custom route segments</span>
          </label>
        </div>
      </div>

      <div class="panel modern-card section-block">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 2</span>
            <h3>Search for available trips</h3>
          </div>
          <p class="muted small-inline">Flights are hidden until the search details are selected.</p>
        </div>

        <div class="form-row search-row">
          <div class="form-group">
            <label>Departure</label>
            <select id="searchFrom" name="search_from">
              <option value="">Select departure</option>
              <?php foreach (array_keys($fromOptions) as $from): ?>
                <option value="<?= htmlspecialchars($from) ?>"><?= htmlspecialchars($from) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Destination</label>
            <select id="searchTo" name="search_to">
              <option value="">Select destination</option>
              <?php foreach (array_keys($toOptions) as $to): ?>
                <option value="<?= htmlspecialchars($to) ?>"><?= htmlspecialchars($to) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date</label>
            <select id="searchDate" name="search_date">
              <option value="">Select date</option>
              <?php foreach (array_keys($dateOptions) as $date): ?>
                <option value="<?= htmlspecialchars($date) ?>"><?= htmlspecialchars($date) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row search-row slim-row">
          <div class="form-group">
            <label>Sort trips by</label>
            <select id="sortTrips">
              <option value="default">Recommended</option>
              <option value="price">Price</option>
              <option value="stops">Number of stops</option>
              <option value="duration">Flight duration</option>
            </select>
          </div>
          <div class="form-group search-hint-box">
            <label>Search status</label>
            <div id="flightResultsMessage" class="search-hint">Choose departure, destination, and date to view matching flights.</div>
          </div>
        </div>

        <div class="flight-selection-list" id="flightSelectionList">
          <?php foreach ($allFlights as $flight): ?>
            <?php $durationText = flight_duration($flight['departure'], $flight['arrival']); ?>
            <label
              class="flight-option <?= $selectedFlight && (int)$selectedFlight['id'] === (int)$flight['id'] ? 'selected-flight' : '' ?> hidden"
              data-flight-option
              data-flight-id="<?= (int)$flight['id'] ?>"
              data-from="<?= htmlspecialchars($flight['from']) ?>"
              data-to="<?= htmlspecialchars($flight['to']) ?>"
              data-date="<?= htmlspecialchars($flight['date']) ?>"
              data-stops="<?= (int)$flight['stops'] ?>"
              data-duration-minutes="<?= flight_duration_minutes($flight['departure'], $flight['arrival']) ?>"
              data-economy-price="<?= htmlspecialchars((string)$flight['economy_price']) ?>"
              data-business-price="<?= htmlspecialchars((string)$flight['business_price']) ?>"
              data-first-price="<?= htmlspecialchars((string)$flight['first_price']) ?>"
            >
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
                data-stops="<?= (int)$flight['stops'] ?>"
                data-duration="<?= htmlspecialchars($durationText) ?>"
                data-economy-price="<?= htmlspecialchars((string)$flight['economy_price']) ?>"
                data-business-price="<?= htmlspecialchars((string)$flight['business_price']) ?>"
                data-first-price="<?= htmlspecialchars((string)$flight['first_price']) ?>"
              >
              <div class="flight-option-header">
                <div>
                  <strong><?= htmlspecialchars($flight['name']) ?></strong>
                  <div class="muted"><?= htmlspecialchars($flight['from']) ?> → <?= htmlspecialchars($flight['to']) ?></div>
                </div>
                <span class="pill"><?= htmlspecialchars($flight['code']) ?></span>
              </div>
              <div class="flight-option-grid wide-grid">
                <span><strong>Date:</strong> <?= htmlspecialchars($flight['date']) ?></span>
                <span><strong>Time:</strong> <?= htmlspecialchars($flight['departure']) ?> - <?= htmlspecialchars($flight['arrival']) ?></span>
                <span><strong>Duration:</strong> <?= htmlspecialchars($durationText) ?></span>
                <span><strong>Stops:</strong> <?= (int)$flight['stops'] === 0 ? 'Direct' : (int)$flight['stops'] . ' stop(s)' ?></span>
                <span><strong>Economy:</strong> $<?= number_format((float)$flight['economy_price'], 0) ?></span>
                <span><strong>Business:</strong> $<?= number_format((float)$flight['business_price'], 0) ?></span>
                <span><strong>First Class:</strong> $<?= number_format((float)$flight['first_price'], 0) ?></span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel modern-card section-block gated-section" id="classSeatSection">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 3</span>
            <h3>Passenger details, class, and seats</h3>
          </div>
          <span class="lock-note" id="classSeatLock">Choose a matching trip before completing this section.</span>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Class</label>
            <select id="classType" name="class">
              <option value="economy">Economy</option>
              <option value="business">Business</option>
              <option value="first">First Class</option>
            </select>
          </div>

          <div class="form-group">
            <label>Passengers</label>
            <select id="passengers" name="passengers" onchange="generateSeats(); updatePrice();">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
            </select>
          </div>

          <div class="form-group">
            <label>Insurance</label>
            <select id="insuranceSelect" name="insurance_option_id">
              <?php foreach ($insuranceOptions as $option): ?>
                <option value="<?= (int)$option['id'] ?>" data-price="<?= htmlspecialchars((string)($option['price'] ?? 0)) ?>" data-name="<?= htmlspecialchars($option['name'] ?? 'Insurance') ?>">
                  <?= htmlspecialchars($option['name']) ?><?= ((float)($option['price'] ?? 0) > 0) ? ' (+$' . number_format((float)$option['price'], 0) . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="panel section-subcard passenger-card-block">
          <h4>Passenger names</h4>
          <p class="muted small-inline">Add the full name for each traveler, including the second passenger when you book more than one seat.</p>
          <div id="passengerNamesWrap" class="passenger-name-grid"></div>
        </div>

        <div class="panel section-subcard bag-card-block">
          <h4>Bags</h4>
          <div class="form-row">
            <div class="form-group">
              <label>Hand luggage</label>
              <select id="handBags" name="hand_bags">
                <option value="0">0</option>
                <option value="1">1 (+$20)</option>
                <option value="2">2 (+$40)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Checked luggage</label>
              <select id="checkedBags" name="checked_bags">
                <option value="0">0</option>
                <option value="1">1 (+$45)</option>
                <option value="2">2 (+$90)</option>
              </select>
            </div>
            <div class="form-group bag-note-box">
              <label>Baggage notes</label>
              <div class="search-hint">Hand luggage is stored in the cabin. Checked luggage is added to the hold and priced separately.</div>
            </div>
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
            <p class="muted small-inline" id="seatZoneNote">Economy seats are currently shown.</p>
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
      <div class="panel modern-card sticky-card summary-card">
        <h3>Booking summary</h3>
        <div class="summary-list">
          <div><span>Trip type</span><strong id="sumTripType">One-way</strong></div>
          <div><span>Flight</span><strong id="sumFlightName">Choose a trip</strong></div>
          <div><span>Flight code</span><strong id="sumFlightCode">-</strong></div>
          <div><span>Date</span><strong id="sumFlightDate">-</strong></div>
          <div><span>Flight time</span><strong id="sumFlightTime">-</strong></div>
          <div><span>Stops</span><strong id="sumFlightStops">-</strong></div>
          <div><span>Class</span><strong id="sumClass">Economy</strong></div>
          <div><span>Passenger(s)</span><strong id="sumSeats">1</strong></div>
          <div><span>Outbound seat</span><strong id="sumOutboundSeat">Not selected</strong></div>
          <div id="summaryReturnSeatRow" class="hidden"><span>Return seat</span><strong id="sumReturnSeat">Not selected</strong></div>
          <div><span>Fare</span><strong id="sumFlight">$0</strong></div>
          <div><span>Insurance</span><strong id="sumInsurance">$0</strong></div>
          <div><span>Bags</span><strong id="sumBags">$0</strong></div>
          <div class="summary-total"><span>Total</span><strong id="sumTotal">$0</strong></div>
        </div>

        <div class="payment-card">
          <div class="payment-card-head">
            <h4>Payment method</h4>
            <p class="muted">Choose how the traveler will pay.</p>
          </div>
          <div class="form-group">
            <label>Method</label>
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
