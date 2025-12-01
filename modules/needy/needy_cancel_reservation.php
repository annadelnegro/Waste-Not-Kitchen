<?php
// Waste-Not-Kitchen - Needy cancels a reserved donated plate

session_start();
require_once __DIR__ . '/../../config/config.php';

// Require logged-in user (do not set a temp needy id)
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please sign in to manage your reserved plates.';
    header('Location: /Waste-Not-Kitchen/modules/Auth/views/login.php');
    exit;
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
# Make sure this row belongs to this needy and hasn't been picked up (claimed)
$stmt = $pdo->prepare("
    SELECT id, status
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

// Do not allow cancelling if the donation was already picked up
if (isset($row['status']) && strtolower($row['status']) === 'claimed') {
    $_SESSION['flash_message'] = 'Cannot cancel: this reservation has already been picked up.';
    header('Location: needy-cart.php');
    exit;
}

// Unassign it and mark it available again so others can reserve
$update = $pdo->prepare("
    UPDATE donations
    SET needy_id = NULL, status = 'available'
    WHERE id = :did
");
$update->execute([':did' => $donation_id]);

$_SESSION['flash_message'] = 'Reservation cancelled. Plate returned to the donated pool.';

header('Location: needy-cart.php');
exit;