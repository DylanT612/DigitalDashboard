<?php
// Database connection details
$host = 'localhost';
$user = 'root';
$pass = 'mysql'; 
$dbname = 'dashboardDB';

try {
    // Create a new PDO instance and set the error mode to exception
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Handle connection failure
    echo "Connection failed: " . $e->getMessage();
}
?>
