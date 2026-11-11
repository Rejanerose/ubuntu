
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$host = "localhost";
$username = "root";
$password = "";
$database = "login_west";
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit("Database connection failed. Please check your database settings.");
}
$conn->set_charset("utf8mb4");
?>
