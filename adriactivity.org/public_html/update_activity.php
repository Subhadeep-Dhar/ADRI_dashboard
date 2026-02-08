<?php
// Include database connection
include 'db.php';

// Check if the data is received via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['start_time'], $_POST['end_time'], $_POST['activity'])) {
    $id = $_POST['id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $activity = $_POST['activity'];

    // Update the activity in the database
    $sql = "UPDATE availability SET start_time = ?, end_time = ?, activity = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $start_time, $end_time, $activity, $id);

    if ($stmt->execute()) {
        echo 'Activity updated successfully.';
    } else {
        echo 'Error updating activity.';
    }
    $stmt->close();
}
?>
