<?php
  include_once 'includes/config.php';
  include_once 'includes/header.php';
?>

<main class="container">
  <section class="hero">
    <div class="hero-content">
      <h1>Selamat Datang di UPTD Puskesmas Sananwetan</h1>
      <p>Register online and avoid waiting in long lines</p>
      <div class="hero-buttons">
        <a href="#polyclinic-section" class="btn btn-primary">Register Now</a>
        <a href="queue-status.php" class="btn btn-secondary">Check Queue Status</a>
      </div>
    </div>
  </section>

  <section id="polyclinic-section" class="polyclinic-section">
    <h2>Available Polyclinics</h2>
    <p>Select a polyclinic to register for a queue number</p>
    
    <div class="polyclinic-container" id="polyclinic-list">
      <!-- Polyclinics will be loaded here via JavaScript -->
      <div class="loading">Loading polyclinics...</div>
    </div>
  </section>

  <section class="features">
    <h2>How It Works</h2>
    <div class="feature-cards">
      <div class="feature-card">
        <div class="feature-icon">1</div>
        <h3>Select Polyclinic</h3>
        <p>Choose from our available specialized polyclinics</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">2</div>
        <h3>Register</h3>
        <p>Fill in your details to get a registration code</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">3</div>
        <h3>Wait Remotely</h3>
        <p>Track your queue position online or on-site</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">4</div>
        <h3>Get Treatment</h3>
        <p>See the doctor when your number is called</p>
      </div>
    </div>
  </section>
</main>

<?php include_once 'includes/footer.php'; ?>