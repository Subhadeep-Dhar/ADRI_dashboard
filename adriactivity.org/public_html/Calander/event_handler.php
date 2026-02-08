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



      <style>
        /* Basic styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0px;
            padding: 0;
            text-align: center;
        }

        /* Container for the entire calendar */
#calendar {
    display: flex;
    flex-direction: column;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

/* Header row for days of the week */
.calendar-header-row {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* Create 7 equal columns */
    background-color: #101F30;
    color: #fff;
    font-weight: bold;
    text-align: center;
}

/* Individual day header */
.calendar-header {
    padding: 10px;
}

/* Calendar rows for each week */
.calendar-row {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* Same as header */
    gap: 5px;
}

/* Each day cell */
.day, .empty {
    padding: 10px;
    text-align: center;
    cursor: pointer;
}

.day {
    background-color: #fff;
    /* border: 1px solid #ddd; */
    margin-bottom: 30px;
    border-radius: 5px;
}

.empty {
    background-color: #f0f0f0;
    border: none;
}

.event {
    margin-top: 5px;
    font-size: 0.8rem;
    color: #fff;
    padding: 5px;
    border-radius: 5px;
    background-color: #e53935;
}

/* Make the calendar responsive */
@media screen and (max-width: 600px) {
    #calendar {
        padding: 10px;
    }

    .calendar-header-row, .calendar-row {
        grid-template-columns: repeat(7, 1fr); /* 7 columns for small screens */
    }

    .calendar-header, .day {
        font-size: 0.8rem; /* Reduce font size for smaller screens */
    }
}


        .event:hover {
            background-color: #e53935;
        }

        #eventBox {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            z-index: 1000;
            width: 300px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
        }

        #eventBox input,
        #eventBox textarea,
        #eventBox button {
            display: block;
            width: 100%;
            margin: 10px 0;
        }

        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #eventDetails {
            font-size: 14px;
            margin-top: 10px;
        }



        #eventDetails select {
            margin-top: 10px;
            padding: 5px;
            font-size: 14px;
            width: 100%;
        }

        #eventDetails button {
            margin: 10px 5px;
            padding: 8px 12px;
            background-color: #4caf50; /* Green for Confirm */
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
        }

        #eventDetails button#cancelRemoveEvent {
            background-color: #ff4d4d; /* Red for Cancel */
        }

        #eventDetails button:hover {
            opacity: 0.9;
        }


        .man_bt {
            width: 100px;
            height: auto;
            /* float: left; */
            background: #191919;
            text-align: center;
            color: #fff;
            padding: 10px 25px;
            font-size: 16px;
            border-radius: 5px;
            border: none;
            transition: ease all 0.5s;
            cursor: pointer;
            font-weight: 300;
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
                     
                     <li><a href="future_events.php"><i class="fa " style="">📅</i> <span>Future events</span></a></li>

                     <li><a href="login.php"><i style="font-size:20px; color:#1ed085" class="fa fa-signin">&#xf08b;</i> <span>Login</span></a></li>
                     
                     <li><a href="logout.php"><i style="font-size:20px" class="fa">&#xf08b;</i> <span>Logout</span></a></li>


                     
                  </ul>
               </div>
            </nav>
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


               
    <h1 style="margin-top:5%">Interactive Calendar with Event Description</h1>

    <!-- Calendar Navigation -->
    <div id="calendarNavigation">
        <button id="prevMonth" class="man_bt">&lt; Prev</button>
        <select id="monthSelector"></select>
        <select id="yearSelector"></select>
        <button id="nextMonth"class="man_bt">Next &gt;</button>
    </div>

    <div id="calendar"></div>    

    <!-- Event Box -->
    <!-- Event Box -->
<div id="overlay"></div>
<div id="eventBox">
    <h3 id="eventBoxTitle">Add Event</h3>
    <div id="eventDetails" style="display: none;">
        <!-- Show the event details when an event already exists for the selected date -->
    </div>
    <form id="eventForm">
        <input type="text" id="eventName" placeholder="Event Name" required />
        <label for="startDate">Start Date</label>
        <input type="date" id="startDate" required />
        <label for="endDate">End Date (Optional)</label>
        <input type="date" id="endDate" />
        <label for="startTime">Start Time</label>
        <input type="time" id="startTime" />
        <label for="endTime">End Time</label>
        <input type="time" id="endTime" />
        <label for="location">Location (Optional)</label>
        <input type="text" id="location" placeholder="Event Location" />
        <label for="eventDescription">Event Description (Optional)</label>
        <textarea id="eventDescription" rows="4" placeholder="Add an optional event description..."></textarea>
        <button type="submit">Save Event</button>
    </form>
    <button id="removeEventButton" style="display: none;" onclick="removeEvent()">Remove Event</button>
    <button onclick="closeBox()">Cancel</button>
</div>


    <script>
    const calendar = document.getElementById("calendar");
    const eventBox = document.getElementById("eventBox");
    const overlay = document.getElementById("overlay");
    const eventForm = document.getElementById("eventForm");
    const eventDetails = document.getElementById("eventDetails");
    const removeEventButton = document.getElementById("removeEventButton");
    const eventBoxTitle = document.getElementById("eventBoxTitle");

    let events = {};
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();



    // Populate month and year selectors
    function populateSelectors() {
        monthSelector.innerHTML = "";
        yearSelector.innerHTML = "";

        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        months.forEach((month, index) => {
            const option = document.createElement("option");
            option.value = index;
            option.textContent = month;
            if (index === currentMonth) option.selected = true;
            monthSelector.appendChild(option);
        });

        const startYear = currentYear - 50;
        const endYear = currentYear + 50;
        for (let year = startYear; year <= endYear; year++) {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true;
            yearSelector.appendChild(option);
        }
    }

    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    // Save event
    eventForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const eventName = document.getElementById("eventName").value;
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value || startDate;
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const eventDescription = document.getElementById("eventDescription").value;

    let eventData = {
        name: eventName,
        startDate: startDate,
        endDate: endDate,
        startTime: startTime,
        endTime: endTime,
        description: eventDescription
    };

    // Send the event data to the server using AJAX
    fetch('save_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(eventData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Event saved successfully!');
            closeBox(); // Close the modal or form
            generateCalendar(new Date().getFullYear(), new Date().getMonth()); // Regenerate calendar
        } else {
            alert('Error saving the event: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error with the request');
    });
});


    // Generate calendar
    function generateCalendar(year, month) {
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();
    calendar.innerHTML = ""; // Clear the calendar container

    // Add header for days of the week
    const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    const headerRow = document.createElement("div");
    headerRow.className = "calendar-header-row";
    daysOfWeek.forEach((day) => {
        const header = document.createElement("div");
        header.className = "calendar-header";
        header.textContent = day;
        headerRow.appendChild(header);
    });
    calendar.appendChild(headerRow);

    // Add blank spaces for previous month's days (if the first day is not Sunday)
    const totalCells = daysInMonth + firstDay;
    const rows = Math.ceil(totalCells / 7); // Number of rows in the calendar
    let currentDay = 1;

    // Create rows of the calendar
    for (let row = 0; row < rows; row++) {
        const rowDiv = document.createElement("div");
        rowDiv.className = "calendar-row";

        for (let col = 0; col < 7; col++) {
            const dayCell = document.createElement("div");

            // Check if it's a valid day in the current month
            if (row === 0 && col < firstDay) {
                dayCell.className = "empty"; // Empty cell for previous month days
            } else if (currentDay <= daysInMonth) {
                dayCell.className = "day";
                dayCell.dataset.date = `${year}-${String(month + 1).padStart(2, "0")}-${String(currentDay).padStart(2, "0")}`;
                dayCell.innerHTML = `<span class="date">${currentDay}</span>`;

                // Add event indicator for each event
                const dateKey = dayCell.dataset.date;
                if (events[dateKey] && events[dateKey].length > 0) {
                    events[dateKey].forEach((event) => {
                        const eventElement = document.createElement("div");
                        eventElement.className = "event";
                        eventElement.textContent = event.name;
                        eventElement.style.backgroundColor = event.color; // Apply the event color
                        dayCell.appendChild(eventElement);
                    });
                }

                // Increase the current day number
                currentDay++;

                // Add event listener for clicking a day
                dayCell.addEventListener("click", () => openBox(dayCell.dataset.date));
            } else {
                dayCell.className = "empty"; // Empty cell for days outside the current month
            }

            rowDiv.appendChild(dayCell);
        }

        calendar.appendChild(rowDiv); // Append the row of days
    }
}

 
    
    // Open event box
    let selectedDate = null; // Store the selected date

