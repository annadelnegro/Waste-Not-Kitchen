<?php
// Waste-Not-Kitchen - Reserve plate (add to orders)

session_start();
require_once __DIR__ . '/../../config/config.php';

// Require logged-in user. If none, send them to login to avoid FK errors
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please sign in to reserve plates.';
    header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: customer-dashboard.php');
    exit;
}

// Read form data
$plate_id = isset($_POST['plate_id']) ? (int)$_POST['plate_id'] : 0;
$qty      = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

// Basic validation
if ($plate_id <= 0 || $qty <= 0) {
    $_SESSION['flash_message'] = 'Invalid reservation request.';
    header('Location: customer-dashboard.php');
    exit;
}

// Look up the plate
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
    header('Location: customer-dashboard.php');
    exit;
}

$available = (int)$plate['quantity'];
if ($available <= 0) {
    $_SESSION['flash_message'] = 'This plate is no longer available.';
    header('Location: customer-dashboard.php');
    exit;
}

// Clamp quantity to available
if ($qty > $available) {
    $qty = $available;
}

// Check if there is already a reserved order for this user + plate
$stmt = $pdo->prepare("
    SELECT id, quantity
    FROM orders
    WHERE buyer_id = :uid
      AND plate_id = :pid
      AND status = 'reserved'
    LIMIT 1
");
$stmt->execute([
    ':uid' => $user_id,
    ':pid' => $plate_id,
]);
$existingOrder = $stmt->fetch();

try {
    // Verify session user exists in users table to avoid FK errors
    $ucheck = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $ucheck->execute([$user_id]);
    $userRow = $ucheck->fetch();
    if (!$userRow) {
        // Log for debugging and send friendly message
        error_log("reserve_order: session user_id={$user_id} not found in users table");
        $_SESSION['flash_message'] = 'Your session is invalid. Please sign in again.';
        header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
        exit;
    }

    $pdo->beginTransaction();

    if ($existingOrder) {
        // Merge quantities: old + new
        $newQty = (int)$existingOrder['quantity'] + $qty;

        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET quantity = :newQty
            WHERE id = :order_id
        ");
        $updateStmt->execute([
            ':newQty'   => $newQty,
            ':order_id' => $existingOrder['id'],
        ]);
    } else {
        // No existing reservation: create a new row
        $insertStmt = $pdo->prepare("
            INSERT INTO orders (plate_id, buyer_id, quantity, status)
            VALUES (:plate_id, :buyer_id, :qty, 'reserved')
        ");
        $insertStmt->execute([
            ':plate_id' => $plate_id,
            ':buyer_id' => $user_id,
            ':qty'      => $qty,
        ]);
    }

    // Decrease plate quantity by the amount reserved
    $updatePlate = $pdo->prepare("
        UPDATE plates
        SET quantity = quantity - :qty
        WHERE id = :id
    ");
    $updatePlate->execute([
        ':qty' => $qty,
        ':id'  => $plate_id,
    ]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Error reserving plate: ' . $e->getMessage();
}

// Redirect back to dashboard (NOT to cart)
header('Location: customer-dashboard.php');
exit;