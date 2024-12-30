<?php
session_start(); // Start the session to store session variables

// Include database connection file
require_once 'db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user input from the form
    $user_username = $_POST['username'];
    $user_email = $_POST['email'];
    $user_password = $_POST['password'];

    // Validate input (simple example)
    if (empty($user_username) || empty($user_email) || empty($user_password)) {
        $_SESSION['error_message'] = "All fields are required!";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
    } else {
        // Hash the password
        $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);

        // Check if the username or email already exists in the database
        $sql_check = "SELECT * FROM users WHERE username = ? OR email = ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("ss", $user_username, $user_email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // If username or email already exists, store the error message and show it
                $_SESSION['error_message'] = "Username or email already exists!";
                $_SESSION['form_data'] = ['username' => $user_username, 'email' => $user_email]; // Store form data to repopulate the form
            } else {
                // Insert new user data into the 'users' table
                $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("sss", $user_username, $hashed_password, $user_email);

                    if ($stmt->execute()) {
                        // Registration successful, set session variable and redirect to login page
                        $_SESSION['success_message'] = "Registration successful! Please log in.";
                        header("Location: login.php");
                        exit();
                    } else {
                        // If error occurs while executing insert query
                        $_SESSION['error_message'] = "Error: " . $stmt->error;
                    }
                    $stmt->close(); // Close the prepared statement after executing
                } else {
                    // Handle query preparation failure
                    $_SESSION['error_message'] = "Error preparing the insert query.";
                }
            }
            $stmt_check->close(); // Close the check query prepared statement
        } else {
            // Handle query preparation failure
            $_SESSION['error_message'] = "Error preparing the select query.";
        }
    }
    $conn->close(); // Close the database connection after all operations
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <link rel="icon" href="ADRI_favicon.png" type="image/png" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/responsive.css" />
    <link rel="stylesheet" href="css/colors.css" />
    <link rel="stylesheet" href="css/bootstrap-select.css" />
    <link rel="stylesheet" href="css/perfect-scrollbar.css" />
    <link rel="stylesheet" href="css/custom.css" />
</head>
<body class="inner_page login">
    <div class="full_container">
        <div class="container">
            <div class="center verticle_center full_height">
                <div class="login_section">
                    <div class="logo_login">
                        <div class="center">
                            <h2 style="color:#fff; text-align:center;">Create your account</h2>
                        </div>
                    </div>
                    <div class="login_form">
                        <form method="POST" action="register.php">
                            <fieldset>
                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="field">
                                    <label class="label_field">Username</label>
                                    <input type="text" name="username" placeholder="Username" value="<?php echo isset($_SESSION['form_data']['username']) ? $_SESSION['form_data']['username'] : ''; ?>" required />
                                </div>
                                <div class="field">
                                    <label class="label_field">Email Address</label>
                                    <input type="email" name="email" placeholder="youremail@adriindia.org" pattern=".+@adriindia.org" value="<?php echo isset($_SESSION['form_data']['email']) ? $_SESSION['form_data']['email'] : ''; ?>" required />

                                    <script>
function validateEmail() {
  const email = document.getElementById("email").value;
  const emailRegex = /.+@adriindia.org/;
  if (!emailRegex.test(email)) {
    alert("Please enter your official ADRI email");
  }
}
</script>
                                </div>
                                <div class="field">
                                <label class="label_field">Password</label>
<input type="password" id="password" name="password" placeholder="Password" required>

<script>
  const passwordInput = document.getElementById('password');
  passwordInput.addEventListener('input', () => { 
    const passwordRegex = /^(?=.*[0-9])(?=.*[!@#$%^&*()_+])[a-zA-Z0-9!@#$%^&*()_+]{4,}$/;
    if (!passwordRegex.test(passwordInput.value)) {
      passwordInput.setCustomValidity('Password must contain at least 1 number, 1 special character, and be at least 4 characters long.'); 
    } else {
      passwordInput.setCustomValidity(''); 
    }
  });
</script>
                                </div><br><br>
                                <p>Already have an account? <a href="login.php" style="color:blue">Log in</a></p>
                                <div class="field margin_0">
                                    <button type="submit" class="main_bt">Create Account</button>
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
