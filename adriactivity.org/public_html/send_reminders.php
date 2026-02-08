<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include 'db.php';

function sendEventReminder($event_name, $event_date, $event_time, $location, $description) {
    global $conn; // Ensure database connection is accessible

    // Create a new PHPMailer instance inside the function
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'testing9832test@gmail.com'; // Replace with your email
        $mail->Password = 'ysrt pjzw nior kcgm'; // Replace with your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Fetch all users' emails
        $query = "SELECT username, email FROM users";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    $username = $row['username'];
    $email = $row['email'];

    $subject = "Upcoming Event: $event_name";
    $body = "
        <p>Dear $username,</p>
        <p>An event has been scheduled:</p>
        <p><strong>Event:</strong> $event_name</p>
        <p><strong>Date:</strong> $event_date</p>
        <p><strong>Time:</strong> $event_time</p>
        <p><strong>Location:</strong> $location</p>
        <p><strong>Description:</strong> $description</p>
        <p>Please mark your calendar!</p>
        <br>
        <p>Best Regards,</p>
        <p>ADRI</p>
    ";

    $mail->setFrom('testing9832test@gmail.com', 'ADRI India');
    $mail->addAddress($email, $username);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send(); // Send mail to this user

    $mail->clearAddresses(); // Important: Clear recipients before next loop!
}

        } else {
            echo "No users found in the database.";
        }
    } catch (Exception $e) {
        echo "Email could not be sent. Error: {$mail->ErrorInfo}";
    }


    // Close database connection
    // $conn->close();

}
?>
