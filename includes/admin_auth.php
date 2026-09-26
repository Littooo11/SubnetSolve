<?php
// includes/admin_auth.php
// Include this at the top of any admin page (after session_start() and config.php).

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if (empty($_SESSION["is_admin"])) {
    header("Location: ../dashboard.php?error=" . urlencode("You don't have access to the admin panel."));
    exit();
}