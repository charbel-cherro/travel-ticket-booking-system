<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="page">

  <div class="page-header">
    <h2>My Profile</h2>
    <p class="muted">Update your account information.</p>
  </div>

  <div class="panel">

    <form class="form">

      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="charbel cherro">
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" value="charbel@email.com">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" placeholder="+961 XX XXX XXX">
        </div>

        <div class="form-group">
          <label>Country</label>
          <input type="text" placeholder="Lebanon">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>New Password</label>
          <input type="password" placeholder="Enter new password">
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" placeholder="Confirm password">
        </div>
      </div>

      <button class="btn-primary">Save Changes</button>

    </form>

  </div>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>