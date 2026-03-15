<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-page">
  <div class="auth-container">
    <h1 class="brand">LebaneseAirline</h1>
    <h2>Create Your Account</h2>
    <p class="muted">Sign up to book flights and manage trips</p>

    <form id="registerForm" action="login.php" method="GET">
      <input type="text" placeholder="Full Name" required>

      <input type="email" placeholder="Email Address" required>

      <!-- PASSWORD -->
      <input type="password" id="password" placeholder="Password" required>

      <!-- CONFIRM PASSWORD -->
      <input type="password" id="confirmPassword" placeholder="Confirm Password" required>

      <div class="terms">
<label class="checkbox">
<input type="checkbox" required>
<span>
<a href="../auth/policy.php" class="link">I agree to the Terms and Privacy Policy</a>
</span>
</label>
</div>

      <button class="btn-primary" type="submit">Sign Up</button>
    </form>

    <p class="small">
      Already have an account? <a class="link" href="login.php">Sign In</a>
    </p>
  </div>
</div>

<script>
document.getElementById("registerForm").addEventListener("submit", function(e){

    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirmPassword").value;

    if(password !== confirmPassword){
        alert("Password does not match!");
        e.preventDefault(); 
    }

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>