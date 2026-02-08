<?php
session_start();
require_once '../db.php';

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

<!DOCTYPE html>
<html>

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
      <link rel="icon" href="../ADRI_favicon.png" type="image/png" />
      <!-- bootstrap css -->
      <link rel="stylesheet" href="../css/bootstrap.min.css" />
      <!-- site css -->
      <link rel="stylesheet" href="../style.css" />
      <!-- responsive css -->
      <link rel="stylesheet" href="../css/responsive.css" />
      <!-- color css -->
      <link rel="stylesheet" href="../css/colors.css" />
      <!-- select bootstrap -->
      <link rel="stylesheet" href="../css/bootstrap-select.css" />
      <!-- scrollbar css -->
      <link rel="stylesheet" href="../css/perfect-scrollbar.css" />
      <!-- custom css -->
      <link rel="stylesheet" href="../css/custom.css" />
    <script>
        // Ignore this in your implementation
        window.isMbscDemo = true;
    </script>

    <!-- <title>
        Add/edit/delete events
    </title> -->

    <!-- Mobiscroll JS and CSS Includes -->
    <link rel="stylesheet" href="css/mobiscroll.javascript.min.css">
    <script src="js/mobiscroll.javascript.min.js"></script>

    <style type="text/css">
            body {
        margin: 0;
        padding: 0;
    }

    body,
    html {
        height: 100%;
    }

            .event-color-c {
        display: flex;
        margin: 16px;
        align-items: center;
        cursor: pointer;
    }
    
    .event-color-label {
        flex: 1 0 auto;
    }
    
    .event-color {
        width: 30px;
        height: 30px;
        border-radius: 15px;
        margin-right: 10px;
        margin-left: 240px;
        background: #5ac8fa;
    }
    
    .crud-color-row {
        display: flex;
        justify-content: center;
        margin: 5px;
    }
    
    .crud-color-c {
        padding: 3px;
        margin: 2px;
    }
    
    .crud-color {
        position: relative;
        min-width: 46px;
        min-height: 46px;
        margin: 2px;
        cursor: pointer;
        border-radius: 23px;
        background: #5ac8fa;
    }
    
    .crud-color-c.selected,
    .crud-color-c:hover {
        box-shadow: inset 0 0 0 3px #007bff;
        border-radius: 48px;
    }
    
    .crud-color:before {
        position: absolute;
        top: 50%;
        left: 50%;
        margin-top: -10px;
        margin-left: -10px;
        color: #f7f7f7;
        font-size: 20px;
        text-shadow: 0 0 3px #000;
        display: none;
    }
    
    .crud-color-c.selected .crud-color:before {
        display: block;
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

            <li><a href="../index.php"><i style="font-size:24px; color:#ddd;" class="fa">&#xf015;</i> <span>Home</span></a></li>                     
                
                <li><a href="../form.php"><i style="font-size:20px; color:yellow" class="fa">&#xf15c;</i> <span>Form</span></a></li>
                
                <li><a href="../tables.php"><i style="font-size:20px;" class="fa purple_color2">&#xf0ce;</i> <span>Dashboard</span></a></li>
                
                <li><a href="../contact.php"><i class="fa fa-paper-plane red_color"></i> <span>Contact</span></a></li>
                
                <li><a href="Calendar/Calendar.php"><i class="fa " style="">📅</i> <span>Future events</span></a></li>

                <li><a href="../login.php"><i style="font-size:20px; color:#1ed085" class="fa fa-signin">&#xf08b;</i> <span>Login</span></a></li>
                
                <li><a href="../logout.php"><i style="font-size:20px" class="fa">&#xf08b;</i> <span>Logout</span></a></li>


                
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
                        <a href="../index.html"><img class="img-responsive" style="height:48px" src="../ADRI_logo.png" alt="#" /></a>
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
                                    
                                    <a class="dropdown-item" href="../help.html">Help</a>
                                    <a class="dropdown-item" href="../logout.php"><span>Log Out</span> <i class="fa fa-sign-out"></i></a>
                                </div>
                              </li>
                          </ul>
                        </div>
                    </div>
                  </div>
              </nav>
            </div>
            <!-- end topbar -->

            <div mbsc-page class="demo-create-read-update-delete-CRUD" style="margin:50px; min-height:40%">
              <div style="height:auto;">
                <div id="demo-add-delete-event"></div>
    
            <div style="display: none">
                <div id="demo-add-popup">
                    <div class="mbsc-form-group">
                        <label>
                        Title
                        <input mbsc-input id="event-title">
                    </label>
                        <label>
                        Description
                        <textarea mbsc-textarea id="event-desc"></textarea>
                    </label>
                    </div>
                    <div class="mbsc-form-group">
                        <label>
                        All-day
                        <input mbsc-switch id="event-all-day" type="checkbox" />
                    </label>
                        <label>
                        Starts
                        <input mbsc-input id="start-input" />
                    </label>
                        <label>
                        Ends
                        <input mbsc-input id="end-input" />
                    </label>
                    <label id="travel-time-group">
                        <select data-label="Travel time" mbsc-dropdown id="travel-time-selection">
                            <option value="0">None</option>
                            <option value="5">5 minutes</option>
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </label>
                        <div id="event-date"></div>
                        <div id="event-color-picker" class="event-color-c">
                            <div class="event-color-label">Color</div>
                            <div id="event-color-cont">
                                <div id="event-color" class="event-color"></div>
                            </div>
                        </div>
                        <label>
                        Show as busy
                        <input id="event-status-busy" mbsc-segmented type="radio" name="event-status" value="busy">
                    </label>
                        <label>
                        Show as free
                        <input id="event-status-free" mbsc-segmented type="radio" name="event-status" value="free">
                    </label>
                        <div class="mbsc-button-group">
                            <button class="mbsc-button-block" id="event-delete" mbsc-button data-color="danger" data-variant="outline">Delete event</button>
                        </div>
                    </div>
                </div>
    
                <div id="demo-event-color">
                    <div class="crud-color-row">
                        <div class="crud-color-c" data-value="#ffeb3c">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#ffeb3c"></div>
                        </div>
                        <div class="crud-color-c" data-value="#ff9900">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#ff9900"></div>
                        </div>
                        <div class="crud-color-c" data-value="#f44437">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#f44437"></div>
                        </div>
                        <div class="crud-color-c" data-value="#ea1e63">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#ea1e63"></div>
                        </div>
                        <div class="crud-color-c" data-value="#9c26b0">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#9c26b0"></div>
                        </div>
                    </div>
                    <div class="crud-color-row">
                        <div class="crud-color-c" data-value="#3f51b5">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#3f51b5"></div>
                        </div>
                        <div class="crud-color-c" data-value="">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check"></div>
                        </div>
                        <div class="crud-color-c" data-value="#009788">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#009788"></div>
                        </div>
                        <div class="crud-color-c" data-value="#4baf4f">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#4baf4f"></div>
                        </div>
                        <div class="crud-color-c" data-value="#7e5d4e">
                            <div class="crud-color mbsc-icon mbsc-font-icon mbsc-icon-material-check" style="background:#7e5d4e"></div>
                        </div>
                    </div>
                </div>
            </div>
      
          </div>
        </div>
      </div>
    </div>


    <script>
    
      mobiscroll.setOptions({
        locale: mobiscroll.localeEn,                              // Specify language like: locale: mobiscroll.localePl or omit setting to use default
        theme: 'ios',                                             // Specify theme like: theme: 'ios' or omit setting to use default
        themeVariant: 'light'                                   // More info about themeVariant: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-themeVariant
      });

      var oldEvent;
      var tempEvent = {};
      var deleteEvent;
      var restoreEvent;
      var colorPicker;
      var tempColor;
      var titleInput = document.getElementById('event-title');
      var descriptionTextarea = document.getElementById('event-desc');
      var allDaySwitch = document.getElementById('event-all-day');
      var freeSegmented = document.getElementById('event-status-free');
      var busySegmented = document.getElementById('event-status-busy');
      var deleteButton = document.getElementById('event-delete');
      var colorSelect = document.getElementById('event-color-picker');
      var pickedColor = document.getElementById('event-color');
      var colorElms = document.querySelectorAll('.crud-color-c');
      var travelTime = document.getElementById('travel-time-selection');
      var datePickerResponsive = {
        medium: {
          controls: ['calendar'],
          touchUi: false,
        },
      };
      var datetimePickerResponsive = {
        medium: {
          controls: ['calendar', 'time'],
          touchUi: false,
        },
      };
      var myData = [
        {
          // The events will be displayed in this format
        //   id: 1,
        //   start: '2025-01-08T13:00',
        //   end: '2025-01-08T13:45',
        //   title: "Lunch @ Butcher's",
        //   description: '',
        //   allDay: false,
        //   bufferBefore: 15,
        //   free: true,
        //   color: '#009788',
        // },
        // {
        //   id: 2,
        //   start: '2025-01-06T15:00',
        //   end: '2025-01-06T16:00',
        //   title: 'Conference',
        //   description: '',
        //   allDay: false,
        //   bufferBefore: 30,
        //   free: false,
        //   color: '#ff9900',
        
        // },
        }
      ];

      // Load events dynamically
      // Fetch events from the database and initialize the calendar
      function loadCalendar() {
          fetch('fetch_events.php')
              .then(response => response.json())
              .then(data => {
                  // Populate myData with events fetched from the database
                  var myData = data;

                  // Log data for debugging
                  console.log('Loaded events:', myData);

                  // Initialize your calendar here with updated myData
                  initializeCalendar(myData);
              })
              .catch(error => console.error('Error fetching events:', error));
      }

      // Function to initialize the calendar
      function initializeCalendar(data) {
          // Assuming you already have a function to draw/render your calendar
          // Example of using `myData`:
          console.log('Initializing calendar with:', data);
          // Add your calendar initialization logic here
      }

      // On page load, fetch events and display the calendar
      loadCalendar();      function loadCalendar() {
          fetch('fetch_events.php')
              .then(response => response.json())
              .then(data => {
                  // Populate myData with events fetched from the database
                  var myData = data;

                  // Log data for debugging
                  console.log('Loaded events:', myData);

                  // Initialize your calendar here with updated myData
                  initializeCalendar(myData);
              })
              .catch(error => console.error('Error fetching events:', error));
      }

      // Function to initialize the calendar
      function initializeCalendar(data) {
          // Assuming you already have a function to draw/render your calendar
          // Example of using `myData`:
          console.log('Initializing calendar with:', data);
          // Add your calendar initialization logic here
      }

      // On page load, fetch events and display the calendar
      loadCalendar();



      
      function createAddPopup(elm) {
    deleteButton.style.display = 'none';
    deleteEvent = true;
    restoreEvent = false;

    popup.setOptions({
        headerText: 'New event',
        buttons: [
            'cancel',
            {
                text: 'Add',
                keyCode: 'enter',
                handler: function () {
                    const eventToAdd = {
                        id: tempEvent.id,
                        title: tempEvent.title,
                        description: tempEvent.description,
                        allDay: tempEvent.allDay,
                        bufferBefore: travelTime.value,
                        start: tempEvent.start,
                        end: tempEvent.end,
                        color: tempEvent.color,
                    };

                    // Send the event data to the server
                    fetch('save_event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(eventToAdd),
                    })
                        .then(response => response.text())
                        .then(data => {
                            alert(data); // Notify user of success or failure
                            calendar.updateEvent(eventToAdd);
                            calendar.navigateToEvent(eventToAdd);
                            deleteEvent = false;
                            popup.close();
                        })
                        .catch(error => console.error('Error saving event:', error));
                },
                cssClass: 'mbsc-popup-button-primary',
            },
        ],
    });

    mobiscroll.getInst(titleInput).value = tempEvent.title;
    mobiscroll.getInst(descriptionTextarea).value = '';
    mobiscroll.getInst(allDaySwitch).checked = false;
    range.setVal([tempEvent.start, tempEvent.end]);
    mobiscroll.getInst(busySegmented).checked = true;
    range.setOptions({ controls: ['date'], responsive: datePickerResponsive });
    pickedColor.style.background = '';
    travelTime.value = 0;

    popup.setOptions({ anchor: elm });
    popup.open();
}

      
function createEditPopup(args) {
    var ev = args.event;
    deleteButton.style.display = 'block';
    deleteEvent = false;
    restoreEvent = true;

    popup.setOptions({
        headerText: 'Edit event',
        buttons: [
            'cancel',
            {
                text: 'Save',
                keyCode: 'enter',
                handler: function () {
                    var date = range.getVal();
                    var eventToSave = {
                        id: ev.id,
                        title: titleInput.value,
                        description: descriptionTextarea.value,
                        allDay: mobiscroll.getInst(allDaySwitch).checked,
                        bufferBefore: travelTime.value,
                        start: date[0],
                        end: date[1],
                        free: mobiscroll.getInst(freeSegmented).checked,
                        color: ev.color,
                    };

                    // Send the updated event data to the server
                    fetch('save_event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(eventToSave),
                    })
                        .then(response => response.text())
                        .then(data => {
                            alert(data); // Notify user of success or failure
                            calendar.updateEvent(eventToSave);
                            calendar.navigateToEvent(eventToSave);
                            restoreEvent = false;
                            popup.close();
                        })
                        .catch(error => console.error('Error updating event:', error));
                },
                cssClass: 'mbsc-popup-button-primary',
            },
        ],
    });

    mobiscroll.getInst(titleInput).value = ev.title;
    mobiscroll.getInst(descriptionTextarea).value = ev.description || '';
    mobiscroll.getInst(allDaySwitch).checked = ev.allDay || false;
    range.setVal([ev.start, ev.end]);
    mobiscroll.getInst(busySegmented).checked = !ev.free;
    pickedColor.style.background = ev.color || '';
    travelTime.value = ev.bufferBefore || 0;

    popup.setOptions({ anchor: args.domEvent.target });
    popup.open();
}

      
      var calendar = mobiscroll.eventcalendar('#demo-add-delete-event', {
        clickToCreate: 'double',                                  // More info about clickToCreate: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-clickToCreate
        dragToCreate: true,                                       // More info about dragToCreate: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-dragToCreate
        dragToMove: true,                                         // More info about dragToMove: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-dragToMove
        dragToResize: true,                                       // More info about dragToResize: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-dragToResize
        view: {                                                   // More info about view: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-view
          calendar: { labels: true },
        },
        data: myData,                                             // More info about data: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-data
        onEventClick: function (args) {                           // More info about onEventClick: https://mobiscroll.com/docs/javascript/eventcalendar/api#event-onEventClick
          oldEvent = Object.assign({}, args.event);
          tempEvent = args.event;
      
          if (!popup.isVisible()) {
            createEditPopup(args);
          }
        },
        onEventCreated: function (args) {                         // More info about onEventCreated: https://mobiscroll.com/docs/javascript/eventcalendar/api#event-onEventCreated
          popup.close();
          // store temporary event
          tempEvent = args.event;
          createAddPopup(args.target);
        },
        onEventDeleted: function (args) {                         // More info about onEventDeleted: https://mobiscroll.com/docs/javascript/eventcalendar/api#event-onEventDeleted
          mobiscroll.snackbar({
            button: {
              action: function () {
                calendar.addEvent(args.event);
              },
              text: 'Undo',
            },
            message: 'Event deleted',
          });
        },
      });
      
      var popup = mobiscroll.popup('#demo-add-popup', {
        display: 'bottom',                                        // Specify display mode like: display: 'bottom' or omit setting to use default
        contentPadding: false,
        fullScreen: true,
        onClose: function () {                                    // More info about onClose: https://mobiscroll.com/docs/javascript/eventcalendar/api#event-onClose
          if (deleteEvent) {
            calendar.removeEvent(tempEvent);
          } else if (restoreEvent) {
            calendar.updateEvent(oldEvent);
          }
        },
        responsive: {                                             // More info about responsive: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-responsive
          medium: {
            display: 'anchored',                                  // Specify display mode like: display: 'bottom' or omit setting to use default
            width: 400,                                           // More info about width: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-width
            fullScreen: false,
            touchUi: false,
          },
        },
      });
      
      titleInput.addEventListener('input', function (ev) {
        // update current event's title
        tempEvent.title = ev.target.value;
      });
      
      descriptionTextarea.addEventListener('change', function (ev) {
        // update current event's title
        tempEvent.description = ev.target.value;
      });
      
      allDaySwitch.addEventListener('change', function () {
        var checked = this.checked;
      
        var travelTimeGroup = document.querySelector('#travel-time-group');
        if (checked) {
          travelTimeGroup.style.display = 'none';
          travelTime.value = 0;
        } else {
          travelTimeGroup.style.display = 'flex';
        }
      
        // change range settings based on the allDay
        range.setOptions({
          controls: checked ? ['date'] : ['datetime'],
          responsive: checked ? datePickerResponsive : datetimePickerResponsive,
        });
      
        // update current event's allDay property
        tempEvent.allDay = checked;
      });
      
      var range = mobiscroll.datepicker('#event-date', {
        controls: ['date'],
        select: 'range',
        startInput: '#start-input',
        endInput: '#end-input',
        showRangeLabels: false,
        touchUi: true,
        responsive: datePickerResponsive,                         // More info about responsive: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-responsive
        onChange: function (args) {
          var date = args.value;
          // update event's start date
          tempEvent.start = date[0];
          tempEvent.end = date[1];
        },
      });
      
      document.querySelectorAll('input[name=event-status]').forEach(function (elm) {
        elm.addEventListener('change', function () {
          // update current event's free property
          tempEvent.free = mobiscroll.getInst(freeSegmented).checked;
        });
      });
      
      deleteButton.addEventListener('click', function () {
        // delete current event on button click
        calendar.removeEvent(tempEvent);
      
        // save a local reference to the deleted event
        var deletedEvent = tempEvent;
      
        popup.close();
      
        mobiscroll.snackbar({
          button: {
            action: function () {
              calendar.addEvent(deletedEvent);
            },
            text: 'Undo',
          },
          message: 'Event deleted',
        });
      });
      
      colorPicker = mobiscroll.popup('#demo-event-color', {
        display: 'bottom',                                        // Specify display mode like: display: 'bottom' or omit setting to use default
        contentPadding: false,
        showArrow: false,
        showOverlay: false,
        buttons: [
          'cancel',
          {
            text: 'Set',
            keyCode: 'enter',
            handler: function () {
              setSelectedColor();
            },
            cssClass: 'mbsc-popup-button-primary',
          },
        ],
        responsive: {                                             // More info about responsive: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-responsive
          medium: {
            display: 'anchored',                                  // Specify display mode like: display: 'bottom' or omit setting to use default
            anchor: document.getElementById('event-color-cont'),  // More info about anchor: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-anchor
            buttons: {},                                          // More info about buttons: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-buttons
          },
        },
      });
      
      function selectColor(color, setColor) {
        var selectedElm = document.querySelector('.crud-color-c.selected');
        var newSelected = document.querySelector('.crud-color-c[data-value="' + color + '"]');
      
        if (selectedElm) {
          selectedElm.classList.remove('selected');
        }
        if (newSelected) {
          newSelected.classList.add('selected');
        }
        if (setColor) {
          pickedColor.style.background = color || '';
        }
      }
      
      function setSelectedColor() {
        tempEvent.color = tempColor;
        pickedColor.style.background = tempColor;
        colorPicker.close();
      }
      
      colorSelect.addEventListener('click', function () {
        selectColor(tempEvent.color || '');
        colorPicker.open();
      });
      
      colorElms.forEach(function (elm) {
        elm.addEventListener('click', function () {
          tempColor = elm.getAttribute('data-value');
          selectColor(tempColor);
      
          if (!colorPicker.s.buttons.length) {
            setSelectedColor();
          }
        });
      });
        
    </script>

      <!-- footer -->
      <div class="container-fluid">
        <div class="footer">
            <p>Copyright © 2024 Designed by Subhadeep. All rights reserved.
              <!-- Distributed By: <a href="https://themewagon.com/">ThemeWagon</a> -->
            </p>
        </div>
      </div>
              
        
      <!-- jQuery -->
      <script src="../js/jquery.min.js"></script>
      <script src="../js/popper.min.js"></script>
      <script src="../js/bootstrap.min.js"></script>
      <!-- wow animation -->
      <script src="../js/animate.js"></script>
      <!-- select country -->
      <script src="../js/bootstrap-select.js"></script>
      <!-- owl carousel -->
      <script src="../js/owl.carousel.js"></script> 
      <!-- chart js -->
      <script src="../js/Chart.min.js"></script>
      <script src="../js/Chart.bundle.min.js"></script>
      <script src="../js/utils.js"></script>
      <script src="../js/analyser.js"></script>
      <!-- nice scrollbar -->
      <script src="../js/perfect-scrollbar.min.js"></script>
      <script>
        var ps = new PerfectScrollbar('#sidebar');
      </script>
      <!-- custom js -->
      <script src="../js/custom.js"></script>
      <script src="../js/chart_custom_style1.js"></script>

  </body>

</html>