// Open the event form when a date is clicked
function openBox(date) {
    selectedDate = date; // Store the selected date
    const existingEvents = events[date];

    // Reset the form
    eventForm.reset();
    eventDetails.style.display = 'none';
    removeEventButton.style.display = 'none';
    eventBoxTitle.textContent = 'Add Event';

    // If there are existing events for the selected date
    if (existingEvents && existingEvents.length > 0) {
        eventBoxTitle.textContent = 'Event Details';
        eventDetails.style.display = 'block';
        
        // Display the existing event details
        existingEvents.forEach((event, index) => {
            const eventDiv = document.createElement("div");
            eventDiv.className = "existing-event";
            eventDiv.style.backgroundColor = event.color; // Different color for each event
            eventDiv.innerHTML = `
                <strong>${event.name}</strong><br>
                Start: ${event.startDate} ${event.startTime}<br>
                End: ${event.endDate} ${event.endTime}<br>
                Location: ${event.location}<br>
                Description: ${event.description || "No description"}<br>
                <button onclick="editEvent(${index})">Edit</button>
                <button onclick="deleteEvent(${index})">Delete</button>
            `;
            eventDetails.appendChild(eventDiv);
        });

        removeEventButton.style.display = 'block';
    } else {
        eventDetails.style.display = 'none';
    }

    overlay.style.display = 'block'; // Show overlay
    eventBox.style.display = 'block'; // Show the event box
}

// Close the event form
function closeBox() {
    overlay.style.display = 'none';
    eventBox.style.display = 'none';
    eventDetails.style.display = 'none';
    removeEventButton.style.display = 'none';
}

