<?php
// Connect to the database
$conn = new mysqli('localhost:3307', 'root', '', 'adri_dashboard');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch events from the database
$sql = "SELECT * FROM calendar_events";
$result = $conn->query($sql);

$events = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = array(
            'id' => $row['id'],
            'title' => $row['title'],
            'allDay' => $row['allDay'] == 1, // Convert to boolean
            'bufferBefore' => $row['bufferBefore'],
            'start' => $row['start'],
            'end' => $row['end'],
            'description' => $row['description'],
            'color' => $row['color']
        );
    }
}

// Send events as JSON
header('Content-Type: application/json');
echo json_encode($events);

$conn->close();
?>
