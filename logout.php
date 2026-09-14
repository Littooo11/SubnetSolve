<?php
session_start();
require "config.php";

// Remove remember-me token from DB and cookie, if present
if (isset($_COOKIE["remember_token"])) {
    $token = $_COOKIE["remember_token"];
    $del = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE token = ?");
    mysqli_stmt_bind_param($del, "s", $token);
    mysqli_stmt_execute($del);

    setcookie("remember_token", "", time() - 3600, "/");
}

session_unset();
session_destroy();
header("Location: login.php");
exit();
?>