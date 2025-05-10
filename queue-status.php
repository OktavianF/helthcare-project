<?php
  include_once 'includes/config.php';
  include_once 'includes/functions.php';
  include_once 'includes/header.php';
?>

<main class="container">
  <section class="queue-status-section">
    <h1>Queue Status</h1>
    
    <div class="status-tabs">
      <button class="tab-btn active" data-tab="reg-code">Registration Code</button>
      <button class="tab-btn" data-tab="all-queues">Current Queues</button>
    </div>
    
    <div class="tab-content active" id="reg-code-tab">
      <div class="card">
        <h2>Check Your Queue Status</h2>
        <p>Enter your registration code to see your current position in the queue</p>
        
        <form id="code-lookup-form" class="lookup-form">
          <div class="form-group">
            <label for="registration_code">Registration Code</label>
            <input type="text" id="registration_code" name="registration_code" required minlength="8" maxlength="8">
          </div>
          <button type="submit" class="btn btn-primary">Check Status</button>
        </form>
        
        <div id="patient-status" class="patient-status hidden">
          <!-- Patient status will be displayed here -->
        </div>
      </div>
    </div>
    
    <div class="tab-content" id="all-queues-tab">
      <div class="card">
        <h2>Current Queue Numbers</h2>
        <p>Real-time display of current queue numbers for all polyclinics</p>
        
        <div class="current-queues" id="current-queues">
          <!-- Current queue information will be loaded here -->
          <div class="loading">Loading current queue information...</div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include_once 'includes/footer.php'; ?>