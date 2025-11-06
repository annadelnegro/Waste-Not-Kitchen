<?php
// Minimal login page for Waste-Not-Kitchen
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Waste-Not-Kitchen</title>
  <link rel="stylesheet" href="../../../assets/css/login.css" />
  <style>
    @font-face {
      font-family: 'Simply Olive DEMO';
      src: url('../../../assets/fonts/Simply Olive DEMO.ttf') format('opentype');
    }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="../../../assets/images/pigeon.png" alt="Pigeon" class="pigeon-img" />
    <div class="login-box">
      <h2>Welcome Back!</h2>
      <form id="loginForm" method="POST" action="../Controller.php?action=login">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username" required />
        <div class="error-message"></div>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required />
        <div class="error-message"></div>

        <button type="submit" class="login-btn">Login</button>

        <p class="links">
          Don’t Have an Account? <a href="register.php">Sign Up</a><br />
          <a href="forgot_pwd.php">Forgot Password</a>
        </p>
      </form>
    </div>
  </div>

  <script src="../../../assets/js/login.js"></script>
</body>
</html>