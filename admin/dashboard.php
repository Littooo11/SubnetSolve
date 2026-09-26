<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";
require "../includes/avatars.php";

function pct_change($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100);
}
function scalar($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($r);
    return $row ? (float) $row[0] : 0;
}

// ===== Stat cards (each compares the metric now vs the same metric 7 days ago) =====
$totalUsers = scalar($conn, "SELECT COUNT(*) FROM users");
$totalUsersWk = scalar($conn, "SELECT COUNT(*) FROM users WHERE created_at <= NOW() - INTERVAL 7 DAY");
$usersDelta = pct_change($totalUsers, $totalUsersWk);

$activeStudents = scalar($conn, "SELECT COUNT(DISTINCT user_id) FROM scores WHERE played_at >= NOW() - INTERVAL 7 DAY");
$activeStudentsPrev = scalar($conn, "SELECT COUNT(DISTINCT user_id) FROM scores WHERE played_at >= NOW() - INTERVAL 14 DAY AND played_at < NOW() - INTERVAL 7 DAY");
$activeDelta = pct_change($activeStudents, $activeStudentsPrev);

$totalGames = scalar($conn, "SELECT COUNT(*) FROM scores");
$totalGamesWk = scalar($conn, "SELECT COUNT(*) FROM scores WHERE played_at <= NOW() - INTERVAL 7 DAY");
$gamesDelta = pct_change($totalGames, $totalGamesWk);

$totalMatches = scalar($conn, "SELECT COUNT(*) FROM matches WHERE status = 'finished'");
$totalMatchesWk = scalar($conn, "SELECT COUNT(*) FROM matches WHERE status = 'finished' AND ended_at <= NOW() - INTERVAL 7 DAY");
$matchesDelta = pct_change($totalMatches, $totalMatchesWk);

$avgScore = scalar($conn, "SELECT AVG(points) FROM scores");
$avgScoreWk = scalar($conn, "SELECT AVG(points) FROM scores WHERE played_at <= NOW() - INTERVAL 7 DAY");
$avgDelta = pct_change($avgScore, $avgScoreWk);

// ===== User Growth (registrations per day, last 7 days) =====
$growthLabels = [];
$growthData = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date("Y-m-d", strtotime("-$i days"));
    $count = scalar($conn, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$day'");
    $growthLabels[] = date("M j", strtotime($day));
    $growthData[] = (int) $count;
}

// ===== Game Category Popularity =====
$categoryMap = [
    "subnet_dissect_practice" => "Dissect an IP", "subnet_dissect_timed" => "Dissect an IP",
    "binary_practice" => "Binary Game", "binary_game" => "Binary Game",
    "showdown_practice" => "Subnet Showdown", "showdown_timed" => "Subnet Showdown",
    "multiplayer_1v1" => "Multiplayer 1v1",
];
$categoryColors = ["Dissect an IP" => "#3b82f6", "Binary Game" => "#22c55e", "Subnet Showdown" => "#f59e0b", "Multiplayer 1v1" => "#8b5cf6", "Other" => "#64748b"];
$rawCounts = [];
$typeResult = mysqli_query($conn, "SELECT game_type, COUNT(*) AS c FROM scores GROUP BY game_type");
while ($row = mysqli_fetch_assoc($typeResult)) {
    $label = $categoryMap[$row["game_type"]] ?? "Other";
    $rawCounts[$label] = ($rawCounts[$label] ?? 0) + (int) $row["c"];
}
arsort($rawCounts);
$categoryTotal = array_sum($rawCounts) ?: 1;

