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

<!-- Anshul
$2y$10$PGfc4gKeQeDdBNzHFbfuw.qs97tf69Uf7duNBJ7p/5h14w./xTwOu
anshul.chp@adriindia.org
0
NULL
192.168.1.151 -->

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
        p{
            font-size:medium;
            
        }
        label {
            display: inline-block;
            margin-bottom: .5rem;
            font-size: large;
            color: black;
        }



        /* Popup Styling */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 350px;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            z-index: 1000;
        }
        .popup.success {
            border-left: 5px solid #28a745;
        }
        .popup.error {
            border-left: 5px solid #dc3545;
        }
        .popup h3 {
            margin: 0;
            font-size: 18px;
        }
        .popup p {
            font-size: 14px;
            color: #555;
        }
        .popup .close-btn {
            display: block;
            margin: 15px auto 0;
            padding: 8px 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .popup .close-btn:hover {
            background: #0056b3;
        }
        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
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
                            <!-- <li><a href="contact.php"><i class="fa fa-paper-plane red_color"></i> <span>Contact</span></a></li> -->

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
                                        <h2>Enter event</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p style="color:red; margin-top:30px;"> Note: If you are not the authorized person, then do not fill it without permission of authorized person. </p>

                    <div class = "login_form">
                        <form class="event-form" action="" method="POST">
                        <!-- <h2 style = "text-align:center; margin-bottom:8%;">Create Event</h2> -->
                        <div class ="field">
                            <label for="eventName">Event Name</label>
                            <input type="text" id="eventName" name="eventName" placeholder="Enter event name" required>
                        </div>

                        <div class ="field">
                            <label for="startDate">Starting Date</label>
                            <input type="date" id="startDate" name="startDate" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class ="field">
                            <label for="endDate">Ending Date</label>
                            <input type="date" id="endDate" name="endDate" required>
                        </div>

                        <div class ="field">
                            <label for="startTime">Starting Time</label>
                            <input type="time" id="startTime" name="startTime" required>
                        </div>

                        <div class ="field">
                            <label for="endTime">Ending Time</label>
                            <input type="time" id="endTime" name="endTime" required>
                        </div>

                        <div class ="field">
                            <label for="location">Venue</label>
                            <input type="text" id="location" name="location" placeholder="Enter location" required>
                        </div>

                        <div class ="field">
                            <label for="description">Event Description</label>
                            <input type="text"id="description" name="description" placeholder="Enter event description"></textarea>
                        </div>

                            <button class="main_bt" style="float:right; margin-top:20px" type="submit">Submit</button>
                        </form>

                        <!-- Popup Overlay -->
<div id="popupOverlay" class="popup-overlay"></div>

<!-- Popup Message -->
<div id="popupMessage" class="popup">
    <div id="popupContent"></div>
    <button id="closePopup">OK</button>
</div>

<script>
    // Display popup if there's a message
    window.addEventListener("load", function () {
        const popupMessage = <?php echo json_encode($popupMessage); ?>;
        const popupType = <?php echo json_encode($popupType); ?>;

        if (popupMessage) {
            const popupContent = document.getElementById("popupContent");
            popupContent.innerHTML = popupMessage;

            if (popupType === "success") {
                popupContent.classList.add("success");
            } else {
                popupContent.classList.add("error");
            }

            // Show popup
            document.getElementById("popupOverlay").style.display = "block";
            document.getElementById("popupMessage").style.display = "block";

            // Close popup
            document.getElementById("closePopup").addEventListener("click", function () {
                document.getElementById("popupOverlay").style.display = "none";
                document.getElementById("popupMessage").style.display = "none";
            });
        }
    });
</script>


<style>
    .popup-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.popup {
    display: none;
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 20px;
    width: 350px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    text-align: center;
}

.popup .success {
    color: green;
    font-weight: bold;
}

.popup .error {
    color: red;
    font-weight: bold;
}

#closePopup {
    margin-top: 10px;
    background: #4CAF50;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 5px;
    cursor: pointer;    
}

