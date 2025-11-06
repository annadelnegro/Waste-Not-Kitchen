<?php
// Registration view - frontend only (layout + form)
// Backend logic for saving to DB lives in modules/Auth/Controller.php?action=register
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | Waste-Not-Kitchen</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Register page styles -->
  <link rel="stylesheet" href="../../../assets/css/register.css">

  <!-- Register page JS (validation + AJAX + toast) -->
  <script defer src="../../../assets/js/register.js"></script>
</head>
<body>
  <div class="page-wrapper">
    

    <!-- LEFT: Registration form -->
    <div class="register-form-container">
      <form id="registerForm"
            class="register-form"
            method="POST"
            action="../Controller.php?action=register"
            novalidate>
        <h2 class="form-title">Create Account</h2>

        <!-- Full Name -->
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input
            type="text"
            id="full_name"
            name="full_name"
            placeholder="John Doe"
            required
          >
          <div class="error-message"></div>
        </div>

        <!-- Username -->
        <div class="form-group">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="john_doe"
            required
          >
          <div class="error-message"></div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="********"
            required
          >
          <div class="error-message"></div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            placeholder="********"
            required
          >
          <div class="error-message"></div>
        </div>

        <!-- Role -->
        <div class="form-group">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="">Select role</option>
            <option value="restaurant">Restaurant</option>
            <option value="customer">Customer</option>
            <option value="donor">Donor</option>
            <option value="needy">Needy</option>
          </select>
          <div class="error-message"></div>
        </div>

        <!-- Address -->
        <div class="form-group">
          <label for="address">Address</label>
          <input
            type="text"
            id="address"
            name="address"
            placeholder="123 Main St, Orlando, FL"
            required
          >
          <div class="error-message"></div>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input
            type="text"
            id="phone"
            name="phone"
            placeholder="123-456-7890"
          >
          <div class="error-message"></div>
        </div>

        <!-- Security Question -->
        <div class="form-group">
          <label for="security_question">Security Question</label>
          <select id="security_question" name="security_question" required>
            <option value="">Select a question</option>
            <option value="What was your first pet’s name?">What was your first pet’s name?</option>
            <option value="What city were you born in?">What city were you born in?</option>
            <option value="What is your favorite book?">What is your favorite book?</option>
            <option value="What was your high school mascot?">What was your high school mascot?</option>
            <option value="What was the name of your childhood best friend?">What was the name of your childhood best friend?</option>
            <option value="What is your favorite movie?">What is your favorite movie?</option>
            <option value="What was your first car?">What was your first car?</option>
            <option value="What is your mother’s maiden name?">What is your mother’s maiden name?</option>
            <option value="What is your dream vacation spot?">What is your dream vacation spot?</option>
            <option value="What is your favorite restaurant?">What is your favorite restaurant?</option>
          </select>
          <div class="error-message"></div>
        </div>

        <!-- Security Answer -->
        <div class="form-group">
          <label for="security_answer">Security Answer</label>
          <input
            type="text"
            id="security_answer"
            name="security_answer"
            placeholder="Answer"
            required
          >
          <div class="error-message"></div>
        </div>

        <!-- Payment section (shown only for customer/donor via JS) -->
        <div id="payment-section" class="payment-section hidden">
          <h3 class="section-heading">Payment Information</h3>

          <div class="form-group">
            <label for="cardholder_name">Cardholder Name</label>
            <input
              type="text"
              id="cardholder_name"
              name="cardholder_name"
              placeholder="Name on card"
            >
            <div class="error-message"></div>
          </div>

          <div class="form-group">
            <label for="card_number">Card Number</label>
            <input
              type="text"
              id="card_number"
              name="card_number"
              placeholder="1234567812345678"
            >
            <div class="error-message"></div>
          </div>

          <div class="form-group">
            <label for="cvc">CVC</label>
            <input
              type="text"
              id="cvc"
              name="cvc"
              placeholder="123"
            >
            <div class="error-message"></div>
          </div>

          <div class="form-group">
            <label for="expiration_date">Expiration Date (MM/YY)</label>
            <input
              type="text"
              id="expiration_date"
              name="expiration_date"
              placeholder="08/28"
            >
            <div class="error-message"></div>
          </div>
        </div>

        <button type="submit" class="submit-btn">Register Now</button>
      </form>
    </div>

    <!-- RIGHT: Info panel (fixed) -->
    <div class="info-panel">
      <h2 class="info-title">Know Your Role</h2>

      <div class="role-list">
        <div class="role-item">
          <img src="../../../assets/images/restaurant.png" alt="Restaurant" class="role-icon">
          <p><strong>Restaurant</strong> - Share your surplus meals instead of wasting them. List plates, set prices, and help the community.</p>
        </div>
        <div class="role-item">
          <img src="../../../assets/images/customer.png" alt="Customer" class="role-icon">
          <p><strong>Customer</strong> - Enjoy affordable meals from nearby restaurants while helping reduce food waste.</p>
        </div>
        <div class="role-item">
          <img src="../../../assets/images/donor.png" alt="Donor" class="role-icon">
          <p><strong>Donor</strong> - Pay it forward by buying plates that go directly to those in need.</p>
        </div>
        <div class="role-item">
          <img src="../../../assets/images/needy.png" alt="Needy" class="role-icon">
          <p><strong>Needy</strong> - Receive free meals that have already been donated and are ready to pick up.</p>
        </div>
      </div>

      <div class="info-section">
        <h3>Username Requirements</h3>
        <p>4-20 characters.</p>
        <p>Letters, numbers, and underscores only.</p>
      </div>

      <div class="info-section">
        <h3>Password Requirements</h3>
        <p>At least 8 characters long.</p>
        <p>Contain at least one uppercase letter.</p>
        <p>Contain at least one lowercase letter.</p>
        <p>Include at least one number.</p>
        <p>Include at least one special character (!, @, #, $, %, etc.).</p>
      </div>
    </div>
  </div>
</body>
</html>