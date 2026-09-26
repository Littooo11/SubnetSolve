<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION["username"];

// Same idea as games.php - add an entry here for each new module.
// "url" = "#" means the actual lesson content isn't built yet.
$modules = [
    [
        "name" => "How to Play: Dissect an IP Address",
        "desc" => "Learn how network address, broadcast address, subnet mask, wildcard mask, and usable host range are calculated.",
        "url"  => "#",
    ],
    [
        "name" => "How to Play: Binary Game",
        "desc" => "Learn how to convert binary numbers to decimal, step by step.",
        "url"  => "#",
    ],
    [
        "name" => "How to Play: Subnet Showdown",
        "desc" => "Learn the multiple-choice format and the topics you'll be quizzed on.",
        "url"  => "#",
    ],
    [
        "name" => "How to Play: 1v1 Multiplayer",
        "desc" => "Learn how lobbies, room codes, and live matches work.",
        "url"  => "#",
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Learning Modules - SubNetSolve</title>
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
        <a href="learning_modules.php" class="nav-link active">Learning Modules</a>
        <a href="practice.php" class="nav-link">Practice Mode</a>
        <a href="games.php" class="nav-link">Games</a>
        <a href="lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Learning Modules</h2>
            <p>Not sure how a game works? Start here.</p>
        </div>

        <div class="games-grid">
            <?php foreach ($modules as $m): ?>
                <div class="game-tile">
                    <h3><?= htmlspecialchars($m["name"]) ?></h3>
                    <p><?= htmlspecialchars($m["desc"]) ?></p>
                    <a href="<?= htmlspecialchars($m["url"]) ?>">How to Play</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>