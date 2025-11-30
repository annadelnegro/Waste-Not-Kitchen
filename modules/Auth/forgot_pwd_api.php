<?php
// Minimal standalone forgot password API to avoid Controller complexity during debugging.
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

try {
    // Support both form-encoded (FormData) and raw JSON bodies
    $rawInput = file_get_contents('php://input');
    $body = [];
    if (!empty($_POST)) {
        $body = $_POST;
    } elseif ($rawInput) {
        $decoded = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $body = $decoded;
        }
    }

    // small debug dump to help diagnose missing fields (will be removed later)
    @file_put_contents(__DIR__ . '/../../tmp/forgot_request_debug.txt', date('c') . "\n" . $rawInput . "\n" . print_r($body, true) . "\n---\n", FILE_APPEND);

    $username = trim($body['username'] ?? '');
    $security_answer = trim($body['security_answer'] ?? '');
    $new_password = trim($body['new_password'] ?? '');
    $confirm_password = trim($body['confirm_password'] ?? '');

    // Step 1: username exists -> return security question
    if ($username && !$security_answer && !$new_password) {
        $stmt = $pdo->prepare("SELECT security_question FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(["status" => "error", "message" => "Oops! That username does not exist"]);
            exit;
        }
        echo json_encode(["status" => "success", "security_question" => $user['security_question']]);
        exit;
    }

    // Step 2: verify security answer
    if ($username && $security_answer && !$new_password) {
        $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($security_answer, $user['security_answer'])) {
            echo json_encode(["status" => "error", "message" => "Incorrect answer. Try again"]);
            exit;
        }
        echo json_encode(["status" => "success"]);
        exit;
    }

    // Step 3: reset password
    if ($username && $new_password && $confirm_password) {
        if ($new_password !== $confirm_password) {
            echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
            exit;
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $new_password)) {
            echo json_encode(["status" => "error", "message" => "Password does not meet requirements"]);
            exit;
        }
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed, $username]);
        echo json_encode(["status" => "success"]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid request"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
