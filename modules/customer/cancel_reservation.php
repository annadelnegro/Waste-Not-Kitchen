<?php
// Waste-Not-Kitchen - Cancel a reservation (remove from orders and restore plate quantity)

session_start();
require_once __DIR__ . '/../../config/config.php';

// Require logged-in user (do not set temp user id)
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please sign in to manage your reservations.';
    header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: customer-cart.php');
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    header('Location: customer-cart.php');
    exit;
}

// Look up the order (and make sure it belongs to this user and is reserved)
$stmt = $pdo->prepare("
    SELECT id, plate_id, quantity
    FROM orders
    WHERE id = :oid
      AND buyer_id = :uid
      AND status = 'reserved'
    LIMIT 1
");
$stmt->execute([
    ':oid' => $order_id,
    ':uid' => $user_id,
]);
$order = $stmt->fetch();

if (!$order) {
    // nothing to do or not owned by user
    header('Location: customer-cart.php');
    exit;
}

$plate_id = (int)$order['plate_id'];
$qty      = (int)$order['quantity'];

try {
    $pdo->beginTransaction();

    // Restore quantity to the plate
    $updatePlate = $pdo->prepare("
        UPDATE plates
        SET quantity = quantity + :qty
        WHERE id = :pid
    ");
    $updatePlate->execute([
        ':qty' => $qty,
        ':pid' => $plate_id,
    ]);

    // Remove the order row entirely
    $deleteOrder = $pdo->prepare("
        DELETE FROM orders
        WHERE id = :oid
    ");
    $deleteOrder->execute([':oid' => $order_id]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
}

header('Location: customer-cart.php');
exit;