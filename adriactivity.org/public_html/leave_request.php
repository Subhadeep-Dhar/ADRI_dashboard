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

// Include email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Function to calculate leave balance
// Replace your existing leave balance calculation PHP code with this:

function getLeaveBalance($username, $conn) {
    // Calculate total approved regular leaves for current year (excluding weekends)
    $current_year = date('Y');
    $sql = "SELECT start_date, end_date
            FROM leave_request 
            WHERE employee_name = ? 
            AND type = 'Regular Leave' 
            AND status = 'Approved'
            AND YEAR(start_date) = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $username, $current_year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total_weekdays = 0;
        
        while ($row = $result->fetch_assoc()) {
            $start_date = new DateTime($row['start_date']);
            $end_date = new DateTime($row['end_date']);
            
            // Count only weekdays (Monday to Friday)
            $current_date = clone $start_date;
            while ($current_date <= $end_date) {
                $day_of_week = $current_date->format('N'); // 1 = Monday, 7 = Sunday
                if ($day_of_week >= 1 && $day_of_week <= 5) { // Monday to Friday
                    $total_weekdays++;
                }
                $current_date->add(new DateInterval('P1D'));
            }
        }
        
        $stmt->close();
        
        return 15 - $total_weekdays; // 15 days limit minus used weekdays
    }
    return 15; // Default to full balance if query fails
}

// Use this to calculate the leave balance for JavaScript
$leave_balance = getLeaveBalance($username, $conn); // Replace $username with actual username variable

// Function to check for overlapping leave requests

// Function to calculate working days (excluding weekends)
// Function to calculate working days (excluding weekends - Saturday and Sunday)
function calculateWorkingDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include end date in calculation
    
    $working_days = 0;
    $current = clone $start;
    
    while ($current < $end) {
        $day_of_week = $current->format('w'); // 0 = Sunday, 6 = Saturday
        
        // Exclude Saturday (6) and Sunday (0) - only count Monday(1) to Friday(5)
        if ($day_of_week != 0 && $day_of_week != 6) {
            $working_days++;
        }
        
        $current->modify('+1 day');
    }
    
    return $working_days;
}

// Function to check for overlapping leave requests
function hasOverlappingLeave($username, $start_date, $end_date, $conn) {
    $sql = "SELECT COUNT(*) as count FROM leave_request 
            WHERE employee_name = ? 
            AND status IN ('Pending', 'Approved')
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssss", $username, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] > 0;
    }
    return false;
}

// Get current leave balance
$leave_balance = getLeaveBalance($_SESSION['username'], $conn);