</style>


                        <?php
                            // Connect to the database

                            include "db.php";
                            include "send_reminders.php"; // Include the email function

                            $conn = new mysqli('localhost', 'u727069115_adri_dashboard', '#Adri_activity002', 'u727069115_adri_dashboard');

                            // Check the connection
                            if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";
$newlyAddedEvent = null; // For displaying after insert

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = $conn->real_escape_string($_POST['eventName']);
    $start_date = $conn->real_escape_string($_POST['startDate']);
    $end_date = $conn->real_escape_string($_POST['endDate']);
    $start_time = $conn->real_escape_string($_POST['startTime']);
    $end_time = $conn->real_escape_string($_POST['endTime']);
    $location = $conn->real_escape_string($_POST['location']);
    $description = $conn->real_escape_string($_POST['description']);

    $checkQuery = "SELECT event_name, start_time, end_time FROM events 
                    WHERE (start_date = '$start_date' AND 
                    (start_time BETWEEN '$start_time' AND '$end_time' 
                    OR end_time BETWEEN '$start_time' AND '$end_time'))";

    $result = $conn->query($checkQuery);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $popupMessage = "An event already exists on this date and time!<br>
                         Event Name: " . $row['event_name'] . "<br>
                         Timing: " . $row['start_time'] . " - " . $row['end_time'];
        $popupType = "error";
    } else {
        $sql = "INSERT INTO events (event_name, start_date, end_date, start_time, end_time, location, description, created_by) 
VALUES ('$event_name', '$start_date', '$end_date', '$start_time', '$end_time', '$location', '$description', '$username')";

        if ($conn->query($sql) === TRUE) {
            sendEventReminder($event_name, $start_date, $start_time, $location, $description);
            $message = "Event added successfully!";
            $messageType = "success";

            // Retrieve the newly inserted event
            $last_id = $conn->insert_id;
            $fetchEvent = "SELECT * FROM events WHERE id = $last_id";
            $eventResult = $conn->query($fetchEvent);
            if ($eventResult && $eventResult->num_rows > 0) {
                $newlyAddedEvent = $eventResult->fetch_assoc();
            }
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "error";
        }
    }

    
}

// Fetch events created by the logged-in user
$userEvents = $conn->query("SELECT * FROM events WHERE created_by = '$username' ORDER BY start_date DESC");

if ($userEvents->num_rows > 0): ?>
    <!-- <h3 style="text-align:center;">Your Events</h3> -->
    <div class="event-cards-container">
        <?php while ($event = $userEvents->fetch_assoc()): ?>
            <div class="event-card">
                <h4><?= htmlspecialchars($event['event_name']) ?></h4>
                <div class="event-detail"><strong>Date:</strong> <?= $event['start_date'] ?> - <?= $event['end_date'] ?></div>
                <div class="event-detail"><strong>Time:</strong> <?= $event['start_time'] ?> - <?= $event['end_time'] ?></div>
                <div class="event-detail"><strong>Venue:</strong> <?= htmlspecialchars($event['location']) ?></div>
                <div class="event-detail"><strong>Description:</strong> <?= nl2br(htmlspecialchars($event['description'])) ?></div>
                <div class="event-actions">
                    <form method="POST" action="update_event.php">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <button type="submit">Update</button>
                    </form>
                    <form method="POST" action="delete_event.php">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <button style="float:right;" type="submit" onclick="return confirm('Are you sure you want to delete this event?')">Delete</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p style="text-align:center; color:black;">No events created by you yet.</p>
<?php endif; ?>


<style>
    /* Container styling */
/* Container for the table */
/* Wrapper for the whole table */
.event-cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); /* Better for desktop */
    gap: 25px;
    padding: 30px;
    max-width: 95%;
    margin: 0 auto;
}

.event-card {
    background: #ffffffcc;
    border-radius: 15px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    padding: 20px 25px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.3s ease;
    height: 100%;
}

.event-card:hover {
    transform: translateY(-5px);
}

