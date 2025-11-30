<?php
session_start();
// Only allow admin users to access this dashboard
if (empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    // Not authorized — redirect to login
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
</head>
<body>

<div class="container">
    <h1>Administrator Dashboard</h1>
    <p>Welcome, Admin. Select an action below.</p>

    <section>
        <h2>Search Member Information</h2>
        <form action="modules/Admin/search_member.php" method="GET">
            <div class="form-group">
                <label for="member_search">Enter Member ID or Name:</label><br><br>
                <input type="text" id="member_search" name="query" placeholder="e.g. John Doe or ID#123" required>
                <button type="submit" class="btn btn-primary">Search Member</button>
            </div>
        </form>
    </section>

    <hr>

    <section>
        <h2>Generate Reports</h2>
        <div class="report-grid">
            
            <div class="report-card">
                <h3>Restaurant Activity</h3>
                <p>Annual activity report.</p>
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
                    <input type="hidden" name="report_type" value="restaurant_annual">
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

            <div class="report-card">
                <h3>Purchase History</h3>
                <p>Annual report for Customer/Donor.</p>
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
                    <input type="hidden" name="report_type" value="customer_purchase">
                    <input type="text" name="username" placeholder="Enter Username" required>
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

            <div class="report-card">
                <h3>Free Plates Given</h3>
                <p>Annual report for needy members.</p>
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
                    <input type="hidden" name="report_type" value="needy_plates">
                    <input type="text" name="username" placeholder="Enter Username" required>
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

            <div class="report-card">
                <h3>Tax Declaration</h3>
                <p>Year-end donation report.</p>
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
                    <input type="hidden" name="report_type" value="donor_tax">
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

        </div>
    </section>
</div>

<!-- fixed bottom-right logout button -->
<a href="/Waste-Not-Kitchen/logout.php" class="admin-logout-bottom" aria-label="Logout">Logout</a>
</body>
</html>