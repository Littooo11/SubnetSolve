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

// ===== Helper Function for User Growth Data =====
function get_user_growth_dataset($conn, $growthRange) {
    $growthLabels = [];
    $growthData = [];
    $anchorDate = "2026-09-26";

    switch ($growthRange) {
        case "3_days": $numDays = 3; break;
        case "30_days": $numDays = 30; break;
        case "3_months": $numDays = 90; break;
        case "6_months": $numDays = 180; break;
        case "1_year": $numDays = 365; break;
        case "all_time":
            $start = new DateTime($anchorDate);
            $today = new DateTime();
            $numDays = max(1, $today->diff($start)->days + 1);
            break;
        case "7_days":
        default: $numDays = 7; break;
    }

    for ($i = $numDays - 1; $i >= 0; $i--) {
        $day = date("Y-m-d", strtotime("-$i days"));
        if ($day < $anchorDate) continue;

        $count = scalar($conn, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$day'");
        $growthLabels[] = date("M j", strtotime($day));
        $growthData[] = (int) $count;
    }

    return ["labels" => $growthLabels, "data" => $growthData];
}

// ===== Helper Function for System Activity Dataset (Games & Lessons) =====
function get_system_activity_dataset($conn, $activityRange) {
    $labels = [];
    $gamesPlayed = [];
    $lessonsCompleted = [];
    $anchorDate = "2026-09-26";

    switch ($activityRange) {
        case "3_days": $numDays = 3; break;
        case "30_days": $numDays = 30; break;
        case "3_months": $numDays = 90; break;
        case "6_months": $numDays = 180; break;
        case "1_year": $numDays = 365; break;
        case "all_time":
            $start = new DateTime($anchorDate);
            $today = new DateTime();
            $numDays = max(1, $today->diff($start)->days + 1);
            break;
        case "7_days":
        default: $numDays = 7; break;
    }

    for ($i = $numDays - 1; $i >= 0; $i--) {
        $day = date("Y-m-d", strtotime("-$i days"));
        if ($day < $anchorDate) continue;

        $gamesCount = scalar($conn, "SELECT COUNT(*) FROM scores WHERE DATE(played_at) = '$day'");
        $lessonsCount = scalar($conn, "SELECT COUNT(*) FROM user_module_progress WHERE DATE(completed_at) = '$day'");

        $labels[] = date("M j", strtotime($day));
        $gamesPlayed[] = (int) $gamesCount;
        $lessonsCompleted[] = (int) $lessonsCount;
    }

    return [
        "labels" => $labels,
        "games" => $gamesPlayed,
        "lessons" => $lessonsCompleted
    ];
}

// ===== AJAX Endpoints =====
if (isset($_GET["ajax_growth"])) {
    header("Content-Type: application/json");
    echo json_encode(get_user_growth_dataset($conn, $_GET["growth_range"] ?? "7_days"));
    exit();
}

if (isset($_GET["ajax_activity"])) {
    header("Content-Type: application/json");
    echo json_encode(get_system_activity_dataset($conn, $_GET["activity_range"] ?? "7_days"));
    exit();
}

// Stat Cards
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

// Initial Load Datasets
$initialGrowth = get_user_growth_dataset($conn, "7_days");
$initialActivity = get_system_activity_dataset($conn, "7_days");

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

$topPerformers = mysqli_fetch_all(mysqli_query($conn, "SELECT u.username, u.avatar, up.career_xp, up.quizzes_completed, up.total_correct, up.total_wrong
    FROM user_progress up JOIN users u ON u.id = up.user_id
    ORDER BY up.career_xp DESC LIMIT 5"), MYSQLI_ASSOC);

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
        <a href="teacher_applications.php" class="admin-nav-link">🎓 Teacher Apps</a>
        <a href="manage_questions.php" class="admin-nav-link">❓ Manage Questions</a>
        <a href="../leaderboards.php" class="admin-nav-link">🏆 Leaderboard</a>
        <a href="reports.php" class="admin-nav-link">📊 Reports</a>

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

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
            <!-- User Growth Chart -->
            <div class="admin-panel">
                <div class="admin-panel-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0;">👥 User Growth</h3>
                    <select id="growthRangeSelect" class="admin-select" style="padding:0.2rem 0.5rem; background:var(--panel-light); color:var(--text); border:1px solid var(--border); border-radius:6px; cursor:pointer;">
                        <option value="3_days">Last 3 Days</option>
                        <option value="7_days" selected>Last 7 Days</option>
                        <option value="30_days">Last 30 Days (1 Month)</option>
                        <option value="3_months">Last 3 Months</option>
                        <option value="6_months">Last 6 Months</option>
                        <option value="1_year">Last 1 Year</option>
                        <option value="all_time">All Time (Since Sep 26, 2026)</option>
                    </select>
                </div>
                <canvas id="growthChart" height="180"></canvas>
            </div>

            <!-- Activity & Gameplay Chart -->
            <div class="admin-panel">
                <div class="admin-panel-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0;">📊 System Activity & Gameplay</h3>
                    <select id="activityRangeSelect" class="admin-select" style="padding:0.2rem 0.5rem; background:var(--panel-light); color:var(--text); border:1px solid var(--border); border-radius:6px; cursor:pointer;">
                        <option value="3_days">Last 3 Days</option>
                        <option value="7_days" selected>Last 7 Days</option>
                        <option value="30_days">Last 30 Days (1 Month)</option>
                        <option value="3_months">Last 3 Months</option>
                        <option value="6_months">Last 6 Months</option>
                        <option value="1_year">Last 1 Year</option>
                        <option value="all_time">All Time (Since Sep 26, 2026)</option>
                    </select>
                </div>
                <canvas id="activityChart" height="180"></canvas>
            </div>
        </div>

        <div class="admin-panels-row">
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
// Growth Chart
const growthChartCtx = document.getElementById("growthChart").getContext("2d");
let growthChart = new Chart(growthChartCtx, {
    type: "line",
    data: {
        labels: <?= json_encode($initialGrowth["labels"]) ?>,
        datasets: [{
            label: "Registrations",
            data: <?= json_encode($initialGrowth["data"]) ?>,
            borderColor: "#3b82f6",
            backgroundColor: "rgba(59,130,246,0.15)",
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: "#8b93ab" }, grid: { color: "#23304f" } },
            y: { ticks: { color: "#8b93ab", precision: 0 }, grid: { color: "#23304f" }, beginAtZero: true }
        }
    }
});

document.getElementById("growthRangeSelect").addEventListener("change", function() {
    fetch(`dashboard.php?ajax_growth=1&growth_range=${this.value}`)
        .then(r => r.json())
        .then(data => {
            growthChart.data.labels = data.labels;
            growthChart.data.datasets[0].data = data.data;
            growthChart.update();
        });
});

// Activity Chart (Games Played & Lessons Taken)
const activityChartCtx = document.getElementById("activityChart").getContext("2d");
let activityChart = new Chart(activityChartCtx, {
    type: "line",
    data: {
        labels: <?= json_encode($initialActivity["labels"]) ?>,
        datasets: [
            {
                label: "Games Played",
                data: <?= json_encode($initialActivity["games"]) ?>,
                borderColor: "#f59e0b",
                backgroundColor: "rgba(245,158,11,0.15)",
                fill: true,
                tension: 0.3
            },
            {
                label: "Lessons Completed",
                data: <?= json_encode($initialActivity["lessons"]) ?>,
                borderColor: "#22c55e",
                backgroundColor: "rgba(34,197,94,0.15)",
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        plugins: { legend: { display: true, labels: { color: "#8b93ab" } } },
        scales: {
            x: { ticks: { color: "#8b93ab" }, grid: { color: "#23304f" } },
            y: { ticks: { color: "#8b93ab", precision: 0 }, grid: { color: "#23304f" }, beginAtZero: true }
        }
    }
});

document.getElementById("activityRangeSelect").addEventListener("change", function() {
    fetch(`dashboard.php?ajax_activity=1&activity_range=${this.value}`)
        .then(r => r.json())
        .then(data => {
            activityChart.data.labels = data.labels;
            activityChart.data.datasets[0].data = data.games;
            activityChart.data.datasets[1].data = data.lessons;
            activityChart.update();
        });
});

// Category Doughnut Chart
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