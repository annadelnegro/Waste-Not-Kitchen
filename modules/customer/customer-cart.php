<?php
// Waste-Not-Kitchen Customer Cart Dashboard
session_start();

// Bring in the PDO connection
require_once __DIR__ . '/../../config/config.php';

// Require logged-in user (do not set a temporary test user here)
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please sign in to view your reservation.';
    header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Customer details from profiles
$customerName  = 'Customer name';
$customerPhone = 'Customer phone number';

$profileStmt = $pdo->prepare("
    SELECT full_name, phone
    FROM profiles
    WHERE user_id = :uid
    LIMIT 1
");
$profileStmt->execute([':uid' => $user_id]);
if ($row = $profileStmt->fetch()) {
    if (!empty($row['full_name'])) $customerName  = $row['full_name'];
    if (!empty($row['phone']))     $customerPhone = $row['phone'];
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
$payStmt->execute([':uid' => $user_id]);
if ($row = $payStmt->fetch()) {
    $digits = preg_replace('/\D/', '', $row['card_number']);
    $last4  = substr($digits, -4);
    $paymentDisplay = 'Visa: ********' . $last4;
}

// Get all reserved plates for this user
$sql = "
    SELECT
        o.id AS order_id,
        p.title,
        p.description,
        p.price,
        o.quantity
    FROM orders o
    JOIN plates p ON o.plate_id = p.id
    WHERE o.buyer_id = :uid
      AND o.status = 'reserved'
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $user_id]);
$cartItems = $stmt->fetchAll();

// Compute the total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Customer-Cart-Dashboard</title>
		
		<!-- External CSS -->
		<link rel="stylesheet" href="../../assets/css/customer-cart.css">
	</head>

    <body>
        <div class="page">
            <a href="customer-dashboard.php" class="back-button">Back</a>

            <h1 class="cart-title">Customer Reservation</h1>

            <div class="cart-wrapper">
                <!-- LEFT PANEL -->
                <div class="left-panel">
                    <div class="section-title">Customer Details</div>
                    <div class="info-pill">
                        <?= htmlspecialchars($customerName) ?>
                    </div>
                    <div class="info-pill">
                        <?= htmlspecialchars($customerPhone) ?>
                    </div>

                    <div class="section-title">Payment Method</div>
                    <div class="info-pill">
                        <?= htmlspecialchars($paymentDisplay) ?>
                    </div>
                </div>

                <!-- RIGHT PANEL -->
                <div class="right-panel">
                    <div class="right-title">Plates Reserved</div>

                    <?php if (empty($cartItems)): ?>
                        <p>No plates reserved yet.</p>
                    <?php else: ?>
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                                $lineTotal = $item['price'] * $item['quantity'];
                                $qty       = (int)$item['quantity'];
                            ?>
                            <div class="cart-item">
                                <div class="item-header">
                                    <!-- Plate title -->
                                    <div><?= htmlspecialchars($item['title']) ?></div>

                                    <!-- Price and quantity -->
                                    <div class="price-qty">
                                        $<?= number_format($lineTotal, 2) ?><br>
                                        <?= $qty . ' item' . ($qty > 1 ? 's' : '') ?>
                                    </div>
                                </div>

                                <div class="item-body">
                                    <div class="item-desc">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </div>

                                    <form method="post" action="cancel_reservation.php">
                                        <input type="hidden" name="order_id" value="<?= (int)$item['order_id'] ?>">
                                        <button type="submit" class="trash">X</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Total at the bottom -->
                    <div class="total-line">
                        Total: <span>$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </body> 
</html>