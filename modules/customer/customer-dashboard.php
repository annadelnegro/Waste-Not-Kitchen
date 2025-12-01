<?php
// Waste-Not-Kitchen Customer Dashboard
session_start();

// bring in PDO connection
require_once __DIR__ . '/../../config/config.php';

// read and clear flash message, if any
$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// get all available plates (respect restaurant availability window)
$sql = "SELECT id, title, description, price, quantity, available_from, available_until
				FROM plates
				WHERE quantity > 0
					AND (available_from IS NULL OR available_from <= NOW())
					AND (available_until IS NULL OR available_until >= NOW())";
$stmt = $pdo->query($sql);
$plates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Customer-Dashboard</title>
		
		<!-- External CSS -->
		<link rel="stylesheet" href="../../assets/css/customer-dashboard.css">
	</head>
	
	<body>
		<div class="page">
			<a href="customer-cart.php" class="cart-button">See Reservation</a>

			<a href="/Waste-Not-Kitchen/modules/Auth/views/profile.php" class="back-button">Back to Profile</a>

			<!-- Flash message showing -->
			<?php if ($flash): ?>
				<div class="flash-message">
					<?= htmlspecialchars($flash) ?>
				</div>
			<?php endif; ?>

			<h1 class="dashboard-title">Customer Dashboard</h1>

			<h2 class="plates-avail">Plates Available</h2>

			<section class="card-grid">
				<?php if (empty($plates)): ?>
					<p>No plates available right now.</p>
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

							<!-- Price + quantity -->
							<div class="meta-row">
								<div class="pill">
									$<?= number_format($plate['price'], 2) ?>
								</div>
								<div class="pill">
									<?= (int)$plate['quantity'] ?> available
								</div>
							</div>

							<?php if (!empty($plate['available_until'])): ?>
								<div style="color:red;font-size:0.9rem;margin-top:6px;margin-bottom:6px;">Available until <?= htmlspecialchars(date('m/d/y', strtotime($plate['available_until']))) ?></div>
							<?php endif; ?>

							<!-- Reserve form (posts plate_id + qty) -->
							<div class="action-row">
								<form method="post" action="reserve_order.php" class="action-row">
									<input type="hidden" name="plate_id" value="<?= (int)$plate['id'] ?>">
									<input
										class="qty-input"
										type="number"
										name="qty"
										min="1"
										max="<?= (int)$plate['quantity'] ?>"
										value="1"
									>
									<button type="submit" class="add-btn">Reserve</button>
								</form>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		</div>
	</body>
</html>