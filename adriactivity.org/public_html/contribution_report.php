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

// Query to fetch all users and their contributions for the selected month
$sql_contributions = "SELECT 
    u.username,
    u.user_id,
    COUNT(a.id) as total_activities,
    MIN(a.start_time) as first_activity_time,
    MAX(a.end_time) as last_activity_time,
    GROUP_CONCAT(DISTINCT a.activity SEPARATOR ', ') as activity_types
FROM users u
LEFT JOIN availability a ON u.username = a.username 
    AND MONTH(a.activity_date) = ? 
    AND YEAR(a.activity_date) = ?
GROUP BY u.username, u.user_id
ORDER BY total_activities DESC, u.username ASC";

$contributions = [];
$total_users = 0;
$active_users = 0;
$total_activities = 0;
$most_active_user = '';
$max_activities = 0;

if ($stmt = $conn->prepare($sql_contributions)) {
    $stmt->bind_param("ii", $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $contributions[] = $row;
        $total_users++;
        
        if ($row['total_activities'] > 0) {
            $active_users++;
            $total_activities += $row['total_activities'];
        }
        
        if ($row['total_activities'] > $max_activities) {
            $max_activities = $row['total_activities'];
            $most_active_user = $row['username'];
        }
    }
    $stmt->close();
}

// Calculate additional statistics
$avg_activities_per_user = $active_users > 0 ? round($total_activities / $active_users, 1) : 0;
$participation_rate = $total_users > 0 ? round(($active_users / $total_users) * 100, 1) : 0;

// Get detailed daily contributions for the month
$sql_daily = "SELECT 
    activity_date,
    username,
    start_time,
    end_time,
    activity
FROM availability 
WHERE MONTH(activity_date) = ? AND YEAR(activity_date) = ?
ORDER BY activity_date DESC, start_time ASC";

