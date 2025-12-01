<?php
// Waste-Not-Kitchen Needy Dashboard
session_start();

require_once __DIR__ . '/../../config/config.php';

// TEMP: pretend user with id 3 is the needy user
// (later your login system will set this)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3;
}
$needy_id = (int)$_SESSION['user_id'];

// Flash message
$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// How many plates this needy already has reserved?
$reservedCountStmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity), 0) AS total_reserved
    FROM donations
    WHERE needy_id = :nid
");
$reservedCountStmt->execute([':nid' => $needy_id]);
$reservedRow = $reservedCountStmt->fetch();
$currentReserved = (int)$reservedRow['total_reserved'];
$remainingAllowed = max(0, 2 - $currentReserved);

// Get all donated plates that are not yet reserved by any needy
// Aggregate quantity by plate
$sql = "
    SELECT
        d.plate_id,
        SUM(d.quantity) AS available_qty,
        p.title,
        p.description
    FROM donations d
    JOIN plates p ON d.plate_id = p.id
    WHERE d.needy_id IS NULL
    GROUP BY d.plate_id, p.title, p.description
    HAVING SUM(d.quantity) > 0
";
$stmt = $pdo->query($sql);
$availablePlates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Needy-Dashboard</title>
		
		<!-- External CSS -->
		<link rel="stylesheet" href="../../assets/css/needy-dashboard.css">
	</head>
	
	<body>
		<div class="page">
			<a href="needy-cart.php" class="cart-button">See Reservation</a>

			<a href="../.." class="back-button">Back</a>

            <?php if ($flash): ?>
                <div class="flash-message">
                    <?= htmlspecialchars($flash) ?>
                </div>
            <?php endif; ?>

			<h1 class="dashboard-title">Needy Dashboard</h1>

			<h2 class="plates-avail">Plates Donated and Available</h2>

			<section class="card-grid">
				<?php if (empty($availablePlates)): ?>
                    <p>No donated plates available right now.</p>
                <?php else: ?>
                    <?php foreach ($availablePlates as $plate): ?>
                        <?php
                            $availableQty = (int)$plate['available_qty'];
                            // front-end max: can't exceed both donated qty and remaining allowed
                            $maxSelectable = min($availableQty, max(0, $remainingAllowed));
                        ?>
                        <article class="plate-card">
                            <div class="plate-title">
                                <?= htmlspecialchars($plate['title']) ?>
                            </div>

                            <div class="description-box">
                                <?= htmlspecialchars($plate['description']) ?>
                            </div>

                            <div class="meta-row">
                                <div class="pill">
                                    <?= $availableQty ?> available
                                </div>
                            </div>

                            <div class="action-row">
                                <?php if ($maxSelectable <= 0): ?>
                                    <div class="pill">Reservation limit reached</div>
                                <?php else: ?>
                                    <form method="post" action="needy_reserve.php" class="action-row">
                                        <input type="hidden" name="plate_id" value="<?= (int)$plate['plate_id'] ?>">
                                        <input
                                            class="qty-input"
                                            type="number"
                                            name="qty"
                                            min="1"
                                            max="<?= $maxSelectable ?>"
                                            value="1"
                                        >
                                        <button type="submit" class="add-btn">Reserve</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
			</section>
		</div>
	</body>
</html>