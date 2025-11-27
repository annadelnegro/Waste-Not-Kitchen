<?php
// Waste-Not-Kitchen Donor Cart Dashboard
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

            <h1 class="cart-title">Donor Cart</h1>

            <div class="cart-wrapper">
            <!-- LEFT PANEL -->
            <div class="left-panel">
                <div class="section-title">Donor Details</div>
                <div class="info-pill">Donor name</div>
                <div class="info-pill">Donor phone number</div>

                <div class="section-title">Payment Method</div>
                <div class="info-pill">Visa: ********9999</div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="right-panel">
                <div class="right-title">Plates In Cart</div>

                <!-- Cart item 1 -->
                <div class="cart-item">
                <div class="item-header">
                    <div>Dish A</div>
                    <div class="price-qty">
                    $20<br>1 item
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
                    $20<br>1 item
                    </div>
                </div>
                <div class="item-body">
                    <div class="item-desc">Description here</div>
                    <div class="trash">X</div>
                </div>
                </div>

                <!-- Cart item 3 -->
                <div class="cart-item">
                <div class="item-header">
                    <div>Dish C</div>
                    <div class="price-qty">
                    $20<br>1 item
                    </div>
                </div>
                <div class="item-body">
                    <div class="item-desc">Description here</div>
                    <div class="trash">X</div>
                </div>
                </div>

                <!-- Cart item 4 -->
                <div class="cart-item">
                <div class="item-header">
                    <div>Dish D</div>
                    <div class="price-qty">
                    $20<br>2 items
                    </div>
                </div>
                <div class="item-body">
                    <div class="item-desc">Description here</div>
                    <div class="trash">X</div>
                </div>
                </div>

                <div class="total-line">Total: <span>$100</span></div>
            </div>
            </div>

            <div class="bottom-buttons">
                <button class="btn btn-order">Donate Now</button>
            </div>
            </div>
        </div>
    </body> 
</html>