$daily_contributions = [];
if ($stmt = $conn->prepare($sql_daily)) {
    $stmt->bind_param("ii", $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $daily_contributions[] = $row;
    }
    $stmt->close();
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
    <title>Contribution Report - Adri Dashboard</title>
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
        th, td {
            color: black;
        }

        .report-header {
            /* background: linear-gradient(135deg, #28a745 0%, #20c997 100%); */
            /* background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); */
            /* background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); */
            /* background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);  Hmm */
            /* background: linear-gradient(135deg, #1e293b 0%, #334155 100%); */
            /* background: linear-gradient(135deg, #1a202c 0%, #2d3748 50%, #4299e1 100%); */
            /* background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); */
            /* background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); hmm */
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
            color: #28a745;
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

        .activity-details {
            font-size: 0.9em;
            color: #666;
        }

        .user-rank {
            background: linear-gradient(45deg, #ffd700, #ffed4a);
            color: #333;
            padding: 3px 8px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.8em;
        }

        .activity-badge {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
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
                        
                        <!-- Add Contribution Report to sidebar -->
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
                                    <h1 style="color:white">Contribution Report</h1>
                                    <h3 style="color:white"><?php echo $month_name; ?></h3>
                                    <p style="color:white">Comprehensive monthly user activity and contribution analysis</p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <h5 style="color:white">Generated on: <?php echo date('F j, Y'); ?></h5>
                                    <h6 style="color:white">Report ID: <?php echo strtoupper(substr(md5('contribution_' . $month_name . date('Y-m-d')), 0, 8)); ?></h6>
                                </div>
                            </div>
                        </div>

                        <!-- Month Selector -->
                        <div class="month-selector no-print">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Select Month & Year</h4>
                                    <form method="GET" action="contribution_report.php" class="form-inline">
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
                            <p>Export this contribution report in your preferred format</p>
                            <button onclick="downloadExcel()" class="btn-download btn-excel">📊 Download Excel</button>
                            <button onclick="downloadWord()" class="btn-download btn-word">📄 Download Word</button>
                            <button onclick="window.print()" class="btn-download" style="background-color: #6c757d; color: white;">🖨️ Print Report</button>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number"><?php echo $total_users; ?></div>
                                    <h5>Total Users</h5>
                                    <p class="text-muted">Registered</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number" style="color: #28a745;"><?php echo $active_users; ?></div>
                                    <h5>Active Users</h5>
                                    <p class="text-muted">Contributing</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number" style="color: #ffc107;"><?php echo $total_activities; ?></div>
                                    <h5>Total Activities</h5>
                                    <p class="text-muted">This Month</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="stats-number" style="color: #17a2b8;"><?php echo $participation_rate; ?>%</div>
                                    <h5>Participation Rate</h5>
                                    <p class="text-muted">User Engagement</p>
                                </div>
                            </div>
                        </div>

                        <!-- User Contributions Summary -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="white_shd full margin_bottom_30">
                                    <div class="full graph_head">
                                        <div class="heading1 margin_0">
                                            <h2>👥 User Contribution Summary - <?php echo $month_name; ?></h2>
                                        </div>
                                    </div>
                                    <div class="table_section padding_infor_info">
                                        <div class="table-responsive-sm">
                                            <table class="table table-striped" id="contributionsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Username</th>
                                                        <th>Total Activities</th>
                                                        <!-- <th>First Activity</th>
                                                        <th>Last Activity</th> -->
                                                        <th>Activity Types</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (count($contributions) > 0) {
                                                        $rank = 1;
                                                        foreach ($contributions as $contribution) {
                                                            $status = $contribution['total_activities'] > 0 ? 'Active' : 'Inactive';
                                                            $status_class = $contribution['total_activities'] > 0 ? 'badge badge-success' : 'badge badge-secondary';
                                                            
                                                            echo "<tr>";
                                                            echo "<td>";
                                                            if ($contribution['total_activities'] > 0) {
                                                                echo "<span class='user-rank'>#" . $rank . "</span>";
                                                                $rank++;
                                                            } else {
                                                                echo "<span class='badge badge-light'>-</span>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td><strong>" . htmlspecialchars($contribution['username']) . "</strong>";
                                                            if ($contribution['username'] == $most_active_user && $max_activities > 0) {
                                                                echo " <span class='badge badge-warning'>⭐ Top Contributor</span>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td><span class='badge badge-primary'>" . $contribution['total_activities'] . "</span></td>";
                                                            // echo "<td>" . ($contribution['first_activity_time'] ? date('h:i A', strtotime($contribution['first_activity_time'])) : '-') . "</td>";
                                                            // echo "<td>" . ($contribution['last_activity_time'] ? date('h:i A', strtotime($contribution['last_activity_time'])) : '-') . "</td>";
                                                            echo "<td class='activity-details'>";
                                                            if ($contribution['activity_types']) {
                                                                $activities = explode(', ', $contribution['activity_types']);
                                                                foreach (array_unique($activities) as $activity) {
                                                                    if (trim($activity)) {
                                                                        echo "<span class='activity-badge'>" . htmlspecialchars(trim($activity)) . "</span>";
                                                                    }
                                                                }
                                                            } else {
                                                                echo "<span class='text-muted'>No activities</span>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td><span class='" . $status_class . "'>" . $status . "</span></td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='7' class='text-center'>No users found.</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Activity Breakdown -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="white_shd full margin_bottom_30">
                                    <div class="full graph_head">
                                        <div class="heading1 margin_0">
                                            <h2>📅 Daily Activity Breakdown - <?php echo $month_name; ?></h2>
                                        </div>
                                    </div>
                                    <div class="table_section padding_infor_info">
                                        <div class="table-responsive-sm">
                                            <table class="table table-hover" id="dailyActivitiesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Username</th>
                                                        <th>Start Time</th>
                                                        <th>End Time</th>
                                                        <th>Activity</th>
                                                        <th>Duration</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (count($daily_contributions) > 0) {
                                                        foreach ($daily_contributions as $daily) {
                                                            $start_time = strtotime($daily['start_time']);
                                                            $end_time = strtotime($daily['end_time']);
                                                            $duration = $end_time - $start_time;
                                                            $duration_formatted = gmdate("H:i", $duration);
                                                            
                                                            echo "<tr>";
                                                            echo "<td><strong>" . date('M d, Y', strtotime($daily['activity_date'])) . "</strong></td>";
                                                            echo "<td>" . htmlspecialchars($daily['username']) . "</td>";
                                                            echo "<td>" . date('h:i A', strtotime($daily['start_time'])) . "</td>";
                                                            echo "<td>" . date('h:i A', strtotime($daily['end_time'])) . "</td>";
                                                            echo "<td><span class='activity-badge'>" . htmlspecialchars($daily['activity']) . "</span></td>";
                                                            echo "<td><span class='badge badge-info'>" . $duration_formatted . "</span></td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='6' class='text-center'>No activities found for " . $month_name . "</td></tr>";
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
                                            <h2>📈 Monthly Contribution Analysis</h2>
                                        </div>
                                    </div>
                                    <div class="padding_infor_info">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4>Key Performance Indicators</h4>
                                                <ul class="list-group">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        User Participation Rate
                                                        <span class="badge badge-primary badge-pill">
                                                            <?php echo $participation_rate; ?>%
                                                        </span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Average Activities per Active User
                                                        <span class="badge badge-secondary badge-pill">
                                                            <?php echo $avg_activities_per_user; ?>
                                                        </span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Top Contributor
                                                        <span class="badge badge-warning badge-pill">
                                                            <?php echo $most_active_user ? htmlspecialchars($most_active_user) : 'None'; ?>
                                                        </span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Peak Activities Count
                                                        <span class="badge badge-success badge-pill">
                                                            <?php echo $max_activities; ?>
                                                        </span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h4>Report Information</h4>
                                                <div class="alert alert-info">
                                                    <p><strong>Report Generation:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                                                    <p><strong>Data Period:</strong> <?php echo $month_name; ?></p>
                                                    <p><strong>Total Data Points:</strong> <?php echo count($daily_contributions); ?> activity records</p>
                                                    <?php if ($current_month == date('n') && $current_year == date('Y')): ?>
                                                        <p><strong>Status:</strong> <span class="badge badge-success">Current Month - Live Data</span></p>
                                                    <?php else: ?>
                                                        <p><strong>Status:</strong> <span class="badge badge-secondary">Historical Data - Archived</span></p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if ($participation_rate < 50): ?>
                                                <div class="alert alert-warning">
                                                    <h6>💡 Recommendation</h6>
                                                    <p>Participation rate is below 50%. Consider implementing engagement initiatives to increase user activity.</p>
                                                </div>
                                                <?php elseif ($participation_rate >= 80): ?>
                                                <div class="alert alert-success">
                                                    <h6>🎉 Excellent Engagement!</h6>
                                                    <p>Great job! User participation rate is excellent this month.</p>
                                                </div>
                                                <?php endif; ?>
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
    // Create workbook with multiple sheets
    const wb = XLSX.utils.book_new();
    
    // Summary Sheet
    const summaryData = [
        ['ADRI Contribution Report Summary'],
        ['Month/Year', '<?php echo $month_name; ?>'],
        ['Report Generated', '<?php echo date('F j, Y \a\t g:i A'); ?>'],
        ['Total Users', '<?php echo $total_users; ?>'],
        ['Active Users', '<?php echo $active_users; ?>'],
        ['Total Activities', '<?php echo $total_activities; ?>'],
        ['Participation Rate', '<?php echo $participation_rate; ?>%'],
        ['Top Contributor', '<?php echo $most_active_user ? htmlspecialchars($most_active_user) : 'None'; ?>'],
        ['Peak Activities Count', '<?php echo $max_activities; ?>'],
        ['Average Activities per Active User', '<?php echo $avg_activities_per_user; ?>'],
        [],
        ['Key Performance Indicators'],
        ['User Participation Rate', '<?php echo $participation_rate; ?>%'],
        ['Average Activities per Active User', '<?php echo $avg_activities_per_user; ?>'],
        ['Top Contributor', '<?php echo $most_active_user ? htmlspecialchars($most_active_user) : 'None'; ?>'],
        ['Peak Activities Count', '<?php echo $max_activities; ?>']
    ];
    
    const summarySheet = XLSX.utils.aoa_to_sheet(summaryData);
    XLSX.utils.book_append_sheet(wb, summarySheet, 'Summary');
    
    // User Contributions Sheet
    const contributionsTable = document.getElementById('contributionsTable');
    const contributionsSheet = XLSX.utils.table_to_sheet(contributionsTable);
    XLSX.utils.book_append_sheet(wb, contributionsSheet, 'User Contributions');
    
    // Daily Activities Sheet
    const dailyActivitiesTable = document.getElementById('dailyActivitiesTable');
    const dailyActivitiesSheet = XLSX.utils.table_to_sheet(dailyActivitiesTable);
    XLSX.utils.book_append_sheet(wb, dailyActivitiesSheet, 'Daily Activities');
    
    // Generate filename with current date and month
    const currentDate = new Date();
    const fileName = `ADRI_Contribution_Report_<?php echo $month_name; ?>_${currentDate.getFullYear()}.xlsx`;
    
    // Download the file
    XLSX.writeFile(wb, fileName);
}

// Function to download Word document
function downloadWord() {
    // Create HTML content for Word document
    let wordContent = `
        <html>
        <head>
            <meta charset="utf-8">
            <title>ADRI Contribution Report - <?php echo $month_name; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .stats-grid { display: flex; justify-content: space-around; margin: 20px 0; }
                .stat-item { text-align: center; padding: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .section-title { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
                .badge { background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8em; }
                .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
                .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
                .alert-warning { background-color: #fff3cd; border: 1px solid #ffeaa7; }
                .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 ADRI Contribution Report</h1>
                <h2><?php echo $month_name; ?></h2>
                <p>Comprehensive monthly user activity and contribution analysis</p>
                <p><strong>Generated on:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                <p><strong>Report ID:</strong> <?php echo strtoupper(substr(md5('contribution_' . $month_name . date('Y-m-d')), 0, 8)); ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $total_activities; ?></h3>
                    <p>Total Activities</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo $participation_rate; ?>%</h3>
                    <p>Participation Rate</p>
                </div>
            </div>
            
            <h2 class="section-title">👥 User Contribution Summary</h2>
            ${document.getElementById('contributionsTable').outerHTML}
            
            <h2 class="section-title">📅 Daily Activity Breakdown</h2>
            ${document.getElementById('dailyActivitiesTable').outerHTML}
            
            <h2 class="section-title">📈 Monthly Contribution Analysis</h2>
            <div class="alert alert-info">
                <h4>Key Performance Indicators</h4>
                <ul>
                    <li>User Participation Rate: <?php echo $participation_rate; ?>%</li>
                    <li>Average Activities per Active User: <?php echo $avg_activities_per_user; ?></li>
                    <li>Top Contributor: <?php echo $most_active_user ? htmlspecialchars($most_active_user) : 'None'; ?></li>
                    <li>Peak Activities Count: <?php echo $max_activities; ?></li>
                </ul>
            </div>
            
            <div class="alert alert-info">
                <h4>Report Information</h4>
                <p><strong>Report Generation:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                <p><strong>Data Period:</strong> <?php echo $month_name; ?></p>
                <p><strong>Total Data Points:</strong> ${document.getElementById('dailyActivitiesTable').rows.length - 1} activity records</p>
            </div>
            
            <div style="margin-top: 30px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
                <p>&copy; 2025 ADRI India, All rights reserved</p>
            </div>
        </body>
        </html>
    `;
    
    // Create and download the Word document
    const blob = new Blob([wordContent], {
        type: 'application/msword'
    });
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `ADRI_Contribution_Report_<?php echo $month_name; ?>_${new Date().getFullYear()}.doc`;
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Clean up the URL object
    URL.revokeObjectURL(link.href);
}

// Additional utility functions for better user experience
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to download buttons
    const excelBtn = document.querySelector('button[onclick="downloadExcel()"]');
    const wordBtn = document.querySelector('button[onclick="downloadWord()"]');
    
    if (excelBtn) {
        excelBtn.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '⏳ Generating Excel...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 2000);
        });
    }
    
    if (wordBtn) {
        wordBtn.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '⏳ Generating Word...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 2000);
        });
    }
    
    // Add table search functionality
    addTableSearch();
    
    // Add responsive table handling
    makeTablesResponsive();
});

// Function to add search functionality to tables
function addTableSearch() {
    const tables = ['contributionsTable', 'dailyActivitiesTable'];
    
    tables.forEach(tableId => {
        const table = document.getElementById(tableId);
        if (table) {
            // Create search input
            const searchContainer = document.createElement('div');
            searchContainer.className = 'table-search-container';
            searchContainer.style.marginBottom = '15px';
            
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'form-control';
            searchInput.placeholder = `Search in ${tableId.replace('Table', '').replace(/([A-Z])/g, ' $1').toLowerCase()}...`;
            searchInput.style.maxWidth = '300px';
            
            searchContainer.appendChild(searchInput);
            table.parentNode.insertBefore(searchContainer, table);
            
            // Add search functionality
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                    
                    row.style.display = found ? '' : 'none';
                }
            });
        }
    });
}

// Function to make tables responsive
function makeTablesResponsive() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        // Add horizontal scroll for mobile
        if (window.innerWidth <= 768) {
            table.style.fontSize = '12px';
        }
        
        // Add zebra striping if not already present
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            if (index % 2 === 0) {
                row.style.backgroundColor = '#f8f9fa';
            }
        });
    });
}

// Window resize handler for responsive tables
window.addEventListener('resize', function() {
    makeTablesResponsive();
});

</script>

</body>
</html>