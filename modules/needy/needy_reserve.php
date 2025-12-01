<?php
// Waste-Not-Kitchen - Needy reserves donated plates

session_start();
require_once __DIR__ . '/../../config/config.php';

// TEMP: pretend user with id 3 is the needy user
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3;
}
$needy_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: needy-dashboard.php');
    exit;
}

$plate_id = isset($_POST['plate_id']) ? (int)$_POST['plate_id'] : 0;
$qty      = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

if ($plate_id <= 0 || $qty <= 0) {
    $_SESSION['flash_message'] = 'Invalid reservation request.';
    header('Location: needy-dashboard.php');
    exit;
}

// How many already reserved?
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity), 0) AS total_reserved
    FROM donations
    WHERE needy_id = :nid
");
$stmt->execute([':nid' => $needy_id]);
$row = $stmt->fetch();
$currentReserved = (int)$row['total_reserved'];

$remainingAllowed = max(0, 2 - $currentReserved);
if ($remainingAllowed <= 0) {
    $_SESSION['flash_message'] = 'You already have 2 plates reserved.';
    header('Location: needy-dashboard.php');
    exit;
}

// Clamp requested qty to remaining allowed
$qty = min($qty, $remainingAllowed);

// Get available donated quantity for this plate
$stmt = $pdo->prepare("
    SELECT id, donor_id, plate_id, quantity
    FROM donations
    WHERE plate_id = :pid
      AND needy_id IS NULL
    ORDER BY donated_at ASC
");
$stmt->execute([':pid' => $plate_id]);
$poolRows = $stmt->fetchAll();

$totalAvailable = 0;
foreach ($poolRows as $r) {
    $totalAvailable += (int)$r['quantity'];
}

if ($totalAvailable <= 0) {
    $_SESSION['flash_message'] = 'No donated plates of this type available.';
    header('Location: needy-dashboard.php');
    exit;
}

// Clamp again to what’s actually available
$qty = min($qty, $totalAvailable);

if ($qty <= 0) {
    $_SESSION['flash_message'] = 'Unable to reserve requested quantity.';
    header('Location: needy-dashboard.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $remainingToTake = $qty;

    foreach ($poolRows as $row) {
        if ($remainingToTake <= 0) break;

        $donationId  = (int)$row['id'];
        $donorId     = (int)$row['donor_id'];
        $rowQty      = (int)$row['quantity'];

        if ($rowQty <= 0) continue;

        $take = min($rowQty, $remainingToTake);

        if ($take === $rowQty) {
            // Use this whole row: just assign needy_id
            $update = $pdo->prepare("
                UPDATE donations
                SET needy_id = :nid
                WHERE id = :did
            ");
            $update->execute([
                ':nid' => $needy_id,
                ':did' => $donationId,
            ]);
        } else {
            // Split the row: reduce pool row, create new row for needy
            $updatePool = $pdo->prepare("
                UPDATE donations
                SET quantity = quantity - :take
                WHERE id = :did
            ");
            $updatePool->execute([
                ':take' => $take,
                ':did'  => $donationId,
            ]);

            $insertNeedy = $pdo->prepare("
                INSERT INTO donations (donor_id, needy_id, plate_id, quantity)
                VALUES (:donor_id, :needy_id, :plate_id, :qty)
            ");
            $insertNeedy->execute([
                ':donor_id' => $donorId,
                ':needy_id' => $needy_id,
                ':plate_id' => $plate_id,
                ':qty'      => $take,
            ]);
        }

        $remainingToTake -= $take;
    }

    $pdo->commit();

    $_SESSION['flash_message'] = 'Reserved ' . $qty . ' plate(s).';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Error reserving plates: ' . $e->getMessage();
}

header('Location: needy-dashboard.php');
exit;
