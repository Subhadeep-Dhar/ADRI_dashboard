<?php
// Connect to the database
$conn = new mysqli('localhost:3307', 'root', '', 'adri_dashboard');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch events
$sql = "SELECT id, title, allDay, bufferBefore, start, end, description, color FROM calendar_events";
$result = $conn->query($sql);

// Prepare an array to store events
$events = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'allDay' => $row['allDay'] == 1 ? true : false, // Convert to boolean
            'bufferBefore' => (int)$row['bufferBefore'],
            'start' => $row['start'],
            'end' => $row['end'],
            'description' => $row['description'],
            'color' => $row['color']
        ];
    }
}

// Return the events as JSON
header('Content-Type: application/json');
echo json_encode($events);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
    
    <!-- Include Mobiscroll CSS -->
    <link rel="stylesheet" href="https://cdn.mobiscroll.com/5.19.1/css/mobiscroll.min.css">
</head>
<body>
    <div id="myCalendar"></div>
    
    <!-- Include Mobiscroll JS -->
    <script src="https://cdn.mobiscroll.com/5.19.1/js/mobiscroll.min.js"></script>
    
    <!-- Calendar Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fetch events from the database
            fetch('get_events.php')
                .then(response => response.json())
                .then(events => {
                    // Initialize the Mobiscroll calendar with fetched events
                    mobiscroll.eventcalendar('#myCalendar', {
                        data: events, // Add events to the calendar
                        view: {
                            calendar: { type: 'month' },
                            eventList: { type: 'day' }
                        },
                        clickToCreate: true,
                        dragToCreate: true,
                        dragToMove: true,
                        dragToResize: true,
                        onEventClick: function (args) {
                            alert('Event: ' + args.event.title); // Example click handler
                        }
                    });
                })
                .catch(error => console.error('Error fetching events:', error));
        });
    </script>
</body>
</html>
