<?php
// Helper to send JSON and ensure no stray output corrupts the response
function send_json($data) {
    // Discard any buffered output (warnings, stray whitespace, etc.)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $json = json_encode($data);
    // Also write the raw JSON to a local debug file so we can inspect empty responses
    $debugFile = __DIR__ . '/../../tmp/last_response.json';
    @file_put_contents($debugFile, $json === false ? json_encode(['json_error' => json_last_error_msg(), 'data' => $data]) : $json);
    // Log the exact JSON we're sending to the error log
    error_log("Controller send_json: " . $json);
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: application/json');
    }
    echo $json;
    exit;
}

// Include config and ensure DB errors are returned as JSON instead of raw text
try {
    require_once __DIR__ . '/../../config/config.php'; // DB connection
} catch (Exception $e) {
    // Config threw during DB connection — return JSON error
    send_json(["status" => "error", "message" => $e->getMessage()]);
}

// `send_json()` will manage headers and buffer cleanup when sending responses.

// Start session management for login/logout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper to hash sensitive info
function hashData($data) {
    return password_hash(trim($data), PASSWORD_DEFAULT);
}

// Lightweight file logger for debugging (writes inside project so we can read it)
function debug_log($msg) {
    $file = __DIR__ . '/../../tmp/api_debug.log';
    $time = date('c');
    $line = "[$time] " . (is_string($msg) ? $msg : json_encode($msg)) . "\n";
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

// Capture fatal errors on shutdown to help debugging empty responses
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        debug_log(['shutdown_error' => $err]);
    }
});



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
                send_json(["status" => "error", "message" => "Missing required fields"]);
            }

            // --- Check if username already exists ---
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                send_json([
                    "status" => "error",
                    "field" => "username",
                    "message" => "Username already exists"
                ]);
            }

            // --- Hash password & security answer only ---
            $hashedPassword = hashData($password);
            $hashedSecurityAnswer = hashData($security_answer);

            // Basic server-side payment field sanitation (only if role requires and fields provided)
            $card_number_clean = preg_replace('/\D/', '', $card_number); // digits only
            $cvc_clean = preg_replace('/\D/', '', $cvc); // digits only
            $expiration_date_clean = strtoupper(trim($expiration_date)); // keep MM/YY format

            // Validate payment fields only if user role needs them and card number was provided
            $needsPayment = in_array($role, ['customer', 'donor']);
                if ($needsPayment && $card_number_clean) {
                    if (!preg_match('/^\d{13,19}$/', $card_number_clean)) {
                        send_json(["status" => "error", "message" => "Invalid card number format"]);
                    }
                    if ($cvc_clean && !preg_match('/^\d{3,4}$/', $cvc_clean)) {
                        send_json(["status" => "error", "message" => "Invalid CVC format"]);
                    }
                    if ($expiration_date_clean && !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiration_date_clean)) {
                        send_json(["status" => "error", "message" => "Invalid expiration date format"]);
                    }
                }

            // --- Insert into users table ---
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, security_question, security_answer)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hashedPassword, $role, $security_question, $hashedSecurityAnswer]);
            $user_id = $pdo->lastInsertId();

            // --- Insert into profiles table ---
            $stmt = $pdo->prepare("
                INSERT INTO profiles (user_id, full_name, address, phone)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $full_name, $address, $phone]);

            // --- If role is restaurant, insert into restaurants table (use full_name as restaurant_name) ---
            if (strtolower($role) === 'restaurant') {
                try {
                    $rstmt = $pdo->prepare("INSERT INTO restaurants (user_id, restaurant_name) VALUES (?, ?)");
                    $rstmt->execute([$user_id, $full_name]);
                } catch (Exception $e) {
                    // don't block registration if restaurants insert fails; consider logging the error
                }
            }

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

            send_json(["status" => "success"]);
        } catch (Exception $e) {
            send_json(["status" => "error", "message" => $e->getMessage()]);
        }
        // If we reached here without sending a response, send a generic error
        send_json(["status" => "error", "message" => "Invalid forgot password request"]);
        break;

    case 'update_profile':
        try {
            if (empty($_SESSION['user_id'])) send_json(['status'=>'error','message'=>'Unauthorized']);

            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            // Debug: log incoming profile update requests to help trace DB issues
            try {
                debug_log(['update_profile_called' => ['session_user_id' => $_SESSION['user_id'] ?? null, 'session_username' => $_SESSION['username'] ?? null, 'body' => $body]]);
            } catch (Exception $e) {
                // swallow logging errors
            }
            $field = $body['field'] ?? '';
            $value = trim($body['value'] ?? '');

            $allowed = ['full_name','phone','address'];
            if (!in_array($field, $allowed)) send_json(['status'=>'error','message'=>'Invalid field']);

            // Basic server validation
            if ($field === 'full_name' && $value === '') send_json(['status'=>'error','message'=>'Name required']);
            if ($field === 'phone' && $value === '') send_json(['status'=>'error','message'=>'Phone required']);

            // Ensure profiles row exists for this user
            $rstmt = $pdo->prepare('SELECT id FROM profiles WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $prof = $rstmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($prof) {
                $sql = "UPDATE profiles SET {$field} = ? WHERE user_id = ?";
                $ust = $pdo->prepare($sql);
                $ust->execute([$value ?: null, $_SESSION['user_id']]);
            } else {
                // Insert a new profile row with the provided field
                $full = null; $addr = null; $ph = null;
                if ($field === 'full_name') $full = $value;
                if ($field === 'address') $addr = $value;
                if ($field === 'phone') $ph = $value;
                $ist = $pdo->prepare('INSERT INTO profiles (user_id, full_name, address, phone) VALUES (?,?,?,?)');
                $ist->execute([$_SESSION['user_id'], $full, $addr, $ph]);
            }

            // Update session so subsequent page interactions reflect new data
            if ($field === 'full_name') $_SESSION['full_name'] = $value;
            if ($field === 'address') $_SESSION['address'] = $value;
            if ($field === 'phone') $_SESSION['phone'] = $value;

            send_json(['status'=>'success']);
        } catch (Exception $e) {
            send_json(['status'=>'error','message'=>$e->getMessage()]);
        }
        break;
    case 'get_orders':
        try {
            // Only allow logged-in restaurants
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') {
                send_json(['status' => 'error', 'message' => 'Unauthorized']);
            }

            $status = $_GET['status'] ?? '';
            $allowed = ['reserved','paid','picked_up'];
            if (!in_array($status, $allowed)) $status = 'reserved';

            // Find restaurant id for this user
            $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $rest = $rstmt->fetch(PDO::FETCH_ASSOC);
            if (!$rest) {
                // Fallback: try to find restaurant by username (in case session user_id mismatch)
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                    $fr->execute([$uname]);
                    $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$rest) {
                send_json(['status' => 'error', 'message' => 'no_rest_found', 'session' => ['user_id' => $_SESSION['user_id'] ?? null, 'username' => $_SESSION['username'] ?? null, 'role' => $_SESSION['role'] ?? null]]);
            }

                $sql = "SELECT o.id, o.quantity, o.status, p.title AS plate_title, p.price AS plate_price, u.username AS buyer_username
                    FROM orders o
                    JOIN plates p ON o.plate_id = p.id
                    JOIN users u ON o.buyer_id = u.id
                    WHERE p.restaurant_id = ? AND o.status = ?
                    ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$rest['id'], $status]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            send_json($rows);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_donations':
        try {
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') {
                send_json(['status' => 'error', 'message' => 'Unauthorized']);
            }

            $filter = $_GET['filter'] ?? '';
            $allowed = ['available','reserved','claimed'];
            if (!in_array($filter, $allowed)) $filter = 'available';

            // find restaurant id
            $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $rest = $rstmt->fetch(PDO::FETCH_ASSOC);
            if (!$rest) {
                // Fallback to username-based lookup
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                    $fr->execute([$uname]);
                    $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$rest) {
                send_json(['status' => 'error', 'message' => 'no_rest_found', 'session' => ['user_id' => $_SESSION['user_id'] ?? null, 'username' => $_SESSION['username'] ?? null, 'role' => $_SESSION['role'] ?? null]]);
            }

                // Filter by donation status column (available/reserved/claimed)
                    $sql = "SELECT d.id, d.quantity, d.needy_id, d.status, p.title AS plate_title, p.price AS plate_price, u.username AS donor_username, n.username AS needy_username
                        FROM donations d
                        JOIN plates p ON d.plate_id = p.id
                        JOIN users u ON d.donor_id = u.id
                        LEFT JOIN users n ON d.needy_id = n.id
                        WHERE p.restaurant_id = ? AND d.status = ?
                        ORDER BY d.donated_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$rest['id'], $filter]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            send_json($rows);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'confirm_order_pickup':
        try {
            // POST JSON body
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $order_id = intval($body['order_id'] ?? 0);
            if (!$order_id) send_json(['status' => 'error', 'message' => 'Missing order_id']);
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') send_json(['status' => 'error', 'message' => 'Unauthorized']);

            // Resolve restaurant id for this session (user_id or fallback by username)
            $rest = null;
            $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $rest = $rstmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$rest) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                    $fr->execute([$uname]);
                    $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$rest) send_json(['status' => 'error', 'message' => 'Not found or unauthorized']);

            // Verify restaurant owns the plate for this order (by restaurant id)
            $verify = $pdo->prepare('SELECT o.id FROM orders o JOIN plates p ON o.plate_id = p.id WHERE o.id = ? AND p.restaurant_id = ?');
            $verify->execute([$order_id, $rest['id']]);
            if (!$verify->fetch()) send_json(['status' => 'error', 'message' => 'Not found or unauthorized']);

            $ustmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $ustmt->execute(['picked_up', $order_id]);
            // Log current plate quantity for debugging (do not double-decrement here)
            try {
                $ost = $pdo->prepare('SELECT o.plate_id, o.quantity AS order_qty, p.quantity AS plate_qty FROM orders o JOIN plates p ON o.plate_id = p.id WHERE o.id = ? LIMIT 1');
                $ost->execute([$order_id]);
                $oinfo = $ost->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($oinfo) {
                    debug_log(['order_pickup' => ['order_id' => $order_id, 'plate_id' => $oinfo['plate_id'], 'order_qty' => intval($oinfo['order_qty']), 'plate_qty_before' => intval($oinfo['plate_qty'])]]);
                }
            } catch (Exception $e) {
                // ignore
            }

            send_json(['status' => 'success']);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_my_orders':
        try {
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'customer') {
                send_json(['status' => 'error', 'message' => 'Unauthorized']);
            }

            $status = $_GET['status'] ?? '';
            // allow 'reserved' or 'pay_and_pickup' (maps to paid OR picked_up)
            $allowed = ['reserved', 'pay_and_pickup'];
            if (!in_array($status, $allowed)) $status = 'reserved';

            if ($status === 'reserved') {
                $cond = "o.status = 'reserved'";
                $params = [$_SESSION['user_id']];
            } else {
                // pay_and_pickup -> include paid and picked_up
                $cond = "o.status IN ('paid','picked_up')";
                $params = [$_SESSION['user_id']];
            }

            $sql = "SELECT o.id, o.quantity, o.status, p.title AS plate_title, p.price AS plate_price, r.restaurant_name, r.id AS restaurant_id, pr.address AS restaurant_address, pr.phone AS restaurant_phone
                    FROM orders o
                    JOIN plates p ON o.plate_id = p.id
                    JOIN restaurants r ON p.restaurant_id = r.id
                    LEFT JOIN profiles pr ON r.user_id = pr.user_id
                    WHERE o.buyer_id = ? AND {$cond}
                    ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fallback: if no rows found, try to resolve by username in case session user_id is out-of-sync
            if (empty($rows)) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fsql = "SELECT o.id, o.quantity, o.status, p.title AS plate_title, p.price AS plate_price, r.restaurant_name, r.id AS restaurant_id, pr.address AS restaurant_address, pr.phone AS restaurant_phone
                              FROM orders o
                              JOIN users ub ON o.buyer_id = ub.id
                              JOIN plates p ON o.plate_id = p.id
                              JOIN restaurants r ON p.restaurant_id = r.id
                              LEFT JOIN profiles pr ON r.user_id = pr.user_id
                              WHERE ub.username = ? AND {$cond}
                              ORDER BY o.created_at DESC";
                    $fstmt = $pdo->prepare($fsql);
                    $fstmt->execute([$uname]);
                    $rows = $fstmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }
            send_json($rows);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'add_plate':
        try {
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') {
                send_json(['status'=>'error','message'=>'Unauthorized']);
            }

            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $title = trim($body['title'] ?? '');
            $description = trim($body['description'] ?? '');
            $price = trim($body['price'] ?? '');
            $quantity = intval($body['quantity'] ?? 0);
            $available_from = trim($body['available_from'] ?? '');
            $available_until = trim($body['available_until'] ?? '');

            if (!$title) send_json(['status'=>'error','message'=>'Title required']);
            if (!preg_match('/^[0-9]+(\.[0-9]{2})?$/', $price)) send_json(['status'=>'error','message'=>'Price must be ##.##']);
            if (!is_int($quantity) && !ctype_digit(strval($quantity))) send_json(['status'=>'error','message'=>'Quantity must be numeric']);
            if ($quantity < 0) send_json(['status'=>'error','message'=>'Quantity must be >= 0']);

            // Normalize datetimes: optionally accept 'YYYY-MM-DD HH:MM:SS' or 'YYYY-MM-DDTHH:MM' from client
            $normalize = function($v){
                if (!$v) return null;
                $v = str_replace('T',' ',$v);
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/',$v)) $v .= ':00';
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $v);
                return $dt ? $dt->format('Y-m-d H:i:s') : null;
            };

            $af = $normalize($available_from);
            $au = $normalize($available_until);
            if ($available_from && !$af) send_json(['status'=>'error','message'=>'Invalid available_from datetime']);
            if ($available_until && !$au) send_json(['status'=>'error','message'=>'Invalid available_until datetime']);

            // resolve restaurant id
            $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $rest = $rstmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$rest) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                    $fr->execute([$uname]);
                    $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$rest) send_json(['status'=>'error','message'=>'Restaurant not found']);

            // Check title uniqueness within restaurant
            $chk = $pdo->prepare('SELECT COUNT(*) FROM plates WHERE restaurant_id = ? AND title = ?');
            $chk->execute([$rest['id'], $title]);
            if ($chk->fetchColumn() > 0) send_json(['status'=>'error','message'=>'that plate already exists!']);

            // Insert plate
            $ist = $pdo->prepare('INSERT INTO plates (restaurant_id, title, description, price, quantity, available_from, available_until) VALUES (?,?,?,?,?,?,?)');
            $ist->execute([$rest['id'], $title, $description, $price, $quantity, $af, $au]);
            send_json(['status'=>'success']);
        } catch (Exception $e) {
            send_json(['status'=>'error','message'=>$e->getMessage()]);
        }
        break;

        case 'update_plate':
            try {
                if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') {
                    send_json(['status'=>'error','message'=>'Unauthorized']);
                }

                $body = json_decode(file_get_contents('php://input'), true) ?: [];
                $plate_id = intval($body['plate_id'] ?? 0);
                $title = trim($body['title'] ?? '');
                $description = trim($body['description'] ?? '');
                $price = trim($body['price'] ?? '');
                $quantity = intval($body['quantity'] ?? 0);
                $available_from = trim($body['available_from'] ?? '');
                $available_until = trim($body['available_until'] ?? '');

                if (!$plate_id) send_json(['status'=>'error','message'=>'Missing plate_id']);
                if (!$title) send_json(['status'=>'error','message'=>'Title required']);
                if (!preg_match('/^[0-9]+(\.[0-9]{2})?$/', $price)) send_json(['status'=>'error','message'=>'Price must be ##.##']);
                if (!is_int($quantity) && !ctype_digit(strval($quantity))) send_json(['status'=>'error','message'=>'Quantity must be numeric']);
                if ($quantity < 0) send_json(['status'=>'error','message'=>'Quantity must be >= 0']);

                // Normalize datetimes
                $normalize = function($v){
                    if (!$v) return null;
                    $v = str_replace('T',' ',$v);
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/',$v)) $v .= ':00';
                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $v);
                    return $dt ? $dt->format('Y-m-d H:i:s') : null;
                };

                $af = $normalize($available_from);
                $au = $normalize($available_until);
                if ($available_from && !$af) send_json(['status'=>'error','message'=>'Invalid available_from datetime']);
                if ($available_until && !$au) send_json(['status'=>'error','message'=>'Invalid available_until datetime']);

                // resolve restaurant id
                $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
                $rstmt->execute([$_SESSION['user_id']]);
                $rest = $rstmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$rest) {
                    $uname = $_SESSION['username'] ?? null;
                    if ($uname) {
                        $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                        $fr->execute([$uname]);
                        $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                }
                if (!$rest) send_json(['status'=>'error','message'=>'Restaurant not found']);

                // Verify plate belongs to this restaurant
                $v = $pdo->prepare('SELECT id FROM plates WHERE id = ? AND restaurant_id = ?');
                $v->execute([$plate_id, $rest['id']]);
                if (!$v->fetch()) send_json(['status'=>'error','message'=>'Plate not found or unauthorized']);

                // Check title uniqueness within restaurant (exclude current plate)
                $chk = $pdo->prepare('SELECT COUNT(*) FROM plates WHERE restaurant_id = ? AND title = ? AND id != ?');
                $chk->execute([$rest['id'], $title, $plate_id]);
                if ($chk->fetchColumn() > 0) send_json(['status'=>'error','message'=>'that plate already exists!']);

                // Perform update
                $ust = $pdo->prepare('UPDATE plates SET title = ?, description = ?, price = ?, quantity = ?, available_from = ?, available_until = ? WHERE id = ?');
                $ust->execute([$title, $description, $price, $quantity, $af, $au, $plate_id]);
                send_json(['status'=>'success']);
            } catch (Exception $e) {
                send_json(['status'=>'error','message'=>$e->getMessage()]);
            }
            break;

    case 'get_my_donations':
        try {
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'donor') {
                send_json(['status' => 'error', 'message' => 'Unauthorized']);
            }

            $filter = $_GET['filter'] ?? '';
            $allowed = ['reserved', 'claimed'];
            if (!in_array($filter, $allowed)) $filter = 'reserved';

            $sql = "SELECT d.id, d.quantity, d.status, p.title AS plate_title, p.price AS plate_price, r.restaurant_name, r.id AS restaurant_id, pr.address AS restaurant_address, pr.phone AS restaurant_phone, n.username AS needy_username
                    FROM donations d
                    JOIN plates p ON d.plate_id = p.id
                    JOIN restaurants r ON p.restaurant_id = r.id
                    LEFT JOIN profiles pr ON r.user_id = pr.user_id
                    LEFT JOIN users n ON d.needy_id = n.id
                    WHERE d.donor_id = ? AND d.status = ?
                    ORDER BY d.donated_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $filter]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fallback by username if nothing found (handles seeded data vs session mismatch)
            if (empty($rows)) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fsql = "SELECT d.id, d.quantity, d.status, p.title AS plate_title, p.price AS plate_price, r.restaurant_name, r.id AS restaurant_id, pr.address AS restaurant_address, pr.phone AS restaurant_phone, n.username AS needy_username
                              FROM donations d
                              JOIN users ud ON d.donor_id = ud.id
                              JOIN plates p ON d.plate_id = p.id
                              JOIN restaurants r ON p.restaurant_id = r.id
                              LEFT JOIN profiles pr ON r.user_id = pr.user_id
                              LEFT JOIN users n ON d.needy_id = n.id
                              WHERE ud.username = ? AND d.status = ?
                              ORDER BY d.donated_at DESC";
                    $fstmt = $pdo->prepare($fsql);
                    $fstmt->execute([$uname, $filter]);
                    $rows = $fstmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }

            send_json($rows);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_my_claims':
        try {
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'needy') {
                send_json(['status' => 'error', 'message' => 'Unauthorized']);
            }

            $filter = $_GET['filter'] ?? '';
            $allowed = ['reserved', 'claimed'];
            if (!in_array($filter, $allowed)) $filter = 'reserved';

            // Return donations where this needy is assigned
            $sql = "SELECT d.id, d.quantity, d.status, d.donated_at, p.title AS plate_title, p.price AS plate_price, r.restaurant_name, pr.address AS restaurant_address, pr.phone AS restaurant_phone
                    FROM donations d
                    JOIN plates p ON d.plate_id = p.id
                    JOIN restaurants r ON p.restaurant_id = r.id
                    LEFT JOIN profiles pr ON r.user_id = pr.user_id
                    WHERE d.needy_id = ? AND d.status = ?
                    ORDER BY d.donated_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $filter]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fallback by username if session user_id mismatch
            if (empty($rows)) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fsql = "SELECT d.id, d.quantity, d.status, d.donated_at, p.title AS plate_title, p.price AS plate_price, r.restaurant_name, pr.address AS restaurant_address, pr.phone AS restaurant_phone
                              FROM donations d
                              JOIN users un ON d.needy_id = un.id
                              JOIN plates p ON d.plate_id = p.id
                              JOIN restaurants r ON p.restaurant_id = r.id
                              LEFT JOIN profiles pr ON r.user_id = pr.user_id
                              WHERE un.username = ? AND d.status = ?
                              ORDER BY d.donated_at DESC";
                    $fstmt = $pdo->prepare($fsql);
                    $fstmt->execute([$uname, $filter]);
                    $rows = $fstmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }

            send_json($rows);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'cancel_my_order':
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $order_id = intval($body['order_id'] ?? 0);
            if (!$order_id) send_json(['status' => 'error', 'message' => 'Missing order_id']);
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'customer') send_json(['status' => 'error', 'message' => 'Unauthorized']);

            // Verify order belongs to buyer and is cancellable (reserved)
            $vstmt = $pdo->prepare('SELECT o.id, o.quantity, o.plate_id FROM orders o WHERE o.id = ? AND o.buyer_id = ? AND o.status = ?');
            $vstmt->execute([$order_id, $_SESSION['user_id'], 'reserved']);
            $order = $vstmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) send_json(['status' => 'error', 'message' => 'Order not found or cannot be cancelled']);

            // Return quantity back to plates
            $ust = $pdo->prepare('UPDATE plates SET quantity = quantity + ? WHERE id = ?');
            $ust->execute([$order['quantity'], $order['plate_id']]);

            // Delete the order record
            $dstmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
            $dstmt->execute([$order_id]);

            send_json(['status' => 'success']);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'confirm_donation_pickup':
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $donation_id = intval($body['donation_id'] ?? 0);
            if (!$donation_id) send_json(['status' => 'error', 'message' => 'Missing donation_id']);
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') send_json(['status' => 'error', 'message' => 'Unauthorized']);

            // Resolve restaurant id (user_id or username fallback)
            $rest = null;
            $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $rest = $rstmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$rest) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                    $fr->execute([$uname]);
                    $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$rest) send_json(['status' => 'error', 'message' => 'Not found or unauthorized']);

            // verify restaurant owns plate (by restaurant id)
            $verify = $pdo->prepare('SELECT d.id FROM donations d JOIN plates p ON d.plate_id = p.id WHERE d.id = ? AND p.restaurant_id = ?');
            $verify->execute([$donation_id, $rest['id']]);
            if (!$verify->fetch()) send_json(['status' => 'error', 'message' => 'Not found or unauthorized']);

            // Mark donation as claimed and record pickup timestamp
            // Fetch donation to know plate and quantity
            $dst = $pdo->prepare('SELECT plate_id, quantity FROM donations WHERE id = ? LIMIT 1');
            $dst->execute([$donation_id]);
            $don = $dst->fetch(PDO::FETCH_ASSOC);
            if ($don) {
                $plate_id = intval($don['plate_id']);
                $don_qty = intval($don['quantity']);
                // Decrement plates.quantity atomically, avoid negative values
                $dec = $pdo->prepare('UPDATE plates SET quantity = GREATEST(quantity - ?, 0) WHERE id = ?');
                $dec->execute([$don_qty, $plate_id]);
                // Optionally log remaining quantity for debugging
                try {
                    $pq = $pdo->prepare('SELECT quantity FROM plates WHERE id = ? LIMIT 1');
                    $pq->execute([$plate_id]);
                    $prow = $pq->fetch(PDO::FETCH_ASSOC) ?: null;
                    debug_log(['donation_pickup' => ['donation_id' => $donation_id, 'plate_id' => $plate_id, 'donation_qty' => $don_qty, 'remaining' => $prow['quantity'] ?? null]]);
                } catch (Exception $e) {
                    // swallow
                }
            }

            $ustmt = $pdo->prepare("UPDATE donations SET status = 'claimed', donated_at = NOW() WHERE id = ?");
            $ustmt->execute([$donation_id]);
            send_json(['status' => 'success']);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'return_donation':
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $donation_id = intval($body['donation_id'] ?? 0);
            if (!$donation_id) send_json(['status' => 'error', 'message' => 'Missing donation_id']);
            if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'restaurant') send_json(['status' => 'error', 'message' => 'Unauthorized']);

            // Resolve restaurant id (user_id or username fallback)
            $rest = null;
            $rstmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
            $rstmt->execute([$_SESSION['user_id']]);
            $rest = $rstmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$rest) {
                $uname = $_SESSION['username'] ?? null;
                if ($uname) {
                    $fr = $pdo->prepare('SELECT r.id FROM restaurants r JOIN users u ON r.user_id = u.id WHERE u.username = ? LIMIT 1');
                    $fr->execute([$uname]);
                    $rest = $fr->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
            if (!$rest) send_json(['status' => 'error', 'message' => 'Not found or unauthorized']);

            // Verify ownership by restaurant id
            $verify = $pdo->prepare('SELECT d.id FROM donations d JOIN plates p ON d.plate_id = p.id WHERE d.id = ? AND p.restaurant_id = ?');
            $verify->execute([$donation_id, $rest['id']]);
            if (!$verify->fetch()) send_json(['status' => 'error', 'message' => 'Not found or unauthorized']);

            // Return to pool: clear needy_id and set status back to available
            // Fetch donation details to know plate and quantity
            $dst = $pdo->prepare('SELECT plate_id, quantity, status FROM donations WHERE id = ? LIMIT 1');
            $dst->execute([$donation_id]);
            $don = $dst->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($don) {
                $plate_id = intval($don['plate_id']);
                $don_qty = intval($don['quantity']);
                $don_status = $don['status'];
                // If donation was reserved or claimed, add the items back to plates.quantity
                // (safe -- avoids adding back for already-available donations)
                if (in_array($don_status, ['reserved', 'claimed'])) {
                    $inc = $pdo->prepare('UPDATE plates SET quantity = quantity + ? WHERE id = ?');
                    $inc->execute([$don_qty, $plate_id]);
                    try {
                        $pq = $pdo->prepare('SELECT quantity FROM plates WHERE id = ? LIMIT 1');
                        $pq->execute([$plate_id]);
                        $prow = $pq->fetch(PDO::FETCH_ASSOC) ?: null;
                        debug_log(['return_donation' => ['donation_id' => $donation_id, 'plate_id' => $plate_id, 'added_back' => $don_qty, 'remaining' => $prow['quantity'] ?? null]]);
                    } catch (Exception $e) {
                        // swallow
                    }
                }
            }

            $ustmt = $pdo->prepare("UPDATE donations SET needy_id = NULL, status = 'available' WHERE id = ?");
            $ustmt->execute([$donation_id]);
            send_json(['status' => 'success']);
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

        case 'login':
        try {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$password) {
                send_json(["status" => "error", "message" => "Missing required fields"]);
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session id to prevent fixation
                session_regenerate_id(true);

                // Store minimal user info in session
                $_SESSION['user_id'] = $user['id'] ?? null;
                $_SESSION['username'] = $user['username'] ?? null;
                $_SESSION['role'] = $user['role'] ?? null;

                // Try to load basic profile info if available
                try {
                    $pstmt = $pdo->prepare("SELECT full_name, address, phone FROM profiles WHERE user_id = ?");
                    $pstmt->execute([$_SESSION['user_id']]);
                    $profile = $pstmt->fetch(PDO::FETCH_ASSOC);
                    if ($profile) {
                        $_SESSION['full_name'] = $profile['full_name'] ?? null;
                        $_SESSION['address'] = $profile['address'] ?? null;
                        $_SESSION['phone'] = $profile['phone'] ?? null;
                    }
                } catch (Exception $e) {
                    // ignore profile fetch errors; still allow login
                }

                send_json(["status" => "success"]);
            } else {
                send_json([
                    "status" => "error",
                    "field" => "password",
                    "message" => "Incorrect Username or Password"
                ]);
            }
        } catch (Exception $e) {
            send_json(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

        case 'forgot_pwd':
        try {
            $username = trim($_POST['username'] ?? '');
            $security_answer = trim($_POST['security_answer'] ?? '');
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            // Step 1: Check if username exists
            if ($username && !$security_answer && !$new_password) {
                $stmt = $pdo->prepare("SELECT security_question FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    send_json(["status" => "error", "message" => "Oops! That username does not exist"]);
                }

                send_json(["status" => "success", "security_question" => $user['security_question']]);
            }

            // Step 2: Verify answer
            if ($username && $security_answer && !$new_password) {
                $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($security_answer, $user['security_answer'])) {
                    send_json(["status" => "error", "message" => "Incorrect answer. Try again"]);
                }

                send_json(["status" => "success"]);
            }

            // Step 3: Reset password
            if ($username && $new_password && $confirm_password) {
                if ($new_password !== $confirm_password) {
                    send_json(["status" => "error", "message" => "Passwords do not match"]);
                }

                // Validate password
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $new_password)) {
                    send_json(["status" => "error", "message" => "Password does not meet requirements"]);
                }

                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$hashedPassword, $username]);

                send_json(["status" => "success"]);
            }

        } catch (Exception $e) {
            send_json(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
    
        default:
        send_json(["status" => "error", "message" => "Invalid action"]);
        break;
    }