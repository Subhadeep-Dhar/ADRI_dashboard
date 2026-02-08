<?php
session_start();
require_once 'db.php';

// Check if cookies exist and match the current IP address
if (isset($_COOKIE['user_ip']) && isset($_COOKIE['user_id']) && isset($_COOKIE['username'])) {
    $user_ip = $_COOKIE['user_ip'];
    $user_id = $_COOKIE['user_id'];
    $username = $_COOKIE['username'];

    // Get the user's device IP stored in the database
    $sql = "SELECT * FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Check if the stored device IP matches the current IP
            if ($user['device_ip'] == $user_ip) {
                // Set session variables if the device IP matches
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
            } else {
                // If IP doesn't match, clear the cookies and session
                setcookie('user_ip', '', time() - 3600, '/');
                setcookie('user_id', '', time() - 3600, '/');
                setcookie('username', '', time() - 3600, '/');
                session_unset();
                session_destroy();
            }
        }
        $stmt->close();
    }
}

// Now check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get current month and year
$current_month = date('n');
$current_year = date('Y');
$month_name = date('F Y');

// Handle month/year selection if provided
if (isset($_GET['month']) && isset($_GET['year'])) {
    $selected_month = intval($_GET['month']);
    $selected_year = intval($_GET['year']);
    if ($selected_month >= 1 && $selected_month <= 12 && $selected_year >= 2020 && $selected_year <= 2030) {
        $current_month = $selected_month;
        $current_year = $selected_year;
        $month_name = date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year));
    }
}

// Query to fetch events for the selected month
$sql_events = "SELECT * FROM events 
               WHERE (MONTH(start_date) = ? AND YEAR(start_date) = ?) 
               OR (MONTH(end_date) = ? AND YEAR(end_date) = ?)
               ORDER BY start_date ASC, start_time ASC";

