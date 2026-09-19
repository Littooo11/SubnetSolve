<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$badges = mysqli_query($conn, "SELECT * FROM badges ORDER BY criteria_type, criteria_value");

$earnedStmt = mysqli_prepare($conn, "SELECT badge_id, earned_at FROM user_badges WHERE user_id = ?");
mysqli_stmt_bind_param($earnedStmt, "i", $userId);
mysqli_stmt_execute($earnedStmt);
$earnedResult = mysqli_stmt_get_result($earnedStmt);
$earnedMap = [];
while ($row = mysqli_fetch_assoc($earnedResult)) {
    $earnedMap[(int) $row["badge_id"]] = $row["earned_at"];
}

$criteriaLabels = [
    "quizzes_completed" => "sessions completed",
    "level"              => "reach Level",
    "current_streak"     => "day streak",
    "career_xp"          => "career XP",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Achievements - SubNetSolve</title>
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
        <a href="achievements.php" class="nav-link active">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Achievements</h2>
            <p>Unlock badges by playing games and leveling up.</p>
        </div>

        <div class="badge-grid">
            <?php while ($b = mysqli_fetch_assoc($badges)):
                $earned = isset($earnedMap[(int) $b["id"]]);
                $label = $criteriaLabels[$b["criteria_type"]] ?? $b["criteria_type"];
            ?>
                <div class="badge-tile <?= $earned ? "" : "locked" ?>">
                    <div class="badge-icon"><?= $b["icon"] ?></div>
                    <h4><?= htmlspecialchars($b["name"]) ?></h4>
                    <p><?= htmlspecialchars($b["description"]) ?></p>
                    <?php if ($earned): ?>
                        <div class="badge-earned-date">Earned <?= date("M j, Y", strtotime($earnedMap[(int) $b["id"]])) ?></div>
                    <?php else: ?>
                        <div class="badge-earned-date" style="color:var(--text-dim);">Requires <?= number_format($b["criteria_value"]) ?> <?= htmlspecialchars($label) ?></div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>
</body>
</html>