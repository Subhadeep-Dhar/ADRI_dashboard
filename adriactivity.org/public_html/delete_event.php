<?php
include 'db.php';
include 'send_reminders.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];

    $event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();

    // Send cancellation email
    sendEventReminder($event['event_name'] . "[CANCELLED] ", $event['start_date'], $event['start_time'], $event['location'], "This event has been cancelled.");

    // Delete event
    $conn->query("DELETE FROM events WHERE id = $event_id");

    header("Location: save_event.php");
    exit;
}
?>
