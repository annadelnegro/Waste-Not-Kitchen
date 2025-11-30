<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || strtolower((string)($_SESSION['role'] ?? '')) !== 'restaurant') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../utils/Db.php';

try {
    $pdo = Db::conn();

    $stmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $rest = $stmt->fetch();
    if (!$rest) {
        echo json_encode(['status' => 'success', 'plates' => []]);
        exit;
    }

    $rid = (int)$rest['id'];
    $pstmt = $pdo->prepare('SELECT id, title, description, price, quantity, available_from, available_until FROM plates WHERE restaurant_id = ? ORDER BY id DESC');
    $pstmt->execute([$rid]);
    $plates = $pstmt->fetchAll();

    echo json_encode(['status' => 'success', 'plates' => $plates]);
    exit;
} catch (Throwable $e) {
    // Don't leak internal errors to clients
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit;
}

?>
