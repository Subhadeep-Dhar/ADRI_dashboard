<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// Initialize variables
$username = '';
$user_id = 0;

// Check if cookies exist and match the current user ID and username
if (isset($_COOKIE['user_id']) && isset($_COOKIE['username'])) {
    $user_id = intval($_COOKIE['user_id']);
    $username = $_COOKIE['username'];

    // Fetch the user's details from the database
    $sql = "SELECT * FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Set session variables if user details match
            if ($user['username'] === $username) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
            }
        }
        $stmt->close();
    }
}

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get user's leave requests (simple query without JOIN)
$user_requests_query = "SELECT * FROM leave_request WHERE employee_name = ? ORDER BY created_at DESC";
$user_requests_stmt = $conn->prepare($user_requests_query);
$user_requests_stmt->bind_param("s", $_SESSION['username']);
$user_requests_stmt->execute();
$user_requests_result = $user_requests_stmt->get_result();

// Get all supervisors for name lookup
$supervisors = array();
$supervisor_result = $conn->query("SELECT id, name FROM supervisors");
while ($row = $supervisor_result->fetch_assoc()) {
    $supervisors[$row['id']] = $row['name'];
}

// Check if current user is a supervisor
$supervisor_check = $conn->prepare("SELECT id, name FROM supervisors WHERE name = ?");
$supervisor_check->bind_param("s", $_SESSION['username']);
$supervisor_check->execute();
$supervisor_check_result = $supervisor_check->get_result();
$is_supervisor = $supervisor_check_result->num_rows > 0;
$supervisor_data = $is_supervisor ? $supervisor_check_result->fetch_assoc() : null;

// Get pending requests for supervisor
$pending_requests_result = null;
if ($is_supervisor && $supervisor_data) {
    $pending_query = "SELECT * FROM leave_request WHERE supervisor_id = ? AND status = 'Pending' ORDER BY created_at ASC";
    $pending_stmt = $conn->prepare($pending_query);
    $pending_stmt->bind_param("i", $supervisor_data['id']);
    $pending_stmt->execute();
    $pending_requests_result = $pending_stmt->get_result();
}

