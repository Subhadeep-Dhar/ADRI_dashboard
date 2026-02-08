<?php
require_once 'db.php';

// Include email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Check if required parameters are present
if (!isset($_GET['action']) || !isset($_GET['id']) || !isset($_GET['token'])) {
    die("Invalid request parameters.");
}

$action = $_GET['action'];
$leave_request_id = intval($_GET['id']);
$token = $_GET['token'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    die("Invalid action specified.");
}

// Function to calculate leave balance for an employee
function getLeaveBalance($username, $conn) {
    // Calculate total approved regular leaves for current year
    $current_year = date('Y');
    $sql = "SELECT COALESCE(SUM(DATEDIFF(end_date, start_date) + 1), 0) as used_days 
            FROM leave_request 
            WHERE employee_name = ? 
            AND type = 'Sick Leave' 
            AND status = 'Approved'
            AND YEAR(start_date) = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $username, $current_year);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $used_days = $row['used_days'];
        $stmt->close();
        
        return 15 - $used_days; // 15 days limit minus used days
    }
    return 15; // Default to full balance if query fails
}

// Get leave request details
$sql = "SELECT lr.*, s.name as supervisor_name, s.email as supervisor_email 
        FROM leave_request lr 
        JOIN supervisors s ON lr.supervisor_id = s.id 
        WHERE lr.id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $leave_request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $leave_request = $result->fetch_assoc();
        
        // Verify token
        $expected_token = md5($leave_request_id . $action . $leave_request['supervisor_id']);
        if ($token !== $expected_token) {
            die("Invalid security token.");
        }
        
        // Check if already processed
        if ($leave_request['status'] !== 'Pending') {
            $message = "This leave request has already been " . strtolower($leave_request['status']) . ".";
            displayMessage($message, "warning");
            exit();
        }
        
        // If approving a regular leave (Sick Leave), check balance
        if ($action === 'approve' && $leave_request['type'] === 'Sick Leave') {
            $current_balance = getLeaveBalance($leave_request['employee_name'], $conn);
            $requested_days = (strtotime($leave_request['end_date']) - strtotime($leave_request['start_date'])) / (60 * 60 * 24) + 1;
            
            // Check if employee has sufficient balance
            if ($requested_days > $current_balance) {
                $message = "Cannot approve this leave request. Employee " . htmlspecialchars($leave_request['employee_name']) . 
                          " has only " . $current_balance . " regular leave days remaining, but requested " . $requested_days . " days.";
                displayMessage($message, "error");
                exit();
            }
        }
        
        // Update leave request status
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
        $update_sql = "UPDATE leave_request SET status = ? WHERE id = ?";
        
        if ($update_stmt = $conn->prepare($update_sql)) {
            $update_stmt->bind_param("si", $new_status, $leave_request_id);
            
            if ($update_stmt->execute()) {
                // Send notification email to employee
                $email_sent = sendStatusUpdateEmail(
                    $leave_request['employee_name'],
                    $leave_request['supervisor_name'],
                    $leave_request['start_date'],
                    $leave_request['end_date'],
                    $leave_request['type'],
                    $leave_request['reason'],
                    $new_status
                );
                
                $message = "Leave request has been successfully " . strtolower($new_status) . ".";
                if ($email_sent) {
                    $message .= " The employee has been notified via email.";
                } else {
                    $message .= " However, the notification email could not be sent.";
                }
                
                // Add balance information for approved regular leaves
                if ($new_status === 'Approved' && $leave_request['type'] === 'Sick Leave') {
                    $new_balance = getLeaveBalance($leave_request['employee_name'], $conn);
                    $message .= " Employee's remaining regular leave balance: " . $new_balance . " days.";
                }
                
                displayMessage($message, "success");
            } else {
                displayMessage("Error updating leave request status.", "error");
            }
            $update_stmt->close();
        } else {
            displayMessage("Error preparing update statement.", "error");
        }
    } else {
        displayMessage("Leave request not found.", "error");
    }
    $stmt->close();
} else {
    displayMessage("Error retrieving leave request.", "error");
}

