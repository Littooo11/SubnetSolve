<?php
// includes/block_admin_play.php
// Admin accounts run the platform; they don't play on it. Call this near the top of
// any page or endpoint that lets someone play a game, join a match, or earn XP.
//
//   block_admins_from_playing($conn);          // normal pages: shows a "can't play" screen
//   block_admins_from_playing($conn, true);    // JSON endpoints: returns a 403 instead
//
// Checks the database (not just the session), so it still works if an account was
// promoted to admin after they logged in.

function block_admins_from_playing($conn, $asJson = false) {
    if (empty($_SESSION["user_id"])) return;

    $stmt = mysqli_prepare($conn, "SELECT is_admin FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $row = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$row || !$row["is_admin"]) return;

    if ($asJson) {
        http_response_code(403);
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "error" => "Admin accounts can't play games."]);
        exit();
    }

    // Links differ depending on whether we're inside /games/ or the site root
    $prefix = strpos($_SERVER["PHP_SELF"], "/games/") !== false ? "../" : "";

    http_response_code(403);
    header("Cache-Control: no-cache, no-store, must-revalidate");
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admins Can't Play - SubNetSolve</title>
</head>
<body style="margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0b1224; color:#e7ebf5; font-family:'Segoe UI',Arial,sans-serif;">
    <div style="max-width:420px; text-align:center; background:#111a33; border:1px solid #23304f; border-radius:12px; padding:2.5rem;">
        <div style="font-size:2.5rem; margin-bottom:0.5rem;">🛠</div>
        <h2 style="margin:0 0 0.6rem;">Admin accounts can't play games</h2>
        <p style="color:#8b93ab; line-height:1.6; margin:0 0 1.6rem;">
            This account manages SubNetSolve, so games, multiplayer, and XP are turned off for it.
            Use a regular student account to play.
        </p>
        <a href="<?= $prefix ?>admin/dashboard.php" style="display:inline-block; background:#3b82f6; color:#fff; text-decoration:none; padding:0.65rem 1.3rem; border-radius:8px; margin:0.2rem;">Go to Admin Panel</a>
        <a href="<?= $prefix ?>dashboard.php" style="display:inline-block; background:#16213f; color:#e7ebf5; text-decoration:none; padding:0.65rem 1.3rem; border-radius:8px; border:1px solid #23304f; margin:0.2rem;">Dashboard</a>
    </div>
</body>
</html>
    <?php
    exit();
}