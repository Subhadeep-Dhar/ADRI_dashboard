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
                     
                     <li><a href="form.php"><i style="font-size:20px; color:yellow" class="fa">&#xf15c;</i> <span>Form</span></a></li>
                     
                     <!--<li><a href="tables.php"><i style="font-size:20px;" class="fa purple_color2">&#xf0ce;</i> <span>Dashboard</span></a></li>-->
                     <li><a href="contribution_report.php"><i style="font-size:20px; color:#20c997;" class="fa">&#xf0ae;</i> <span>Contributions</span></a></li>

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
                           <!-- <li class="nav-item"> <a class="nav-link" href="../../pages/samples/error-500.html"> 500 </a></li>
                           <li class="nav-item"> <a class="nav-link" href="../../pages/samples/login.html"> Login </a></li>
                           <li class="nav-item"> <a class="nav-link" href="../../pages/samples/register.html"> Register </a></li> -->
                        </ul>
                        </div>
                     </li>
                     
                     <li><a href="leave_request.php"><i style="font-size:20px; color:#ff6b6b;" class="fa">&#xf274;</i> <span>Leave Request</span></a></li>
                     <li><a href="leave_dashboard.php"><i style="font-size:20px; color:#fff;" class="fa">&#xf0ae;</i> <span style="color:#fff;">Leave Dashboard</span></a></li>                      
                     
                     <!-- <li><a href="login.php"><img src="signin.png" height="30px" style="margin-left:-7px; margin-right:9px"></img> <span>Login</span></a></li> -->

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
                           <a href="index.php"><img class="img-responsive" src="ADRI_logo.png" alt="#" /></a>
                        </div> -->
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
                              <h2>Enter your schedule</h2>
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
                           $activities = $_POST['activity'];
$start_times = $_POST['start_time'];
$end_times = $_POST['end_time'];
$activity_date = $_POST['activity_date']; // assuming it's the same for all rows
$username = $_SESSION['username']; // or however you get the logged-in user

$sql = "INSERT INTO availability (username, start_time, end_time, activity, activity_date) VALUES (?, ?, ?, ?, ?)";

if ($stmt = $conn->prepare($sql)) {
    for ($i = 0; $i < count($activities); $i++) {
        $single_activity = $activities[$i];
        $start_time = $start_times[$i];
        $end_time = $end_times[$i];

        $stmt->bind_param("sssss", $username, $start_time, $end_time, $single_activity, $activity_date);
        $stmt->execute();
    }

    echo "Activities saved successfully.";
} else {
    echo "Failed to prepare statement: " . $conn->error;
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
   <!-- For 1 week -->
   <label class="label_field" style="width: 200px; font-size: large;">Select Date</label>
   <input type="date" name="activity_date" value="<?php echo date('Y-m-d'); ?>" required />
</div>

<div id="activityContainer">
   <div class="field activity-block">
      <!-- Activity input with add icon -->
      <div class="form-row">
         <label class="label_field">
            Activity
            <span id="addActivityBtn" title="Add more">[ + ]</span>
         </label>
         <input type="text" name="activity[]" placeholder="Enter Activity" required maxlength="100" />
      </div>

      <!-- Start time input -->
      <div class="form-row">
         <label class="label_field">Start Time</label>
         <input type="time" name="start_time[]" required />
      </div>

      <!-- End time input -->
      <div class="form-row">
         <label class="label_field">End Time</label>
         <input type="time" name="end_time[]" required />
      </div>
   </div>
</div>



<style>
   /* Base Field Wrapper */
/* Wrapper for each activity block */
.activity-block {
  display: flex;
  flex-direction: column;
  background: #f9f9f9;
  padding: 15px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 15px;
}

/* Each row inside the block */
.form-row {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

/* Label styling */
.label_field {
  width: 200px;
  font-size: large;
  font-weight: 600;
  color: #333;
  margin-right: 10px;
}

/* Input styling */
.form-row input[type="text"],
.form-row input[type="time"],
.form-row input[type="date"] {
  flex: 1;
  min-width: 200px;
  padding: 10px 14px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 10px;
  background-color: #fff;
  transition: border-color 0.3s, box-shadow 0.3s;
}

.form-row input:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
  outline: none;
}

/* Add/Remove icon */
#addActivityBtn,
.removeBtn {
  font-size: 18px;
  cursor: pointer;
  margin-left: 8px;
  color: #007bff;
  user-select: none;
}

#addActivityBtn:hover,
.removeBtn:hover {
  text-decoration: none;
}


   </style>

<p id="activityLimitMsg" style="color: red; display: none;">You can add up to 5 activities at once.</p>

<script>
   let activityCount = 1;
   const maxActivities = 5;

   document.addEventListener("DOMContentLoaded", function () {
      const addBtn = document.getElementById("addActivityBtn");
      const container = document.getElementById("activityContainer");
      const limitMsg = document.getElementById("activityLimitMsg");

      addBtn.addEventListener("click", function () {
         if (activityCount >= maxActivities) return;

         // Create new block
         const block = document.createElement("div");
         block.className = "field activity-block";

         block.innerHTML = `
            <div class="form-row">
               <label class="label_field">
                  Activity
                  <span class="removeBtn" title="Remove">[ - ]</span>
               </label>
               <input type="text" name="activity[]" placeholder="Enter Activity" required maxlength="100" />
            </div>
            <div class="form-row">
               <label class="label_field">Start Time</label>
               <input type="time" name="start_time[]" required />
            </div>
            <div class="form-row">
               <label class="label_field">End Time</label>
               <input type="time" name="end_time[]" required />
            </div>
         `;
         document.querySelector('form').addEventListener('submit', function (e) {
   const startTimes = document.getElementsByName('start_time[]');
   const endTimes = document.getElementsByName('end_time[]');

   const defaultStart = startTimes[0].value;
   const defaultEnd = endTimes[0].value;

   for (let i = 1; i < startTimes.length; i++) {
      if (!startTimes[i].value) {
         startTimes[i].value = defaultStart;
      }
      if (!endTimes[i].value) {
         endTimes[i].value = defaultEnd;
      }
   }
});

         // Add remove handler
         block.querySelector(".removeBtn").addEventListener("click", function () {
            block.remove();
            activityCount--;
            limitMsg.style.display = "none";
            addBtn.style.pointerEvents = "auto";
            addBtn.style.opacity = "1";
         });

         container.appendChild(block);
         activityCount++;

         if (activityCount >= maxActivities) {
            limitMsg.style.display = "block";
            addBtn.style.pointerEvents = "none";
            addBtn.style.opacity = "0.5";
         }
      });
   });
</script>
                        <div class="field margin_0">
                              <button type="submit" style="float:right;" class="main_bt">Submit</button>
                        </div>
                     </fieldset>
                  </form>

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