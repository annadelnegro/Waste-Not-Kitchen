<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If not logged in, redirect to login page
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// If this user is an admin, redirect them to the admin dashboard instead of the profile
if (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
  header('Location: /Waste-Not-Kitchen/admin-dashboard.php');
  exit;
}

$full_name = $_SESSION['full_name'] ?? '';
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
$address = $_SESSION['address'] ?? '';
$phone = $_SESSION['phone'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile | Waste-Not-Kitchen</title>
  <link rel="stylesheet" href="../../../assets/css/profile.css" />
  <link rel="stylesheet" href="../../../assets/css/plates-modal.css" />
  <style>
    /* ensure same font fallback used by inline pages */
    @font-face { font-family: 'Simply Olive DEMO'; src: url('../../../assets/fonts/Simply Olive DEMO.ttf') format('opentype'); }
  </style>
</head>
<body>
  <div class="profile-header">Profile</div>

  <div class="profile-actions">
    <?php
      // Compute an absolute dashboard URL based on role so the "Back" button
      // always goes to the correct dashboard regardless of current path.
      $roleLower = strtolower($role ?? '');
      switch ($roleLower) {
        case 'customer':
          $backUrl = '/Waste-Not-Kitchen/modules/customer/customer-dashboard.php';
          break;
        case 'donor':
          $backUrl = '/Waste-Not-Kitchen/modules/donor/donor-dashboard.php';
          break;
        case 'needy':
          $backUrl = '/Waste-Not-Kitchen/modules/needy/needy-dashboard.php';
          break;
        case 'restaurant':
          // restaurants use the Offers list as their dashboard
          $backUrl = '/Waste-Not-Kitchen/modules/Offers/views/list.php';
          break;
        default:
          $backUrl = '/Waste-Not-Kitchen/index.php';
      }
    ?>
    <?php if (strtolower($role) !== 'restaurant'): ?>
      <a class="btn" href="<?php echo $backUrl; ?>">Back to Dashboard</a>
    <?php endif; ?>
    <?php if (strtolower($role) === 'restaurant'): ?>
      <a class="btn" href="activity.php">See Activity</a>
    <?php elseif (strtolower($role) === 'customer'): ?>
      <a class="btn" href="customer_activity.php">See Activity</a>
    <?php elseif (strtolower($role) === 'donor'): ?>
      <a class="btn" href="donor_activity.php">See Activity</a>
    <?php elseif (strtolower($role) === 'needy'): ?>
      <a class="btn" href="needy_activity.php">See Activity</a>
    <?php endif; ?>
  </div>

  <div class="profile-card-wrap">
    <div class="profile-card">
      <div class="profile-left">
        <?php
          $imgMap = [
            'customer' => '../../../assets/images/customer.png',
            'donor' => '../../../assets/images/donor.png',
            'needy' => '../../../assets/images/needy.png',
            'restaurant' => '../../../assets/images/restaurant.png'
          ];
          $photo = $imgMap[strtolower($role ?? '')] ?? '../../../assets/images/pigeon.png';
        ?>
        <img class="profile-photo" src="<?php echo $photo; ?>" alt="Profile Photo" />
        <div class="card-title"><?php echo ucfirst(htmlspecialchars($role ?: 'User')); ?> Card</div>
      </div>

      <div class="profile-details">
        <div class="detail-row"><div class="detail-label">Full Name</div><div class="detail-value" id="profile-full_name"><?php echo htmlspecialchars($full_name ?: '-'); ?></div><div><button class="edit-field" data-field="full_name">✎</button></div></div>
        <div class="detail-row"><div class="detail-label">Username</div><div class="detail-value"><?php echo htmlspecialchars($username); ?></div></div>
        <div class="detail-row"><div class="detail-label">Role</div><div class="detail-value"><?php echo htmlspecialchars(ucfirst($role)); ?></div></div>
        <div class="detail-row"><div class="detail-label">Phone Number</div><div class="detail-value" id="profile-phone"><?php echo htmlspecialchars($phone ?: '-'); ?></div><div><button class="edit-field" data-field="phone">✎</button></div></div>
        <div class="detail-row"><div class="detail-label">Address</div><div class="detail-value" id="profile-address"><?php echo htmlspecialchars($address ?: '-'); ?></div><div><button class="edit-field" data-field="address">✎</button></div></div>

        <?php
        // Try to fetch masked card info if present in DB
        $cardLabel = '';
        $masked = '';
        try {
          if (file_exists(__DIR__ . '/../../../config/config.php')) {
            require_once __DIR__ . '/../../../config/config.php';
            if (!empty($_SESSION['user_id'])) {
              $pstmt = $pdo->prepare('SELECT card_number, cardholder_name FROM payment_info WHERE user_id = ? ORDER BY id DESC LIMIT 1');
              $pstmt->execute([$_SESSION['user_id']]);
              $pi = $pstmt->fetch(PDO::FETCH_ASSOC);
              if ($pi && !empty($pi['card_number'])) {
                $masked = htmlspecialchars($pi['card_number']);
                $cardLabel = 'Card Details';
              }
            }
          }
        } catch (Exception $e) {
          // ignore DB errors
        }

        if ($masked): ?>
          <div class="detail-row"><div class="detail-label"><?php echo $cardLabel; ?></div><div class="detail-value"><?php echo $masked; ?></div><div><button class="edit-field">✎</button></div></div>
        <?php endif; ?>
      </div>

      <?php if (strtolower($role) === 'restaurant'): ?>
        <div class="actions-panel">
        <div class="actions-header">Actions</div>
        <button class="action-link open-add-plate" type="button">Add a Plate</button>
        <button class="action-link open-view-plates" type="button">View Plates</button>
      </div>
      <?php endif; ?>
        <?php if (strtolower($role) === 'restaurant'): ?>
        <!-- Add Plate Modal (hidden) -->
        <div id="add-plate-modal" class="modal" style="display:none;">
          <div class="modal-content">
            <h2>Add a Plate</h2>
            <div class="modal-body">
              <label>Plate Title</label>
              <input id="plate-title" type="text" placeholder="e.g. Chicken Rice" />

              <label>Plate Description</label>
              <textarea id="plate-desc" placeholder="Short description, e.g. 1 large serving"></textarea>

              <div class="two-cols">
                <div>
                  <label>Price</label>
                  <input id="plate-price" type="text" placeholder="e.g. 6.00" pattern="\d{1,3}\.\d{2}" title="Enter price as digits with two decimals, e.g. 6.00" inputmode="decimal" />
                </div>
                <div>
                  <label>Inventory</label>
                  <input id="plate-qty" type="number" min="0" placeholder="e.g. 10" />
                </div>
              </div>

              <div class="two-cols">
                <div>
                  <label>Available From</label>
                  <input id="plate-from" type="date" title="Format: YYYY-MM-DD (midnight)" />
                </div>
                <div>
                  <label>Available To</label>
                  <input id="plate-to" type="date" title="Format: YYYY-MM-DD (midnight)" />
                </div>
              </div>

              <div id="plate-error" class="small-muted" style="color:#d14b4b; display:none;"></div>
            </div>
            <div class="modal-actions">
              <button id="plate-save" class="btn">Save</button>
              <button id="plate-cancel" class="btn back">Cancel</button>
            </div>
          </div>
        </div>
        <!-- Edit Plate Modal (hidden, separate from Add) -->
        <div id="edit-plate-modal" class="modal" style="display:none;">
          <div class="modal-content">
            <h2>Edit Plate</h2>
            <div class="modal-body">
              <label>Plate Title</label>
              <input id="edit-plate-title" type="text" placeholder="e.g. Chicken Rice" />

              <label>Plate Description</label>
              <textarea id="edit-plate-desc" placeholder="Short description, e.g. 1 large serving"></textarea>

              <div class="two-cols">
                <div>
                  <label>Price</label>
                  <input id="edit-plate-price" type="text" placeholder="e.g. 6.00" pattern="\d{1,3}\.\d{2}" title="Enter price as digits with two decimals, e.g. 6.00" inputmode="decimal" />
                </div>
                <div>
                  <label>Inventory</label>
                  <input id="edit-plate-qty" type="number" min="0" placeholder="e.g. 10" />
                </div>
              </div>

              <div class="two-cols">
                <div>
                  <label>Available From</label>
                  <input id="edit-plate-from" type="date" title="Format: YYYY-MM-DD (midnight)" />
                </div>
                <div>
                  <label>Available To</label>
                  <input id="edit-plate-to" type="date" title="Format: YYYY-MM-DD (midnight)" />
                </div>
              </div>

              <div id="edit-plate-error" class="small-muted" style="color:#d14b4b; display:none;"></div>
            </div>
            <div class="modal-actions">
              <button id="edit-plate-save" class="btn">Save</button>
              <button id="edit-plate-cancel" class="btn back">Cancel</button>
            </div>
          </div>
        </div>
        <script src="../../../assets/js/plates-modal.js"></script>
        <script src="../../../assets/js/view-plates.js"></script>
        <script src="../../../assets/js/edit-plate.js"></script>
        <?php endif; ?>
    </div>
  </div>

  <div class="signout-wrap">
    <a class="signout-btn" href="logout.php">Sign Out</a>
  </div>

  <!-- Profile Edit Modal -->
  <div id="profile-edit-modal" class="modal" style="display:none;">
    <div class="modal-content">
      <h2>Edit Profile</h2>
      <div class="modal-body">
        <label id="edit-label">Field</label>
        <input id="edit-input" type="text" style="width:100%;padding:8px;border-radius:6px;border:1px solid #ddd;" />
        <div id="edit-error" class="small-muted" style="color:#d14b4b; display:none; margin-top:8px;"></div>
      </div>
      <div class="modal-actions">
        <button id="edit-save" class="btn">Save</button>
        <button id="edit-cancel" class="btn back">Cancel</button>
      </div>
    </div>
  </div>

  <script src="../../../assets/js/profile.js"></script>
  <script src="../../../assets/js/profile-edit.js"></script>
</body>
</html>
