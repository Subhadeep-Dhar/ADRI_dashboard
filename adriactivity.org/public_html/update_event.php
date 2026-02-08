<?php
include "db.php";
include "send_reminders.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && !isset($_POST['update_event'])) {
    // Show update form
    $event_id = $_POST['event_id'];
    $event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
    ?>

<style>
    /* Reset and base */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 0;
    color: #333;
}

/* Container */
.update-container {
    max-width: 600px;
    margin: 60px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

/* Heading */
.update-container h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #444;
}

/* Form group */
.form-group {
    margin-bottom: 20px;
}

/* Labels */
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

/* Inputs and textarea */
.form-group input[type="text"],
.form-group input[type="date"],
.form-group input[type="time"],
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    transition: border-color 0.3s;
    font-size: 15px;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #007bff;
    outline: none;
}

/* Buttons */
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 25px;
}

.form-actions button {
    padding: 10px 20px;
    font-size: 15px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s;
}

button.update-btn {
    background-color: #007bff;
    color: white;
}

button.update-btn:hover {
    background-color: #0056b3;
}

button.cancel-btn {
    background-color: #f44336;
    color: white;
}

button.cancel-btn:hover {
    background-color: #d32f2f;
}

/* Responsive */
@media (max-width: 600px) {
    .form-actions {
        flex-direction: column;
    }

    .form-actions button {
        width: 100%;
        margin-bottom: 10px;
    }
}

</style>
<div class="update-container">
    <h2>Update Event: <?= htmlspecialchars($event['event_name']) ?></h2>
    <form method="POST" action="update_event.php">
    <div class="form-group">
        <input type="hidden" name="event_id" value="<?= $event_id ?>">
        <input type="hidden" name="update_event" value="1">

        <label>Event Name:</label>
        <input type="text" name="event_name" value="<?= htmlspecialchars($event['event_name']) ?>" required><br>

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?= $event['start_date'] ?>" required><br>

        <label>End Date:</label>
        <input type="date" name="end_date" value="<?= $event['end_date'] ?>" required><br>

        <label>Start Time:</label>
        <input type="time" name="start_time" value="<?= $event['start_time'] ?>" required><br>

        <label>End Time:</label>
        <input type="time" name="end_time" value="<?= $event['end_time'] ?>" required><br>

        <label>Location:</label>
        <input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" required><br>

        <label>Description:</label><br>
        <textarea name="description" rows="4" cols="50"><?= htmlspecialchars($event['description']) ?></textarea><br><br>
        </div>

        <div class="form-actions">
            <button type="submit" class="update-btn">Update Event</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='save_event.php'">Cancel</button>
        </div>

    </form>
</div>

    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    // Handle the update
    $event_id = $_POST['event_id'];
    $event_name = $conn->real_escape_string($_POST['event_name']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $location = $conn->real_escape_string($_POST['location']);
    $description = $conn->real_escape_string($_POST['description']);

    $updateSQL = "UPDATE events 
                  SET event_name = '$event_name', 
                      start_date = '$start_date',
                      end_date = '$end_date',
                      start_time = '$start_time',
                      end_time = '$end_time',
                      location = '$location',
                      description = '$description'
                  WHERE id = $event_id";

    if ($conn->query($updateSQL) === TRUE) {
        sendEventReminder("$event_name [UPDATED]", $start_date, $start_time, $location, $description);
        $_SESSION['message'] = "Event updated successfully!";
        $_SESSION['messageType'] = "success";
    } else {
        $_SESSION['message'] = "Error updating event: " . $conn->error;
        $_SESSION['messageType'] = "error";
    }

    header("Location: save_event.php");
    exit;
}
?>
