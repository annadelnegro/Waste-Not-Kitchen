<?php
// Waste-Not-Kitchen - Needy cancels a reserved donated plate

session_start();
require_once __DIR__ . '/../../config/config.php';

// TEMP: needy
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3;
}
$needy_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: needy-cart.php');
    exit;
}

$donation_id = isset($_POST['donation_id']) ? (int)$_POST['donation_id'] : 0;

if ($donation_id <= 0) {
    header('Location: needy-cart.php');
    exit;
}

// Make sure this row belongs to this needy
$stmt = $pdo->prepare("
    SELECT id
    FROM donations
    WHERE id = :did
      AND needy_id = :nid
    LIMIT 1
");
$stmt->execute([
    ':did' => $donation_id,
    ':nid' => $needy_id,
]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash_message'] = 'Reservation not found.';
    header('Location: needy-cart.php');
    exit;
}

// Just unassign it: back to general donated pool
$update = $pdo->prepare("
    UPDATE donations
    SET needy_id = NULL
    WHERE id = :did
");
$update->execute([':did' => $donation_id]);

$_SESSION['flash_message'] = 'Reservation cancelled. Plate returned to the donated pool.';

header('Location: needy-cart.php');
exit;