<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only customers may view this page
if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? '');
$full_name = htmlspecialchars($_SESSION['full_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Orders | Waste-Not-Kitchen</title>
  <link rel="stylesheet" href="../../../assets/css/customer-activity.css" />
  <style>
    @font-face { font-family: 'Simply Olive DEMO'; src: url('../../../assets/fonts/Simply Olive DEMO.ttf') format('opentype'); }
  </style>
</head>
<body>
  <h1 class="page-title">Order History</h1>

  <div class="top-actions">
    <a class="btn back" href="../Offers/views/list.php">Back to Dashboard</a>
    <a class="btn" href="profile.php">Back to Profile</a>
  </div>

  <div class="activity-wrap single-cell">
    <div class="box orders-box">
      <div class="box-header">My Orders</div>
      <div class="tabs">
        <button class="tab-btn active" data-filter="reserved">Reserved</button>
        <button class="tab-btn" data-filter="pay_and_pickup">Paid &amp; Picked Up</button>
      </div>
      <div id="orders-list" class="list">
        <div class="small-muted">Loading…</div>
      </div>
    </div>
  </div>

  <script src="../../../assets/js/customer-activity.js"></script>
</body>
</html>
