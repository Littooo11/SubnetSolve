<?php
// config.php - database connection

$host = "127.0.0.1";
$db   = "subnet_game";       // change to your database name
$user = "root";         // default XAMPP username
$pass = "";             // default XAMPP password (blank)

// Using mysqli (procedural)
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>