.event-card h4 {
    margin-top: 0;
    font-size: 1.4rem;
    color: #333;
}

.event-detail {
    margin-bottom: 10px;
    font-size: 0.95rem;
    color: #444;
    line-height: 1.4;
}

.event-actions {
    margin-top: auto;
    display: flex;
    justify-content: flex-start;
    gap: 10px;
    padding-top: 10px;
}

.event-actions form {
    display: inline;
}

.event-actions button {
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.event-actions button:first-child {
    background-color: #2980b9;
    color: white;
}

.event-actions button:first-child:hover {
    background-color: #1c6691;
}

.event-actions button:last-child {
    background-color: #e74c3c;
    color: white;
}

.event-actions button:last-child:hover {
    background-color: #c0392b;
}

/* Responsive */
@media (max-width: 500px) {
    .event-card {
        padding: 15px 18px;
    }

    .event-card h4 {
        font-size: 1.2rem;
    }

    .event-detail {
        font-size: 0.9rem;
    }

    .event-actions button {
        font-size: 13px;
        padding: 6px 12px;
    }
}

/* Mobile-friendly tweaks */
@media (max-width: 768px) {
    .event-cards-container {
        /* grid-template-columns: 1fr; */
        width: 400px;
        margin-left:-60px;
    }

    .event-card {
        padding: 18px;
    }
}

    </style>

<?php if (!empty($message)) : ?>
    <div class="overlay" id="overlay"></div>
    <div class="popup <?= $messageType ?>" id="popup">
        <h3><?= ($messageType === 'success') ? 'Success!' : 'Error!' ?></h3>
        <p><?= $message ?></p>
        <button class="close-btn" onclick="closePopup()">OK</button>
    </div>

    <script>
        document.getElementById("popup").style.display = "block";
        document.getElementById("overlay").style.display = "block";

        function closePopup() {
            document.getElementById("popup").style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }
    </script>
<?php endif; ?>
                    <div class="container-fluid">
                        <div class="footer">
                        <p>&copy; 2025 ADRI India, All rights reserved |  <a style="color:#101F60" href = "developer.php"> Developer contact </a>
                            
                        </p>
                        </div>
                    </div>



                    


                    <script>
                        // Toggle between the editable and non-editable views
                        document.querySelectorAll('.edit_btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const rowId = this.getAttribute('data-id');
                                document.querySelector(`#row_${rowId} .start_time_display`).style.display = 'none';
                                document.querySelector(`#row_${rowId} .end_time_display`).style.display = 'none';
                                document.querySelector(`#row_${rowId} .activity_display`).style.display = 'none';
                                document.querySelector(`#row_${rowId} .start_time_input`).style.display = 'inline-block';
                                document.querySelector(`#row_${rowId} .end_time_input`).style.display = 'inline-block';
                                document.querySelector(`#row_${rowId} .activity_input`).style.display = 'inline-block';
                                document.querySelector(`#row_${rowId} .edit_btn`).style.display = 'none';
                                document.querySelector(`#row_${rowId} .save_btn`).style.display = 'inline-block';
                            });
                        });

                        // Save the updated activity data
                        document.querySelectorAll('.save_btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const rowId = this.getAttribute('data-id');
                                const start_time = document.querySelector(`#row_${rowId} .start_time_input`).value;
                                const end_time = document.querySelector(`#row_${rowId} .end_time_input`).value;
                                const activity = document.querySelector(`#row_${rowId} .activity_input`).value;

                                // Send the updated data via AJAX
                                const xhr = new XMLHttpRequest();
                                xhr.open('POST', 'update_activity.php', true);
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                    // On success, update the display and hide the input fields
                                    document.querySelector(`#row_${rowId} .start_time_display`).innerText = start_time;
                                    document.querySelector(`#row_${rowId} .end_time_display`).innerText = end_time;
                                    document.querySelector(`#row_${rowId} .activity_display`).innerText = activity;
                                    document.querySelector(`#row_${rowId} .start_time_display`).style.display = 'inline-block';
                                    document.querySelector(`#row_${rowId} .end_time_display`).style.display = 'inline-block';
                                    document.querySelector(`#row_${rowId} .activity_display`).style.display = 'inline-block';
                                    document.querySelector(`#row_${rowId} .start_time_input`).style.display = 'none';
                                    document.querySelector(`#row_${rowId} .end_time_input`).style.display = 'none';
                                    document.querySelector(`#row_${rowId} .activity_input`).style.display = 'none';
                                    document.querySelector(`#row_${rowId} .edit_btn`).style.display = 'inline-block';
                                    document.querySelector(`#row_${rowId} .save_btn`).style.display = 'none';
                                    } else {
                                    alert('Error updating activity.');
                                    }
                                };
                                xhr.send(`id=${rowId}&start_time=${start_time}&end_time=${end_time}&activity=${activity}`);
                            });
                        });
                    </script>
                </div>
            </div>
            
        

        <script>
            // Toggle between the editable and non-editable views
            document.querySelectorAll('.edit_btn').forEach(button => {
                button.addEventListener('click', function() {
                    const rowId = this.getAttribute('data-id');
                    document.querySelector(`#row_${rowId} .start_time_display`).style.display = 'none';
                    document.querySelector(`#row_${rowId} .end_time_display`).style.display = 'none';
                    document.querySelector(`#row_${rowId} .activity_display`).style.display = 'none';
                    document.querySelector(`#row_${rowId} .start_time_input`).style.display = 'inline-block';
                    document.querySelector(`#row_${rowId} .end_time_input`).style.display = 'inline-block';
                    document.querySelector(`#row_${rowId} .activity_input`).style.display = 'inline-block';
                    document.querySelector(`#row_${rowId} .edit_btn`).style.display = 'none';
                    document.querySelector(`#row_${rowId} .save_btn`).style.display = 'inline-block';
                });
            });

            // Save the updated activity data
            document.querySelectorAll('.save_btn').forEach(button => {
                button.addEventListener('click', function() {
                    const rowId = this.getAttribute('data-id');
                    const start_time = document.querySelector(`#row_${rowId} .start_time_input`).value;
                    const end_time = document.querySelector(`#row_${rowId} .end_time_input`).value;
                    const activity = document.querySelector(`#row_${rowId} .activity_input`).value;

                    // Send the updated data via AJAX
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'update_activity.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                        // On success, update the display and hide the input fields
                        document.querySelector(`#row_${rowId} .start_time_display`).innerText = start_time;
                        document.querySelector(`#row_${rowId} .end_time_display`).innerText = end_time;
                        document.querySelector(`#row_${rowId} .activity_display`).innerText = activity;
                        document.querySelector(`#row_${rowId} .start_time_display`).style.display = 'inline-block';
                        document.querySelector(`#row_${rowId} .end_time_display`).style.display = 'inline-block';
                        document.querySelector(`#row_${rowId} .activity_display`).style.display = 'inline-block';
                        document.querySelector(`#row_${rowId} .start_time_input`).style.display = 'none';
                        document.querySelector(`#row_${rowId} .end_time_input`).style.display = 'none';
                        document.querySelector(`#row_${rowId} .activity_input`).style.display = 'none';
                        document.querySelector(`#row_${rowId} .edit_btn`).style.display = 'inline-block';
                        document.querySelector(`#row_${rowId} .save_btn`).style.display = 'none';
                        } else {
                        alert('Error updating activity.');
                        }
                    };
                    xhr.send(`id=${rowId}&start_time=${start_time}&end_time=${end_time}&activity=${activity}`);
                });
            });
        </script>
                  <!-- footer -->
                  
               </div>
               <!-- end dashboard inner -->
            </div>
         
   




   <!-- <body class="dashboard dashboard_1">
        <div class="full_container">
            <div class="inner_container">
                <-- Sidebar  -->
                <!-- <nav id="sidebar"> -->
                
                <!-- end sidebar --
                right content -->
                
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