<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION["username"];

// Add a new array entry here any time you build another game.
// "url" = null means it's not built yet (shows "Coming Soon").
$games = [
    [
        "name" => "Dissect an IP Address",
        "desc" => "Solve network address, broadcast, mask, and host range problems against the clock.",
        "url"  => "games/dissect_ip_timed.php",
    ],
    [
        "name" => "Binary Game",
        "desc" => "Convert flashing binary numbers to decimal as fast as you can before the 3-minute timer runs out.",
        "url"  => "games/binary_game.php",
    ],
    [
        "name" => "Subnet Showdown",
        "desc" => "Multiple-choice questions on network address, masks, host range, IP class, and public/private — 30 seconds each.",
        "url"  => "games/subnet_showdown_timed.php",
    ],
    [
        "name" => "1v1 Match",
        "desc" => "Challenge another player to a live head-to-head quiz race.",
        "url"  => "lobby.php",
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Games - SubNetSolve</title>
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
        <a href="learning_modules.php" class="nav-link">Learning Modules</a>
        <a href="practice.php" class="nav-link">Practice Mode</a>
        <a href="games.php" class="nav-link active">Games</a>
        <a href="lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Games</h2>
            <p>Pick a mode and start practicing.</p>
        </div>

        <div class="games-grid">
            <?php foreach ($games as $g): ?>
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