<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";
require "includes/avatars.php";
require "includes/rank_tiers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$tab = $_GET["tab"] ?? "overall";
$period = $_GET["period"] ?? "all";

$gameFilter = "";
if ($tab === "subnet") $gameFilter = "AND s.game_type LIKE 'subnet_dissect%'";
if ($tab === "binary") $gameFilter = "AND s.game_type LIKE 'binary%'";
if ($tab === "showdown") $gameFilter = "AND s.game_type LIKE 'showdown%'";

$dateFilter = "";
if ($period === "7") $dateFilter = "AND s.played_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
if ($period === "30") $dateFilter = "AND s.played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$sql = "SELECT u.id, u.username, u.avatar, up.level,
        COALESCE(SUM(s.points), 0) AS total_score,
        COUNT(s.id) AS games_played
        FROM users u
        JOIN user_progress up ON up.user_id = u.id
        LEFT JOIN scores s ON s.user_id = u.id $gameFilter $dateFilter
        GROUP BY u.id, u.username, u.avatar, up.level
        ORDER BY total_score DESC
        LIMIT 50";
$result = mysqli_query($conn, $sql);

$players = [];
$myRank = null;
$myRow = null;
$rank = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $row["rank"] = $rank;
    $players[] = $row;
    if ((int) $row["id"] === (int) $userId) { $myRank = $rank; $myRow = $row; }
    $rank++;
}

// If the current user isn't in the top 50, fetch their own stats separately
if (!$myRow) {
    $meSql = "SELECT u.id, u.username, u.avatar, up.level,
        COALESCE(SUM(s.points), 0) AS total_score,
        COUNT(s.id) AS games_played
        FROM users u
        JOIN user_progress up ON up.user_id = u.id
        LEFT JOIN scores s ON s.user_id = u.id $gameFilter $dateFilter
        WHERE u.id = $userId
        GROUP BY u.id, u.username, u.avatar, up.level";
    $myRow = mysqli_fetch_assoc(mysqli_query($conn, $meSql));
}

function tab_url($tab, $period) { return "leaderboards.php?tab=$tab&period=$period"; }

// Precompute display ranges for the tier legend (e.g. "500 - 999")
$tiersList = get_rank_tiers();
$tierRanges = [];
foreach ($tiersList as $i => $t) {
    if ($i === 0) {
        $tierRanges[] = number_format($t["min"]) . "+";
    } else {
        $tierRanges[] = number_format($t["min"]) . " - " . number_format($tiersList[$i - 1]["min"] - 1);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leaderboards - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="layout">
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
        <a href="lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link active">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div class="welcome">
                <h2>🏆 Leaderboards</h2>
                <p>See how you rank among other players. Keep practicing and climb the ranks!</p>
            </div>
            <select onchange="window.location.href=this.value" style="background:var(--panel); color:var(--text); border:1px solid var(--border); border-radius:8px; padding:0.5rem 0.8rem; font-size:0.82rem;">
                <option value="<?= tab_url($tab, 'all') ?>" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
                <option value="<?= tab_url($tab, '7') ?>" <?= $period === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="<?= tab_url($tab, '30') ?>" <?= $period === '30' ? 'selected' : '' ?>>Last 30 Days</option>
            </select>
        </div>

        <div class="tab-row">
            <a href="<?= tab_url('overall', $period) ?>" class="tab-btn <?= $tab === 'overall' ? 'active' : '' ?>">Overall</a>
            <a href="<?= tab_url('subnet', $period) ?>" class="tab-btn <?= $tab === 'subnet' ? 'active' : '' ?>">Subnetting Game</a>
            <a href="<?= tab_url('binary', $period) ?>" class="tab-btn <?= $tab === 'binary' ? 'active' : '' ?>">Binary Game</a>
            <a href="<?= tab_url('showdown', $period) ?>" class="tab-btn <?= $tab === 'showdown' ? 'active' : '' ?>">Subnet Showdown</a>
            <span class="tab-btn disabled">1v1 Matches (Coming Soon)</span>
        </div>

        <div class="explore-panel">
            <?php if (empty($players)): ?>
                <p style="color:var(--text-dim); font-size:0.9rem;">No scores yet for this filter — be the first to play!</p>
            <?php else: ?>
            <table class="lb-table">
                <tr><th>#</th><th>Player</th><th>Total Score</th><th>Games Played</th><th>Rank</th></tr>
                <?php foreach ($players as $p): ?>
                    <tr class="<?= (int) $p["id"] === (int) $userId ? "me" : "" ?>">
                        <td><?= $p["rank"] == 1 ? "🥇" : ($p["rank"] == 2 ? "🥈" : ($p["rank"] == 3 ? "🥉" : $p["rank"])) ?></td>
                        <td class="player-cell"><?= render_avatar($p["avatar"], 28) ?> <?= htmlspecialchars($p["username"]) ?></td>
                        <td><?= number_format($p["total_score"]) ?></td>
                        <td><?= $p["games_played"] ?></td>
                        <td><?= render_tier_badge($p["total_score"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <?php if ($myRank && $myRank > 50): ?>
                <p style="margin-top:1rem; color:var(--text-dim); font-size:0.85rem;">You're ranked #<?= $myRank ?> for this filter — keep playing to break into the top 50!</p>
            <?php endif; ?>
        </div>
    </main>

    <aside class="right-col">
        <div class="panel-box">
            <h4>🏆 Your Rank</h4>
            <?php if ($myRow): ?>
                <div style="display:flex; align-items:center; gap:0.7rem; margin-bottom:0.8rem;">
                    <?= render_avatar($myRow["avatar"], 44) ?>
                    <div>
                        <div style="font-weight:bold;"><?= htmlspecialchars($myRow["username"]) ?></div>
                        <?= render_tier_badge($myRow["total_score"]) ?>
                    </div>
                </div>
                <div class="stat-line"><span>Total Score</span><span class="xp"><?= number_format($myRow["total_score"]) ?></span></div>
                <div class="stat-line"><span>Games Played</span><span><?= $myRow["games_played"] ?></span></div>
                <div class="stat-line"><span>Rank</span><span>#<?= $myRank ?: "50+" ?></span></div>
            <?php else: ?>
                <p style="font-size:0.82rem; color:var(--text-dim);">Play a game to appear on the leaderboard.</p>
            <?php endif; ?>
        </div>

        <div class="panel-box rank-tier-legend">
            <h4>Rank Tiers</h4>
            <?php foreach (get_rank_tiers() as $i => $t): ?>
                <div class="tier-row">
                    <div class="tier-name"><span class="tier-dot" style="background:<?= $t['color'] ?>;"></span><?= $t['name'] ?></div>
                    <span style="color:var(--text-dim);"><?= $tierRanges[$i] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
</div>
</body>
</html>