// Handle form submission (add event)
eventForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const eventName = document.getElementById("eventName").value;
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value || startDate;
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const location = document.getElementById("location").value;
    const eventDescription = document.getElementById("eventDescription").value;

    const eventData = {
        name: eventName,
        startDate: startDate,
        endDate: endDate,
        startTime: startTime,
        endTime: endTime,
        location: location,
        description: eventDescription,
        color: getRandomColor() // Assign a random color to each event
    };

    // Send the event data to the server
    fetch('save_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ eventData, date: selectedDate })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Event saved successfully!');
            closeBox(); // Close the modal
            generateCalendar(currentYear, currentMonth); // Regenerate the calendar
        } else {
            alert('Error saving the event: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error with the request');
    });
});

// Edit event
function editEvent(index) {
    const existingEvent = events[selectedDate][index];

    // Populate the form with the existing event details
    document.getElementById("eventName").value = existingEvent.name;
    document.getElementById("startDate").value = existingEvent.startDate;
    document.getElementById("endDate").value = existingEvent.endDate;
    document.getElementById("startTime").value = existingEvent.startTime;
    document.getElementById("endTime").value = existingEvent.endTime;
    document.getElementById("location").value = existingEvent.location;
    document.getElementById("eventDescription").value = existingEvent.description;

    // Change the form action to update the event
    eventBoxTitle.textContent = "Edit Event";

    // Remove the selected event from the list
    removeEventButton.style.display = 'block';
    removeEventButton.onclick = function() {
        deleteEvent(index);
    };
}

// Delete event
function deleteEvent(index) {
    const eventToDelete = events[selectedDate][index];

    // Send the event data to the server to delete
    fetch('delete_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ eventId: eventToDelete.id, date: selectedDate })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Event deleted successfully!');
            closeBox();
            generateCalendar(currentYear, currentMonth); // Regenerate calendar
        } else {
            alert('Error deleting the event: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error with the request');
    });
}

// Fetch events and populate the calendar
function generateCalendar(year, month) {
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();
    calendar.innerHTML = "";

    // Add header for days of the week
    const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    const headerRow = document.createElement("div");
    headerRow.className = "calendar-header-row";
    daysOfWeek.forEach((day) => {
        const header = document.createElement("div");
        header.className = "calendar-header";
        header.textContent = day;
        headerRow.appendChild(header);
    });
    calendar.appendChild(headerRow);

    let currentDay = 1;
    const totalCells = daysInMonth + firstDay;
    const rows = Math.ceil(totalCells / 7);

    for (let row = 0; row < rows; row++) {
        const rowDiv = document.createElement("div");
        rowDiv.className = "calendar-row";

        for (let col = 0; col < 7; col++) {
            const dayCell = document.createElement("div");

            if (row === 0 && col < firstDay) {
                dayCell.className = "empty";
            } else if (currentDay <= daysInMonth) {
                dayCell.className = "day";
                dayCell.dataset.date = `${year}-${String(month + 1).padStart(2, "0")}-${String(currentDay).padStart(2, "0")}`;
                dayCell.innerHTML = `<span class="date">${currentDay}</span>`;

                // Display events for the date
                const dateKey = dayCell.dataset.date;
                if (events[dateKey] && events[dateKey].length > 0) {
                    events[dateKey].forEach((event) => {
                        const eventElement = document.createElement("div");
                        eventElement.className = "event";
                        eventElement.style.backgroundColor = event.color;
                        eventElement.textContent = event.name;
                        dayCell.appendChild(eventElement);
                    });
                }

                // Set click listener to open the event form
                dayCell.addEventListener("click", () => openBox(dateKey));
                currentDay++;
            } else {
                dayCell.className = "empty";
            }

            rowDiv.appendChild(dayCell);
        }

        calendar.appendChild(rowDiv);
    }
}


    // Show add event form
    function showAddEventForm() {
        eventDetails.style.display = "none";
        removeEventButton.style.display = "none"; // Hide "Remove Event" button
        const addMoreButton = document.getElementById("addMoreEventButton");
        if (addMoreButton) addMoreButton.style.display = "none"; // Hide "Add More Event" button
        eventForm.style.display = "block"; // Show the form
        eventBoxTitle.textContent = "Add Another Event";
    }



    // Event listeners for navigation
    prevMonth.addEventListener("click", () => {
        currentMonth -= 1;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear -= 1;
        }
        populateSelectors();
        generateCalendar(currentYear, currentMonth);
    });

    nextMonth.addEventListener("click", () => {
        currentMonth += 1;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear += 1;
        }
        populateSelectors();
        generateCalendar(currentYear, currentMonth);
    });

    monthSelector.addEventListener("change", (e) => {
        currentMonth = parseInt(e.target.value, 10);
        generateCalendar(currentYear, currentMonth);
    });

    yearSelector.addEventListener("change", (e) => {
        currentYear = parseInt(e.target.value, 10);
        generateCalendar(currentYear, currentMonth);
    });

    // Initialize calendar
    populateSelectors();
    generateCalendar(currentYear, currentMonth);

</script>


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
