<?php
// Database connection parameters
$host = 'localhost'; // Database host
$username = 'root';  // Database username
$password = '';      // Database password
$dbname = 'adri_dashboard'; // Database name

// Create connection (Do not close this connection early)
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create the database if it doesn't exist
$createDatabase = "CREATE DATABASE IF NOT EXISTS $dbname";

// Execute the SQL query for database creation
if ($conn->query($createDatabase) === TRUE) {
    // echo "Database created successfully or already exists.\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Switch to the database for table creation
$conn->select_db($dbname);

// SQL to create the 'users' table if it doesn't exist
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    login_status BOOLEAN DEFAULT FALSE
);";

// Execute the SQL query for 'users' table
if ($conn->query($createUsersTable) === TRUE) {
    // echo "Users table created successfully or already exists.\n";
} else {
    echo "Error creating users table: " . $conn->error . "\n";
}

// SQL to create the 'availability' table if it doesn't exist
$createAvailabilityTable = "
CREATE TABLE IF NOT EXISTS availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    availability_status VARCHAR(255),
    comments TEXT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);";

// Execute the SQL query for 'availability' table
if ($conn->query($createAvailabilityTable) === TRUE) {
    // echo "Availability table created successfully or already exists.\n";
} else {
    echo "Error creating availability table: " . $conn->error . "\n";
}

// Note: Do not close the connection here if you plan to use it for queries below
?>
