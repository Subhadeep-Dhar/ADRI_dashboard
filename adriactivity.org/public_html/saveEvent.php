<?php
include 'db.php';

// Add event
function addEvent($name, $startDate, $endDate, $startTime, $endTime, $location, $description, $date) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO events (name, start_date, end_date, start_time, end_time, location, description, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $startDate, $endDate, $startTime, $endTime, $location, $description, $date);
    return $stmt->execute();
}

// Edit event
function editEvent($id, $name, $startDate, $endDate, $startTime, $endTime, $location, $description) {
    global $conn;
    $stmt = $conn->prepare("UPDATE events SET name = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?, location = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $name, $startDate, $endDate, $startTime, $endTime, $location, $description, $id);
    return $stmt->execute();
}

// Delete event
function deleteEvent($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Get events for a specific date
function getEventsByDate($date) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM events WHERE date = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result();
}
?>
