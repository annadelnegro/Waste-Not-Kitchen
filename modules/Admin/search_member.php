<?php
// modules/Admin/search_member.php

// 1. Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Connect to the database
$db_path = __DIR__ . '/../../config/config.php';
if (!file_exists($db_path)) {
    die("Error: Could not find database configuration file at: " . $db_path);
}
require_once $db_path; 

// 3. THEME STYLING (Deep Blue & Gold)
// Styles moved to external CSS file for maintainability
echo '<link rel="stylesheet" href="../../assets/css/admin-search-member.css" />';

// Enforce admin-only access
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: /Waste-Not-Kitchen/login.php');
    exit;
}

echo '<div class="container">';
echo '<a href="../../admin-dashboard.php" class="back-btn">&larr; Back to Dashboard</a>';

if (isset($_GET['query'])) {
    $search = trim($_GET['query']);

    try {
        // Logic: Check if input is Number (ID) or String (Name/Username)
        if (ctype_digit($search)) {
            $sql = "SELECT u.id, u.username, u.role, p.full_name, p.phone, p.address 
                    FROM users u 
                    LEFT JOIN profiles p ON u.id = p.user_id 
                    WHERE u.id = ? OR p.full_name LIKE ?";
            $params = [$search, "%$search%"];
        } else {
            $sql = "SELECT u.id, u.username, u.role, p.full_name, p.phone, p.address 
                    FROM users u 
                    LEFT JOIN profiles p ON u.id = p.user_id 
                    WHERE p.full_name LIKE ? OR u.username LIKE ?";
            $params = ["%$search%", "%$search%"];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        echo "<h2>Search Results for: \"" . htmlspecialchars($search) . "\"</h2>";

        if ($results) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Phone</th><th>Address</th></tr>";
            foreach ($results as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>";
                echo "<td>" . ucfirst(htmlspecialchars($row['role'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['address'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='text-align:center; font-size: 18px; margin-top: 20px;'>No members found matching that ID, Name, or Username.</p>";
        }

    } catch (PDOException $e) {
        echo "<div class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

} else {
    echo "<p>No search query provided.</p>";
}
echo '</div>';
?>