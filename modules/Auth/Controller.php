<?php
require_once '../../config/config.php'; // DB connection
header('Content-Type: application/json');

// Helper to hash sensitive info
function hashData($data) {
    return password_hash(trim($data), PASSWORD_DEFAULT);
}

// Determine which action to run
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        try {
            // --- Sanitize inputs ---
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $security_question = $_POST['security_question'] ?? '';
            $security_answer = $_POST['security_answer'] ?? '';

            // --- Payment info (may be null) ---
            $cardholder_name = trim($_POST['cardholder_name'] ?? '');
            $card_number = trim($_POST['card_number'] ?? '');
            $cvc = trim($_POST['cvc'] ?? '');
            $expiration_date = trim($_POST['expiration_date'] ?? '');

            // --- Basic validation ---
            if (!$username || !$password || !$role || !$full_name || !$address || !$security_question || !$security_answer) {
                echo json_encode(["status" => "error", "message" => "Missing required fields"]);
                exit;
            }

            // --- Check if username already exists ---
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode([
                    "status" => "error",
                    "field" => "username",
                    "message" => "Username already exists"
                ]);
                exit;
            }

            // --- Hash password & security answer only ---
            $hashedPassword = hashData($password);
            $hashedSecurityQuestion = hashData($security_question);
            $hashedSecurityAnswer = hashData($security_answer);

            // Basic server-side payment field sanitation (only if role requires and fields provided)
            $card_number_clean = preg_replace('/\D/', '', $card_number); // digits only
            $cvc_clean = preg_replace('/\D/', '', $cvc); // digits only
            $expiration_date_clean = strtoupper(trim($expiration_date)); // keep MM/YY format

            // Validate payment fields only if user role needs them and card number was provided
            $needsPayment = in_array($role, ['customer', 'donor']);
            if ($needsPayment && $card_number_clean) {
                if (!preg_match('/^\d{13,19}$/', $card_number_clean)) {
                    echo json_encode(["status" => "error", "message" => "Invalid card number format"]);
                    exit;
                }
                if ($cvc_clean && !preg_match('/^\d{3,4}$/', $cvc_clean)) {
                    echo json_encode(["status" => "error", "message" => "Invalid CVC format"]);
                    exit;
                }
                if ($expiration_date_clean && !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiration_date_clean)) {
                    echo json_encode(["status" => "error", "message" => "Invalid expiration date format"]);
                    exit;
                }
            }

            // --- Insert into users table ---
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, security_question, security_answer)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hashedPassword, $role, $hashedSecurityQuestion, $hashedSecurityAnswer]);
            $user_id = $pdo->lastInsertId();

            // --- Insert into profiles table ---
            $stmt = $pdo->prepare("
                INSERT INTO profiles (user_id, full_name, address, phone)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $full_name, $address, $phone]);

            // --- Insert into payment_info if applicable ---
            // Insert payment info only if role requires AND a card number was actually submitted.
            if ($needsPayment && $card_number_clean) {
                // Store masked card number (last 4) instead of full number for minimal exposure; omit CVC entirely if blank.
                $masked_card = str_repeat('*', max(strlen($card_number_clean) - 4, 0)) . substr($card_number_clean, -4);
                $stmt = $pdo->prepare("
                    INSERT INTO payment_info (user_id, card_number, cvc, expiration_date, cardholder_name)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $masked_card,
                    $cvc_clean ?: null,
                    $expiration_date_clean ?: null,
                    $cardholder_name ?: null
                ]);
            }

            echo json_encode(["status" => "success"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

        case 'login':
        try {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$password) {
                echo json_encode(["status" => "error", "message" => "Missing required fields"]);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "field" => "password",
                    "message" => "Incorrect Username or Password"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    
        default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}
?>