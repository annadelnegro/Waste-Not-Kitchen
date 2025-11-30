<?php
<<<<<<< Updated upstream
// Include your configuration/database connection if needed
// require_once 'config/db.php'; 
=======
>>>>>>> Stashed changes
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
    <style>
<<<<<<< Updated upstream
        /* 1. MAIN BACKGROUND COLOR */
        body { 
            font-family: Arial, sans-serif; 
            background-color: #05339c; /* Deep Blue */
            padding: 20px; 
            color: white; /* Default text color to white */
=======
        body { 
            font-family: Arial, sans-serif; 
            background-color: #05339c;
            padding: 20px; 
            color: white; 
>>>>>>> Stashed changes
        }

        .container { 
            max-width: 900px; 
            margin: 0 auto; 
<<<<<<< Updated upstream
            background: #05339c; /* Changed from white to Deep Blue */
            padding: 30px; 
            border-radius: 8px; 
            /* Removed shadow since it blends with background now, 
               but kept border-radius in case you add a border later */
        }

        /* Make main headers white to appear on blue background */
        h1, h2 { color: white; text-align: center; }
        p { text-align: center; color: #e0e0e0; } /* Light gray for subtitles */
        label { color: white; }
        
        hr { border: 0; border-top: 1px solid #4a76d4; margin: 20px 0; } /* Lighter blue line */
        
        /* Form Styling */
        .form-group { margin-bottom: 15px; text-align: center; }
        
        /* General Input styling */
        input[type="text"], select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        /* Search Bar specific width */
        #member_search { width: 300px; }

        /* Button Styling */
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: 0.3s; color: white; }
        .btn-primary { background-color: #007bff; border: 1px solid white; } /* Added white border to pop */
        .btn-primary:hover { background-color: #0056b3; }
        
        /* Grid Layout */
=======
            background: #05339c; 
            padding: 30px; 
            border-radius: 8px; 
        }

        h1, h2 { color: white; text-align: center; }
        p { text-align: center; color: #e0e0e0; }
        label { color: white; }
        
        hr { border: 0; border-top: 1px solid #4a76d4; margin: 20px 0; }
        
        .form-group { margin-bottom: 15px; text-align: center; }
        input[type="text"], select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        #member_search { width: 300px; }

        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: 0.3s; color: white; }
        .btn-primary { background-color: #007bff; border: 1px solid white; }
        .btn-primary:hover { background-color: #0056b3; }
        
>>>>>>> Stashed changes
        .report-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
            justify-content: center;
        }
        
<<<<<<< Updated upstream
        /* 2. FOREGROUND BOX COLOR (Yellow Cards) */
=======
>>>>>>> Stashed changes
        .report-card { 
            border: 1px solid #cba328; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center; 
<<<<<<< Updated upstream
            background-color: #e6c960; /* Golden Yellow */
=======
            background-color: #e6c960; 
>>>>>>> Stashed changes
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
<<<<<<< Updated upstream
            color: #000; /* TEXT MUST BE BLACK HERE to read on yellow */
        }
        
        /* Specific overrides to ensure text inside yellow cards is black */
        .report-card h3 { margin-top: 0; color: #000; }
        .report-card p { color: #222; }

        /* Inputs specifically inside report cards */
=======
            color: #000; 
        }
        
        .report-card h3 { margin-top: 0; color: #000; }
        .report-card p { color: #222; }

>>>>>>> Stashed changes
        .report-card input[type="text"] {
            width: 80%;
            margin-bottom: 10px;
            text-align: center;
            border: 1px solid #b39a40;
        }

<<<<<<< Updated upstream
        /* Dark blue buttons inside the yellow cards */
=======
>>>>>>> Stashed changes
        .btn-report { background-color: #05339c; width: 80%; margin-top: auto; border: 1px solid #042a80;} 
        .btn-report:hover { background-color: #032066; }
    </style>
</head>
<body>

<div class="container">
    <h1>Administrator Dashboard</h1>
    <p>Welcome, Admin. Select an action below.</p>

    <section>
        <h2>Search Member Information</h2>
<<<<<<< Updated upstream
        <form action="modules/search_member.php" method="GET">
=======
        <form action="modules/Admin/search_member.php" method="GET">
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
                <form action="modules/reports.php" method="POST" style="width:100%">
=======
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
>>>>>>> Stashed changes
                    <input type="hidden" name="report_type" value="restaurant_annual">
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

            <div class="report-card">
                <h3>Purchase History</h3>
                <p>Annual report for Customer/Donor.</p>
<<<<<<< Updated upstream
                <form action="modules/reports.php" method="POST" style="width:100%">
=======
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
>>>>>>> Stashed changes
                    <input type="hidden" name="report_type" value="customer_purchase">
                    <input type="text" name="username" placeholder="Enter Username" required>
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

            <div class="report-card">
                <h3>Free Plates Given</h3>
                <p>Annual report for needy members.</p>
<<<<<<< Updated upstream
                <form action="modules/reports.php" method="POST" style="width:100%">
=======
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
>>>>>>> Stashed changes
                    <input type="hidden" name="report_type" value="needy_plates">
                    <input type="text" name="username" placeholder="Enter Username" required>
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

            <div class="report-card">
                <h3>Tax Declaration</h3>
                <p>Year-end donation report.</p>
<<<<<<< Updated upstream
                <form action="modules/reports.php" method="POST" style="width:100%">
=======
                <form action="modules/Admin/reports.php" method="POST" style="width:100%">
>>>>>>> Stashed changes
                    <input type="hidden" name="report_type" value="donor_tax">
                    <button type="submit" class="btn btn-report">Generate Report</button>
                </form>
            </div>

        </div>
    </section>
</div>

</body>
</html>