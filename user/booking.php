<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/flight-data.php';
include __DIR__ . '/../includes/mailer.php';
require_login();

$allFlights = get_all_flights(false);
$destinations = get_all_destinations();
$insuranceOptions = get_all_insurance_options();
$bookedSeatsMap = function_exists('get_booked_seats_map') ? get_booked_seats_map() : [];

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
    $tripType = trim($_POST['ticketType'] ?? 'oneway');
    if (!in_array($tripType, ['oneway', 'round', 'multi'], true)) {
        $tripType = 'oneway';
    }

    $classType = trim($_POST['class'] ?? 'economy');
    if (!in_array($classType, ['economy', 'business', 'first'], true)) {
        $classType = 'economy';
    }

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
        header('Location: ' . BASE_URL . '/user/booking.php');
        exit();
    }

    if ($outboundSeat === '') {
        $_SESSION['flash_error'] = 'Please choose an outbound seat.';
        header('Location: ' . BASE_URL . '/user/booking.php');
        exit();
    }

    if ($tripType === 'round' && $returnSeat === '') {
        $_SESSION['flash_error'] = 'Please choose a return seat for a round-trip booking.';
        header('Location: ' . BASE_URL . '/user/booking.php');
        exit();
    }

    $multiSegments = [];
    $segmentFlights = [];
    $flight = null;
    $flightId = 0;

    if ($tripType === 'multi') {
        $multiFlightIds = array_values(array_filter(array_map('intval', $_POST['multi_flight_ids'] ?? [])));

        if (count($multiFlightIds) < 2) {
            $_SESSION['flash_error'] = 'For a real multi-city booking, please choose at least two real flight segments.';
            header('Location: ' . BASE_URL . '/user/booking.php');
            exit();
        }

        foreach ($multiFlightIds as $segmentOrder => $segmentFlightId) {
            $segmentFlight = get_flight_by_id($segmentFlightId);

            if (!$segmentFlight || ($segmentFlight['status'] ?? 'active') === 'cancelled') {
                $_SESSION['flash_error'] = 'One of the selected multi-city flight segments is not available.';
                header('Location: ' . BASE_URL . '/user/booking.php');
                exit();
            }

            $segmentFlights[] = $segmentFlight;

            $multiSegments[] = [
                'flight_id' => (int)$segmentFlight['id'],
                'flight_code' => $segmentFlight['code'],
                'from' => $segmentFlight['from'],
                'to' => $segmentFlight['to'],
                'date' => $segmentFlight['date'],
                'arrival_date' => $segmentFlight['arrival_date'] ?? $segmentFlight['date'],
                'departure' => $segmentFlight['departure'],
                'arrival' => $segmentFlight['arrival'],
            ];
        }

        $flight = $segmentFlights[0];
        $flightId = (int)$flight['id'];

        // For multi-city, reserve the same selected seat(s) on every selected flight segment.
        foreach ($segmentFlights as $segmentFlight) {
            $takenSeats = find_taken_seats((int)$segmentFlight['id'], $classType, 'outbound', $outboundSeat);
            if (!empty($takenSeats)) {
                $_SESSION['flash_error'] = 'Seat(s) already taken on flight ' . $segmentFlight['code'] . ': ' . implode(', ', $takenSeats);
                header('Location: ' . BASE_URL . '/user/booking.php');
                exit();
            }
        }
    } else {
        $flightId = (int)($_POST['flight_id'] ?? 0);
        $flight = get_flight_by_id($flightId);

        if (!$flight) {
            $_SESSION['flash_error'] = 'Please choose a valid flight.';
            header('Location: ' . BASE_URL . '/user/booking.php');
            exit();
        }
    }

    $baseFare = 0.0;

    if ($tripType === 'multi') {
        foreach ($segmentFlights as $segmentFlight) {
            $baseFare += flight_price_for_class($segmentFlight, $classType) * $passengers;
        }
    } else {
        $baseFare = flight_price_for_class($flight, $classType) * $passengers;

        if ($tripType === 'round') {
            $baseFare *= 2;
        }
    }

    $total = $baseFare + $insuranceCost + $bagFee;

    if ($tripType === 'multi') {
        $routeCities = [];
        if (!empty($segmentFlights)) {
            $routeCities[] = $segmentFlights[0]['from'];
            foreach ($segmentFlights as $segmentFlight) {
                $routeCities[] = $segmentFlight['to'];
            }
        }

        $route = implode(' → ', $routeCities);
        $flightName = 'Multi-city: ' . $route;
        $flightCode = implode(' / ', array_map(fn($f) => $f['code'], $segmentFlights));
        $flightTime = implode(' | ', array_map(function ($f) {
            return $f['code'] . ' ' .
                $f['date'] . ' ' . $f['departure'] . ' - ' .
                ($f['arrival_date'] ?? $f['date']) . ' ' . $f['arrival'];
        }, $segmentFlights));
        $bookingDate = $segmentFlights[0]['date'];
    } else {
        $route = $flight['from'] . ' → ' . $flight['to'];
        $flightName = $flight['name'];
        $flightCode = $flight['code'];
        $flightTime = $flight['departure'] . ' - ' . $flight['arrival'];
        $bookingDate = $flight['date'];
    }

    try {
        $booking = add_booking([
            'user_id' => (int)$_SESSION['user_id'],
            'user_name' => $_SESSION['user_name'] ?? 'Traveler',
            'user_email' => $_SESSION['user_email'] ?? '',
            'flight_id' => $flightId,
            'insurance_id' => $insuranceId,
            'route' => $route,
            'flight_name' => $flightName,
            'flight_code' => $flightCode,
            'flight_time' => $flightTime,
            'date' => $bookingDate,
            'trip_type' => $tripType,
            'class' => $classType,
            'passengers' => $passengers,
            'passenger_names' => $passengerNames,
            'seat_number' => $outboundSeat,
            'return_seat_number' => $tripType === 'round' ? $returnSeat : '',
            'insurance' => $insuranceCost,
            'insurance_name' => $insuranceName,
            'hand_bags' => $handBags,
            'checked_bags' => $checkedBags,
            'bag_fee' => $bagFee,
            'payment' => $payment,
            'status' => 'Pending',
            'total' => $total,
            'multi_segments' => $multiSegments
        ]);

        // add_booking() reserves seats for the first selected flight.
        // For real multi-city bookings, also reserve the same seat(s) on the other selected flight segments.
        if ($tripType === 'multi' && count($segmentFlights) > 1 && table_exists('booking_seats')) {
            foreach (array_slice($segmentFlights, 1) as $segmentFlight) {
                foreach (split_seats($outboundSeat) as $seat) {
                    insert_dynamic('booking_seats', [
                        'booking_id' => (int)$booking['id'],
                        'flight_id' => (int)$segmentFlight['id'],
                        'class_type' => $classType,
                        'seat_number' => $seat,
                        'leg_type' => 'outbound',
                    ]);
                }
            }
        }

        send_booking_request_email(
            $_SESSION['user_email'] ?? '',
            $_SESSION['user_name'] ?? 'Traveler',
            $booking
        );
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        header('Location: ' . BASE_URL . '/user/booking.php');
        exit();
    }

    $_SESSION['flash_success'] = 'Booking #' . $booking['id'] . ' has been requested and is pending admin approval.';
    header('Location: ' . BASE_URL . '/user/my-bookings.php');
    exit();
}
?>
<script>
  window.bookedSeatsByFlight = <?= json_encode($bookedSeatsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<section class="page">
  <?php if ($error = flash_message('flash_error')): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="page-header split-header">
    <div>
      <span class="eyebrow">Booking flow</span>
      <h2>Book a flight</h2>
      <p class="muted">Choose a single flight, round trip, or build a real multi-city trip from available flights.</p>
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
            <span>Select two or more real flights</span>
          </label>
        </div>
      </div>

      <div class="panel modern-card section-block" id="singleFlightSearchSection">
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
            <?php
              $arrivalDate = $flight['arrival_date'] ?? $flight['date'];
              $durationText = flight_duration($flight['departure'], $flight['arrival'], $flight['date'], $arrivalDate);
            ?>
            <label
              class="flight-option <?= $selectedFlight && (int)$selectedFlight['id'] === (int)$flight['id'] ? 'selected-flight' : '' ?> hidden"
              data-flight-option
              data-flight-id="<?= (int)$flight['id'] ?>"
              data-from="<?= htmlspecialchars($flight['from']) ?>"
              data-to="<?= htmlspecialchars($flight['to']) ?>"
              data-date="<?= htmlspecialchars($flight['date']) ?>"
              data-arrival-date="<?= htmlspecialchars($flight['arrival_date'] ?? $flight['date']) ?>"
              data-stops="<?= (int)$flight['stops'] ?>"
              data-duration-minutes="<?= flight_duration_minutes($flight['departure'], $flight['arrival'], $flight['date'], $flight['arrival_date'] ?? $flight['date']) ?>"
              data-economy-price="<?= htmlspecialchars((string)$flight['economy_price']) ?>"
              data-business-price="<?= htmlspecialchars((string)$flight['business_price']) ?>"
              data-first-price="<?= htmlspecialchars((string)$flight['first_price']) ?>"
              data-seat-count="<?= (int)($flight['seat_count'] ?? 15) ?>"
              data-economy-seats="<?= (int)($flight['economy_seats'] ?? 10) ?>"
              data-business-seats="<?= (int)($flight['business_seats'] ?? 3) ?>"
              data-first-seats="<?= (int)($flight['first_seats'] ?? 2) ?>"
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
                data-arrival-date="<?= htmlspecialchars($flight['arrival_date'] ?? $flight['date']) ?>"
                data-departure="<?= htmlspecialchars($flight['departure']) ?>"
                data-arrival="<?= htmlspecialchars($flight['arrival']) ?>"
                data-stops="<?= (int)$flight['stops'] ?>"
                data-duration="<?= htmlspecialchars($durationText) ?>"
                data-economy-price="<?= htmlspecialchars((string)$flight['economy_price']) ?>"
                data-business-price="<?= htmlspecialchars((string)$flight['business_price']) ?>"
                data-first-price="<?= htmlspecialchars((string)$flight['first_price']) ?>"
                data-seat-count="<?= (int)($flight['seat_count'] ?? 15) ?>"
                data-economy-seats="<?= (int)($flight['economy_seats'] ?? 10) ?>"
                data-business-seats="<?= (int)($flight['business_seats'] ?? 3) ?>"
                data-first-seats="<?= (int)($flight['first_seats'] ?? 2) ?>"
              >
              <div class="flight-option-header">
                <div>
                  <strong><?= htmlspecialchars($flight['name']) ?></strong>
                  <div class="muted"><?= htmlspecialchars($flight['from']) ?> → <?= htmlspecialchars($flight['to']) ?></div>
                </div>
                <span class="pill"><?= htmlspecialchars($flight['code']) ?></span>
              </div>
              <div class="flight-option-grid wide-grid">
                <span><strong>Departure date:</strong> <?= htmlspecialchars($flight['date']) ?></span>
                <span><strong>Arrival date:</strong> <?= htmlspecialchars($flight['arrival_date'] ?? $flight['date']) ?></span>
                <span><strong>Time:</strong> <?= htmlspecialchars($flight['departure']) ?> - <?= htmlspecialchars($flight['arrival']) ?></span>
                <span><strong>Duration:</strong> <?= htmlspecialchars($durationText) ?></span>
                <span><strong>Stops:</strong> <?= (int)$flight['stops'] === 0 ? 'Direct' : (int)$flight['stops'] . ' stop(s)' ?></span>
                <span><strong>Economy:</strong> $<?= number_format((float)$flight['economy_price'], 0) ?></span>
                <span><strong>Business:</strong> $<?= number_format((float)$flight['business_price'], 0) ?></span>
                <span><strong>First Class:</strong> $<?= number_format((float)$flight['first_price'], 0) ?></span>
                <span><strong>Seats:</strong> <?= (int)($flight['seat_count'] ?? 15) ?> total</span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel modern-card section-block" id="multiCityContainer" style="display:none;">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 2</span>
            <h3>Build your multi-city trip</h3>
          </div>
          <p class="muted small-inline">Choose two or more real flights first. Each selected segment shows its departure date/time and arrival date/time.</p>
        </div>

        <div id="multiCitySegments" class="multi-city-real-list">
          <div class="form-row multi-segment">
            <div class="form-group">
              <label>Segment 1 flight</label>
              <select name="multi_flight_ids[]" class="multi-flight-select">
                <option value="">Choose first flight</option>
                <?php foreach ($allFlights as $segmentFlight): ?>
                  <?php
                    $segmentArrivalDate = $segmentFlight['arrival_date'] ?? $segmentFlight['date'];
                    $segmentDuration = flight_duration(
                        $segmentFlight['departure'],
                        $segmentFlight['arrival'],
                        $segmentFlight['date'],
                        $segmentArrivalDate
                    );
                  ?>
                  <option
                    value="<?= (int)$segmentFlight['id'] ?>"
                    data-economy-price="<?= htmlspecialchars((string)$segmentFlight['economy_price']) ?>"
                    data-business-price="<?= htmlspecialchars((string)$segmentFlight['business_price']) ?>"
                    data-first-price="<?= htmlspecialchars((string)$segmentFlight['first_price']) ?>"
                    data-code="<?= htmlspecialchars($segmentFlight['code']) ?>"
                    data-route="<?= htmlspecialchars($segmentFlight['from'] . ' → ' . $segmentFlight['to']) ?>"
                    data-departure-date="<?= htmlspecialchars($segmentFlight['date']) ?>"
                    data-arrival-date="<?= htmlspecialchars($segmentArrivalDate) ?>"
                    data-departure-time="<?= htmlspecialchars($segmentFlight['departure']) ?>"
                    data-arrival-time="<?= htmlspecialchars($segmentFlight['arrival']) ?>"
                    data-duration="<?= htmlspecialchars($segmentDuration) ?>"
                  >
                    <?= htmlspecialchars($segmentFlight['code']) ?> —
                    <?= htmlspecialchars($segmentFlight['from']) ?> → <?= htmlspecialchars($segmentFlight['to']) ?>
                    | <?= htmlspecialchars($segmentFlight['date']) ?> <?= htmlspecialchars($segmentFlight['departure']) ?>
                    → <?= htmlspecialchars($segmentArrivalDate) ?> <?= htmlspecialchars($segmentFlight['arrival']) ?>
                    | <?= htmlspecialchars($segmentDuration) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="segment-preview search-hint">Choose a flight to see departure and arrival details.</div>
            </div>
          </div>

          <div class="form-row multi-segment">
            <div class="form-group">
              <label>Segment 2 flight</label>
              <select name="multi_flight_ids[]" class="multi-flight-select">
                <option value="">Choose second flight</option>
                <?php foreach ($allFlights as $segmentFlight): ?>
                  <?php
                    $segmentArrivalDate = $segmentFlight['arrival_date'] ?? $segmentFlight['date'];
                    $segmentDuration = flight_duration(
                        $segmentFlight['departure'],
                        $segmentFlight['arrival'],
                        $segmentFlight['date'],
                        $segmentArrivalDate
                    );
                  ?>
                  <option
                    value="<?= (int)$segmentFlight['id'] ?>"
                    data-economy-price="<?= htmlspecialchars((string)$segmentFlight['economy_price']) ?>"
                    data-business-price="<?= htmlspecialchars((string)$segmentFlight['business_price']) ?>"
                    data-first-price="<?= htmlspecialchars((string)$segmentFlight['first_price']) ?>"
                    data-code="<?= htmlspecialchars($segmentFlight['code']) ?>"
                    data-route="<?= htmlspecialchars($segmentFlight['from'] . ' → ' . $segmentFlight['to']) ?>"
                    data-departure-date="<?= htmlspecialchars($segmentFlight['date']) ?>"
                    data-arrival-date="<?= htmlspecialchars($segmentArrivalDate) ?>"
                    data-departure-time="<?= htmlspecialchars($segmentFlight['departure']) ?>"
                    data-arrival-time="<?= htmlspecialchars($segmentFlight['arrival']) ?>"
                    data-duration="<?= htmlspecialchars($segmentDuration) ?>"
                  >
                    <?= htmlspecialchars($segmentFlight['code']) ?> —
                    <?= htmlspecialchars($segmentFlight['from']) ?> → <?= htmlspecialchars($segmentFlight['to']) ?>
                    | <?= htmlspecialchars($segmentFlight['date']) ?> <?= htmlspecialchars($segmentFlight['departure']) ?>
                    → <?= htmlspecialchars($segmentArrivalDate) ?> <?= htmlspecialchars($segmentFlight['arrival']) ?>
                    | <?= htmlspecialchars($segmentDuration) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="segment-preview search-hint">Choose a flight to see departure and arrival details.</div>
            </div>
          </div>
        </div>

        <button type="button" class="btn-secondary" id="addSegmentBtn">+ Add another real flight segment</button>

        <template id="multiFlightSegmentTemplate">
          <div class="form-row multi-segment">
            <div class="form-group">
              <label>Another segment flight</label>
              <select name="multi_flight_ids[]" class="multi-flight-select">
                <option value="">Choose flight</option>
                <?php foreach ($allFlights as $segmentFlight): ?>
                  <?php
                    $segmentArrivalDate = $segmentFlight['arrival_date'] ?? $segmentFlight['date'];
                    $segmentDuration = flight_duration(
                        $segmentFlight['departure'],
                        $segmentFlight['arrival'],
                        $segmentFlight['date'],
                        $segmentArrivalDate
                    );
                  ?>
                  <option
                    value="<?= (int)$segmentFlight['id'] ?>"
                    data-economy-price="<?= htmlspecialchars((string)$segmentFlight['economy_price']) ?>"
                    data-business-price="<?= htmlspecialchars((string)$segmentFlight['business_price']) ?>"
                    data-first-price="<?= htmlspecialchars((string)$segmentFlight['first_price']) ?>"
                    data-code="<?= htmlspecialchars($segmentFlight['code']) ?>"
                    data-route="<?= htmlspecialchars($segmentFlight['from'] . ' → ' . $segmentFlight['to']) ?>"
                    data-departure-date="<?= htmlspecialchars($segmentFlight['date']) ?>"
                    data-arrival-date="<?= htmlspecialchars($segmentArrivalDate) ?>"
                    data-departure-time="<?= htmlspecialchars($segmentFlight['departure']) ?>"
                    data-arrival-time="<?= htmlspecialchars($segmentFlight['arrival']) ?>"
                    data-duration="<?= htmlspecialchars($segmentDuration) ?>"
                  >
                    <?= htmlspecialchars($segmentFlight['code']) ?> —
                    <?= htmlspecialchars($segmentFlight['from']) ?> → <?= htmlspecialchars($segmentFlight['to']) ?>
                    | <?= htmlspecialchars($segmentFlight['date']) ?> <?= htmlspecialchars($segmentFlight['departure']) ?>
                    → <?= htmlspecialchars($segmentArrivalDate) ?> <?= htmlspecialchars($segmentFlight['arrival']) ?>
                    | <?= htmlspecialchars($segmentDuration) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="segment-preview search-hint">Choose a flight to see departure and arrival details.</div>
            </div>
          </div>
        </template>
      </div>

      <div class="panel modern-card section-block gated-section" id="classSeatSection">
        <div class="section-title-row">
          <div>
            <span class="step-pill">Step 3</span>
            <h3>Passenger details, class, and seats</h3>
          </div>
          <span class="lock-note" id="classSeatLock">Complete Step 2 first, then choose passenger details and seats.</span>
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


    </div>

    <aside class="booking-sidebar">
      <div class="panel modern-card sticky-card summary-card">
        <h3>Booking summary</h3>
        <div class="summary-list">
          <div><span>Trip type</span><strong id="sumTripType">One-way</strong></div>
          <div><span>Flight</span><strong id="sumFlightName">Choose a trip</strong></div>
          <div><span>Flight code</span><strong id="sumFlightCode">-</strong></div>
          <div><span>Departure date</span><strong id="sumFlightDate">-</strong></div>
          <div><span>Arrival date</span><strong id="sumArrivalDate">-</strong></div>
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

        <button class="btn-primary full-width" type="submit">Request booking</button>
      </div>
    </aside>
  </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const multiCityContainer = document.getElementById('multiCityContainer');
  const singleFlightSearchSection = document.getElementById('singleFlightSearchSection');
  const addSegmentBtn = document.getElementById('addSegmentBtn');
  const template = document.getElementById('multiFlightSegmentTemplate');
  const tripTypeInputs = document.querySelectorAll('input[name="ticketType"]');
  const summaryTripType = document.getElementById('sumTripType');
  const summaryFlightName = document.getElementById('sumFlightName');
  const summaryFlightCode = document.getElementById('sumFlightCode');
  const summaryFlightDate = document.getElementById('sumFlightDate');
  const summaryArrivalDate = document.getElementById('sumArrivalDate');
  const summaryFlightTime = document.getElementById('sumFlightTime');
  const summaryFlightStops = document.getElementById('sumFlightStops');
  const summaryFare = document.getElementById('sumFlight');
  const summaryTotal = document.getElementById('sumTotal');
  const classType = document.getElementById('classType');
  const passengers = document.getElementById('passengers');

  function currentTripType() {
    const selected = document.querySelector('input[name="ticketType"]:checked');
    return selected ? selected.value : 'oneway';
  }

  function money(value) {
    return '$' + Number(value || 0).toLocaleString();
  }

  function updateSegmentPreviews() {
    const selects = Array.from(document.querySelectorAll('.multi-flight-select'));

    selects.forEach((select, index) => {
      const option = select.options[select.selectedIndex];
      const preview = select.closest('.form-group')?.querySelector('.segment-preview');

      if (!preview) {
        return;
      }

      if (!option || !option.value) {
        preview.textContent = 'Choose a flight to see departure and arrival details.';
        return;
      }

      preview.innerHTML =
        '<strong>Segment ' + (index + 1) + ':</strong> ' +
        option.dataset.code + ' — ' + option.dataset.route + '<br>' +
        '<strong>Departure:</strong> ' + option.dataset.departureDate + ' at ' + option.dataset.departureTime + '<br>' +
        '<strong>Arrival:</strong> ' + option.dataset.arrivalDate + ' at ' + option.dataset.arrivalTime + '<br>' +
        '<strong>Duration:</strong> ' + option.dataset.duration;
    });
  }

  function updateMultiSummary() {
    if (currentTripType() !== 'multi') {
      return;
    }

    const selects = Array.from(document.querySelectorAll('.multi-flight-select'));
    const selectedOptions = selects
      .map(select => select.options[select.selectedIndex])
      .filter(option => option && option.value);

    updateSegmentPreviews();

    if (summaryTripType) summaryTripType.textContent = 'Multi-city';

    if (selectedOptions.length === 0) {
      if (summaryFlightName) summaryFlightName.textContent = 'Choose real flight segments';
      if (summaryFlightCode) summaryFlightCode.textContent = '-';
      if (summaryFlightDate) summaryFlightDate.textContent = '-';
      if (summaryArrivalDate) summaryArrivalDate.textContent = '-';
      if (summaryFlightTime) summaryFlightTime.textContent = '-';
      if (summaryFlightStops) summaryFlightStops.textContent = '-';
      if (summaryFare) summaryFare.textContent = '$0';
      if (summaryTotal) summaryTotal.textContent = '$0';
      return;
    }

    const selectedClass = classType ? classType.value : 'economy';
    const passengerCount = passengers ? parseInt(passengers.value || '1', 10) : 1;
    let fare = 0;

    selectedOptions.forEach(option => {
      const priceKey = selectedClass === 'first' ? 'firstPrice' : (selectedClass === 'business' ? 'businessPrice' : 'economyPrice');
      fare += parseFloat(option.dataset[priceKey] || '0') * passengerCount;
    });

    if (summaryFlightName) summaryFlightName.textContent = selectedOptions.length + ' flight segment(s)';
    if (summaryFlightCode) summaryFlightCode.textContent = selectedOptions.map(option => option.textContent.trim().split('—')[0].trim()).join(' / ');
    if (summaryFlightDate) summaryFlightDate.textContent = selectedOptions[0].dataset.departureDate;
    if (summaryArrivalDate) summaryArrivalDate.textContent = selectedOptions[selectedOptions.length - 1].dataset.arrivalDate;
    if (summaryFlightTime) summaryFlightTime.textContent = 'See each segment below';
    if (summaryFlightStops) summaryFlightStops.textContent = selectedOptions.length > 1 ? (selectedOptions.length - 1) + ' connection(s)' : 'Direct';
    if (summaryFare) summaryFare.textContent = money(fare);

    // Keep total simple here. The server still calculates the final total including bags and insurance.
    if (summaryTotal) summaryTotal.textContent = money(fare);
  }

  function toggleMultiCity() {
    const isMulti = currentTripType() === 'multi';

    if (multiCityContainer) {
      multiCityContainer.style.display = isMulti ? 'block' : 'none';
    }

    if (singleFlightSearchSection) {
      singleFlightSearchSection.style.display = isMulti ? 'none' : 'block';
    }

    if (isMulti) {
      updateMultiSummary();
    }
  }

  tripTypeInputs.forEach(input => {
    input.addEventListener('change', toggleMultiCity);
  });

  if (addSegmentBtn && template) {
    addSegmentBtn.addEventListener('click', function () {
      const clone = template.content.cloneNode(true);
      document.getElementById('multiCitySegments').appendChild(clone);
      updateSegmentPreviews();
      updateMultiSummary();
    });
  }

  document.addEventListener('change', function (event) {
    if (
      event.target.classList.contains('multi-flight-select') ||
      event.target.id === 'classType' ||
      event.target.id === 'passengers'
    ) {
      updateMultiSummary();
    }
  });

  toggleMultiCity();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
