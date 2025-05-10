<?php
  include_once 'includes/config.php';
  include_once 'includes/functions.php';
  
  $polyclinic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  // Get polyclinic details
  $polyclinic = [];
  if ($polyclinic_id) {
    $stmt = $conn->prepare("SELECT * FROM polyclinics WHERE id = ?");
    $stmt->bind_param("i", $polyclinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $polyclinic = $result->fetch_assoc();
    } else {
      // Redirect if polyclinic not found
      header("Location: index.php");
      exit;
    }
    $stmt->close();
  } else {
    // Redirect if no ID provided
    header("Location: index.php");
    exit;
  }
  
  include_once 'includes/header.php';
?>

<main class="container">
  <section class="registration-section">
    <div class="back-link">
      <a href="index.php">&larr; Back to Polyclinics</a>
    </div>
    
    <h1>Registration for <?php echo htmlspecialchars($polyclinic['name']); ?></h1>
    
    <div class="polyclinic-info">
      <div class="quota-info">
        <p>Daily Quota: <span id="daily-quota"><?php echo $polyclinic['daily_quota']; ?></span></p>
        <p>Available Slots: <span id="available-slots"><?php echo $polyclinic['available_quota']; ?></span></p>
      </div>
    </div>
    
    <?php if ($polyclinic['available_quota'] <= 0): ?>
    <div class="alert alert-error">
      <p>We're sorry, but this polyclinic has reached its daily quota. Please try again tomorrow or select another polyclinic.</p>
      <a href="index.php" class="btn btn-secondary">View Other Polyclinics</a>
    </div>
    <?php else: ?>
    
    <form id="registration-form" class="registration-form">
      <input type="hidden" id="polyclinic_id" name="polyclinic_id" value="<?php echo $polyclinic_id; ?>">
      
      <div class="form-group">
        <label for="nik">NIK (National ID Number)*</label>
        <input type="text" id="nik" name="nik" required pattern="[0-9]+" minlength="16" maxlength="16">
        <div class="form-help">Enter your 16-digit National ID Number</div>
      </div>
      
      <div class="form-group">
        <label for="name">Full Name*</label>
        <input type="text" id="name" name="name" required>
      </div>
      
      <div class="form-group">
        <label for="address">Address*</label>
        <textarea id="address" name="address" required></textarea>
      </div>
      
      <div class="form-group">
        <label for="phone">Phone Number*</label>
        <input type="tel" id="phone" name="phone" required pattern="[0-9]+">
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Register</button>
        <button type="reset" class="btn btn-secondary">Clear Form</button>
      </div>
    </form>
    
    <div id="registration-result" class="registration-result hidden">
      <!-- Result will be shown here -->
    </div>
    <?php endif; ?>
  </section>
</main>

<?php include_once 'includes/footer.php'; ?>