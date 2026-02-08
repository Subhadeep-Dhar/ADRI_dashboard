<?php
session_start();
require_once 'db.php';

// Check if there's a login attempt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate the login
    $sql = "SELECT * FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Successful login, create session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username']; // Store the username in the session

                // Get current user IP
                $user_ip = $_SERVER['REMOTE_ADDR'];

                // Update the users table with the new device IP address
                $updateIp = "UPDATE users SET device_ip = ? WHERE user_id = ?";
                if ($updateStmt = $conn->prepare($updateIp)) {
                    $updateStmt->bind_param("si", $user_ip, $user['user_id']);
                    $updateStmt->execute();
                }

                // Set cookies for 30 days
                setcookie('user_ip', $user_ip, time() + 60 * 60 * 24 * 30, '/'); // Cookie for 30 days
                setcookie('user_id', $user['user_id'], time() + 60 * 60 * 24 * 30, '/'); // Cookie for 30 days
                setcookie('username', $user['username'], time() + 60 * 60 * 24 * 30, '/'); // Cookie for 30 days

                // Redirect to the homepage or dashboard
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Invalid password."; // Store the error message
            }
        } else {
            $_SESSION['error_message'] = "Username not found."; // Store the error message
        }

        $stmt->close();
    }
    $conn->close(); // Close the connection
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADRI dashboard | Login</title>
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
        /* Professional Industrial Login Styles */

/* Global Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #1e3c72 100%);
    background-attachment: fixed;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Industrial Background Pattern */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 60px 60px;
    z-index: -1;
    opacity: 0.3;
}

.full_container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 1200px;
}

.center {
    display: flex;
    justify-content: center;
    align-items: center;
}

.verticle_center {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Login Section */
.login_section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    padding: 0;
    width: 100%;
    max-width: 450px;
    position: relative;
    overflow: hidden;
}

/* Header Section */
.logo_login {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    padding: 40px 30px 30px;
    position: relative;
    border-radius: 16px 16px 0 0;
}

.logo_login::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.1) 50%, transparent 60%),
        repeating-linear-gradient(
            90deg,
            transparent,
            transparent 2px,
            rgba(255,255,255,0.03) 2px,
            rgba(255,255,255,0.03) 4px
        );
    pointer-events: none;
}

.logo_login h2 {
    color: #ffffff !important;
    font-size: 24px;
    font-weight: 600;
    text-align: center !important;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

/* Form Section */
.login_form {
    padding: 40px 30px;
    background: #ffffff;
    border-radius: 0 0 16px 16px;
}

form fieldset {
    border: none;
    margin: 0;
    padding: 0;
}

/* Form Fields */
.field {
    margin-bottom: 25px;
    position: relative;
}

.field.margin_0 {
    margin-bottom: 0;
}

.label_field {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Input Styles */
input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e8ecef;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 400;
    color: #2c3e50;
    background: #ffffff;
    transition: all 0.3s ease;
    outline: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.04);
}

input[type="text"]:focus,
input[type="password"]:focus {
    border-color: #3498db;
    box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.04),
        0 0 0 3px rgba(52, 152, 219, 0.1);
    transform: translateY(-1px);
}

input[type="text"]::placeholder,
input[type="password"]::placeholder {
    color: #95a5a6;
    font-weight: 400;
}

/* Button Styles */
.main_bt {
    width: 100%;
    padding: 16px 24px;
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border: none;
    border-radius: 8px;
    color: #ffffff;
    font-size: 16px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 
        0 4px 15px rgba(52, 152, 219, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    position: relative;
    overflow: hidden;
}

.main_bt:hover {
    background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
    transform: translateY(-2px);
    box-shadow: 
        0 6px 20px rgba(52, 152, 219, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.main_bt:active {
    transform: translateY(0);
    box-shadow: 
        0 2px 10px rgba(52, 152, 219, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.main_bt::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.main_bt:hover::before {
    left: 100%;
}

/* Error Messages */
p[style*="color:red"] {
    color: #e74c3c !important;
    font-size: 14px;
    font-weight: 500;
    margin: 15px 0;
    padding: 12px 16px;
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.2);
    border-radius: 6px;
    border-left: 4px solid #e74c3c;
}

/* Note Message */
p[style*="color:red"]:last-of-type {
    color: #f39c12 !important;
    background: rgba(243, 156, 18, 0.1);
    border-color: rgba(243, 156, 18, 0.2);
    border-left-color: #f39c12;
    font-size: 13px;
    line-height: 1.4;
}

/* Responsive Design */
@media (max-width: 768px) {
    .full_container {
        padding: 15px;
    }
    
    .login_section {
        max-width: 100%;
        margin: 0 10px;
    }
    
    .logo_login {
        padding: 30px 20px 25px;
    }
    
    .logo_login h2 {
        font-size: 20px;
    }
    
    .login_form {
        padding: 30px 20px;
    }
    
    input[type="text"],
    input[type="password"] {
        padding: 14px 16px;
        font-size: 16px; /* iOS zoom prevention */
    }
    
    .main_bt {
        padding: 14px 20px;
        font-size: 15px;
    }
}

@media (max-width: 480px) {
    .logo_login {
        padding: 25px 15px 20px;
    }
    
    .logo_login h2 {
        font-size: 18px;
    }
    
    .login_form {
        padding: 25px 15px;
    }
    
    .field {
        margin-bottom: 20px;
    }
}

/* Loading Animation */
.main_bt.loading {
    pointer-events: none;
    position: relative;
}

.main_bt.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin: -10px 0 0 -10px;
    border: 2px solid transparent;
    border-top: 2px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Additional Industrial Elements */
.login_section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3498db, #2980b9, #3498db);
    background-size: 200% 100%;
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Focus Indicators for Accessibility */
input:focus,
button:focus {
    outline: none;
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
    .login_section {
        background: #ffffff;
        border: 2px solid #2c3e50;
    }
    
    .logo_login {
        background: #2c3e50;
    }
    
    input[type="text"],
    input[type="password"] {
        border-width: 2px;
    }
}
    </style>
</head>
<body class="inner_page login">
    <div class="full_container">
        <div class="container">
            <div class="center verticle_center full_height">
                <div class="login_section">
                    <div class="logo_login">
                        <div class="center">
                            <h2 style="text-align:center">Login to your account</h2>
                        </div>
                    </div>
                    <div class="login_form">
                        <form method="POST" action="login.php">
                            <fieldset>
                                <br><br><br>
                                <div class="field">
                                    <!--<label class="label_field">Username</label>-->
                                    <input type="text" name="username" placeholder="Username" required />
                                </div>
                                <br><br><br>
                                <div class="field">
                                    <!--<label class="label_field">Password</label>-->
                                    <input type="password" name="password" placeholder="Password" required />
                                </div>
                                
                                <?php
                                // Check if there's an error message in the session
                                if (isset($_SESSION['error_message'])) {
                                    echo '<p style="color:red;">' . $_SESSION['error_message'] . '</p>';
                                    // Clear the error message after showing it
                                    unset($_SESSION['error_message']);
                                }
                                ?><br><br>
                                <p style="color:red">Note: Your username and password are sent to your email. If not sent, please contact the administrator.</p>
                                <div class="field margin_0">
                                    <button type="submit" class="main_bt">Login</button>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
