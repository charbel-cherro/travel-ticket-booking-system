document.addEventListener('DOMContentLoaded', function () {
  const bookingForm = document.getElementById('bookingForm');
  if (!bookingForm) return;

  const ticketTypeInputs = document.querySelectorAll('input[name="ticketType"]');
  const tripCards = document.querySelectorAll('.choice-card');
  const flightOptionCards = Array.from(document.querySelectorAll('[data-flight-option]'));
  const flightSelectionList = document.getElementById('flightSelectionList');
  const searchFrom = document.getElementById('searchFrom');
  const searchTo = document.getElementById('searchTo');
  const searchDate = document.getElementById('searchDate');
  const sortTrips = document.getElementById('sortTrips');
  const flightResultsMessage = document.getElementById('flightResultsMessage');
  const classType = document.getElementById('classType');
  const seatsSelect = document.getElementById('passengers');
  const insuranceSelect = document.getElementById('insuranceSelect');
  const handBags = document.getElementById('handBags');
  const checkedBags = document.getElementById('checkedBags');
  const passengerNamesWrap = document.getElementById('passengerNamesWrap');
  const addSegmentBtn = document.getElementById('addSegmentBtn');
  const multiCityContainer = document.getElementById('multiCityContainer');
  const multiCitySegments = document.getElementById('multiCitySegments');
  const returnSeatContainer = document.getElementById('returnSeatContainer');
  const summaryReturnSeatRow = document.getElementById('summaryReturnSeatRow');
  const seatZoneNote = document.getElementById('seatZoneNote');
  const seatNumberInput = document.getElementById('seatNumber');
  const returnSeatNumberInput = document.getElementById('returnSeatNumber');
  const seatMapOutbound = document.getElementById('seatMapOutbound');
  const seatMapReturn = document.getElementById('seatMapReturn');
  const classSeatSection = document.getElementById('classSeatSection');
  const classSeatLock = document.getElementById('classSeatLock');

  const summary = {
    trip: document.getElementById('sumTripType'),
    name: document.getElementById('sumFlightName'),
    code: document.getElementById('sumFlightCode'),
    date: document.getElementById('sumFlightDate'),
    time: document.getElementById('sumFlightTime'),
    stops: document.getElementById('sumFlightStops'),
    class: document.getElementById('sumClass'),
    seats: document.getElementById('sumSeats'),
    outboundSeat: document.getElementById('sumOutboundSeat'),
    returnSeat: document.getElementById('sumReturnSeat'),
    fare: document.getElementById('sumFlight'),
    insurance: document.getElementById('sumInsurance'),
    bags: document.getElementById('sumBags'),
    total: document.getElementById('sumTotal')
  };

  const seatLayout = {
    first: ['1A', '1B', '2A', '2B'],
    business: ['3A', '3B', '3C', '3D', '4A', '4B', '4C', '4D'],
    economy: [
      '5A', '5B', '5C', '5D', '6A', '6B', '6C', '6D',
      '7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D'
    ]
  };

  const takenSeats = {
    first: ['1B'],
    business: ['3C', '4A'],
    economy: ['5D', '6B', '7A', '8C']
  };

  function getTripType() {
    const selected = document.querySelector('input[name="ticketType"]:checked');
    return selected ? selected.value : 'oneway';
  }

  function getSelectedFlightInput() {
    return document.querySelector('input[name="flight_id"]:checked');
  }

  function classLabel(value) {
    if (value === 'business') return 'Business';
    if (value === 'first') return 'First Class';
    return 'Economy';
  }

  function tripLabel(value) {
    if (value === 'round') return 'Round-trip';
    if (value === 'multi') return 'Multi-city';
    return 'One-way';
  }

  function selectedFlightPrice() {
    const selected = getSelectedFlightInput();
    if (!selected) return 0;
    if (classType.value === 'business') return parseFloat(selected.dataset.businessPrice || '0');
    if (classType.value === 'first') return parseFloat(selected.dataset.firstPrice || '0');
    return parseFloat(selected.dataset.economyPrice || '0');
  }

  function renderPassengerFields() {
    const count = parseInt(seatsSelect.value || '1', 10);
    passengerNamesWrap.innerHTML = '';
    for (let i = 1; i <= count; i += 1) {
      const block = document.createElement('div');
      block.className = 'form-group';
      block.innerHTML = `
        <label>Passenger ${i} full name</label>
        <input type="text" name="passenger_names[]" placeholder="Passenger ${i} full name" required>
      `;
      passengerNamesWrap.appendChild(block);
    }
  }

  function buildSeatMap(container, hiddenInput, prefix) {
  if (!container || !hiddenInput) return;

  container.innerHTML = '';
  const selectedClass = classType.value;
  const seats = seatLayout[selectedClass];
  const unavailable = takenSeats[selectedClass] || [];

  let selectedSeats = [];

  seats.forEach(code => {
    const seat = document.createElement('button');
    seat.type = 'button';
    seat.className = 'seat';
    seat.textContent = code;

    if (unavailable.includes(code)) {
      seat.classList.add('taken');
      seat.disabled = true;
    }

    seat.addEventListener('click', function () {
      if (seat.classList.contains('taken')) return;

      const maxSeats = parseInt(seatsSelect.value || '1');

      if (seat.classList.contains('selected')) {
        seat.classList.remove('selected');
        selectedSeats = selectedSeats.filter(s => s !== code);
      } else {
        if (selectedSeats.length >= maxSeats) {
          alert(`You can only select ${maxSeats} seat(s).`);
          return;
        }

        seat.classList.add('selected');
        selectedSeats.push(code);
      }

      hiddenInput.value = selectedSeats.join(',');

      if (prefix === 'return') {
        summary.returnSeat.textContent = hiddenInput.value || 'Not selected';
      } else {
        summary.outboundSeat.textContent = hiddenInput.value || 'Not selected';
      }
    });

    container.appendChild(seat);
  });

  hiddenInput.value = '';
}

  function renderSeatMaps() {
    buildSeatMap(seatMapOutbound, seatNumberInput, 'outbound');
    if (getTripType() === 'round') {
      buildSeatMap(seatMapReturn, returnSeatNumberInput, 'return');
    }
  }

  function updateTripCards() {
    const value = getTripType();
    tripCards.forEach(card => {
      const radio = card.querySelector('input');
      card.classList.toggle('active-choice', radio.checked);
    });

    summary.trip.textContent = tripLabel(value);

    if (value === 'round') {
      returnSeatContainer.classList.remove('hidden');
      summaryReturnSeatRow.classList.remove('hidden');
    } else {
      returnSeatContainer.classList.add('hidden');
      summaryReturnSeatRow.classList.add('hidden');
      returnSeatNumberInput.value = '';
      summary.returnSeat.textContent = 'Not selected';
    }

    if (value === 'multi') {
      multiCityContainer.style.display = 'block';
      flightSelectionList.parentElement.querySelector('.section-title-row p').textContent = 'Multi-city uses custom segments instead of one prebuilt flight list.';
    } else {
      multiCityContainer.style.display = 'none';
      flightSelectionList.parentElement.querySelector('.section-title-row p').textContent = 'Flights are hidden until the search details are selected.';
    }

    applyFlightFilters();
    renderSeatMaps();
    updateSummary();
  }

  function updateFlightCards() {
    flightOptionCards.forEach(card => {
      const radio = card.querySelector('input[name="flight_id"]');
      card.classList.toggle('selected-flight', !!radio.checked);
    });
  }

  function updateSelectedFlightSummary() {
    const selected = getSelectedFlightInput();
    if (!selected) {
      summary.name.textContent = 'Choose a trip';
      summary.code.textContent = '-';
      summary.date.textContent = '-';
      summary.time.textContent = '-';
      summary.stops.textContent = '-';
      updateFlightCards();
      return;
    }

    summary.name.textContent = selected.dataset.name || '-';
    summary.code.textContent = selected.dataset.code || '-';
    summary.date.textContent = selected.dataset.date || '-';
    summary.time.textContent = `${selected.dataset.departure || '-'} - ${selected.dataset.arrival || '-'}`;
    summary.stops.textContent = parseInt(selected.dataset.stops || '0', 10) === 0 ? 'Direct' : `${selected.dataset.stops} stop(s)`;
    updateFlightCards();
  }

  function updateSummary() {
    const selected = getSelectedFlightInput();
    const passengers = parseInt(seatsSelect.value || '1', 10);
    const insurance = parseFloat((insuranceSelect.selectedOptions[0] || {}).dataset.price || '0');
    const tripType = getTripType();
    const bagTotal = (parseInt(handBags.value || '0', 10) * 20) + (parseInt(checkedBags.value || '0', 10) * 45);
    let flightCost = selectedFlightPrice() * passengers;

    if (tripType === 'round') flightCost *= 2;
    if (tripType === 'multi') {
      const segments = document.querySelectorAll('.multi-segment').length;
      flightCost *= Math.max(segments, 1);
    }

    const total = flightCost + insurance + bagTotal;

    summary.class.textContent = classLabel(classType.value);
    summary.seats.textContent = passengers;
    summary.fare.textContent = `$${flightCost.toFixed(0)}`;
    summary.insurance.textContent = `$${insurance.toFixed(0)}`;
    summary.bags.textContent = `$${bagTotal.toFixed(0)}`;
    summary.total.textContent = `$${total.toFixed(0)}`;
    seatZoneNote.textContent = `${classLabel(classType.value)} seats are currently shown.`;
  }

  function sortVisibleCards(cards) {
    const selectedSort = sortTrips.value;
    cards.sort((a, b) => {
      if (selectedSort === 'price') {
        return selectedPrice(a) - selectedPrice(b);
      }
      if (selectedSort === 'stops') {
        return parseInt(a.dataset.stops || '0', 10) - parseInt(b.dataset.stops || '0', 10);
      }
      if (selectedSort === 'duration') {
        return parseInt(a.dataset.durationMinutes || '0', 10) - parseInt(b.dataset.durationMinutes || '0', 10);
      }
      return parseInt(a.dataset.flightId || '0', 10) - parseInt(b.dataset.flightId || '0', 10);
    });
    cards.forEach(card => flightSelectionList.appendChild(card));
  }

  function selectedPrice(card) {
    if (classType.value === 'business') return parseFloat(card.dataset.businessPrice || '0');
    if (classType.value === 'first') return parseFloat(card.dataset.firstPrice || '0');
    return parseFloat(card.dataset.economyPrice || '0');
  }

  function applyFlightFilters() {
    const from = searchFrom.value;
    const to = searchTo.value;
    const date = searchDate.value;
    const tripType = getTripType();

    let visibleCards = [];

    flightOptionCards.forEach(card => {
      const matches = from && to && date
        && card.dataset.from === from
        && card.dataset.to === to
        && card.dataset.date === date
        && tripType !== 'multi';

      card.classList.toggle('hidden', !matches);
      if (matches) visibleCards.push(card);
      if (!matches) {
        const radio = card.querySelector('input[name="flight_id"]');
        radio.checked = false;
      }
    });

    sortVisibleCards(visibleCards);

    if (tripType === 'multi') {
      flightResultsMessage.textContent = 'Multi-city booking uses the segment builder below.';
      classSeatSection.classList.remove('is-locked');
      classSeatLock.textContent = 'Add your custom segments, then choose class, passenger names, bags, and seats.';
    } else if (!from || !to || !date) {
      flightResultsMessage.textContent = 'Choose departure, destination, and date to view matching flights.';
      classSeatSection.classList.add('is-locked');
      classSeatLock.textContent = 'Choose a matching trip before completing this section.';
    } else if (!visibleCards.length) {
      flightResultsMessage.textContent = 'No trips match your search yet. Try another date or route.';
      classSeatSection.classList.add('is-locked');
      classSeatLock.textContent = 'Choose a matching trip before completing this section.';
    } else {
      flightResultsMessage.textContent = `${visibleCards.length} matching trip(s) found.`;
      classSeatSection.classList.remove('is-locked');
      classSeatLock.textContent = 'Select a trip, then choose class, passenger names, bags, and seats.';
      if (!getSelectedFlightInput()) {
        const firstRadio = visibleCards[0].querySelector('input[name="flight_id"]');
        firstRadio.checked = true;
      }
    }

    updateSelectedFlightSummary();
    updateSummary();
  }

  ticketTypeInputs.forEach(input => input.addEventListener('change', updateTripCards));
  flightOptionCards.forEach(card => {
    const input = card.querySelector('input[name="flight_id"]');
    input.addEventListener('change', function () {
      updateSelectedFlightSummary();
      updateSummary();
    });
  });
  [searchFrom, searchTo, searchDate, sortTrips].forEach(el => el.addEventListener('change', applyFlightFilters));
  classType.addEventListener('change', function () {
    renderSeatMaps();
    applyFlightFilters();
    updateSummary();
  });
  seatsSelect.addEventListener('change', function () {
  renderPassengerFields();
  renderSeatMaps(); 
  updateSummary();
});
  insuranceSelect.addEventListener('change', updateSummary);
  handBags.addEventListener('change', updateSummary);
  checkedBags.addEventListener('change', updateSummary);

  if (addSegmentBtn) {
    addSegmentBtn.addEventListener('click', function () {
      const row = document.createElement('div');
      row.className = 'form-row multi-segment';
      row.innerHTML = `
        <div class="form-group">
          <label>From</label>
          <input type="text" name="multiFrom[]" placeholder="City">
        </div>
        <div class="form-group">
          <label>To</label>
          <input type="text" name="multiTo[]" placeholder="City">
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" name="multiDate[]">
        </div>
      `;
      multiCitySegments.appendChild(row);
      updateSummary();
    });
  }

  bookingForm.addEventListener('submit', function (e) {
    const tripType = getTripType();
    if (tripType !== 'multi' && !getSelectedFlightInput()) {
      e.preventDefault();
      alert('Please select a matching flight before confirming your booking.');
      return;
    }
    if (!seatNumberInput.value) {
      e.preventDefault();
      alert('Please select an outbound seat before confirming your booking.');
      return;
    }
    if (tripType === 'round' && !returnSeatNumberInput.value) {
      e.preventDefault();
      alert('Please select a return seat for your round-trip booking.');
      return;
    }
  });

  renderPassengerFields();
  updateTripCards();
  renderSeatMaps();
  updateSelectedFlightSummary();
  applyFlightFilters();
  updateSummary();
});
