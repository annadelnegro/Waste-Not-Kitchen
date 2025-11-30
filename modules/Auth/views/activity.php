<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only restaurants may view this page
if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') {
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
  <title>Activity | Waste-Not-Kitchen</title>
  <link rel="stylesheet" href="../../../assets/css/activity.css" />
  <style>
    @font-face { font-family: 'Simply Olive DEMO'; src: url('../../../assets/fonts/Simply Olive DEMO.ttf') format('opentype'); }
  </style>
</head>
<body>
  <h1 class="page-title">Activity</h1>

  <div class="top-actions">
    <a class="btn back" href="../Offers/views/list.php">Back to Dashboard</a>
    <a class="btn" href="profile.php">Back to Profile</a>
  </div>

  <div class="activity-wrap">
    <div class="box orders-box">
      <div class="box-header">Orders</div>
      <div class="tabs">
        <button class="tab-btn active" data-filter="reserved">Reserved</button>
        <button class="tab-btn" data-filter="picked_up">Picked Up</button>
      </div>
      <div id="orders-list" class="list">
        <div class="small-muted">Loading…</div>
      </div>
    </div>

    <div class="box donations-box">
      <div class="box-header">Donations</div>
      <div class="tabs">
        <button class="tab-btn active" data-filter="available">Available</button>
        <button class="tab-btn" data-filter="reserved">Reserved</button>
        <button class="tab-btn" data-filter="claimed">Picked Up</button>
      </div>
      <div id="donations-list" class="list">
        <div class="small-muted">Loading…</div>
      </div>
    </div>
  </div>

  <script src="../../../assets/js/activity.js"></script>
</body>
</html>
