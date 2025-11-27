<?php
// Waste-Not-Kitchen Needy Cart Dashboard
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

            <h1 class="cart-title">Needy Reservation</h1>

            <div class="cart-wrapper">
            <!-- LEFT PANEL -->
            <div class="left-panel">
                <div class="section-title">Needy Details</div>
                <div class="info-pill">Needy name</div>
                <div class="info-pill">Needy phone number</div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="right-panel">
                <div class="right-title">Plates Reserved</div>

                <!-- Cart item 1 -->
                <div class="cart-item">
                <div class="item-header">
                    <div>Dish A</div>
                    <div class="price-qty">
                    1 item
                    </div>
                </div>
                <div class="item-body">
                    <div class="item-desc">Description here</div>
                    <div class="trash">X</div>
                </div>
                </div>

                <!-- Cart item 2 -->
                <div class="cart-item">
                <div class="item-header">
                    <div>Dish B</div>
                    <div class="price-qty">
                    1 item
                    </div>
                </div>
                <div class="item-body">
                    <div class="item-desc">Description here</div>
                    <div class="trash">X</div>
                </div>
                </div>
            </div>
            </div>
            </div>
        </div>
    </body> 
</html>