// Fetch supervisors for dropdown
$supervisors_query = "SELECT id, name FROM supervisors ORDER BY name";
$supervisors_result = $conn->query($supervisors_query);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_SESSION['username'])) {
        $employee_name = $_SESSION['username'];
        $supervisor_id = $_POST['supervisor_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $leave_type = $_POST['leave_type'];
        $reason = trim($_POST['reason']);

        // Validate inputs
        if (!empty($supervisor_id) && !empty($start_date) && !empty($end_date) && !empty($leave_type)) {
            
            // Validate date logic
            if ($start_date <= $end_date) {
                
                // Check for past dates
                // if ($start_date < date('Y-m-d')) {
                //     $error_message = "Cannot request leave for past dates. Please select a future date.";
                // } 
                
                // Check for overlapping leave requests
                if (hasOverlappingLeave($employee_name, $start_date, $end_date, $conn)) {
                    $error_message = "You already have a pending or approved leave request that overlaps with these dates. Please check your existing requests.";
                }
                else {
                    // Calculate requested leave days (excluding weekends)
                    $requested_days = calculateWorkingDays($start_date, $end_date);
                    
                    // Check leave balance only for regular leave
                    if ($leave_type === 'Regular Leave') {
                        if ($requested_days > $leave_balance) {
                            if ($leave_balance <= 0) {
                                $error_message = "You do not have any regular leave days remaining for this year. Your current balance is 0 days.";
                            } else {
                                $error_message = "Insufficient leave balance. You have only $leave_balance regular leave days remaining, but requested $requested_days working days (weekends excluded).";
                            }
                        } else {
                            // Proceed with leave request for regular leave
                            $proceed_with_request = true;
                        }
                    } else {
                        // OOD has no limit, proceed with request
                        $proceed_with_request = true;
                    }
                    
                    if (isset($proceed_with_request) && $proceed_with_request) {
                        // Validate reason for regular leave if more than 3 working days
                        if ($leave_type === 'Regular Leave' && $requested_days > 3 && empty($reason)) {
                            $error_message = "Reason is required for regular leave requests longer than 3 working days.";
                        } else {
                            // Insert leave request
                            $sql = "INSERT INTO leave_request (employee_name, supervisor_id, start_date, end_date, type, reason, status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
                            
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("sissss", $employee_name, $supervisor_id, $start_date, $end_date, $leave_type, $reason);
                                
                                if ($stmt->execute()) {
                                    $leave_request_id = $conn->insert_id;
                                    
                                    // Send email to supervisor
                                    if (sendLeaveRequestEmail($leave_request_id, $supervisor_id, $employee_name, $start_date, $end_date, $leave_type, $reason, $requested_days)) {
                                        $success_message = "Leave request submitted successfully! An email notification has been sent to your supervisor for approval.";
                                        // Refresh leave balance after successful submission
                                        $leave_balance = getLeaveBalance($_SESSION['username'], $conn);
                                    } else {
                                        $success_message = "Leave request submitted successfully, but email notification failed. Please inform your supervisor manually.";
                                    }
                                } else {
                                    $error_message = "Error submitting leave request: " . $stmt->error;
                                }
                                $stmt->close();
                            } else {
                                $error_message = "Error preparing statement: " . $conn->error;
                            }
                        }
                    }
                }
            } else {
                $error_message = "End date must be after or equal to start date.";
            }
        } else {
            $error_message = "All required fields must be filled.";
        }
    } else {
        $error_message = "You must be logged in to submit a leave request.";
    }
}


