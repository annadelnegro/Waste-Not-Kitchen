<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
$db_path = __DIR__ . '/../../config/config.php';
if (!file_exists($db_path)) {
    die("Error: Could not find database configuration file at: " . $db_path);
}
require_once $db_path;

// Enforce admin-only access
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    // If request is AJAX/POST, return minimal message; otherwise redirect to login
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<div class="error">Unauthorized: admin access required.</div>';
        exit;
    }
    header('Location: /Waste-Not-Kitchen/login.php');
    exit;
}

// Styling (moved to external CSS file)
echo '<link rel="stylesheet" href="../../assets/css/admin-reports.css" />';

echo '<div class="container">';
echo '<a href="../../admin-dashboard.php" class="back-btn">&larr; Back to Dashboard</a>';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['report_type'];
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;

    try {
        switch ($type) {
            
            // Annual Report
            case 'restaurant_annual':
                echo "<h2>Annual Restaurant Activity Report</h2>";
                
                $sql = "SELECT r.restaurant_name, COUNT(o.id) as total_orders, SUM(p.price * o.quantity) as total_revenue
                        FROM restaurants r
                        JOIN plates p ON r.id = p.restaurant_id
                        JOIN orders o ON p.id = o.plate_id
                        WHERE YEAR(o.created_at) = YEAR(CURDATE())
                        GROUP BY r.id";
                
                $stmt = $pdo->query($sql);
                $results = $stmt->fetchAll();
                
                if ($results) {
                    echo "<table><tr><th>Restaurant Name</th><th>Total Orders</th><th>Est. Revenue</th></tr>";
                    foreach ($results as $row) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['restaurant_name']) . "</td>
                                <td>" . htmlspecialchars($row['total_orders']) . "</td>
                                <td>$" . number_format($row['total_revenue'], 2) . "</td>
                              </tr>";
                    }
                    echo "</table>";
                } else { echo "<p>No restaurant activity found for the current year.</p>"; }
                break;

            // Purchase history
            case 'customer_purchase':
                if (!$username) { echo "<p>Error: Username is required.</p>"; break; }
                echo "<h2>Purchase History for: " . htmlspecialchars($username) . "</h2>";
                
                $sql = "SELECT o.created_at, p.title, o.quantity, o.status, (p.price * o.quantity) as total_cost
                        FROM users u
                        JOIN orders o ON u.id = o.buyer_id
                        JOIN plates p ON o.plate_id = p.id
                        WHERE u.username = ? AND YEAR(o.created_at) = YEAR(CURDATE())
                        ORDER BY o.created_at DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username]);
                $results = $stmt->fetchAll();

                if ($results) {
                    echo "<table><tr><th>Date</th><th>Plate</th><th>Quantity</th><th>Status</th><th>Total Cost</th></tr>";
                    foreach ($results as $row) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['created_at']) . "</td>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>" . htmlspecialchars($row['quantity']) . "</td>
                                <td>" . htmlspecialchars($row['status']) . "</td>
                                <td>$" . number_format($row['total_cost'], 2) . "</td>
                              </tr>";
                    }
                    echo "</table>";
                } else { echo "<p>No purchases found for user <strong>$username</strong> this year.</p>"; }
                break;

            // Free plates recived
            case 'needy_plates':
                if (!$username) { echo "<p>Error: Username is required.</p>"; break; }
                echo "<h2>Free Plates Received by: " . htmlspecialchars($username) . "</h2>";

                $sql = "SELECT d.donated_at, p.title, d.quantity
                        FROM users u
                        JOIN donations d ON u.id = d.needy_id
                        JOIN plates p ON d.plate_id = p.id
                        WHERE u.username = ? AND YEAR(d.donated_at) = YEAR(CURDATE())
                        ORDER BY d.donated_at DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username]);
                $results = $stmt->fetchAll();

                if ($results) {
                    echo "<table><tr><th>Date Received</th><th>Plate Name</th><th>Quantity</th></tr>";
                    foreach ($results as $row) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['donated_at']) . "</td>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>" . htmlspecialchars($row['quantity']) . "</td>
                              </tr>";
                    }
                    echo "</table>";
                } else { echo "<p>No free plates found for user <strong>$username</strong> this year.</p>"; }
                break;

            // Year end tax deduction report
            case 'donor_tax':
                echo "<h2>Year-End Tax Report (Donors)</h2>";
                echo "<p>The following users have made donations this year eligible for tax reporting.</p>";

                $sql = "SELECT u.username, prof.full_name, SUM(p.price * d.quantity) as total_donation_value
                        FROM users u
                        JOIN donations d ON u.id = d.donor_id
                        JOIN plates p ON d.plate_id = p.id
                        LEFT JOIN profiles prof ON u.id = prof.user_id
                        WHERE YEAR(d.donated_at) = YEAR(CURDATE())
                        GROUP BY u.id, u.username, prof.full_name";

                $stmt = $pdo->query($sql);
                $results = $stmt->fetchAll();

                if ($results) {
                    echo "<table><tr><th>Username</th><th>Donor Name</th><th>Total Tax-Deductible Value</th></tr>";
                    foreach ($results as $row) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['username']) . "</td>
                                <td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>
                                <td>$" . number_format($row['total_donation_value'], 2) . "</td>
                              </tr>";
                    }
                    echo "</table>";
                } else { echo "<p>No donations found for this year.</p>"; }
                break;
        }
    } catch (PDOException $e) {
        echo "<div style='color: #ff6b6b; background: #521414; padding: 10px; border: 1px solid red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
echo '</div>';
?>