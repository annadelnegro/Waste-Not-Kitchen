<?php
// Waste-Not-Kitchen Donor Dashboard
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

			<h1 class="dashboard-title">Donor Dashboard</h1>

			<h2 class="plates-avail">Plates Available</h2>

			<section class="card-grid">
				<!-- Card 1 -->
				<article class="plate-card">
					<div class="plate-title">Dish A</div>
					<div class="description-box">Description</div>
					<div class="meta-row">
					<div class="pill">$20</div>
					<div class="pill">2 available</div>
					</div>
					<div class="action-row">
					<input class="qty-input" type="number" min="1" value="1">
					<button class="add-btn">Reserve</button>
					</div>
				</article>

				<!-- Card 2 -->
				<article class="plate-card">
					<div class="plate-title">Dish B</div>
					<div class="description-box">Description</div>
					<div class="meta-row">
					<div class="pill">$20</div>
					<div class="pill">2 available</div>
					</div>
					<div class="action-row">
					<input class="qty-input" type="number" min="1" value="1">
					<button class="add-btn">Reserve</button>
					</div>
				</article>

				<!-- Card 3 -->
				<article class="plate-card">
					<div class="plate-title">Dish C</div>
					<div class="description-box">Description</div>
					<div class="meta-row">
					<div class="pill">$20</div>
					<div class="pill">2 available</div>
					</div>
					<div class="action-row">
					<input class="qty-input" type="number" min="1" value="1">
					<button class="add-btn">Reserve</button>
					</div>
				</article>

				<!-- Card 4 -->
				<article class="plate-card">
					<div class="plate-title">Dish D</div>
					<div class="description-box">Description</div>
					<div class="meta-row">
					<div class="pill">$20</div>
					<div class="pill">3 available</div>
					</div>
					<div class="action-row">
					<input class="qty-input" type="number" min="1" value="1">
					<button class="add-btn">Reserve</button>
					</div>
				</article>

				<!-- Card 5 -->
				<article class="plate-card">
					<div class="plate-title">Dish E</div>
					<div class="description-box">Description</div>
					<div class="meta-row">
					<div class="pill">$20</div>
					<div class="pill">1 available</div>
					</div>
					<div class="action-row">
					<input class="qty-input" type="number" min="1" value="1">
					<button class="add-btn">Reserve</button>
					</div>
				</article>

				<!-- Card 6 -->
				<article class="plate-card">
					<div class="plate-title">Dish F</div>
					<div class="description-box">Description</div>
					<div class="meta-row">
					<div class="pill">$20</div>
					<div class="pill">2 available</div>
					</div>
					<div class="action-row">
					<input class="qty-input" type="number" min="1" value="1">
					<button class="add-btn">Reserve</button>
					</div>
				</article>
			</section>
		</div>
	</body>
</html>