$events = [];
if ($stmt = $conn->prepare($sql_events)) {
    $stmt->bind_param("iiii", $current_month, $current_year, $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
}

// Generate statistics
$total_events = count($events);
$completed_events = 0;
$upcoming_events = 0;
$ongoing_events = 0;
$today = date('Y-m-d');
$current_time = date('H:i:s');

foreach ($events as $event) {
    $event_start = $event['start_date'];
    $event_end = $event['end_date'];
    
    if ($event_end < $today) {
        $completed_events++;
    } elseif ($event_start > $today) {
        $upcoming_events++;
    } else {
        $ongoing_events++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- mobile metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <!-- site metas -->
    <title>Event Report - Adri Dashboard</title>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- site icon -->
    <link rel="icon" href="ADRI_favicon.png" type="image/png" />
    <!-- bootstrap css -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <!-- site css -->
    <link rel="stylesheet" href="style.css" />
    <!-- responsive css -->
    <link rel="stylesheet" href="css/responsive.css" />
    <!-- color css -->
    <link rel="stylesheet" href="css/colors.css" />
    <!-- select bootstrap -->
    <link rel="stylesheet" href="css/bootstrap-select.css" />
    <!-- scrollbar css -->
    <link rel="stylesheet" href="css/perfect-scrollbar.css" />
    <!-- custom css -->
    <link rel="stylesheet" href="css/custom.css" />

    <style>
        /* Dropdown menu container styling */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        /* Dropdown button styling */
        .dropdown a {
            text-decoration: none;
            color: #000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Dropdown content (hidden by default) */
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 150px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        /* Links inside the dropdown */
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
        }

        /* Change link color on hover */
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        /* Show the dropdown on hover */
        .dropdown:hover .dropdown-content {
            display: block;
        }

        th, td {
            color: black;
        }

        .report-header {
            background: #27496f;
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 20px;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .download-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .btn-download {
            margin: 5px;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-excel {
            background-color: #28a745;
            color: white;
        }

        .btn-word {
            background-color: #2b5ce6;
            color: white;
        }

        .btn-download:hover {
            opacity: 0.8;
            color: white;
            text-decoration: none;
        }

        .month-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body class="dashboard dashboard_1">
    <div class="full_container">
        <div class="inner_container">
            <!-- Sidebar -->
            <nav id="sidebar">
                <div class="sidebar_blog_1">
                    <div class="sidebar_user_info">
                        <div class="user_profle_side" style="padding: 0px 25px;">
                            <div class="user_info" style="padding-top:0px;">
                                <a href="index.php"><img class="img-responsive" style="height:40px;" src="ADRI_logo.png" alt="#" /></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sidebar_blog_2">
                    <h4>Navigation</h4>
                    <ul class="list-unstyled components">
                        <li><a href="index.php"><i style="font-size:24px; color:#ddd;" class="fa">&#xf015;</i> <span>Home</span></a></li>
                        <!--<li><a href="form.php"><i style="font-size:20px; color:yellow" class="fa">&#xf15c;</i> <span>Form</span></a></li>-->
                        <!--<li><a href="tables.php"><i style="font-size:20px;" class="fa purple_color2">&#xf0ce;</i> <span>Dashboard</span></a></li>-->
                        <li><a href="contribution_report.php"><i style="font-size:20px; color:#20c997;" class="fa">&#xf0ae;</i> <span>Contributions</span></a></li>
                        
                        <li class="nav-item menu-items">
                            <a class="nav-link" data-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
                                <i class="fa" style="">📅</i>
                                <span class="menu-title">Events</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="collapse" id="auth">
                                <ul class="nav flex-column sub-menu">
                                    <li class="nav-item"> <a class="nav-link" href="save_event.php"> Add Event </a></li>
                                    <li class="nav-item"> <a class="nav-link" href="track_events.php"> Track events </a></li>
                                    <li class="nav-item"> <a class="nav-link" href="event_reports.php"> Event Report </a></li>
                                </ul>
                            </div>
                        </li>
                        
                        <li><a href="leave_request.php"><i style="font-size:20px; color:#ff6b6b;" class="fa">&#xf274;</i> <span>Leave Request</span></a></li>
                        <li><a href="leave_dashboard.php"><i style="font-size:20px; color:#fff;" class="fa">&#xf0ae;</i> <span style="color:#fff;">Leave Dashboard</span></a></li>                        

                        <li><a href="logout.php"><i style="font-size:20px" class="fa">&#xf08b;</i> <span>Logout</span></a></li>
                    </ul>
                </div>
            </nav>
            
            <!-- right content -->
            <div id="content">
                <!-- topbar -->
                <div class="topbar">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <div class="full" style="height:70px">
                            <button type="button" id="sidebarCollapse" class="sidebar_toggle"><i class="fa fa-bars"></i></button>
                            <div class="right_topbar">
                                <div class="icon_info">
                                    <ul class="user_profile_dd">
                                        <li>
                                            <a class="dropdown-toggle" data-toggle="dropdown">
                                                <span class="user_info">
                                                    <h6 style="z-index:99"><?php echo htmlspecialchars($username); ?></h6>
                                                </span>
                                            </a>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="developer.php">Developer</a>
                                                <a class="dropdown-item" href="logout.php"><span>Log Out</span> <i class="fa fa-sign-out"></i></a>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <div class="midde_cont">
                    <div class="container-fluid">
                        <!-- Report Header -->
                        <div class="report-header">
                            <div class="row">
                                <div class="col-md-8">
                                    <h1 style = "color:white">Event Report</h1>
                                    <h3 style = "color:white"><?php echo $month_name; ?></h3>
                                    <p style = "color:white">Comprehensive monthly event analysis and summary</p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <h5 style = "color:white">Generated on: <?php echo date('F j, Y'); ?></h5>
                                    <h6 style = "color:white">Report ID: <?php echo strtoupper(substr(md5($month_name . date('Y-m-d')), 0, 8)); ?></h6>
                                </div>
                            </div>
                        </div>

                        <!-- Month Selector -->
                        <div class="month-selector no-print">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Select Month & Year</h4>
                                    <form method="GET" action="event_reports.php" class="form-inline">
                                        <select name="month" class="form-control mr-2">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?php echo $m; ?>" <?php echo ($m == $current_month) ? 'selected' : ''; ?>>
                                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <select name="year" class="form-control mr-2">
                                            <?php for ($y = 2020; $y <= 2030; $y++): ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($y == $current_year) ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary">Generate Report</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Download Section -->
                        <div class="download-section no-print">
                            <h4>📥 Download Report</h4>
                            <p>Export this report in your preferred format</p>
                            <button onclick="downloadExcel()" class="btn-download btn-excel">📊 Download Excel</button>
                            <button onclick="downloadWord()" class="btn-download btn-word">📄 Download Word</button>
                            <button onclick="window.print()" class="btn-download" style="background-color: #6c757d; color: white;">🖨️ Print Report</button>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number"><?php echo $total_events; ?></div>
                                    <h5>Total Events</h5>
                                    <p class="text-muted">This Month</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number" style="color: #28a745;"><?php echo $completed_events; ?></div>
                                    <h5>Completed</h5>
                                    <p class="text-muted">Past Events</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number" style="color: #ffc107;"><?php echo $ongoing_events; ?></div>
                                    <h5>Ongoing</h5>
                                    <p class="text-muted">Current Events</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number" style="color: #17a2b8;"><?php echo $upcoming_events; ?></div>
                                    <h5>Upcoming</h5>
                                    <p class="text-muted">Future Events</p>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Event List -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="white_shd full margin_bottom_30">
                                    <div class="full graph_head">
                                        <div class="heading1 margin_0">
                                            <h2>📋 Detailed Event List - <?php echo $month_name; ?></h2>
                                        </div>
                                    </div>
                                    <div class="table_section padding_infor_info">
                                        <div class="table-responsive-sm">
                                            <table class="table table-striped" id="eventsTable">
                                                <thead>
                                                    <tr>
                                                        <th>S.No.</th>
                                                        <th>Event Name</th>
                                                        <th>Start Date</th>
                                                        <th>Start Time</th>
                                                        <th>End Date</th>
                                                        <th>End Time</th>
                                                        <th>Location</th>
                                                        <th>Description</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (count($events) > 0) {
                                                        $counter = 1;
                                                        foreach ($events as $event) {
                                                            $status = '';
                                                            $status_class = '';
                                                            
                                                            if ($event['end_date'] < $today) {
                                                                $status = 'Completed';
                                                                $status_class = 'badge badge-success';
                                                            } elseif ($event['start_date'] > $today) {
                                                                $status = 'Upcoming';
                                                                $status_class = 'badge badge-info';
                                                            } else {
                                                                $status = 'Ongoing';
                                                                $status_class = 'badge badge-warning';
                                                            }
                                                            
                                                            echo "<tr>";
                                                            echo "<td>" . $counter++ . "</td>";
                                                            echo "<td><strong>" . htmlspecialchars($event['event_name']) . "</strong></td>";
                                                            echo "<td>" . date('M d, Y', strtotime($event['start_date'])) . "</td>";
                                                            echo "<td>" . date('h:i A', strtotime($event['start_time'])) . "</td>";
                                                            echo "<td>" . date('M d, Y', strtotime($event['end_date'])) . "</td>";
                                                            echo "<td>" . date('h:i A', strtotime($event['end_time'])) . "</td>";
                                                            echo "<td>" . htmlspecialchars($event['location']) . "</td>";
                                                            echo "<td>" . htmlspecialchars(substr($event['description'], 0, 50)) . (strlen($event['description']) > 50 ? '...' : '') . "</td>";
                                                            echo "<td><span class='" . $status_class . "'>" . $status . "</span></td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='9' class='text-center'>No events found for " . $month_name . "</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Summary -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="white_shd full margin_bottom_30">
                                    <div class="full graph_head">
                                        <div class="heading1 margin_0">
                                            <h2>📈 Monthly Summary</h2>
                                        </div>
                                    </div>
                                    <div class="padding_infor_info">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4>Key Metrics</h4>
                                                <ul class="list-group">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Event Completion Rate
                                                        <span class="badge badge-primary badge-pill">
                                                            <?php echo $total_events > 0 ? round(($completed_events / $total_events) * 100, 1) : 0; ?>%
                                                        </span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Average Events per Week
                                                        <span class="badge badge-secondary badge-pill">
                                                            <?php echo round($total_events / 4.33, 1); ?>
                                                        </span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Most Active Period
                                                        <span class="badge badge-info badge-pill">
                                                            <?php echo date('M Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>
                                                        </span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h4>Report Notes</h4>
                                                <div class="alert alert-info">
                                                    <p><strong>Report Generation:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                                                    <p><strong>Data Period:</strong> <?php echo $month_name; ?></p>
                                                    <p><strong>Last Updated:</strong> Real-time data as of report generation</p>
                                                    <?php if ($current_month == date('n') && $current_year == date('Y')): ?>
                                                        <p><strong>Status:</strong> <span class="badge badge-success">Current Month - Auto-updating</span></p>
                                                    <?php else: ?>
                                                        <p><strong>Status:</strong> <span class="badge badge-secondary">Historical Data - Finalized</span></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- footer -->
                        <div class="container-fluid">
                            <div class="footer">
                                <p>&copy; 2025 ADRI India, All rights reserved | <a style="color:#101F60" href="developer.php"> Developer contact </a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- wow animation -->
    <script src="js/animate.js"></script>
    <!-- select country -->
    <script src="js/bootstrap-select.js"></script>
    <!-- owl carousel -->
    <script src="js/owl.carousel.js"></script>
    <!-- chart js -->
    <script src="js/Chart.min.js"></script>
    <script src="js/Chart.bundle.min.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/analyser.js"></script>
    <!-- nice scrollbar -->
    <script src="js/perfect-scrollbar.min.js"></script>
    <script>
        var ps = new PerfectScrollbar('#sidebar');
    </script>
    <!-- custom js -->
    <script src="js/custom.js"></script>

    <script>
        // Function to download Excel file
        function downloadExcel() {
            // Get the table data
            const table = document.getElementById('eventsTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Events Report"});
            
            // Add summary sheet
            const summaryData = [
                ['Event Report Summary'],
                ['Month/Year', '<?php echo $month_name; ?>'],
                ['Report Generated', '<?php echo date('F j, Y \a\t g:i A'); ?>'],
                ['Total Events', <?php echo $total_events; ?>],
                ['Completed Events', <?php echo $completed_events; ?>],
                ['Ongoing Events', <?php echo $ongoing_events; ?>],
                ['Upcoming Events', <?php echo $upcoming_events; ?>],
                ['Completion Rate', '<?php echo $total_events > 0 ? round(($completed_events / $total_events) * 100, 1) : 0; ?>%']
            ];
            
            const summaryWs = XLSX.utils.aoa_to_sheet(summaryData);
            XLSX.utils.book_append_sheet(wb, summaryWs, "Summary");
            
            // Save the file
            const filename = `Event_Report_<?php echo date('Y_m', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>.xlsx`;
            XLSX.writeFile(wb, filename);
        }

        // Function to download Word file (simplified HTML version)
        function downloadWord() {
            // Create a comprehensive HTML content for Word export
            const reportContent = `
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Event Report - <?php echo $month_name; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 40px; }
                        .header { text-align: center; border-bottom: 3px solid #667eea; padding-bottom: 20px; margin-bottom: 30px; }
                        .stats { display: flex; justify-content: space-around; margin: 30px 0; }
                        .stat-box { text-align: center; border: 1px solid #ddd; padding: 15px; margin: 10px; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0 0 -100px; }
                        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                        th { background-color: #f8f9fa; font-weight: bold; }
                        .status-completed { color: #28a745; font-weight: bold; }
                        .status-upcoming { color: #17a2b8; font-weight: bold; }
                        .status-ongoing { color: #ffc107; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>📊 ADRI Event Report</h1>
                        <h2><?php echo $month_name; ?></h2>
                        <p>Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></p>
                        <p>Report ID: <?php echo strtoupper(substr(md5($month_name . date('Y-m-d')), 0, 8)); ?></p>
                    </div>
                    
                    <div class="stats">
                        <div class="stat-box">
                            <h3><?php echo $total_events; ?></h3>
                            <p>Total Events</p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $completed_events; ?></h3>
                            <p>Completed</p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $ongoing_events; ?></h3>
                            <p>Ongoing</p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $upcoming_events; ?></h3>
                            <p>Upcoming</p>
                        </div>
                    </div>

                    <br><br><br><br><br><br><br><br><br><br><br><br>
                    
                    <h3>Detailed Event List</h3>
                    ${document.getElementById('eventsTable').outerHTML}
                    
                    <div style="margin-top: 40px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                        <h3>Summary</h3>
                        <p><strong>Event Completion Rate:</strong> <?php echo $total_events > 0 ? round(($completed_events / $total_events) * 100, 1) : 0; ?>%</p>
                        <p><strong>Average Events per Week:</strong> <?php echo round($total_events / 4.33, 1); ?></p>
                        <p><strong>Report Status:</strong> <?php echo ($current_month == date('n') && $current_year == date('Y')) ? 'Current Month - Auto-updating' : 'Historical Data - Finalized'; ?></p>
                    </div>
                    
                    <div style="margin-top: 30px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
                        <p>&copy; 2025 ADRI India. All rights reserved.</p>
                    </div>
                </body>
                </html>
            `;
            
            // Create and download the file
            const blob = new Blob([reportContent], {type: 'application/msword'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Event_Report_<?php echo date('Y_m', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>.doc`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Auto-refresh for current month reports every 5 minutes
        <?php if ($current_month == date('n') && $current_year == date('Y')): ?>
        setInterval(function() {
            // Only refresh if it's still the current month
            const now = new Date();
            if (now.getMonth() + 1 === <?php echo $current_month; ?> && now.getFullYear() === <?php echo $current_year; ?>) {
                location.reload();
            }
        }, 300000); // 5 minutes
        <?php endif; ?>

        // Show loading indicator for downloads
        function showLoadingIndicator(message) {
            const indicator = document.createElement('div');
            indicator.id = 'loadingIndicator';
            indicator.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 20px;
                border-radius: 10px;
                z-index: 9999;
                text-align: center;
            `;
            indicator.innerHTML = `<div class="spinner-border text-light" role="status"></div><p class="mt-2">${message}</p>`;
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                if (document.getElementById('loadingIndicator')) {
                    document.body.removeChild(indicator);
                }
            }, 3000);
        }

        // Enhanced download functions with loading indicators
        const originalDownloadExcel = downloadExcel;
        downloadExcel = function() {
            showLoadingIndicator('Generating Excel file...');
            setTimeout(originalDownloadExcel, 500);
        };

        const originalDownloadWord = downloadWord;
        downloadWord = function() {
            showLoadingIndicator('Generating Word document...');
            setTimeout(originalDownloadWord, 500);
        };
    </script>
</body>
</html>