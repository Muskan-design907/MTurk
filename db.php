<?php
// db.php - database connection file
 
$host = "localhost"; // Usually localhost unless your DB is hosted elsewhere
$dbname = "dbxqv2c9gqhjxl";
$username = "ur9iyguafpilu";
$password = "51gssrtsv3ei";
 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode to exception for better debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
 
