<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/flight-data.php';
$featuredFlights = array_slice(get_all_flights(false), 0, 3);
?>

<section class="hero hero-home">
  <div class="hero-content hero-card premium-hero-card">
    <span class="eyebrow">Modern flight booking</span>
    <h1>Travel smarter with LebaneseAirline</h1>
    <p>Search flights, view flight codes, choose your class and reserve your seat easily.</p>

    <div class="hero-cta-grid">
      <a class="hero-cta hero-cta-primary" href="<?= BASE_URL ?>/user/booking.php">
        <span class="hero-cta-label">Start Booking</span>
        <small>Search flights and select seats</small>
      </a>

      <a class="hero-cta hero-cta-secondary" href="<?= BASE_URL ?>/auth/register.php">
        <span class="hero-cta-label">Create Account</span>
        <small>Sign up before booking</small>
      </a>
    </div>
  </div>
</section>

<section class="page compact-top">
  <div class="section-heading">
    <div>
      <span class="eyebrow">Featured flights</span>
      <h2>Popular routes</h2>
    </div>
    <p class="muted">Choose from our most requested destinations.</p>
  </div>

  <div class="cards-grid cols-3">

    <?php foreach ($featuredFlights as $flight): ?>

      <article class="flight-card modern-card">

        <!-- ⭐ IMAGE -->
        <div class="flight-image">
          <img src="assets/images/<?= strtolower($flight['to']) ?>.jpg" alt="">
        </div>

        <div class="flight-card-top">
          <div>
            <span class="pill"><?= htmlspecialchars($flight['code']) ?></span>
            <h3><?= htmlspecialchars($flight['name']) ?></h3>
          </div>
          <div class="price-tag">$<?= number_format($flight['price'], 0) ?></div>
        </div>

        <p class="muted"><?= htmlspecialchars($flight['from']) ?> → <?= htmlspecialchars($flight['to']) ?></p>

        <div class="flight-meta-grid">
          <div>
            <strong>Date</strong>
            <span><?= htmlspecialchars($flight['date']) ?></span>
          </div>
          <div>
            <strong>Time</strong>
            <span><?= htmlspecialchars($flight['departure']) ?> - <?= htmlspecialchars($flight['arrival']) ?></span>
          </div>
        </div>

        <a class="btn-dark full-width"
           href="<?= BASE_URL ?>/user/booking.php?flight_id=<?= (int)$flight['id'] ?>">
           View Flight
        </a>

      </article>

    <?php endforeach; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>