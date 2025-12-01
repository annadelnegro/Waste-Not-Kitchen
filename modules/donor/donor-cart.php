<?php
// Waste-Not-Kitchen Donor Cart Dashboard
session_start();

require_once __DIR__ . '/../../config/config.php';

// Require logged-in user (do not set a temp donor id)
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please sign in to view your donor cart.';
    header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
    exit;
}

$donor_id = (int)$_SESSION['user_id'];

// Flash message
$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Donor details
$donorName  = 'Donor name';
$donorPhone = 'Donor phone number';

$profileStmt = $pdo->prepare("
    SELECT full_name, phone
    FROM profiles
    WHERE user_id = :uid
    LIMIT 1
");
$profileStmt->execute([':uid' => $donor_id]);
if ($row = $profileStmt->fetch()) {
    if (!empty($row['full_name']))  $donorName  = $row['full_name'];
    if (!empty($row['phone']))      $donorPhone = $row['phone'];
}

// Payment method
$paymentDisplay = 'No card on file';

$payStmt = $pdo->prepare("
    SELECT card_number
    FROM payment_info
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 1
");
$payStmt->execute([':uid' => $donor_id]);
if ($row = $payStmt->fetch()) {
    $digits = preg_replace('/\D/', '', $row['card_number']);
    $last4  = substr($digits, -4);
    $paymentDisplay = 'Visa: ********' . $last4;
}

// Cart items
$cart = isset($_SESSION['donor_cart']) ? $_SESSION['donor_cart'] : [];

$plateDetails = [];
$total = 0;

if (!empty($cart)) {
    $plateIds = array_keys($cart);

    // Build query
    $placeholders = implode(',', array_fill(0, count($plateIds), '?'));
    $sql = "SELECT id, title, description, price FROM plates WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($plateIds);
    $rows = $stmt->fetchAll();

    // Index by plate id
    foreach ($rows as $row) {
        $plateDetails[$row['id']] = $row;
    }

    // Compute total
    foreach ($cart as $pid => $qty) {
        if (!isset($plateDetails[$pid])) continue;
        $price = $plateDetails[$pid]['price'];
        $total += $price * $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Donor-Cart-Dashboard</title>
		
		<!-- External CSS -->
		<link rel="stylesheet" href="../../assets/css/donor-cart.css">
	</head>

    <body>
        <div class="page">
            <a href="donor-dashboard.php" class="back-button">Back</a>

            <?php if ($flash): ?>
                <div class="flash-message">
                    <?= htmlspecialchars($flash) ?>
                </div>
            <?php endif; ?>

            <h1 class="cart-title">Donor Cart</h1>

            <div class="cart-wrapper">
                <!-- LEFT PANEL -->
                <div class="left-panel">
                    <div class="section-title">Donor Details</div>
                    <div class="info-pill">
                        <?= htmlspecialchars($donorName) ?>
                    </div>
                    <div class="info-pill">
                        <?= htmlspecialchars($donorPhone) ?>
                    </div>

                    <div class="section-title">Payment Method</div>
                    <div class="info-pill">
                        <?= htmlspecialchars($paymentDisplay) ?>
                    </div>
                </div>

                <!-- RIGHT PANEL -->
                <div class="right-panel">
                    <div class="right-title">Plates In Cart</div>

                    <?php if (empty($cart)): ?>
                        <p>No plates in your donation cart yet.</p>
                    <?php else: ?>
                        <?php foreach ($cart as $pid => $qty): ?>
                            <?php
                                if (!isset($plateDetails[$pid])) continue;
                                $plate = $plateDetails[$pid];
                                $lineTotal = $plate['price'] * $qty;
                            ?>
                            <div class="cart-item">
                                <div class="item-header">
                                    <!-- Plate title -->
                                    <div><?= htmlspecialchars($plate['title']) ?></div>

                                    <!-- Price and quantity -->
                                    <div class="price-qty">
                                        $<?= number_format($lineTotal, 2) ?><br>
                                        <?= (int)$qty . ' item' . ($qty > 1 ? 's' : '') ?>
                                    </div>
                                </div>

                                <div class="item-body">
                                    <div class="item-desc">
                                        <?= htmlspecialchars($plate['description']) ?>
                                    </div>

                                    <!-- X to remove from cart -->
                                    <form method="post" action="cancel_donation.php">
                                        <input type="hidden" name="plate_id" value="<?= (int)$pid ?>">
                                        <button type="submit" class="trash">X</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Total at the bottom -->
                    <div class="total-line">
                        Total: <span>$<?= number_format($total, 2) ?></span></div>
                </div>
            </div>

            <div class="bottom-buttons">
                <!-- Donate Now posts to donate_now.php -->
                <form method="post" action="donate_now.php">
                    <button class="btn btn-order" type="submit">Donate Now</button>
                </form>
            </div>
        </div>
    </body> 
</html>