<?php
include 'db.php'; // Database connection file

$today = date("Y-m-d"); // Get today's date
$currentTime = date("H:i:s"); // Get current time

$query = "SELECT event_name, description, start_time, end_time, location
          FROM events
          WHERE (start_date = '$today') 
          OR (start_date <= '$today' AND end_date >= '$today')";

$result = mysqli_query($conn, $query);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
}

// Debugging: Log fetched data
error_log("Fetched Events: " . json_encode($events));

header('Content-Type: application/json');
echo json_encode($events);
?>
