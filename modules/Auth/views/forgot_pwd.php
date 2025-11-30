<?php
// Forgot Password Portal - Waste-Not-Kitchen
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password | Waste-Not-Kitchen</title>
  <link rel="stylesheet" href="../../../assets/css/forgot_pwd.css" />
  <style>
    @font-face {
      font-family: 'Simply Olive DEMO';
      src: url('../../../assets/fonts/Simply Olive DEMO.ttf') format('opentype');
    }
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
  </style>
</head>
<body>
  <div class="forgot-container">
    <img src="../../../assets/images/brain1.png" alt="Brain" class="brain-img" id="brainImage" />
    <div class="forgot-box" id="forgotBox">
      <h2>Forgot Password Portal</h2>

      <!-- Step 1: Enter Username -->
      <form id="forgotForm" method="POST" action="../forgot_pwd_api.php">
        <div id="step1">
          <p class="step-text">Enter Username to Continue</p>
          <input type="text" id="username" name="username" placeholder="Username" required />
          <div class="error-message"></div>
          <button type="submit" class="arrow-btn">&#10145;</button>
        </div>

        <!-- Step 2: Security Question -->
        <div id="step2" class="hidden">
          <p class="security-question" id="securityQuestion"></p>
          <label for="security_answer">Security Question Answer</label>
          <input type="text" id="security_answer" name="security_answer" placeholder="Answer" />
          <div class="error-message"></div>
          <button type="submit" class="arrow-btn">&#10145;</button>
        </div>

        <!-- Step 3: Reset Password -->
        <div id="step3" class="hidden">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" placeholder="New Password" />
          <div class="error-message"></div>

          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" />
          <div class="error-message"></div>

          <button type="submit" class="arrow-btn">&#10145;</button>
        </div>
      </form>
    </div>

    <!-- Password requirements -->
    <div id="passwordRequirements" class="requirements hidden">
      <h3>Password Requirements</h3>
      <ul>
        <li>Be at least 8 characters long</li>
        <li>Contain at least one uppercase letter</li>
        <li>Contain at least one lowercase letter</li>
        <li>Include at least one number</li>
        <li>Include at least one special character (!, @, #, $, %, etc.)</li>
      </ul>
    </div>
  </div>

  <script src="../../../assets/js/forgot_pwd.js"></script>
</body>
</html>