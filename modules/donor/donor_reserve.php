<?php
// Waste-Not-Kitchen - Donor adds plates to donation cart and reserves them

session_start();
require_once __DIR__ . '/../../config/config.php';

// TEMP: fake a logged-in donor until login system is wired up
if (!isset($_SESSION['user_id'])) {
    // make sure this user exists in your `users` table and has role 'donor'
    $_SESSION['user_id'] = 1; 
}
$donor_id = (int)$_SESSION['user_id'];

// Only allow POST from the donor dashboard form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: donor-dashboard.php');
    exit;
}

// Read plate_id and qty from the form
$plate_id = isset($_POST['plate_id']) ? (int)$_POST['plate_id'] : 0;
$qty      = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

// Basic validation
if ($plate_id <= 0 || $qty <= 0) {
    $_SESSION['flash_message'] = 'Invalid donation request.';
    header('Location: donor-dashboard.php');
    exit;
}

// Look up the plate to ensure it exists and get available quantity + title
$stmt = $pdo->prepare("
    SELECT id, title, quantity
    FROM plates
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $plate_id]);
$plate = $stmt->fetch();

if (!$plate) {
    $_SESSION['flash_message'] = 'Plate not found.';
    header('Location: donor-dashboard.php');
    exit;
}

$available = (int)$plate['quantity'];
if ($available <= 0) {
    $_SESSION['flash_message'] = 'This plate is no longer available to fund.';
    header('Location: donor-dashboard.php');
    exit;
}

// Initialize donor cart if needed
if (!isset($_SESSION['donor_cart'])) {
    $_SESSION['donor_cart'] = [];
}
$cart = $_SESSION['donor_cart'];

// How many are already reserved in the cart for this plate?
$currentInCart = isset($cart[$plate_id]) ? (int)$cart[$plate_id] : 0;

// We only need to reserve the *additional* amount
// Max we can reserve = what's still available in plates
if ($qty > $available) {
    $qty = $available;
}

if ($qty <= 0) {
    $_SESSION['flash_message'] = 'No more of this plate is available to reserve.';
    header('Location: donor-dashboard.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Decrease plates.quantity by the amount we are adding now
    $updatePlate = $pdo->prepare("
        UPDATE plates
        SET quantity = quantity - :qty
        WHERE id = :pid
    ");
    $updatePlate->execute([
        ':qty' => $qty,
        ':pid' => $plate_id,
    ]);

    // Update session cart
    $cart[$plate_id] = $currentInCart + $qty;
    $_SESSION['donor_cart'] = $cart;

    $pdo->commit();

    $_SESSION['flash_message'] = 'Reserved ' . $qty . ' of "' . $plate['title'] . '."';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Error reserving plate: ' . $e->getMessage();
}

header('Location: donor-dashboard.php');
exit;