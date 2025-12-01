<?php
// Waste-Not-Kitchen Donor Dashboard
session_start();

// PDO connection
require_once __DIR__ . '/../../config/config.php';

// Clear flash message
$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Get all plates that are still available
$sql = "SELECT id, title, description, price, quantity FROM plates WHERE quantity > 0";
$stmt = $pdo->query($sql);
$plates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Donor-Dashboard</title>
		
		<!-- External CSS -->
		<link rel="stylesheet" href="../../assets/css/donor-dashboard.css">
	</head>
	
	<body>
		<div class="page">
			<a href="donor-cart.php" class="cart-button">Cart</a>
			<a href="../.." class="back-button">Back</a>

			<?php if ($flash): ?>
				<div class="flash-message">
					<?= htmlspecialchars($flash) ?>
				</div>
			<?php endif; ?>

			<h1 class="dashboard-title">Donor Dashboard</h1>

			<h2 class="plates-avail">Plates Available</h2>

			<section class="card-grid">
				<?php if (empty($plates)): ?>
					<p>No plates available to buy right now.</p>
				<?php else: ?>
					<?php foreach ($plates as $plate): ?>
						<article class="plate-card">
							<!-- Title -->
							<div class="plate-title">
								<?= htmlspecialchars($plate['title']) ?>
							</div>

							<!-- Description -->
							<div class="description-box">
								<?= htmlspecialchars($plate['description']) ?>
							</div>

							<!-- Price and quantity -->
							<div class="meta-row">
								<div class="pill">
									$<?= number_format($plate['price'], 2) ?>
								</div>
								<div class="pill">
									<?= (int)$plate['quantity'] ?> available
								</div>
							</div>

							<!-- Donate -->
							<div class="action-row">
								<form method="post" action="donor_reserve.php" class="action-row">
									<input type="hidden" name="plate_id" value="<?= (int)$plate['id'] ?>">
									<input
										class="qty-input"
										type="number"
										name="qty"
										min="1"
										max="<?= (int)$plate['quantity'] ?>"
										value="1"
									>
									<button type="submit" class="add-btn">Add to Cart</button>
								</form>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		</div>
	</body>
</html>