// Get ALL leave requests under supervisor's management
$all_supervised_requests_result = null;
$supervised_stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
if ($is_supervisor && $supervisor_data) {
    $all_supervised_query = "SELECT * FROM leave_request WHERE supervisor_id = ? ORDER BY created_at DESC";
    $all_supervised_stmt = $conn->prepare($all_supervised_query);
    $all_supervised_stmt->bind_param("i", $supervisor_data['id']);
    $all_supervised_stmt->execute();
    $all_supervised_requests_result = $all_supervised_stmt->get_result();
    
    // Calculate statistics
    if ($all_supervised_requests_result->num_rows > 0) {
        $supervised_stats['total'] = $all_supervised_requests_result->num_rows;
        $all_supervised_requests_result->data_seek(0);
        while($stat_row = $all_supervised_requests_result->fetch_assoc()) {
            $status = strtolower($stat_row['status']);
            if (isset($supervised_stats[$status])) {
                $supervised_stats[$status]++;
            }
        }
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
    <title>Leave Dashboard - ADRI</title>
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
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #ffeaa7; color: #2d3436; }
        .status-approved { background-color: #00b894; color: white; }
        .status-rejected { background-color: #e17055; color: white; }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #27496F;
            color: white;
            font-weight: bold;
        }
        .card-header.supervisor-header {
            background-color: #27496f;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .btn-action {
            padding: 4px 8px;
            font-size: 12px;
            margin: 2px;
        }
        .stats-card {
            background: #27496f;
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-item {
            text-align: center;
            padding: 10px;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        .stats-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
        }
        .filter-tabs {
            margin-bottom: 15px;
        }
        .filter-btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
            <!-- end sidebar -->
            
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
                                            <a class="dropdown-toggle" data-toggle="dropdown"><span class="user_info"><h6 style="z-index:99"> <?php echo htmlspecialchars($_SESSION['username']); ?></h6></span></a>
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
                <!-- end topbar -->

                <div class="midde_cont">
                    <div class="container-fluid">
                        <div class="row column_title">
                            <div class="col-md-12">
                                <div class="page_title">
                                    <h2>Leave Request Dashboard</h2>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Action Buttons -->
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-12">
                                <a href="leave_request.php" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Submit New Leave Request
                                </a>
                                <?php if ($is_supervisor && $supervisor_data): ?>
                                    <span class="badge badge-info" style="margin-left: 10px; padding: 8px 12px; font-size: 12px;">
                                        You are a supervisor: <?php echo htmlspecialchars($supervisor_data['name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($is_supervisor && $supervisor_data): ?>
                        <!-- Supervisor Statistics -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="stats-card">
                                    <h5 style="margin-bottom: 20px; text-align: center; color:white;"><i class="fa fa-users"></i> Supervisor Overview</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="stats-item">
                                                <span class="stats-number"><?php echo $supervised_stats['total']; ?></span>
                                                <span class="stats-label">Total Requests</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stats-item">
                                                <span class="stats-number"><?php echo $supervised_stats['pending']; ?></span>
                                                <span class="stats-label">Pending</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stats-item">
                                                <span class="stats-number"><?php echo $supervised_stats['approved']; ?></span>
                                                <span class="stats-label">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stats-item">
                                                <span class="stats-number"><?php echo $supervised_stats['rejected']; ?></span>
                                                <span class="stats-label">Rejected</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_supervisor && $pending_requests_result && $pending_requests_result->num_rows > 0): ?>
                        <!-- Pending Requests for Supervisor -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 style="margin: 0;"><i class="fa fa-clock-o"></i> Pending Requests Requiring Your Approval (<?php echo $pending_requests_result->num_rows; ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Leave Type</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Days</th>
                                                        <th>Reason</th>
                                                        <th>Submitted</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    // Reset the result pointer if needed
                                                    if ($pending_requests_result->num_rows > 0) {
                                                        $pending_requests_result->data_seek(0);
                                                    }
                                                    while($request = $pending_requests_result->fetch_assoc()): 
                                                        $start_date = new DateTime($request['start_date']);
                                                        $end_date = new DateTime($request['end_date']);
                                                        $days = $start_date->diff($end_date)->days + 1;
                                                        
                                                        $approve_url = "process_leave.php?action=approve&id=" . $request['id'] . "&token=" . md5($request['id'] . "approve" . $supervisor_data['id']);
                                                        $reject_url = "process_leave.php?action=reject&id=" . $request['id'] . "&token=" . md5($request['id'] . "reject" . $supervisor_data['id']);
                                                    ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($request['employee_name']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($request['type']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                                                        <td><span class="badge badge-secondary"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span></td>
                                                        <td><?php echo $request['reason'] ? htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : '') : '<em>No reason provided</em>'; ?></td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                                        <td>
                                                            <a href="<?php echo $approve_url; ?>" class="btn btn-success btn-action" onclick="return confirm('Are you sure you want to approve this leave request?')">
                                                                <i class="fa fa-check"></i> Approve
                                                            </a>
                                                            <a href="<?php echo $reject_url; ?>" class="btn btn-danger btn-action" onclick="return confirm('Are you sure you want to reject this leave request?')">
                                                                <i class="fa fa-times"></i> Reject
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_supervisor && $all_supervised_requests_result && $all_supervised_requests_result->num_rows > 0): ?>
                        <!-- All Supervised Requests Management Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header supervisor-header">
                                        <h5 style="margin: 0; color:white;"><i class="fa fa-users"></i> All Leave Requests Under Your Supervision (<?php echo $all_supervised_requests_result->num_rows; ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Filter Buttons -->
                                        <div class="filter-tabs">
                                            <button class="btn btn-outline-primary btn-sm filter-btn" onclick="filterTable('all')" id="filter-all">
                                                All (<?php echo $supervised_stats['total']; ?>)
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm filter-btn" onclick="filterTable('pending')" id="filter-pending">
                                                Pending (<?php echo $supervised_stats['pending']; ?>)
                                            </button>
                                            <button class="btn btn-outline-success btn-sm filter-btn" onclick="filterTable('approved')" id="filter-approved">
                                                Approved (<?php echo $supervised_stats['approved']; ?>)
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm filter-btn" onclick="filterTable('rejected')" id="filter-rejected">
                                                Rejected (<?php echo $supervised_stats['rejected']; ?>)
                                            </button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-striped" id="supervisedTable">
                                                <thead>
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Leave Type</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Days</th>
                                                        <th>Status</th>
                                                        <th>Submitted</th>
                                                        <th>Reason</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    // Reset the result pointer
                                                    if ($all_supervised_requests_result->num_rows > 0) {
                                                        $all_supervised_requests_result->data_seek(0);
                                                    }
                                                    while($request = $all_supervised_requests_result->fetch_assoc()): 
                                                        $start_date = new DateTime($request['start_date']);
                                                        $end_date = new DateTime($request['end_date']);
                                                        $days = $start_date->diff($end_date)->days + 1;
                                                        
                                                        $status_class = 'status-' . strtolower($request['status']);
                                                        $row_class = 'status-row-' . strtolower($request['status']);
                                                        
                                                        $approve_url = "process_leave.php?action=approve&id=" . $request['id'] . "&token=" . md5($request['id'] . "approve" . $supervisor_data['id']);
                                                        $reject_url = "process_leave.php?action=reject&id=" . $request['id'] . "&token=" . md5($request['id'] . "reject" . $supervisor_data['id']);
                                                    ?>
                                                    <tr class="<?php echo $row_class; ?>">
                                                        <td><strong><?php echo htmlspecialchars($request['employee_name']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($request['type']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                                                        <td><span class="badge badge-secondary"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span></td>
                                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $request['status']; ?></span></td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                                        <td><?php echo $request['reason'] ? htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : '') : '<em>No reason provided</em>'; ?></td>
                                                        <td>
                                                            <?php if ($request['status'] === 'Pending'): ?>
                                                                <a href="<?php echo $approve_url; ?>" class="btn btn-success btn-action" onclick="return confirm('Are you sure you want to approve this leave request?')">
                                                                    <i class="fa fa-check"></i> Approve
                                                                </a>
                                                                <a href="<?php echo $reject_url; ?>" class="btn btn-danger btn-action" onclick="return confirm('Are you sure you want to reject this leave request?')">
                                                                    <i class="fa fa-times"></i> Reject
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary">
                                                                    <?php echo $request['status']; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- User's Leave Requests -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 style="margin: 0; color:white;"><i class="fa fa-user">&nbsp;</i> My Leave Requests</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($user_requests_result && $user_requests_result->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Leave Type</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Days</th>
                                                        <th>Supervisor</th>
                                                        <th>Status</th>
                                                        <th>Submitted</th>
                                                        <th>Reason</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    // Reset the result pointer if needed
                                                    if ($user_requests_result->num_rows > 0) {
                                                        $user_requests_result->data_seek(0);
                                                    }
                                                    while($request = $user_requests_result->fetch_assoc()): 
                                                        $start_date = new DateTime($request['start_date']);
                                                        $end_date = new DateTime($request['end_date']);
                                                        $days = $start_date->diff($end_date)->days + 1;
                                                        
                                                        $status_class = 'status-' . strtolower($request['status']);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($request['type']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                                                        <td><span class="badge badge-secondary"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span></td>
                                                        <td><?php echo isset($supervisors[$request['supervisor_id']]) ? htmlspecialchars($supervisors[$request['supervisor_id']]) : 'Unknown'; ?></td>
                                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $request['status']; ?></span></td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                                        <td><?php echo $request['reason'] ? htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : '') : '<em>No reason provided</em>'; ?></td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-center" style="padding: 40px;">
                                            <i class="fa fa-file-text-o" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                            <h5 style="color: #6c757d;">No Leave Requests Found</h5>
                                            <p style="color: #9a9a9a;">You haven't submitted any leave requests yet.</p>
                                            <a href="leave_request.php" class="btn btn-primary">Submit Your First Request</a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php
// Add this PHP code after the existing queries (around line 75, after the supervised stats calculation)

// Get upcoming leave requests (approved leaves within next 2 months)
$upcoming_leave_query = "SELECT * FROM leave_request 
                        WHERE status = 'Approved' 
                        AND start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 MONTH)
                        ORDER BY start_date ASC";
$upcoming_leave_result = $conn->query($upcoming_leave_query);

// If user is supervisor, get upcoming leaves for their supervised employees
$upcoming_supervised_leave_result = null;
if ($is_supervisor && $supervisor_data) {
    $upcoming_supervised_query = "SELECT * FROM leave_request 
                                 WHERE status = 'Approved' 
                                 AND supervisor_id = ? 
                                 AND start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 MONTH)
                                 ORDER BY start_date ASC";
    $upcoming_supervised_stmt = $conn->prepare($upcoming_supervised_query);
    $upcoming_supervised_stmt->bind_param("i", $supervisor_data['id']);
    $upcoming_supervised_stmt->execute();
    $upcoming_supervised_leave_result = $upcoming_supervised_stmt->get_result();
}
?>

<!-- Add this HTML section after the "My Leave Requests" section and before the footer -->

<?php if ($is_supervisor && $upcoming_supervised_leave_result && $upcoming_supervised_leave_result->num_rows > 0): ?>
<!-- Upcoming Supervised Leave Requests -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header supervisor-header">
                <h5 style="margin: 0; color:white;"><i class="fa fa-calendar"></i> Upcoming Leaves - Your Team (Next 2 Months)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Days Until Leave</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($leave = $upcoming_supervised_leave_result->fetch_assoc()): 
                                $start_date = new DateTime($leave['start_date']);
                                $end_date = new DateTime($leave['end_date']);
                                $today = new DateTime();
                                $days = $start_date->diff($end_date)->days + 1;
                                $days_until = $today->diff($start_date)->days;
                                
                                // Determine urgency color
                                $urgency_class = '';
                                if ($days_until <= 7) {
                                    $urgency_class = 'badge-danger';
                                } elseif ($days_until <= 14) {
                                    $urgency_class = 'badge-warning';
                                } else {
                                    $urgency_class = 'badge-info';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($leave['employee_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($leave['type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['end_date'])); ?></td>
                                <td><span class="badge badge-secondary"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span></td>
                                <td>
                                    <span class="badge <?php echo $urgency_class; ?>">
                                        <?php 
                                        if ($days_until == 0) {
                                            echo "Today";
                                        } elseif ($days_until == 1) {
                                            echo "Tomorrow";
                                        } else {
                                            echo $days_until . " days";
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $leave['reason'] ? htmlspecialchars(substr($leave['reason'], 0, 50)) . (strlen($leave['reason']) > 50 ? '...' : '') : '<em>No reason provided</em>'; ?></td>
                                <td><span class="status-badge status-approved"><?php echo $leave['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- All Upcoming Leave Requests (Organization Wide) -->
<?php if ($upcoming_leave_result && $upcoming_leave_result->num_rows > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 style="margin: 0; color:white;"><i class="fa fa-calendar-o"></i> &nbsp; All Upcoming Leaves - Organization (Next 2 Months)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <!--<th>Days Until Leave</th>-->
                                <!--<th>Supervisor</th>-->
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset the result pointer if needed
                            if ($upcoming_leave_result->num_rows > 0) {
                                $upcoming_leave_result->data_seek(0);
                            }
                            while($leave = $upcoming_leave_result->fetch_assoc()): 
                                $start_date = new DateTime($leave['start_date']);
                                $end_date = new DateTime($leave['end_date']);
                                $today = new DateTime();
                                $days = $start_date->diff($end_date)->days + 1;
                                $days_until = $today->diff($start_date)->days;
                                
                                // Determine urgency color
                                $urgency_class = '';
                                if ($days_until <= 7) {
                                    $urgency_class = 'badge-danger';
                                } elseif ($days_until <= 14) {
                                    $urgency_class = 'badge-warning';
                                } else {
                                    $urgency_class = 'badge-info';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($leave['employee_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($leave['type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['end_date'])); ?></td>
                                <td><span class="badge badge-secondary"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span></td>
                                
                                <td><?php echo $leave['reason'] ? htmlspecialchars(substr($leave['reason'], 0, 50)) . (strlen($leave['reason']) > 50 ? '...' : '') : '<em>No reason provided</em>'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 style="margin: 0; color:white;"><i class="fa fa-calendar-o"></i> All Upcoming Leaves - Organization (Next 2 Months)</h5>
            </div>
            <div class="card-body">
                <div class="text-center" style="padding: 40px;">
                    <i class="fa fa-calendar-o" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                    <h5 style="color: #6c757d;">No Upcoming Leaves</h5>
                    <p style="color: #9a9a9a;">No approved leaves scheduled for the next 2 months.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Add this to the cleanup section at the end of the file (before closing the existing prepared statements)
if (isset($upcoming_supervised_stmt)) {
    $upcoming_supervised_stmt->close();
}
?>

                <!-- footer -->
                <div class="container-fluid">
                    <div class="footer">
                        <p>&copy; 2025 ADRI India, All rights reserved | <a style="color:#101F60" href="developer.php"> Developer contact </a></p>
                    </div>
                </div>
            </div>
            <!-- end dashboard inner -->
        </div>
    </div>
    
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
    <script src="js/chart_custom_style1.js"></script>
</body>
</html>

<?php
// Close prepared statements and connection
if (isset($user_requests_stmt)) {
    $user_requests_stmt->close();
}
if (isset($supervisor_check)) {
    $supervisor_check->close();
}
if (isset($pending_stmt)) {
    $pending_stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
?>