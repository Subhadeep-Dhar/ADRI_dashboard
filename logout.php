<?php
session_start();
require_once 'db.php';

// Remove remember me token from database and cookie
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Update the database to nullify the remember_token
    $sql = "UPDATE users SET remember_token = NULL WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    // Destroy session and delete the remember me cookie
    session_unset();
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/'); // Expire the cookie

    header("Location: login.php");
    exit();
}
?>
