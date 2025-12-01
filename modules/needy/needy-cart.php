<?php
// Waste-Not-Kitchen Needy Cart Dashboard
session_start();

require_once __DIR__ . '/../../config/config.php';

// TEMP: needy user id
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3;
}
$needy_id = (int)$_SESSION['user_id'];

// Flash
$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Optional: needy details from profiles
$needyName  = 'Needy name';
$needyPhone = 'Needy phone number';

$profileStmt = $pdo->prepare("
    SELECT full_name, phone
    FROM profiles
    WHERE user_id = :uid
    LIMIT 1
");
$profileStmt->execute([':uid' => $needy_id]);
if ($row = $profileStmt->fetch()) {
    if (!empty($row['full_name']))  $needyName  = $row['full_name'];
    if (!empty($row['phone']))      $needyPhone = $row['phone'];
}

// Get reserved donations for this needy user
$sql = "
    SELECT
        d.id AS donation_id,
        d.quantity,
        p.title,
        p.description
    FROM donations d
    JOIN plates p ON d.plate_id = p.id
    WHERE d.needy_id = :nid
    ORDER BY d.donated_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':nid' => $needy_id]);
$cartItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Needy-Cart-Dashboard</title>
		
		<!-- External CSS -->
		<link rel="stylesheet" href="../../assets/css/needy-cart.css">
	</head>

    <body>
        <div class="page">
            <a href="needy-dashboard.php" class="back-button">Back</a>

            <?php if ($flash): ?>
                <div class="flash-message">
                    <?= htmlspecialchars($flash) ?>
                </div>
            <?php endif; ?>

            <h1 class="cart-title">Needy Reservation</h1>

            <div class="cart-wrapper">
                <!-- LEFT PANEL -->
                <div class="left-panel">
                    <div class="section-title">Needy Details</div>
                    <div class="info-pill">
                        <?= htmlspecialchars($needyName) ?>
                    </div>
                    <div class="info-pill">
                        <?= htmlspecialchars($needyPhone) ?>
                    </div>
                </div>

                <!-- RIGHT PANEL -->
                <div class="right-panel">
                    <div class="right-title">Plates Reserved</div>

                    <?php if (empty($cartItems)): ?>
                        <p>You have no reserved plates right now.</p>
                    <?php else: ?>
                        <?php foreach ($cartItems as $item): ?>
                            <?php $qty = (int)$item['quantity']; ?>
                            <div class="cart-item">
                                <div class="item-header">
                                    <div><?= htmlspecialchars($item['title']) ?></div>
                                    <div class="price-qty">
                                        <?= $qty . ' item' . ($qty > 1 ? 's' : '') ?>
                                    </div>
                                </div>
                                <div class="item-body">
                                    <div class="item-desc">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </div>

                                    <form method="post" action="needy_cancel_reservation.php">
                                        <input type="hidden" name="donation_id" value="<?= (int)$item['donation_id'] ?>">
                                        <button type="submit" class="trash">X</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body> 
</html>
