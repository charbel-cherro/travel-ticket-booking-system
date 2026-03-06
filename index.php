<?php include __DIR__ . '/includes/header.php'; ?>

<section class="hero">
  <div class="hero-content">
    <h1>Find & Book Your Perfect Flight</h1>
    <p>Best deals on international and domestic flights.</p>

    <div class="search-container">
      <div class="tabs" id="tripTabs">
        <button type="button" class="tab active" data-mode="round">Round Trip</button>
        <button type="button" class="tab" data-mode="oneway">One Way</button>
        <button type="button" class="tab" data-mode="multi">Multi-City</button>
      </div>

      <form class="search-form" action="user/booking.php" method="GET">
        <div class="form-group">
          <label>From</label>
          <select name="from">
            <option value="Beirut">Beirut (BEY)</option>
            <option value="Dubai">Dubai (DXB)</option>
            <option value="London">London (LHR)</option>
          </select>
        </div>

        <div class="form-group">
          <label>To</label>
          <select name="to">
            <option value="Paris">Paris (CDG)</option>
            <option value="Tokyo">Tokyo (HND)</option>
            <option value="Rome">Rome (FCO)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Departure</label>
          <input type="date" name="depart" required>
        </div>

        <div class="form-group" id="returnField">
          <label>Return</label>
          <input type="date" name="return">
        </div>

        <div class="form-group">
          <label>Passengers</label>
          <select name="seats">
            <option value="1">1 Adult</option>
            <option value="2">2 Adults</option>
            <option value="3">3 Adults</option>
          </select>
        </div>

        <!-- If user uses search form, we don't know price yet, so keep default -->
        <input type="hidden" name="price" value="420">

        <div class="form-group search-btn">
          <button type="submit">Search Flights</button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- PROMO SECTION -->
<section class="promo-section">
  <div class="promo-card">
    <img src="images/paris.jpg" alt="Top Destinations">
    <div class="promo-content">
      <h3>Top Destinations</h3>
      <p>Explore our most popular destinations.</p>
      <a class="btn-dark" href="user/booking.php">View Destinations</a>
    </div>
  </div>

  <div class="promo-card">
    <img src="images/beach.jpg" alt="Special Offers">
    <div class="promo-content">
      <h3>Special Offers</h3>
      <p>Save up to 30% selected flights.</p>
      <a class="btn-dark" href="user/booking.php">See Deals</a>
    </div>
  </div>

  <div class="promo-card">
    <img src="images/map.jpg" alt="Travel Guides">
    <div class="promo-content">
      <h3>Travel Guides</h3>
      <p>Get tips & advice for your trips.</p>
      <a class="btn-dark" href="#">Read Guides</a>
    </div>
  </div>
</section>

<!-- FEATURED FLIGHTS -->
<section class="featured">
  <h2>Featured Flights</h2>
  <p class="subtitle">Best deals for your next adventure</p>

  <div class="flight-cards">

    <div class="flight-card">
      <img src="images/london.jpg" alt="London">
      <div class="flight-info">
        <h3>Beirut to London</h3>
        <span class="price">$350</span>
        <a class="btn-dark" href="user/booking.php?from=Beirut&to=London&price=350">Book Now</a>
      </div>
    </div>

    <div class="flight-card">
      <img src="images/tokyo.jpg" alt="Tokyo">
      <div class="flight-info">
        <h3>Dubai to Tokyo</h3>
        <span class="price">$490</span>
        <a class="btn-dark" href="user/booking.php?from=Dubai&to=Tokyo&price=490">Book Now</a>
      </div>
    </div>

    <div class="flight-card">
      <img src="images/rome.jpg" alt="Rome">
      <div class="flight-info">
        <h3>London to Rome</h3>
        <span class="price">$420</span>
        <a class="btn-dark" href="user/booking.php?from=London&to=Rome&price=420">Book Now</a>
      </div>
    </div>

  </div>
</section>

<!-- INFO BAR -->
<section class="info-bar">
  <div class="info-item"><h4>24/7 Customer Support</h4></div>
  <div class="info-item"><h4>Easy & Secure Booking</h4></div>
  <div class="info-item"><h4>No Hidden Fees</h4></div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>