function sendLeaveRequestEmail($leave_request_id, $supervisor_id, $employee_name, $start_date, $end_date, $leave_type, $reason, $requested_days) {
    global $conn;
    
    // Get supervisor email
    $supervisor_query = "SELECT name, email FROM supervisors WHERE id = ?";
    if ($stmt = $conn->prepare($supervisor_query)) {
        $stmt->bind_param("i", $supervisor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $supervisor = $result->fetch_assoc();
            $supervisor_name = $supervisor['name'];
            $supervisor_email = $supervisor['email'];
            
            $mail = new PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'testing9832test@gmail.com'; // Replace with your email
                $mail->Password = 'ysrt pjzw nior kcgm'; // Replace with your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('testing9832test@gmail.com', 'ADRI India Leave Request');
                $mail->addAddress($supervisor_email, $supervisor_name);
                
                $mail->isHTML(true);
                $mail->Subject = "🔔 Leave Request from $employee_name - Action Required";
                
                // Create approval/rejection URLs
                $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                $approve_url = $base_url . "/process_leave.php?action=approve&id=" . $leave_request_id . "&token=" . md5($leave_request_id . "approve" . $supervisor_id);
                $reject_url = $base_url . "/process_leave.php?action=reject&id=" . $leave_request_id . "&token=" . md5($leave_request_id . "reject" . $supervisor_id);
                
                // Get current leave balance for display
                $current_balance = getLeaveBalance($employee_name, $conn);
                
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; padding: 0; background-color: #f8f9fa;'>
                        <!-- Header -->
                        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                            <h1 style='color: white; margin: 0; font-size: 24px; font-weight: bold;'>Leave Request Approval</h1>
                            <p style='color: #e8eaed; margin: 10px 0 0 0; font-size: 20px;'>ADRI India</p>
                        </div>
                        
                        <!-- Content -->
                        <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                            <p style='font-size: 20px; color: #2c3e50; margin-bottom: 25px;'>
                                Dear <strong>$supervisor_name</strong>,
                            </p>
                            
                            <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>
                                <p style='margin: 0; color: #856404; font-weight: bold; font-size: 20px;'>
                                    A new leave request requires your immediate attention and approval.
                                </p>
                            </div>
                            
                            <!-- Employee Info -->
                            <div style='background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 4px solid #007bff;'>
                                <h3 style='color: #495057; margin: 0 0 20px 0; font-size: 20px;'>📋 Request Details</h3>
                                <table style='width: 100%; border-collapse: collapse;'>
                                    <tr style='border-bottom: 1px solid #dee2e6;'>
                                        <td style='padding: 12px 0; font-weight: bold; color: #495057; width: 35%; font-size: 20px;'>Employee:</td>
                                        <td style='padding: 12px 0; color: #6c757d; font-size: 20px;'><strong>$employee_name</strong></td>
                                    </tr>
                                    <tr style='border-bottom: 1px solid #dee2e6;'>
                                        <td style='padding: 12px 0; font-weight: bold; color: #495057; font-size: 20px;'>Leave Type:</td>
                                        <td style='padding: 12px 0; color: #6c757d;'>
                                            <span style='background: " . ($leave_type === 'Regular Leave' ? '#e7f3ff; color: #dee2e6' : '#fff3e0; color: #dee2e6') . "; padding: 4px 12px; border-radius: 20px; font-size: 20px; font-weight: bold;'>
                                                $leave_type
                                            </span>
                                        </td>
                                    </tr>
                                    <tr style='border-bottom: 1px solid #dee2e6;'>
                                        <td style='padding: 12px 0; font-weight: bold; color: #495057; font-size: 20px;'>Duration:</td>
                                        <td style='padding: 12px 0; color: #6c757d; font-size: 20px;'>
                                            <strong>" . date('M j, Y', strtotime($start_date)) . "</strong> to <strong>" . date('M j, Y', strtotime($end_date)) . "</strong>
                                            <br><small style='color: #28a745; font-weight: bold;'>($requested_days " . ($requested_days == 1 ? 'day' : 'days') . ")</small>
                                        </td>
                                    </tr>";
                
                if ($leave_type === 'Regular Leave') {
                    $remaining_after = $current_balance - $requested_days;
                    $balance_color = $remaining_after >= 5 ? '#28a745' : ($remaining_after >= 0 ? '#ffc107' : '#dc3545');
                    $mail->Body .= "
                                    <tr style='border-bottom: 1px solid #dee2e6;'>
                                        <td style='padding: 12px 0; font-weight: bold; color: #495057; font-size: 20px;'>Leave Balance:</td>
                                        <td style='padding: 12px 0; color: #6c757d; font-size: 20px;'>
                                            Current: <strong style='color: #007bff;'>$current_balance days</strong><br>
                                            After approval: <strong style='color: $balance_color;'>$remaining_after days</strong>
                                        </td>
                                    </tr>";
                }
                
                $mail->Body .= "
                                    <tr>
                                        <td style='padding: 12px 0; font-weight: bold; color: #495057; vertical-align: top; font-size: 20px;'>Reason:</td>
                                        <td style='padding: 12px 0; color: #6c757d; line-height: 1.5; font-size: 20px;'>" . 
                                        ($reason ? '<em>"' . htmlspecialchars($reason) . '"</em>' : '<span style="color: #adb5bd;">No specific reason provided</span>') . 
                                        "</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div style='text-align: center; margin: 30px 0;'>
                                                                
                                <div style='margin-bottom: 15px;'>
                                    <a href='$approve_url' 
                                       style='display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #28a745, #20c997); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 20px; margin: 0 10px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); transition: all 0.3s ease;'>
                                        APPROVE REQUEST
                                    </a>
                                </div>
                                
                                <div>
                                    <a href='$reject_url' 
                                       style='display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 20px; margin: 0 10px; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3); transition: all 0.3s ease;'>
                                        REJECT REQUEST
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Important Notes -->
                            <div style='background: #e8f4fd; border: 1px solid #b8daff; padding: 20px; border-radius: 8px; margin: 25px 0;'>
                                <h4 style='color: #004085; margin: 0 0 10px 0; font-size: 16px;'>📌 Important Notes:</h4>
                                <ul style='color: #004085; margin: 0; padding-left: 20px; line-height: 1.6;'>
                                    <li>Click the buttons above to approve or reject this request instantly</li>
                                    <li>The employee will be automatically notified of your decision via email</li>
                                    " . ($leave_type === 'Regular Leave' ? "<li>This request will deduct from the employee's annual leave balance of 15 days</li>" : "<li>OOD requests have no limit and won't affect the annual leave balance</li>") . "
                                    <li>Processed requests cannot be undone - please review carefully</li>
                                </ul>
                            </div>
                            
                            <!-- Footer -->
                            <div style='margin-top: 35px; padding-top: 25px; border-top: 2px solid #e9ecef; text-align: center;'>
                                <p style='font-size: 13px; color: #6c757d; margin: 0;'>
                                    This is an automated notification from <strong>ADRI India</strong><br>
                                    If you're unable to use the action buttons, please contact the higher personnel<br>
                                    <span style='color: #adb5bd;'>Request ID: #$leave_request_id | Generated on " . date('M j, Y \a\t g:i A') . "</span>
                                </p>
                            </div>
                        </div>
                    </div>
                ";
                
                $mail->send();
                return true;
                
            } catch (Exception $e) {
                error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
                return false;
            }
        }
        $stmt->close();
    }
    return false;
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
    <title>Leave Request - ADRI Dashboard</title>
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
        body{
            overflow:hidden;
        }
        .leave-balance-card {
            background: #27496f;
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .balance-number {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .form-enhanced {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .field-enhanced {
            margin-bottom: 25px;
        }
        .field-enhanced label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }
        .field-enhanced input, .field-enhanced select, .field-enhanced textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .field-enhanced input:focus, .field-enhanced select:focus, .field-enhanced textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        .btn-submit-enhanced {
            background-color: #191919;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-submit-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-submit-enhanced:disabled {
            background: #6c757d !important;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .alert-enhanced {
            border-radius: 10px;
            padding: 15px 20px;
            border: none;
            font-weight: 500;
        }
        .days-counter {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 8px;
            font-weight: 500;
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
                    <h4>General</h4>
                    <ul class="list-unstyled components">
                        <li><a href="index.php"><i style="font-size:24px; color:#ddd;" class="fa">&#xf015;</i> <span>Home</span></a></li>
                        <!--<li><a href="form.php"><i style="font-size:20px; color:yellow" class="fa">&#xf15c;</i> <span>Form</span></a></li>-->
                        <!--<li><a href="tables.php"><i style="font-size:20px;" class="fa purple_color2">&#xf0ce;</i> <span>Dashboard</span></a></li>-->
                        <!--<li><a href="leave_dashboard.php"><i style="font-size:20px; color:#20c997;" class="fa">&#xf0ae;</i> <span>leave dashboard</span></a></li>-->
                        <li><a href="contribution_report.php"><i style="font-size:20px; color:#20c997;" class="fa">&#xf0ae;</i> <span>Contributions</span></a></li>
                        <!--<li><a href="leave_request.php" style="background-color: #1e90ff;"><i style="font-size:20px; color:#fff;" class="fa">&#xf274;</i> <span style="color:#fff;">Leave Request</span></a></li>-->
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
                                            <a class="dropdown-toggle" data-toggle="dropdown"><span class="user_info"><h6 style="z-index:99"> <?php echo htmlspecialchars($username); ?></h6></span></a>
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

                <div class="midde_cont" style="margin-bottom:-40px">
                    <div class="container-fluid">
                        <div class="row column_title">
                            <div class="col-md-12">
                                <div class="page_title">
                                    <h2>Submit Leave Request</h2>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enhanced Leave Balance Display -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="leave-balance-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h4 style="margin-bottom: 10px; opacity: 1; color:white;">Your Leave Balance</h4>
                                            <div style="font-size: 16px; opacity: 1; line-height: 1.6;">
                                                <strong>Regular Leave:</strong> 
                                                <span class="balance-number"><?php echo $leave_balance; ?></span> 
                                                <span style="font-size: 1.2rem;">days remaining</span>
                                                <!--<br>-->
                                                <!--<strong>OOD Leave:</strong> <span style="font-size: 1.2rem; color: #90EE90;">Unlimited</span>-->
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div style="font-size: 14px; opacity: 1;">
                                                <strong>Annual Limit:</strong> 15 days<br>
                                                <strong>Used:</strong> <?php echo (15 - $leave_balance); ?> days<br>
                                                <strong>Current Year:</strong> <?php echo date('Y'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Success/Error Messages -->
                        <?php if (isset($success_message)): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-success alert-enhanced">
                                        <strong>Success!</strong> <?php echo $success_message; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-danger alert-enhanced">
                                        <strong>Error!</strong> <?php echo $error_message; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Enhanced Form -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-enhanced">
                                    <form method="POST" id="leaveForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="field-enhanced">
                                                    <label for="supervisor_id">Select Supervisor *</label>
                                                    <select name="supervisor_id" id="supervisor_id" required>
                                                        <option value="">Choose your supervisor...</option>
                                                        <?php while ($supervisor = $supervisors_result->fetch_assoc()): ?>
                                                            <option value="<?php echo $supervisor['id']; ?>">
                                                                <?php echo htmlspecialchars($supervisor['name']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="field-enhanced">
                                                    <label for="leave_type">Leave Type *</label>
                                                    <select name="leave_type" id="leave_type" required onchange="toggleReasonField()">
                                                        <option value="">Select leave type...</option>
                                                        <option value="Regular Leave">Regular Leave (Annual 15-day limit)</option>
                                                        <option value="OOD">OOD (On Official Duty)</option>
                                                        <option value="Sick leave">Sick leave</option>
                                                        <option value="Work From Home">Work from home</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="field-enhanced">
                                                    <label for="start_date">Start Date *</label>
                                                    <input type="date" name="start_date" id="start_date" required 
                                                           
                                                           onchange="calculateDays()">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="field-enhanced">
                                                    <label for="end_date">End Date *</label>
                                                    <input type="date" name="end_date" id="end_date" required 
                                                           
                                                           onchange="calculateDays()">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div id="days-display" class="days-counter" style="display: none;">
                                                    <strong>Duration:</strong> 
                                                    <span id="days-count">0</span> day(s)
                                                    <span id="balance-warning" style="color: #dc3545; font-weight: bold;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="field-enhanced">
                                                    <label for="reason">Reason for Leave</label>
                                                    <textarea name="reason" id="reason" rows="4" 
                                                              placeholder="Describe the reason for your leave request..."></textarea>
                                                    <small id="reason-helper" class="form-text text-muted" style="display: none;">
                                                        <strong>Note:</strong> Reason is required for regular leave requests longer than 3 days.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <button type="submit" class="btn-submit-enhanced" id="submitBtn">
                                                    Submit Leave Request
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="container-fluid">
                            <div class="footer">
                                <p>&copy; 2025 ADRI India, All rights reserved |  <a style="color:#101F60" href = "developer.php"> Developer contact </a>
                                   <!-- Distributed By: <a href="https://themewagon.com/">ThemeWagon</a> -->
                                </p>
                            </div>
                        </div>

                        <!-- Information Card -->
                        <!-- <div class="row" style="margin-top: 30px;">
                            <div class="col-md-12">
                                <div class="alert alert-info alert-enhanced">
                                    <h5 style="margin-bottom: 15px;">📋 Leave Policy Information</h5>
                                    <ul style="margin-bottom: 0; padding-left: 20px;">
                                        <li><strong>Regular Leave:</strong> 15 days per calendar year, cannot exceed remaining balance</li>
                                        <li><strong>OOD (On Official Duty):</strong> Unlimited, used for official work-related activities</li>
                                        <li><strong>Advance Booking:</strong> Leave requests must be for future dates only</li>
                                        <li><strong>Overlapping Requests:</strong> Cannot submit requests for dates that overlap with existing pending/approved leaves</li>
                                        <li><strong>Supervisor Approval:</strong> All requests require supervisor approval via email notification</li>
                                        <li><strong>Documentation:</strong> Reason required for regular leave requests longer than 3 days</li>
                                    </ul>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
            <!-- end right content -->
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
    <!-- nice scrollbar -->
    <script src="js/perfect-scrollbar.min.js"></script>
    <script>
        var ps = new PerfectScrollbar('#sidebar');
    </script>
    <!-- custom js -->
    <script src="js/custom.js"></script>

    <script>
        function calculateDays() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const leaveType = document.getElementById('leave_type').value;
    const daysDisplay = document.getElementById('days-display');
    const daysCount = document.getElementById('days-count');
    const balanceWarning = document.getElementById('balance-warning');
    const submitBtn = document.getElementById('submitBtn');
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end >= start) {
            // Count only weekdays (Monday to Friday)
            let weekdaysCount = 0;
            const currentDate = new Date(start);
            
            while (currentDate <= end) {
                const dayOfWeek = currentDate.getDay(); // 0 = Sunday, 6 = Saturday
                if (dayOfWeek >= 1 && dayOfWeek <= 5) { // Monday (1) to Friday (5)
                    weekdaysCount++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            daysCount.textContent = weekdaysCount;
            daysDisplay.style.display = 'block';
            
            // Check balance for regular leave
            if (leaveType === 'Regular Leave') {
                const currentBalance = <?php echo $leave_balance; ?>;
                if (weekdaysCount > currentBalance) {
                    balanceWarning.textContent = ` ⚠️ Exceeds available balance (${currentBalance} days)`;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Insufficient Leave Balance';
                } else {
                    balanceWarning.textContent = ` Within available balance`;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Leave Request';
                }
            } else {
                balanceWarning.textContent = 'It will not get deducted from the leave balance.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Leave Request';
            }
        } else {
            daysDisplay.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Invalid Date Range';
        }
    } else {
        daysDisplay.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Leave Request';
        balanceWarning.textContent = '';
    }
}

        function toggleReasonField() {
            const leaveType = document.getElementById('leave_type').value;
            const reasonHelper = document.getElementById('reason-helper');
            
            if (leaveType === 'Regular Leave') {
                reasonHelper.style.display = 'block';
            } else {
                reasonHelper.style.display = 'none';
            }
            
            calculateDays(); // Recalculate when leave type changes
        }

        // Form validation
        document.getElementById('leaveForm').addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const leaveType = document.getElementById('leave_type').value;
            const reason = document.getElementById('reason').value.trim();
            
            if (startDate && endDate && leaveType === 'Regular Leave') {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays > 3 && reason === '') {
                    e.preventDefault();
                    alert('⚠️ Reason is required for regular leave requests longer than 3 days.');
                    document.getElementById('reason').focus();
                    return false;
                }
            }
        });

        // Set minimum date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const minDate = tomorrow.toISOString().split('T')[0];
            
            // document.getElementById('start_date').min = minDate;
            // document.getElementById('end_date').min = minDate;
        });
    </script>
</body>
</html>