<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "adri_dashboard");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch events from the database
$sql = "SELECT event_date, event_title FROM calendar_events";
$result = $conn->query($sql);

$events = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[$row['event_date']] = $row['event_title'];
    }
}

echo json_encode($events);

$conn->close();
?>
