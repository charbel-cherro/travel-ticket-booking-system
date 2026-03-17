document.addEventListener('DOMContentLoaded', function(){
  const bookingForm = document.getElementById('bookingForm');
  if (!bookingForm) return;

  const ticketTypeInputs = document.querySelectorAll('input[name="ticketType"]');
  const tripCards = document.querySelectorAll('.choice-card');
  const flightInputs = document.querySelectorAll('input[name="flight_id"]');
  const flightOptionCards = document.querySelectorAll('[data-flight-option]');
  const classType = document.getElementById('classType');
  const seatsSelect = document.getElementById('seatsSelect');
  const insuranceSelect = document.getElementById('insuranceSelect');
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

  const summary = {
    trip: document.getElementById('sumTripType'),
    name: document.getElementById('sumFlightName'),
    code: document.getElementById('sumFlightCode'),
    date: document.getElementById('sumFlightDate'),
    time: document.getElementById('sumFlightTime'),
    class: document.getElementById('sumClass'),
    seats: document.getElementById('sumSeats'),
    outboundSeat: document.getElementById('sumOutboundSeat'),
    returnSeat: document.getElementById('sumReturnSeat'),
    fare: document.getElementById('sumFlight'),
    insurance: document.getElementById('sumInsurance'),
    total: document.getElementById('sumTotal')
  };

  const seatLayout = {
    first: ['1A','1B','2A','2B'],
    business: ['3A','3B','3C','3D','4A','4B','4C','4D'],
    economy: [
      '5A','5B','5C','5D','6A','6B','6C','6D',
      '7A','7B','7C','7D','8A','8B','8C','8D'
    ]
  };

  const takenSeats = {
    first: ['1B'],
    business: ['3C','4A'],
    economy: ['5D','6B','7A','8C']
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

  function buildSeatMap(container, hiddenInput, prefix) {
    if (!container || !hiddenInput) return;
    container.innerHTML = '';
    const selectedClass = classType.value;
    const seats = seatLayout[selectedClass];
    const unavailable = takenSeats[selectedClass] || [];

    seats.forEach(code => {
      const seat = document.createElement('button');
      seat.type = 'button';
      seat.className = 'seat';
      seat.textContent = code;
      seat.dataset.seatCode = code;

      if (unavailable.includes(code)) {
        seat.classList.add('taken');
        seat.disabled = true;
      }

      seat.addEventListener('click', function(){
        if (seat.classList.contains('taken')) return;
        container.querySelectorAll('.seat').forEach(item => item.classList.remove('selected'));
        seat.classList.add('selected');
        hiddenInput.value = code;
        if (prefix === 'return') {
          summary.returnSeat.textContent = code;
        } else {
          summary.outboundSeat.textContent = code;
        }
      });

      container.appendChild(seat);
    });

    if (prefix === 'outbound') {
      hiddenInput.value = '';
      summary.outboundSeat.textContent = 'Not selected';
    } else {
      hiddenInput.value = '';
      if (summary.returnSeat) summary.returnSeat.textContent = 'Not selected';
    }
  }

  function updateTripCards() {
    const value = getTripType();
    tripCards.forEach(card => {
      const radio = card.querySelector('input');
      card.classList.toggle('active-choice', radio.checked);
    });
    summary.trip.textContent = tripLabel(value);
    const requiresTrip = document.querySelectorAll('[data-requires-trip]');
    requiresTrip.forEach(el => el.disabled = !value);
    classSeatSection.classList.remove('is-locked');

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
    } else {
      multiCityContainer.style.display = 'none';
    }

    renderSeatMaps();
    updateSummary();
  }

  function updateFlightCards() {
    flightOptionCards.forEach(card => {
      const radio = card.querySelector('input[name="flight_id"]');
      card.classList.toggle('selected-flight', radio.checked);
    });
  }

  function updateSelectedFlightSummary() {
    const selected = getSelectedFlightInput();
    if (!selected) return;
    summary.name.textContent = selected.dataset.name || '-';
    summary.code.textContent = selected.dataset.code || '-';
    summary.date.textContent = selected.dataset.date || '-';
    summary.time.textContent = `${selected.dataset.departure || '-'} - ${selected.dataset.arrival || '-'}`;
    updateFlightCards();
  }

  function updateSummary() {
    const selected = getSelectedFlightInput();
    const basePrice = selected ? parseFloat(selected.dataset.price || '0') : 0;
    const passengers = parseInt(seatsSelect.value || '1', 10);
    const insurance = parseFloat(insuranceSelect.value || '0');
    const tripType = getTripType();
    let flightCost = basePrice * passengers;

    if (tripType === 'round') {
      flightCost *= 2;
    }
    if (tripType === 'multi') {
      const segments = document.querySelectorAll('.multi-segment').length;
      flightCost *= Math.max(segments, 1);
    }

    if (classType.value === 'business') flightCost += 250 * passengers;
    if (classType.value === 'first') flightCost += 500 * passengers;

    const total = flightCost + insurance;

    summary.class.textContent = classLabel(classType.value);
    summary.seats.textContent = passengers;
    summary.fare.textContent = `$${flightCost.toFixed(0)}`;
    summary.insurance.textContent = `$${insurance.toFixed(0)}`;
    summary.total.textContent = `$${total.toFixed(0)}`;
    seatZoneNote.textContent = `${classLabel(classType.value)} seats are currently shown.`;
  }

  function renderSeatMaps() {
    buildSeatMap(seatMapOutbound, seatNumberInput, 'outbound');
    if (getTripType() === 'round') {
      buildSeatMap(seatMapReturn, returnSeatNumberInput, 'return');
    }
  }

  ticketTypeInputs.forEach(input => input.addEventListener('change', updateTripCards));
  flightInputs.forEach(input => input.addEventListener('change', function(){
    updateSelectedFlightSummary();
    updateSummary();
  }));
  classType.addEventListener('change', function(){
    renderSeatMaps();
    updateSummary();
  });
  seatsSelect.addEventListener('change', updateSummary);
  insuranceSelect.addEventListener('change', updateSummary);

  if (addSegmentBtn) {
    addSegmentBtn.addEventListener('click', function(){
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

  bookingForm.addEventListener('submit', function(e){
    const tripType = getTripType();
    if (!seatNumberInput.value) {
      e.preventDefault();
      alert('Please select an outbound seat before confirming your booking.');
      return;
    }
    if (tripType === 'round' && !returnSeatNumberInput.value) {
      e.preventDefault();
      alert('Please select a return seat for your round-trip booking.');
    }
  });

  updateTripCards();
  updateSelectedFlightSummary();
  updateSummary();
});


