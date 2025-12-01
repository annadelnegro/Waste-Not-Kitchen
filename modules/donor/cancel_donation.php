<?php
// Waste-Not-Kitchen - Remove item from donor cart and unreserve it

session_start();
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: donor-cart.php');
    exit;
}

$plate_id = isset($_POST['plate_id']) ? (int)$_POST['plate_id'] : 0;

if ($plate_id <= 0 || !isset($_SESSION['donor_cart'][$plate_id])) {
    header('Location: donor-cart.php');
    exit;
}

$qty = (int)$_SESSION['donor_cart'][$plate_id];

try {
    $pdo->beginTransaction();

    // Restore those plates back to the plates table
    $updatePlate = $pdo->prepare("
        UPDATE plates
        SET quantity = quantity + :qty
        WHERE id = :pid
    ");
    $updatePlate->execute([
        ':qty' => $qty,
        ':pid' => $plate_id,
    ]);

    // Remove from session cart
    unset($_SESSION['donor_cart'][$plate_id]);

    $pdo->commit();

    $_SESSION['flash_message'] = 'Removed reserved plates from your cart and restored availability.';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Error cancelling donation: ' . $e->getMessage();
}

header('Location: donor-cart.php');
exit;