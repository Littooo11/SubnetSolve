<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";
require "includes/module_progress.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$progress = calculate_learning_progress($conn, $userId);
$completedKeys = $progress["completed_keys"];

$modules = [
    [
        "key" => "dissect_ip",
        "title" => "Dissect an IP Address",
        "desc" => "Learn network, broadcast, wildcard masks, and usable host ranges step-by-step.",
        "link" => "learning/dissect_ip.php",
        "icon" => "🧭"
    ],
    [
        "key" => "binary_game",
        "title" => "Binary Game",
        "desc" => "Master binary-to-decimal conversion using bit place values.",
        "link" => "learning/binary_game.php",
        "icon" => "🔢"
    ],
    [
        "key" => "hex_conversion",
        "title" => "Hex Conversion",
        "desc" => "Understand base-16 positional notation and convert hex to decimal.",
        "link" => "learning/hex_conversion.php",
        "icon" => "⚡"
    ],
    [
        "key" => "ipv6_game",
        "title" => "IPv6 Game",
        "desc" => "Learn full expansion, leading-zero stripping, and RFC 5952 zero compression.",
        "link" => "learning/ipv6_game.php",
        "icon" => "🌐"
    ],
    [
        "key" => "subnet_showdown",
        "title" => "Subnet Showdown",
        "desc" => "Master conceptual multiple-choice subnetting questions.",
        "link" => "learning/subnet_showdown.php",
        "icon" => "⚔️"
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
            <p>Complete each module's single mastery question to earn 20% progress toward lesson completion.</p>
        </div>

        <div class="game-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <h3 style="margin: 0;">Overall Progress</h3>
                <span style="font-weight: bold; color: var(--blue);"><?= $progress["percentage"] ?>% Completed</span>
            </div>
            <div class="progress-bar" style="height: 12px; background: #1e2947; border-radius: 6px; overflow: hidden;">
                <div style="width: <?= $progress["percentage"] ?>%; background: var(--green); height: 100%; transition: width 0.4s ease;"></div>
            </div>
            <p style="margin: 0.5rem 0 0; font-size: 0.85rem; color: var(--text-dim);">
                <?= $progress["completed_count"] ?> of 5 lessons completed.
            </p>
        </div>

        <div class="games-grid">
            <?php foreach ($modules as $m): 
                $isDone = in_array($m["key"], $completedKeys);
            ?>
            <a href="<?= $m['link'] ?>" class="game-tile" style="text-decoration: none; position: relative;">
                <div class="game-icon"><?= $m['icon'] ?></div>
                <div class="game-info">
                    <h4>
                        <?= htmlspecialchars($m['title']) ?>
                        <?php if ($isDone): ?>
                            <span style="color: var(--green); font-size: 0.9rem; margin-left: 0.4rem;">✓ Done (+20%)</span>
                        <?php endif; ?>
                    </h4>
                    <p><?= htmlspecialchars($m['desc']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>