// ===== Recent Activity (merged from several tables) =====
$activity = [];
$r = mysqli_query($conn, "SELECT username, created_at AS ts FROM users ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) $activity[] = ["ts" => $row["ts"], "icon" => "👤", "color" => "blue", "text" => "New user registered: " . $row["username"]];

$r = mysqli_query($conn, "SELECT u.username, s.played_at AS ts, s.game_type FROM scores s JOIN users u ON u.id = s.user_id ORDER BY s.played_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) {
    $label = $categoryMap[$row["game_type"]] ?? $row["game_type"];
    $activity[] = ["ts" => $row["ts"], "icon" => "🎮", "color" => "orange", "text" => $row["username"] . " completed " . $label];
}

$r = mysqli_query($conn, "SELECT id, ended_at AS ts FROM matches WHERE status = 'finished' ORDER BY ended_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) $activity[] = ["ts" => $row["ts"], "icon" => "⚔️", "color" => "purple", "text" => "Match #" . $row["id"] . " completed"];

$r = mysqli_query($conn, "SELECT u.username, b.name, ub.earned_at AS ts FROM user_badges ub JOIN users u ON u.id = ub.user_id JOIN badges b ON b.id = ub.badge_id ORDER BY ub.earned_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) $activity[] = ["ts" => $row["ts"], "icon" => "🏆", "color" => "gold", "text" => $row["username"] . " earned badge: " . $row["name"]];

usort($activity, fn($a, $b) => strtotime($b["ts"]) - strtotime($a["ts"]));
$activity = array_slice($activity, 0, 7);

// ===== Top Performers =====
$topPerformers = mysqli_fetch_all(mysqli_query($conn, "SELECT u.username, u.avatar, up.career_xp, up.quizzes_completed, up.total_correct, up.total_wrong
    FROM user_progress up JOIN users u ON u.id = up.user_id
    ORDER BY up.career_xp DESC LIMIT 5"), MYSQLI_ASSOC);

// ===== Most Played Games =====
$moduleIcons = ["Dissect an IP" => ["🌐", "#3b82f6"], "Binary Game" => ["01", "#22c55e"], "Subnet Showdown" => ["🎯", "#f59e0b"], "Multiplayer 1v1" => ["⚔️", "#8b5cf6"], "Other" => ["🎮", "#64748b"]];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand" style="margin-bottom:1.5rem;">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Admin Panel</p>
            </div>
        </div>

        <div class="admin-nav-label">MAIN NAVIGATION</div>
        <a href="dashboard.php" class="admin-nav-link active">🏠 Dashboard</a>
        <a href="users.php" class="admin-nav-link">👥 User Management</a>
        <a href="#" class="admin-nav-link">📘 Learning Modules <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">📋 Question Management <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">🎮 Game Management <span class="soon">Soon</span></a>
        <a href="../lobby.php" class="admin-nav-link">⚔️ 1v1 Multiplayer</a>
        <a href="../leaderboards.php" class="admin-nav-link">🏆 Leaderboard</a>
        <a href="reports.php" class="admin-nav-link">📊 Reports</a>
        <a href="#" class="admin-nav-link">🕓 Activity Logs <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">📣 Announcements <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">⚙️ System Settings <span class="soon">Soon</span></a>

        <div class="admin-nav-label">ACCOUNT</div>
        <a href="../profile.php" class="admin-nav-link">👤 Admin Profile</a>
        <a href="../dashboard.php" class="admin-nav-link">↩ Back to Site</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>

        <div class="admin-sidebar-footer">
            <div class="brand-mini">SubNet<span style="color:var(--blue);">Solve</span></div>
            <p>Learn · Practice · Compete</p>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="margin:0 0 0.3rem;">Dashboard</h2>
                <p style="color:var(--text-dim); margin:0;">Welcome back, <?= htmlspecialchars($_SESSION["username"]) ?>! Here's an overview of the system.</p>
            </div>
            <div class="admin-date-box">
                <b><?= date("M j, Y") ?></b>
                <?= date("g:i A") ?>
            </div>
        </div>

        <div class="admin-stats-row">
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:rgba(59,130,246,0.15); color:var(--blue);">👤</div>
                <div class="label">Total Users</div>
                <div class="value"><?= number_format($totalUsers) ?></div>
                <div class="delta"><?= $usersDelta >= 0 ? "↑" : "↓" ?> <?= abs($usersDelta) ?>%</div>
                <div class="delta-sub">vs last 7 days</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:rgba(34,197,94,0.15); color:var(--green);">🎓</div>
                <div class="label">Active Students</div>
                <div class="value"><?= number_format($activeStudents) ?></div>
                <div class="delta"><?= $activeDelta >= 0 ? "↑" : "↓" ?> <?= abs($activeDelta) ?>%</div>
                <div class="delta-sub">vs last 7 days</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:rgba(139,92,246,0.15); color:var(--purple);">🎮</div>
                <div class="label">Total Games Played</div>
                <div class="value"><?= number_format($totalGames) ?></div>
                <div class="delta"><?= $gamesDelta >= 0 ? "↑" : "↓" ?> <?= abs($gamesDelta) ?>%</div>
                <div class="delta-sub">vs last 7 days</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:rgba(234,179,8,0.15); color:var(--gold);">🏆</div>
                <div class="label">Total Matches (1v1)</div>
                <div class="value"><?= number_format($totalMatches) ?></div>
                <div class="delta"><?= $matchesDelta >= 0 ? "↑" : "↓" ?> <?= abs($matchesDelta) ?>%</div>
                <div class="delta-sub">vs last 7 days</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:rgba(20,184,166,0.15); color:var(--teal);">⭐</div>
                <div class="label">Average Score</div>
                <div class="value"><?= number_format($avgScore, 0) ?></div>
                <div class="delta"><?= $avgDelta >= 0 ? "↑" : "↓" ?> <?= abs($avgDelta) ?>%</div>
                <div class="delta-sub">vs last 7 days</div>
            </div>
        </div>

        <div class="admin-panels-row">
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h3>👥 User Growth</h3>
                    <span class="admin-select">Last 7 Days</span>
                </div>
                <canvas id="growthChart" height="180"></canvas>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-header"><h3>🎮 Game Category Popularity</h3></div>
                <canvas id="categoryChart" height="180"></canvas>
                <div style="margin-top:0.8rem;">
                    <?php foreach ($rawCounts as $label => $count):
                        $pct = round(($count / $categoryTotal) * 100);
                        $color = $categoryColors[$label] ?? $categoryColors["Other"];
                    ?>
                        <div class="legend-row"><span class="legend-dot" style="background:<?= $color ?>;"></span><?= htmlspecialchars($label) ?><span class="pct"><?= $pct ?>%</span></div>
                    <?php endforeach; ?>
                    <?php if (empty($rawCounts)): ?>
                        <p style="color:var(--text-dim); font-size:0.8rem;">No games played yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-header"><h3>🕓 Recent Activity</h3></div>
                <?php if (empty($activity)): ?>
                    <p style="color:var(--text-dim); font-size:0.82rem;">Nothing yet.</p>
                <?php else: ?>
                    <?php foreach ($activity as $a): ?>
                        <div class="admin-activity-row">
                            <div class="aicon" style="background:rgba(59,130,246,0.15);"><?= $a["icon"] ?></div>
                            <div><?= htmlspecialchars($a["text"]) ?></div>
                            <div class="atime"><?= date("g:i A", strtotime($a["ts"])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-bottom-row">
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h3>🏆 Top Performers</h3>
                    <span class="admin-select">All Time</span>
                </div>
                <?php if (empty($topPerformers)): ?>
                    <p style="color:var(--text-dim); font-size:0.82rem;">No players yet.</p>
                <?php else: ?>
                <table class="admin-table">
                    <tr><th>#</th><th>User</th><th>Score</th><th>Games</th><th>Accuracy</th></tr>
                    <?php foreach ($topPerformers as $i => $p):
                        $total = $p["total_correct"] + $p["total_wrong"];
                        $acc = $total > 0 ? round(($p["total_correct"] / $total) * 100) : 0;
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($p["username"]) ?></td>
                            <td><?= number_format($p["career_xp"]) ?></td>
                            <td><?= $p["quizzes_completed"] ?></td>
                            <td><?= $acc ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-header"><h3>📘 Most Played Games</h3></div>
                <?php if (empty($rawCounts)): ?>
                    <p style="color:var(--text-dim); font-size:0.82rem;">No data yet.</p>
                <?php else: ?>
                    <?php $rank = 1; foreach ($rawCounts as $label => $count):
                        [$icon, $color] = $moduleIcons[$label] ?? $moduleIcons["Other"];
                    ?>
                        <div class="module-row">
                            <div class="module-rank"><?= $rank++ ?></div>
                            <div class="module-icon" style="background:<?= $color ?>22; color:<?= $color ?>;"><?= $icon ?></div>
                            <div><?= htmlspecialchars($label) ?></div>
                            <div class="module-plays"><?= number_format($count) ?> plays</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-header"><h3>📊 System Reports</h3></div>
                <a href="reports.php?report=users" class="report-tile">
                    <div class="ricon" style="background:rgba(59,130,246,0.15); color:var(--blue);">👤</div>
                    <div class="rtext"><div class="t">User Activity Report</div><div class="d">Signups, activity, engagement</div></div>
                    <div class="rarrow">›</div>
                </a>
                <a href="reports.php?report=games" class="report-tile">
                    <div class="ricon" style="background:rgba(34,197,94,0.15); color:var(--green);">🎮</div>
                    <div class="rtext"><div class="t">Game Statistics Report</div><div class="d">Play counts by game type</div></div>
                    <div class="rarrow">›</div>
                </a>
                <a href="reports.php?report=leaderboard" class="report-tile">
                    <div class="ricon" style="background:rgba(234,179,8,0.15); color:var(--gold);">🏆</div>
                    <div class="rtext"><div class="t">Leaderboard Report</div><div class="d">Top players and scores</div></div>
                    <div class="rarrow">›</div>
                </a>
                <a href="reports.php?report=content" class="report-tile">
                    <div class="ricon" style="background:rgba(139,92,246,0.15); color:var(--purple);">🏅</div>
                    <div class="rtext"><div class="t">Content Report</div><div class="d">Badges earned across users</div></div>
                    <div class="rarrow">›</div>
                </a>
            </div>
        </div>
    </main>
</div>

<script>
new Chart(document.getElementById("growthChart"), {
    type: "line",
    data: {
        labels: <?= json_encode($growthLabels) ?>,
        datasets: [{
            data: <?= json_encode($growthData) ?>,
            borderColor: "#3b82f6",
            backgroundColor: "rgba(59,130,246,0.15)",
            fill: true,
            tension: 0.3,
            pointBackgroundColor: "#3b82f6",
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: "#8b93ab" }, grid: { color: "#23304f" } },
            y: { ticks: { color: "#8b93ab" }, grid: { color: "#23304f" }, beginAtZero: true }
        }
    }
});

new Chart(document.getElementById("categoryChart"), {
    type: "doughnut",
    data: {
        labels: <?= json_encode(array_keys($rawCounts)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($rawCounts)) ?>,
            backgroundColor: <?= json_encode(array_map(fn($l) => $categoryColors[$l] ?? $categoryColors["Other"], array_keys($rawCounts))) ?>,
            borderWidth: 0,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        cutout: "65%"
    }
});
</script>
</body>
</html>