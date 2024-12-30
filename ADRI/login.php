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

                // Get user IP
                $user_ip = $_SERVER['REMOTE_ADDR'];

                // Store the IP address in the users table or sessions table
                if (isset($user['device_ip']) && $user['device_ip'] !== $user_ip) {
                    // IP mismatch means new device/login attempt (you can decide to handle this differently)
                } else {
                    // Update the users table with the device IP address
                    $updateIp = "UPDATE users SET device_ip = ? WHERE user_id = ?";
                    if ($updateStmt = $conn->prepare($updateIp)) {
                        $updateStmt->bind_param("si", $user_ip, $user['user_id']);
                        $updateStmt->execute();
                    }
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
    <title>Login</title>
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
</head>
<body class="inner_page login">
    <div class="full_container">
        <div class="container">
            <div class="center verticle_center full_height">
                <div class="login_section">
                    <div class="logo_login">
                        <div class="center">
                            <h2 style="color:#fff; text-align:center">Login to your account</h2>
                        </div>
                    </div>
                    <div class="login_form">
                        <form method="POST" action="login.php">
                            <fieldset>
                                <div class="field">
                                    <label class="label_field">Username</label>
                                    <input type="text" name="username" placeholder="Username" required />
                                </div>
                                <div class="field">
                                    <label class="label_field">Password</label>
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
                                <p>Don't have an account? <a href="register.php" style="color:blue">Register</a></p>
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
