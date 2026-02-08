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
      <title>ADRI dashboard</title>
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

th, td{
         color:black;
      }
</style>
      
   </head>
   <body class="dashboard dashboard_1">
      <div class="full_container">
         <div class="inner_container">
            <!-- Sidebar  -->
            <!-- <nav id="sidebar"> -->
            <nav id="sidebar">
                <div class="sidebar_blog_1">
                   <div class="sidebar_user_info">
                     <div class="user_profle_side" style="padding: 0px 25px;">
                        <div class="user_info" style="padding-top:0px;">
                        <!-- <div class="logo_section"> -->
                           <a href="index.php"><img class="img-responsive" style="height:40px;" src="ADRI_logo.png" alt="#" /></a>
                        <!-- </div> -->
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
                     
                     
                     <!-- <li><a href="Calander/Calendar.php"><i class="fa " style=""></i> <span>Future events</span></a></li> -->
                     
                     <li class="nav-item menu-items">
                        <a class="nav-link" data-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
                           <!-- <span class="menu-icon"> -->
                              <i class="fa" style="">📅</i>
                           <!-- </span> -->
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
                     <!-- <li><a href="login.php"><i style="font-size:20px; color:#1ed085" class="fa fa-signin">&#xf08b;</i> <span>Login</span></a></li> -->
                     
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
                        <!-- <div class="logo_section">
                           <a href="index.php"><img class="img-responsive" style="height:48px" src="ADRI_logo.png" alt="#" /></a>
                        </div> -->
                        <div class="right_topbar">
                           <div class="icon_info">                              
                              <ul class="user_profile_dd">
                                 <li>
                                    <a class="dropdown-toggle" data-toggle="dropdown"><span class="user_info"><h6 style="z-index:99"> <?php echo htmlspecialchars($username); ?></h6> <!-- Display username from session --></span></a>
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

                <!-- Display Events -->

                <div class="midde_cont" >  
                <div class="container-fluid">
                    <div class="row column_title">
                        <div class="col-md-12">
                            <div class="page_title">
                            <h2>Events</h2>
                            </div>
                        </div>
                    </div>      
                </div>

                <div class="container mt-5">
                     <!-- Events Today -->
                     <div class="mb-5">
                        <h3>Events Today</h3> 
                        <!--  style="color:#800020" -->
                        <div class="table-responsive-sm">
                        <table class="table table-striped">
                              <thead>
                                 <tr>
                                    <th></th>
                                    <th>Event Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Location</th>
                                    <th>Description</th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <?php
                                 // Query to fetch today's events
                                 // $sqlToday = "SELECT * FROM events WHERE start_date = CURDATE() ORDER BY start_time ASC";
                                 $sqlToday = "SELECT * FROM events 
                                    WHERE CURDATE() BETWEEN start_date AND end_date 
                                    ORDER BY start_time ASC";

                                 $resultToday = $conn->query($sqlToday);

                                 if ($resultToday->num_rows > 0) {
                                    $counter = 1;
                                    while ($row = $resultToday->fetch_assoc()) {
                                          echo "<tr>";
                                          echo "<td>" . $counter++ . "</td>";
                                          echo "<td>" . htmlspecialchars($row['event_name']) . "</td>";
                                          echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
                                          echo "<td>" . htmlspecialchars($row['end_time']) . "</td>";
                                          echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                          echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                          echo "</tr>";
                                    }
                                 } else {
                                    echo "<tr><td colspan='6'>No events today.</td></tr>";
                                 }
                                 ?>
                              </tbody>
                        </table>
                     </div></div>

                    <!-- Future Events -->
                    <div class="mb-5">
                        <h3>Future Events</h3>
                        <div class="table-responsive-sm">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Event Name</th>
                                    <th>Start Date</th>
                                    <th>Start Time</th>
                                    <th>End Date</th>
                                    <th>End Time</th>
                                    <th>Location</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Query to fetch future events
                                $sqlFuture = "SELECT * FROM events WHERE start_date > CURDATE() ORDER BY start_date ASC, start_time ASC";
                                $resultFuture = $conn->query($sqlFuture);

                                if ($resultFuture->num_rows > 0) {
                                    $counter = 1;
                                    while ($row = $resultFuture->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $counter++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['event_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['start_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['end_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8'>No future events.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div></div>

                    <!-- Past Events -->
                    <div>
                        <h3>Past Events</h3>
                        <div class="table-responsive-sm">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Event Name</th>
                                    <th>Start Date</th>
                                    <th>Start Time</th>
                                    <th>End Date</th>
                                    <th>End Time</th>
                                    <th>Location</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Query to fetch past events
                                $sqlPast = "SELECT * FROM events WHERE end_date < CURDATE() ORDER BY start_date DESC, start_time DESC";
                                $resultPast = $conn->query($sqlPast);

                                if ($resultPast->num_rows > 0) {
                                    $counter = 1;
                                    while ($row = $resultPast->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $counter++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['event_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['start_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['end_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8'>No past events.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table></div>
                    </div>
                </div>




                  <!-- footer -->
                  <div class="container-fluid">
                     <div class="footer">
                        <p>&copy; 2025 ADRI India, All rights reserved |  <a style="color:#101F60" href = "developer.php"> Developer contact </a>
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