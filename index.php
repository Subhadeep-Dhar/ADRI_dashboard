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
?>



<!-- HTML code for the index page -->
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
      <title>Adri dashboard</title>
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
      
   </head>
   <body class="dashboard dashboard_1">
      <div class="full_container">
         <div class="inner_container">
            <!-- Sidebar  -->
            <!-- <nav id="sidebar"> -->
            <nav id="sidebar">
               <div class="sidebar_blog_1">
                   <div class="sidebar_user_info">
                     <div class="user_profle_side">
                        <div class="user_info">
                           <h6>Hii, <?php echo htmlspecialchars($username); ?>!</h6> <!-- Display username from session -->
                        </div>
                     </div>
                  </div>
               </div>
            

               <div class="sidebar_blog_2">
                  <h4>General</h4>
                  <ul class="list-unstyled components">

                  <li><a href="index.php"><i style="font-size:24px; color:#ddd;" class="fa">&#xf015;</i> <span>Home</span></a></li>                     
                     
                     <li><a href="form.php"><i style="font-size:20px; color:yellow" class="fa">&#xf15c;</i> <span>Form</span></a></li>
                     
                     <li><a href="tables.php"><i style="font-size:20px;" class="fa purple_color2">&#xf0ce;</i> <span>Dashboard</span></a></li>
                     
                     <li><a href="contact.php"><i class="fa fa-paper-plane red_color"></i> <span>Contact</span></a></li>
                     
                     <!-- <li><a href="login.php"><img src="signin.png" height="30px" style="margin-left:-7px; margin-right:9px"></img> <span>Login</span></a></li> -->

                     <li><a href="login.php"><i style="font-size:20px; color:#1ed085" class="fa fa-signin">&#xf08b;</i> <span>Login</span></a></li>
                     
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
                        <div class="logo_section">
                           <a href="index.html"><img class="img-responsive" style="height:48px" src="ADRI_logo.png" alt="#" /></a>
                        </div>
                        <div class="right_topbar">
                           <div class="icon_info">
                              <!-- <ul>
                                 <li><a href="#"><i class="fa fa-bell-o"></i><span class="badge">2</span></a></li>
                                 <li><a href="#"><i class="fa fa-question-circle"></i></a></li>
                                 <li><a href="#"><i class="fa fa-envelope-o"></i><span class="badge">3</span></a></li>
                              </ul> -->
                              <ul class="user_profile_dd">
                                 <li>
                                    <a class="dropdown-toggle" data-toggle="dropdown"><span class="user_info"><h6 style="z-index:99"> <?php echo htmlspecialchars($username); ?></h6> <!-- Display username from session --></span></a>
                                    <div class="dropdown-menu">
                                       
                                       <a class="dropdown-item" href="help.html">Help</a>
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
                              <h2>Enter your plans today!!</h2>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

               <div class = "login_form">
                  <?php
                     // session_start();

                     // Check if form is submitted
                     if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        // Check if session has the username
                        if (isset($_SESSION['username'])) {
                           $username = $_COOKIE['username'];
                           $start_time = $_POST['start_time'];
                           $end_time = $_POST['end_time'];
                           $activity = $_POST['activity'];

                           // Validate inputs
                           if (!empty($start_time) && !empty($end_time) && !empty($activity)) {
                                 // Include database connection
                                 // include 'db.php'; // assuming db.php has your DB connection

                                 // Insert into availability table
                                 $sql = "INSERT INTO availability (username, start_time, end_time, activity, timestamp) 
                                       VALUES (?, ?, ?, ?, NOW())"; // Assuming 'timestamp' will store the current timestamp

                                 // Prepare and bind
                                 if ($stmt = $conn->prepare($sql)) {
                                    $stmt->bind_param("ssss", $username, $start_time, $end_time, $activity);

                                    // Execute query
                                    if ($stmt->execute()) {
                                       $success_message = "Activity successfully added.";
                                    } else {
                                       $error_message = "Error: " . $stmt->error;
                                    }

                                    // Close statement
                                    $stmt->close();
                                 } else {
                                    $error_message = "Error: " . $conn->error;
                                 }

                                 // Close connection
                                 $conn->close();
                           } else {
                                 $error_message = "All fields are required.";
                           }
                        } else {
                           $error_message = "You must be logged in to submit activity.";
                        }
                     }
                  ?>

                  <form method="POST" action="">
                     <fieldset>
                        <?php if (isset($error_message)) : ?>
                              <div class="alert alert-danger">
                                 <?php echo $error_message; ?>
                              </div>
                        <?php endif; ?>
                        <?php if (isset($success_message)) : ?>
                              <div class="alert alert-success">
                                 <?php echo $success_message; ?>
                              </div>
                        <?php endif; ?>
                        <div class="field">
                              <label class="label_field" style="width: 145px;">Select starting time</label>
                              <input type="time" name="start_time" required />
                        </div>
                        <div class="field">
                              <label class="label_field" style="width: 145px;">Select ending time</label>
                              <input type="time" name="end_time" required />
                        </div>
                        <div class="field">
                              <label class="label_field" style="width: 145px;">Activity</label>
                              <input type="text" name="activity" placeholder="Enter Activity" required />
                        </div>
                        <div class="field margin_0">
                              <button type="submit" class="main_bt">Submit</button>
                        </div>
                     </fieldset>
                  </form>

               </div>


               <!-- dashboard inner -->
               <div class="midde_cont">
                  <div class="container-fluid">
                     <div class="row column_title">
                        <div class="col-md-12">
                           <div class="page_title">
                              <h2>Dashboard</h2>
                           </div>
                        </div>
                     </div>
                     
                  </div>

                  <div class="midde_cont">
   <div class="container-fluid">
      <!-- <div class="row column_title">
         <div class="col-md-12">
            <div class="page_title">
               <h2>Tables</h2>
            </div>
         </div>
      </div> -->
      <!-- row -->
      <div class="row">
         <!-- table section -->
         <div class="col-md-12">
            <div class="white_shd full margin_bottom_30">
               <div class="full graph_head">
                  <div class="heading1 margin_0">
                     <h2>Employee Activities</h2>
                  </div>
               </div>
               <div class="table_section padding_infor_info">
                  <div class="table-responsive-sm">
                     <table class="table">
                        <thead>
                           <tr>
                              <th>Username</th>
                              <th>Start Time</th>
                              <th>End Time</th>
                              <th>Activity</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php
                           include 'db.php';

                           // Query to fetch all users
                           $usersQuery = "SELECT username FROM users";
                           $usersResult = $conn->query($usersQuery);

                           if ($usersResult->num_rows > 0) {
                               while ($userRow = $usersResult->fetch_assoc()) {
                                   $username = $userRow['username'];

                                   // Query to fetch activities for the user
                                   $activityQuery = "
                                       SELECT 
                                           start_time, 
                                           end_time, 
                                           activity 
                                       FROM availability 
                                       WHERE username = '$username' 
                                         AND DATE(date) = CURDATE()
                                   ";
                                   $activityResult = $conn->query($activityQuery);

                                   if ($activityResult->num_rows > 0) {
                                       // First row with username
                                       $isFirstRow = true;

                                       while ($activityRow = $activityResult->fetch_assoc()) {
                                           $startTime = date('h:i A', strtotime($activityRow['start_time']));
                                           $endTime = date('h:i A', strtotime($activityRow['end_time']));
                                           $activity = $activityRow['activity'];

                                           echo "<tr>";
                                           if ($isFirstRow) {
                                               echo "<td rowspan='{$activityResult->num_rows}'>$username</td>";
                                               $isFirstRow = false;
                                           }
                                           echo "<td>$startTime</td>";
                                           echo "<td>$endTime</td>";
                                           echo "<td>$activity</td>";
                                           echo "</tr>";
                                       }
                                   } else {
                                       // Display empty row if no activities for the user
                                       echo "<tr>";
                                       echo "<td>$username</td>";
                                       echo "<td colspan='3'>No activities for today</td>";
                                       echo "</tr>";
                                   }
                               }
                           } else {
                               echo "<tr><td colspan='4'>No users found.</td></tr>";
                           }

                           $conn->close();
                           ?>
                        </tbody>
                     </table>
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
                        <p>Copyright © 2024 Designed by Subhadeep. All rights reserved.
                           <!-- Distributed By: <a href="https://themewagon.com/">ThemeWagon</a> -->
                        </p>
                     </div>
                  </div>
               </div>
               <!-- end dashboard inner -->
            </div>
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