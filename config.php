<?php
// config.php - database connection

$host = "localhost";
$db   = "subnet_game";       // change to your database name
$user = "root";         // default XAMPP username
$pass = "password123";             // default XAMPP password (blank)

// Using mysqli (procedural)
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>