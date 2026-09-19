<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION["username"];

// Add a new array entry here any time you build another untimed practice mode.
// "url" = null means it's not built yet (shows "Coming Soon").
$practiceModes = [
    [
        "name" => "Dissect an IP Address",
        "desc" => "Solve network address, broadcast, mask, and host range problems at your own pace — no timer.",
        "url"  => "games/subnet_practice.php",
    ],
    [
        "name" => "Binary Conversion",
        "desc" => "Practice converting binary to decimal with no time pressure, unlimited questions.",
        "url"  => "games/binary_practice.php",
    ],
    [
        "name" => "Subnet Showdown",
        "desc" => "Multiple-choice questions on network address, masks, host range, IP class, and public/private — no timer.",
        "url"  => "games/subnet_showdown_practice.php",
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Practice Mode - SubNetSolve</title>
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
        <a href="practice.php" class="nav-link active">Practice Mode</a>
        <a href="games.php" class="nav-link">Games</a>
        <a href="#" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Practice Mode</h2>
            <p>No timer, no pressure — just practice at your own pace.</p>
        </div>

        <div class="games-grid">
            <?php foreach ($practiceModes as $g): ?>
                <div class="game-tile <?= $g["url"] ? "" : "disabled" ?>">
                    <h3><?= htmlspecialchars($g["name"]) ?></h3>
                    <p><?= htmlspecialchars($g["desc"]) ?></p>
                    <?php if ($g["url"]): ?>
                        <a href="<?= htmlspecialchars($g["url"]) ?>">Play</a>
                    <?php else: ?>
                        <a href="#">Coming Soon</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>