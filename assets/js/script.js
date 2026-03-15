document.addEventListener("DOMContentLoaded", function(){

/* ================= ROUTES ================= */

const routes = {
  "Beirut-Paris": { dep:"08:00", arr:"12:30" },
  "Beirut-London": { dep:"09:30", arr:"14:30" },
  "Dubai-Tokyo": { dep:"22:00", arr:"10:00" },
  "London-Rome": { dep:"07:00", arr:"10:00" },
  "Dubai-Paris": { dep:"11:00", arr:"17:00" },

  "Beirut-Doha": { dep:"06:00", arr:"09:00" },
  "Doha-Paris": { dep:"11:00", arr:"16:00" },
  "Beirut-Istanbul": { dep:"07:00", arr:"10:00" },
  "Istanbul-London": { dep:"12:00", arr:"15:30" }
};

/* ================= ELEMENTS ================= */

const fromSelect = document.getElementById("fromSelect");
const toSelect = document.getElementById("toSelect");

const departureInput = document.getElementById("departureTime");
const arrivalInput = document.getElementById("arrivalTime");
const durationField = document.getElementById("flightDuration");

const flightType = document.getElementById("flightType");
const ticketType = document.getElementById("ticketType");
const classType = document.getElementById("classType");

const seatsSelect = document.getElementById("seatsSelect");
const insuranceSelect = document.getElementById("insuranceSelect");

const sumFlight = document.getElementById("sumFlight");
const sumInsurance = document.getElementById("sumInsurance");
const sumSeats = document.getElementById("sumSeats");
const sumTotal = document.getElementById("sumTotal");

const returnDateGroup = document.getElementById("returnDateGroup");
const escaleCityGroup = document.getElementById("escaleCityGroup");

const multiCityContainer = document.getElementById("multiCityContainer");
const multiCitySegments = document.getElementById("multiCitySegments");
const addSegmentBtn = document.getElementById("addSegmentBtn");

const seatMap = document.getElementById("seatMap");
const seatInput = document.getElementById("seatNumber");

/* ================= BASE PRICE ================= */

let basePrice = 420;
const basePriceInput = document.getElementById("basePriceValue");
if(basePriceInput){
  basePrice = parseFloat(basePriceInput.value);
}

/* ================= SCHEDULE ================= */

function setFlightSchedule(){

  if(!departureInput || !arrivalInput) return;

  // MULTI CITY
  if(ticketType && ticketType.value === "multi"){

    const firstFrom = document.querySelector('select[name="multiFrom[]"]');
    const firstTo = document.querySelector('select[name="multiTo[]"]');

    if(!firstFrom || !firstTo) return;

    const key = firstFrom.value + "-" + firstTo.value;

    if(routes[key]){
      departureInput.value = routes[key].dep;
      arrivalInput.value = routes[key].arr;
    }else{
      departureInput.value = "10:00";
      arrivalInput.value = "14:00";
    }

    calculateDuration();
    return;
  }

  // NORMAL
  if(!fromSelect || !toSelect) return;

  const key = fromSelect.value + "-" + toSelect.value;

  if(routes[key]){
    departureInput.value = routes[key].dep;
    arrivalInput.value = routes[key].arr;
  }else{
    departureInput.value = "10:00";
    arrivalInput.value = "14:00";
  }

  calculateDuration();
}

/* ================= DURATION ================= */

function calculateDuration(){

  if(!departureInput.value || !arrivalInput.value) return;

  let dep = departureInput.value.split(":");
  let arr = arrivalInput.value.split(":");

  let depMin = parseInt(dep[0])*60 + parseInt(dep[1]);
  let arrMin = parseInt(arr[0])*60 + parseInt(arr[1]);

  if(arrMin < depMin) arrMin += 1440;

  let diff = arrMin - depMin;

  if(flightType && flightType.value === "escale"){
    diff += 120;
  }

  const h = Math.floor(diff/60);
  const m = diff % 60;

  durationField.value = h+"h "+m+"m";
}

/* ================= PRICE ================= */

function updateSummary(){

  if(!seatsSelect || !insuranceSelect) return;

  const seats = parseInt(seatsSelect.value);
  const insurance = parseFloat(insuranceSelect.value);

  let flightCost = basePrice * seats;

  if(ticketType && ticketType.value === "round"){
    flightCost *= 2;
  }

  if(ticketType && ticketType.value === "multi"){
    const segments = document.querySelectorAll(".multi-segment").length;
    flightCost *= segments;
  }

  if(flightType && flightType.value === "escale"){
    flightCost -= 50;
  }

  if(classType){
    if(classType.value === "business") flightCost += 250 * seats;
    if(classType.value === "first") flightCost += 500 * seats;
  }

  const total = flightCost + insurance;

  if(sumFlight) sumFlight.innerText = "$"+flightCost;
  if(sumInsurance) sumInsurance.innerText = "$"+insurance;
  if(sumSeats) sumSeats.innerText = seats;
  if(sumTotal) sumTotal.innerText = "$"+total;
}

/* ================= MULTI CITY ADD ================= */

if(addSegmentBtn){
  addSegmentBtn.addEventListener("click", function(){

    const segment = document.createElement("div");
    segment.className = "form-row multi-segment";

    segment.innerHTML = `
      <div class="form-group">
        <label>From</label>
        <select name="multiFrom[]">
          <option>Beirut</option>
          <option>Dubai</option>
          <option>London</option>
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
        </select>
      </div>

      <div class="form-group">
        <label>Date</label>
        <input type="date" name="multiDate[]">
      </div>
    `;

    multiCitySegments.appendChild(segment);

    setFlightSchedule();
    updateSummary();
  });
}

/* ================= SEAT MAP ================= */

if(seatMap && seatInput){

  const letters = ["A","B","C","D","E","F"];
  const takenSeats = ["A2","B4","C3","D5"];

  for(let r=1;r<=6;r++){
    for(let c=0;c<6;c++){

      const code = letters[c]+r;

      const seat = document.createElement("div");
      seat.className = "seat";
      seat.innerText = code;

      if(takenSeats.includes(code)){
        seat.classList.add("taken");
      }

      seat.onclick = function(){

        if(seat.classList.contains("taken")) return;

        document.querySelectorAll(".seat").forEach(s=>s.classList.remove("selected"));

        seat.classList.add("selected");
        seatInput.value = code;
      };

      seatMap.appendChild(seat);
    }
  }
}

/* ================= EVENTS ================= */

if(fromSelect) fromSelect.addEventListener("change", setFlightSchedule);
if(toSelect) toSelect.addEventListener("change", setFlightSchedule);

if(flightType){
  flightType.addEventListener("change", function(){

    if(escaleCityGroup){
      escaleCityGroup.style.display =
        (flightType.value === "escale") ? "flex" : "none";
    }

    calculateDuration();
    updateSummary();
  });
}

if(ticketType){
  ticketType.addEventListener("change", function(){

    if(returnDateGroup){
      returnDateGroup.style.display =
        (ticketType.value === "round") ? "flex" : "none";
    }

    if(multiCityContainer){
      multiCityContainer.style.display =
        (ticketType.value === "multi") ? "block" : "none";
    }

    setFlightSchedule();
    updateSummary();
  });
}

if(seatsSelect) seatsSelect.addEventListener("change", updateSummary);
if(insuranceSelect) insuranceSelect.addEventListener("change", updateSummary);
if(classType) classType.addEventListener("change", updateSummary);

/* ================= INIT ================= */

setFlightSchedule();
updateSummary();

});

document.addEventListener("DOMContentLoaded", function(){

    const seatMap = document.getElementById("seatMap");
    const seatInput = document.getElementById("seatNumber");

    if(!seatMap) return;

    const rows = 8;
    const cols = 6;
    const letters = ["A","B","C","D","E","F"];

    // seats already booked (simulation)
    const takenSeats = ["A2","B3","C5","D6","F1","E4"];

    for(let r=1; r<=rows; r++){

        for(let c=0; c<cols; c++){

            const code = letters[c] + r;

            const seat = document.createElement("div");
            seat.classList.add("seat");
            seat.innerText = code;

            if(takenSeats.includes(code)){
                seat.classList.add("taken");
            }

            seat.addEventListener("click", function(){

                if(seat.classList.contains("taken")) return;

                document.querySelectorAll(".seat").forEach(s=>{
                    s.classList.remove("selected");
                });

                seat.classList.add("selected");
                seatInput.value = code;

            });

            seatMap.appendChild(seat);
        }
    }

});
