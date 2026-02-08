<?php
// Connect to the database
$conn = new mysqli('localhost:3307', 'root', '', 'adri_dashboard');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Extract event data
$id = $data['id'];
$title = $data['title'];

$allDay = $data['allDay'] ? 1 : 0;
$bufferBefore = $data['bufferBefore'];
$start = $data['start'];
$end = $data['end'];
$description = $data['description'];
$colour = $data['color'];


// Insert or update the event in the database
$sql = "INSERT INTO calendar_events (id, title, allDay, bufferBefore, start, end, description, color)
        VALUES ('$id', '$title', '$allDay', '$bufferBefore', '$start', '$end', '$description', '$colour')
        ON DUPLICATE KEY UPDATE
        title='$title', allDay='$allDay', bufferBefore='$bufferBefore',
        start='$start', end='$end', description='$description', color='$colour';";

if ($conn->query($sql) === TRUE) {
    echo "Event saved successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
