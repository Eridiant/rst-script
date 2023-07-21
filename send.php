<?php
require 'vendor/autoload.php'; // Load the Dotenv library

use Dotenv\Dotenv;
use \AmoCRM\Client;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Function to save ID to the database
function saveIdToDatabase($id)
{
    // Database credentials
    $dbHost = $_ENV['DB_HOST'];
    $dbUsername = $_ENV['DB_USERNAME'];
    $dbPassword = $_ENV['DB_PASSWORD'];
    $dbName = $_ENV['DB_NAME'];

    // Connect to the database
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Sanitize the ID input (to prevent SQL injection)
    $sanitizedId = $conn->real_escape_string($id);

    // Prepare and execute the SQL query to insert the ID into the database
    $sql = "INSERT INTO ids_table (id) VALUES ('$sanitizedId')";
    if ($conn->query($sql) === TRUE) {
        echo "ID saved successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close the database connection
    $conn->close();
}

// Check if the ID parameter is provided in the API call
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    saveIdToDatabase($id);
} else {
    echo "Error: ID parameter not provided.";
}
