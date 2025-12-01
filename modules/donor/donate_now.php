<?php
// Waste-Not-Kitchen - Process donor payment and convert reserved plates to donations

session_start();
require_once __DIR__ . '/../../config/config.php';

// Require logged-in donor (do not set a temp donor id)
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please sign in to complete your donation.';
    header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
    exit;
}
$donor_id = (int)$_SESSION['user_id'];

// Get cart from session
$cart = isset($_SESSION['donor_cart']) ? $_SESSION['donor_cart'] : [];

if (empty($cart)) {
    $_SESSION['flash_message'] = 'No items to donate.';
    header('Location: donor-cart.php');
    exit;
}

// Fetch plate info for all items in cart (for price)
$plateIds = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($plateIds), '?'));

$sql = "SELECT id, price FROM plates WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($plateIds);
$rows = $stmt->fetchAll();

// Index by plate id
$plateInfo = [];
foreach ($rows as $row) {
    $plateInfo[$row['id']] = $row;
}

// Compute total
$total = 0;
foreach ($cart as $pid => $qty) {
    if (!isset($plateInfo[$pid])) {
        $_SESSION['flash_message'] = 'One of the plates in your cart no longer exists.';
        header('Location: donor-cart.php');
        exit;
    }
    $total += $plateInfo[$pid]['price'] * $qty;
}

// Get latest payment info id (if any)
$paymentInfoId = null;
$payStmt = $pdo->prepare("
    SELECT id
    FROM payment_info
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 1
");
$payStmt->execute([':uid' => $donor_id]);
if ($row = $payStmt->fetch()) {
    $paymentInfoId = (int)$row['id'];
}

try {
    $pdo->beginTransaction();

    // Insert donation rows for each reserved plate
    $insertDonation = $pdo->prepare("
        INSERT INTO donations (donor_id, needy_id, plate_id, quantity)
        VALUES (:donor_id, NULL, :plate_id, :qty)
    ");

    foreach ($cart as $pid => $qty) {
        $insertDonation->execute([
            ':donor_id' => $donor_id,
            ':plate_id' => (int)$pid,
            ':qty'      => (int)$qty,
        ]);
    }

    // Insert a payment record
    $insertPay = $pdo->prepare("
        INSERT INTO payments (user_id, order_id, payment_info_id, amount, payment_status)
        VALUES (:uid, NULL, :payment_info_id, :amount, 'completed')
    ");
    $insertPay->execute([
        ':uid'             => $donor_id,
        ':payment_info_id' => $paymentInfoId,
        ':amount'          => $total,
    ]);

    // Clear the donor cart (reservations are now committed)
    $_SESSION['donor_cart'] = [];

    $pdo->commit();

    $_SESSION['flash_message'] = 'Donation payment processed for $' . number_format($total, 2) . '. Your donated plates are now available to the needy.';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Error processing donation payment: ' . $e->getMessage();
}

header('Location: donor-cart.php');
exit;