<?php
session_start();

// Prevent browser from caching this page —
// stops "back button" from showing it after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require "config.php";
require "includes/avatars.php";

// If no active session, try to restore login from the remember-me cookie
if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_token"])) {
    $token = $_COOKIE["remember_token"];

    $stmt = mysqli_prepare($conn, "SELECT u.id, u.username FROM remember_tokens rt
                                    JOIN users u ON u.id = rt.user_id
                                    WHERE rt.token = ? AND rt.expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["username"] = $user["username"];
    }
}

// kick out anyone who isn't logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION["username"];
$userId   = $_SESSION["user_id"];

// Safety check: if the database was reset/reimported, old session IDs
// may no longer point to a real user. Catch that here instead of crashing.
$userCheck = mysqli_prepare($conn, "SELECT id, avatar FROM users WHERE id = ?");
mysqli_stmt_bind_param($userCheck, "i", $userId);
mysqli_stmt_execute($userCheck);
$userRow = mysqli_stmt_get_result($userCheck)->fetch_assoc();
if (!$userRow) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$myAvatar = $userRow["avatar"];

// Pull this user's real progress. If for some reason there's no row yet
// (e.g. an account created before this table existed), default to zeros.
$stmt = mysqli_prepare($conn, "SELECT * FROM user_progress WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$progress = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$progress) {
    // safety net: create the row now if it's missing
    $insertProgress = mysqli_prepare($conn, "INSERT INTO user_progress (user_id) VALUES (?)");
    mysqli_stmt_bind_param($insertProgress, "i", $userId);
    mysqli_stmt_execute($insertProgress);

    $progress = [
        "level" => 1, "career_xp" => 0, "total_xp" => 0, "xp_to_next_level" => 500,
        "lessons_completed" => 0, "quizzes_completed" => 0, "current_streak" => 0
    ];
}

$level        = $progress["level"];
$careerXP     = $progress["career_xp"];
$totalXP      = $progress["total_xp"];
$xpToNextLvl  = $progress["xp_to_next_level"];
$lessonsDone  = $progress["lessons_completed"];
$quizzesDone  = $progress["quizzes_completed"];
$streak       = $progress["current_streak"];

