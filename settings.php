<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";
require "includes/avatars.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$stmt = mysqli_prepare($conn, "SELECT username, email, avatar, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="layout two-col">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Master Subnetting, Level Up!</p>
            </div>
        </div>
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="#" class="nav-link">Learning Modules</a>
        <a href="practice.php" class="nav-link">Practice Mode</a>
        <a href="games.php" class="nav-link">Games</a>
        <a href="#" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link active">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Settings</h2>
            <p>Manage your account and session.</p>
        </div>

        <div class="explore-panel" style="max-width:520px; margin-bottom:1.25rem;">
            <h3 class="section-title">Account</h3>
            <div style="display:flex; align-items:center; gap:0.9rem; margin-bottom:1rem;">
                <?= render_avatar($user["avatar"], 50) ?>
                <div>
                    <div style="font-weight:bold;"><?= htmlspecialchars($user["username"]) ?></div>
                    <div style="font-size:0.85rem; color:var(--text-dim);"><?= htmlspecialchars($user["email"]) ?></div>
                </div>
            </div>
            <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:1rem;">
                To change your username, email, password, or avatar, head to your profile.
            </p>
            <a href="profile.php" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Edit Profile</a>
        </div>

        <div class="explore-panel" style="max-width:520px; border-color:rgba(239,68,68,0.3);">
            <h3 class="section-title">Session</h3>
            <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:1rem;">
                Signed in as <b><?= htmlspecialchars($user["username"]) ?></b>. Logging out will end your current session on this device.
            </p>
            <a href="logout.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block; border-color:#f87171; color:#f87171;">Log Out</a>
        </div>
    </main>
</div>
</body>
</html>