function sendStatusUpdateEmail($employee_name, $supervisor_name, $start_date, $end_date, $leave_type, $reason, $status) {
    global $conn;
    
    // Get employee email from users table
    $user_query = "SELECT email FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($user_query)) {
        $stmt->bind_param("s", $employee_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $employee_email = $user['email'];
            
            $mail = new PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'testing9832test@gmail.com'; // Replace with your email
                $mail->Password = 'ysrt pjzw nior kcgm'; // Replace with your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('testing9832test@gmail.com', 'ADRI India');
                $mail->addAddress($employee_email, $employee_name);
                
                $mail->isHTML(true);
                
                $status_color = ($status === 'Approved') ? '#28a745' : '#dc3545';
                $status_icon = ($status === 'Approved') ? '✓' : '✗';
                
                $mail->Subject = "Leave Request " . $status . " - " . $leave_type;
                
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                        <div style='text-align: center; margin-bottom: 30px;'>
                            <h2 style='color: #2c3e50; margin-bottom: 10px;'>Leave Request Update</h2>
                            <hr style='border: 1px solid #3498db; width: 50%;'>
                        </div>
                        
                        <div style='text-align: center; margin-bottom: 30px;'>
                            <div style='display: inline-block; padding: 15px 30px; background-color: $status_color; color: white; border-radius: 50px; font-size: 18px; font-weight: bold;'>
                                $status_icon $status
                            </div>
                        </div>
                        
                        <p style='font-size: 16px; color: #34495e;'>Dear $employee_name,</p>
                        
                        <p style='font-size: 14px; color: #34495e; line-height: 1.6;'>
                            Your leave request has been <strong style='color: $status_color;'>" . strtolower($status) . "</strong> by $supervisor_name.
                        </p>
                        
                        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                            <h4 style='color: #495057; margin-bottom: 15px;'>Leave Request Details:</h4>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr style='border-bottom: 1px solid #dee2e6;'>
                                    <td style='padding: 10px; font-weight: bold; color: #495057; width: 30%;'>Leave Type:</td>
                                    <td style='padding: 10px; color: #6c757d;'>$leave_type</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #dee2e6;'>
                                    <td style='padding: 10px; font-weight: bold; color: #495057;'>Start Date:</td>
                                    <td style='padding: 10px; color: #6c757d;'>" . date('F j, Y', strtotime($start_date)) . "</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #dee2e6;'>
                                    <td style='padding: 10px; font-weight: bold; color: #495057;'>End Date:</td>
                                    <td style='padding: 10px; color: #6c757d;'>" . date('F j, Y', strtotime($end_date)) . "</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #dee2e6;'>
                                    <td style='padding: 10px; font-weight: bold; color: #495057;'>Supervisor:</td>
                                    <td style='padding: 10px; color: #6c757d;'>$supervisor_name</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; font-weight: bold; color: #495057; vertical-align: top;'>Reason:</td>
                                    <td style='padding: 10px; color: #6c757d;'>" . ($reason ? htmlspecialchars($reason) : 'No reason provided') . "</td>
                                </tr>
                            </table>
                        </div>";
                
                // Add balance information for regular leave
                if ($leave_type === 'Sick Leave' && $status === 'Approved') {
                    $new_balance = getLeaveBalance($employee_name, $conn);
                    $mail->Body .= "
                        <div style='background-color: #e7f3ff; border: 1px solid #b8daff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p style='margin: 0; font-weight: bold;'>Leave Balance Update:</p>
                            <p style='margin: 5px 0 0 0;'>Your remaining regular leave balance for this year: <strong>$new_balance days</strong></p>
                        </div>";
                }
                
                if ($status === 'Approved') {
                    $mail->Body .= "
                        <div style='background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p style='margin: 0; font-weight: bold;'>Your leave has been approved! Please coordinate with your team for any handover requirements.</p>
                        </div>";
                } else {
                    $mail->Body .= "
                        <div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p style='margin: 0; font-weight: bold;'>Your leave request has been rejected. Please contact your supervisor for more details if needed.</p>
                        </div>";
                }
                
                $mail->Body .= "
                        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; text-align: center;'>
                            <p style='font-size: 12px; color: #6c757d; margin: 0;'>
                                This is an automated email from ADRI India.<br>
                                For any questions, please contact your supervisor.
                            </p>
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

function displayMessage($message, $type) {
    $color_map = [
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107'
    ];
    
    $icon_map = [
        'success' => '✓',
        'error' => '✗',
        'warning' => '⚠'
    ];
    
    $color = $color_map[$type] ?? '#6c757d';
    $icon = $icon_map[$type] ?? 'ℹ';
    
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>Leave Request Processing - ADRI India</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f8f9fa;
                margin: 0;
                padding: 40px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container {
                max-width: 600px;
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            .icon {
                font-size: 60px;
                color: $color;
                margin-bottom: 20px;
                display: block;
            }
            .message {
                font-size: 18px;
                color: #495057;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .logo {
                height: 40px;
                margin-bottom: 30px;
            }
            .footer-text {
                color: #6c757d; 
                font-size: 14px; 
                margin-bottom: 30px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <img src='ADRI_logo.png' alt='ADRI Logo' class='logo'>
            <div class='icon'>$icon</div>
            <div class='message'>$message</div>
            <div class='footer-text'>
                This window can be closed safely.<br>
                ADRI India - Leave Management
            </div>
        </div>
    </body>
    </html>";
}

$conn->close();
?>