// Real leaderboard preview: top 4 by career XP
$topPlayers = [];
$topResult = mysqli_query($conn, "SELECT u.username, u.avatar, up.career_xp
    FROM user_progress up JOIN users u ON u.id = up.user_id
    ORDER BY up.career_xp DESC LIMIT 4");
while ($row = mysqli_fetch_assoc($topResult)) {
    $topPlayers[] = ["name" => $row["username"], "avatar" => $row["avatar"], "xp" => $row["career_xp"]];
}

// TODO: pull from a real activity log once games/quizzes exist
$gameLabels = [
    "subnet_dissect_practice" => "Dissect an IP Address (Practice)",
    "subnet_dissect_timed"    => "Dissect an IP Address (Timed)",
    "binary_practice"         => "Binary Game (Practice)",
    "binary_game"             => "Binary Game (Timed)",
    "showdown_practice"       => "Subnet Showdown (Practice)",
    "showdown_timed"          => "Subnet Showdown",
    "multiplayer_1v1"         => "1v1 Multiplayer Match",
];
$recentActivity = [];
$activityResult = mysqli_query($conn, "SELECT game_type, points, played_at FROM scores WHERE user_id = $userId ORDER BY played_at DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($activityResult)) {
    $recentActivity[] = [
        "text" => $gameLabels[$row["game_type"]] ?? $row["game_type"],
        "xp"   => "+" . $row["points"] . " XP",
        "time" => date("M j, g:i A", strtotime($row["played_at"])),
    ];
}

$xpPercent = $xpToNextLvl > 0 ? round(($totalXP / $xpToNextLvl) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
</head>
<body>
<div class="layout">

    <!-- ===== Sidebar ===== -->
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Master Subnetting, Level Up!</p>
            </div>
        </div>

        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="#" class="nav-link">Learning Modules</a>
        <a href="practice.php" class="nav-link">Practice Mode</a>
        <a href="games.php" class="nav-link">Games</a>
        <a href="lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>

        <div class="daily-challenge">
            <h4>Daily Challenge</h4>
            <p>Complete 3 subnetting questions correctly</p>
            <div class="progress-bar"><div style="width:0%"></div></div>
            <div style="font-size:0.75rem; color:var(--text-dim);">0 / 3</div>
        </div>
    </aside>

    <!-- ===== Main content ===== -->
    <main class="main">
        <div class="topbar">
            <div class="search-wrapper">
                <input class="search" id="dashSearch" placeholder="Search lessons, topics, or challenges..." autocomplete="off">
                <div class="search-results" id="searchResults" style="display:none;"></div>
            </div>
            <div class="topbar-right">
                <?= render_avatar($myAvatar, 34) ?>
                <div>
                    <div style="font-weight:bold; font-size:0.9rem;"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size:0.75rem; color:var(--blue);">Level <?= $level ?></div>
                </div>
            </div>
        </div>

        <div class="welcome">
            <h2>Welcome back, <?= htmlspecialchars($username) ?>!</h2>
            <p>Master IPv4 subnetting through gaming and interactive learning.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.15); color:var(--blue);">L</div>
                <div>
                    <div class="value"><?= $lessonsDone ?></div>
                    <div class="label">Lessons Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(34,197,94,0.15); color:var(--green);">Q</div>
                <div>
                    <div class="value"><?= $quizzesDone ?></div>
                    <div class="label">Quizzes Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,0.15); color:var(--purple);">S</div>
                <div>
                    <div class="value"><?= $streak ?></div>
                    <div class="label">Current Streak</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(234,179,8,0.15); color:var(--gold);">XP</div>
                <div>
                    <div class="value"><?= number_format($careerXP) ?></div>
                    <div class="label">Total XP</div>
                </div>
            </div>
        </div>

        <div class="explore-panel">
            <h3 class="section-title">Explore &amp; Play</h3>
            <div class="explore-grid">
                <div class="explore-card">
                    <div style="color:var(--blue);">Learning Modules</div>
                    <p>Learn IPv4 addressing and subnetting step by step.</p>
                    <a href="#" style="background:var(--blue);">Start Learning</a>
                </div>
                <div class="explore-card">
                    <div style="color:var(--green);">Practice Mode</div>
                    <p>Solve subnetting problems and test your skills.</p>
                    <a href="practice.php" style="background:var(--green);">Practice Now</a>
                </div>
                <div class="explore-card">
                    <div style="color:var(--orange);">Games</div>
                    <p>Browse all game modes, including Binary Game and 1v1 matches.</p>
                    <a href="games.php" style="background:var(--orange);">View Games</a>
                </div>
                <div class="explore-card">
                    <div style="color:var(--purple);">Multiplayer Lobby</div>
                    <p>Challenge other players in real-time subnetting matches.</p>
                    <a href="lobby.php" style="background:var(--purple);">Join Lobby</a>
                </div>
                <div class="explore-card">
                    <div style="color:var(--gold);">Leaderboards</div>
                    <p>See your rank and compete with top players.</p>
                    <a href="leaderboards.php" style="background:var(--gold); color:#1a1a1a;">View Rankings</a>
                </div>
                <div class="explore-card">
                    <div style="color:var(--teal);">Achievements</div>
                    <p>Unlock badges and earn rewards as you progress.</p>
                    <a href="achievements.php" style="background:var(--teal);">View Badges</a>
                </div>
            </div>
        </div>

        <div class="pro-tip">
            <div style="font-size:1.5rem;">💡</div>
            <div>
                <h4>Pro Tip</h4>
                <p>Remember: in subnetting, practice makes perfect! Keep challenging yourself every day to become a subnetting expert.</p>
            </div>
        </div>
    </main>

    <!-- ===== Right column ===== -->
    <aside class="right-col">
        <div class="panel-box">
            <h4>Your Progress <span style="color:var(--blue);">Level <?= $level ?></span></h4>
            <div class="progress-bar"><div style="width:<?= $xpPercent ?>%; background:var(--blue);"></div></div>
            <div style="font-size:0.78rem; color:var(--text-dim);"><?= number_format($totalXP) ?> / <?= number_format($xpToNextLvl) ?> XP</div>
        </div>

        <div class="panel-box">
            <h4>Top Players <a href="#" style="font-size:0.75rem; color:var(--blue); text-decoration:none;">View All</a></h4>
            <?php if (empty($topPlayers)): ?>
                <p style="font-size:0.8rem; color:var(--text-dim); margin:0.4rem 0;">No leaderboard data yet — be the first to score!</p>
            <?php else: ?>
                <?php foreach ($topPlayers as $i => $p): ?>
                    <div class="player-row">
                        <span class="rank"><?= $i + 1 ?></span>
                        <?= render_avatar($p["avatar"], 26) ?>
                        <span><?= htmlspecialchars($p["name"]) ?></span>
                        <span class="xp"><?= number_format($p["xp"]) ?> XP</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="panel-box">
            <h4>Recent Activity</h4>
            <?php if (empty($recentActivity)): ?>
                <p style="font-size:0.8rem; color:var(--text-dim); margin:0.4rem 0;">Nothing yet — start a lesson or quiz to see activity here.</p>
            <?php else: ?>
                <?php foreach ($recentActivity as $a): ?>
                    <div class="activity-row">
                        <div class="dot" style="background:rgba(34,197,94,0.15); color:var(--green);">✓</div>
                        <div>
                            <div><?= htmlspecialchars($a["text"]) ?></div>
                            <div class="meta"><?= htmlspecialchars($a["xp"]) ?> · <?= htmlspecialchars($a["time"]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

</div>

<script>
const searchInput = document.getElementById("dashSearch");
const searchResults = document.getElementById("searchResults");
let searchTimeout;

const typeTagLabels = { game: "Game", practice: "Practice", lesson: "Lesson" };

searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const q = searchInput.value.trim();

    if (q === "") {
        searchResults.style.display = "none";
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch("search.php?q=" + encodeURIComponent(q))
            .then(r => r.json())
            .then(results => {
                if (results.length === 0) {
                    searchResults.innerHTML = '<div class="search-no-results">No matches yet — try a different word.</div>';
                } else {
                    searchResults.innerHTML = results.map(item => `
                        <a href="${item.url}" class="search-result-row">
                            <span class="search-result-icon">${item.icon}</span>
                            <span class="search-result-text">
                                <span class="name">${item.name}</span>
                                <span class="desc">${item.desc}</span>
                            </span>
                            <span class="search-result-tag">${typeTagLabels[item.type] || item.type}</span>
                        </a>
                    `).join("");
                }
                searchResults.style.display = "block";
            })
            .catch(() => { searchResults.style.display = "none"; });
    }, 200);
});

document.addEventListener("click", (e) => {
    if (!e.target.closest(".search-wrapper")) searchResults.style.display = "none";
});
</script>
</body>
</html>