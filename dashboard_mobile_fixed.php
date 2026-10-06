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

    $stmt = mysqli_prepare($conn, "SELECT u.id, u.username, u.is_admin FROM remember_tokens rt
                                    JOIN users u ON u.id = rt.user_id
                                    WHERE rt.token = ? AND rt.expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["is_admin"] = (bool) $user["is_admin"];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="game.css">

    <style>
        /* ===== Responsive/mobile dashboard fixes ===== */

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        img, svg, video {
            max-width: 100%;
            height: auto;
        }

        @media (max-width: 900px) {
            .layout {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                min-height: 100vh;
            }

            /* Turn the desktop sidebar into a compact horizontal mobile nav */
            .sidebar {
                position: static !important;
                width: 100% !important;
                min-width: 0 !important;
                height: auto !important;
                max-height: none !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: .35rem !important;
                padding: .75rem !important;
                white-space: nowrap !important;
                border-right: 0 !important;
                border-bottom: 1px solid var(--border) !important;
                -webkit-overflow-scrolling: touch;
            }

            .sidebar .brand {
                flex: 0 0 auto;
                margin: 0 .5rem 0 0 !important;
            }

            .sidebar .brand p {
                display: none;
            }

            .sidebar .brand h1 {
                font-size: 1rem;
            }

            .sidebar .logo {
                width: 34px;
                height: 34px;
                min-width: 34px;
            }

            .sidebar .nav-link {
                flex: 0 0 auto;
                margin: 0 !important;
                padding: .55rem .7rem !important;
                font-size: .78rem !important;
                border-radius: 8px;
            }

            .sidebar .daily-challenge {
                display: none !important;
            }

            .main,
            .right-col {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
            }

            .main {
                padding: 1rem !important;
            }

            .right-col {
                padding: 0 1rem 1rem !important;
            }

            .topbar {
                width: 100%;
                gap: .75rem;
                flex-wrap: wrap;
            }

            .search-wrapper {
                flex: 1 1 100% !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            .search {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            .topbar-right {
                width: 100%;
                justify-content: flex-end;
            }

            .welcome {
                margin-top: 1rem;
            }

            .welcome h2 {
                font-size: 1.45rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }

            .welcome p {
                line-height: 1.5;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: .7rem !important;
            }

            .stat-card {
                min-width: 0 !important;
                padding: .8rem !important;
            }

            .stat-card .value {
                font-size: 1.25rem;
            }

            .stat-card .label {
                font-size: .72rem;
                line-height: 1.25;
            }

            .explore-grid {
                grid-template-columns: 1fr !important;
                gap: .75rem !important;
            }

            .explore-card {
                min-width: 0 !important;
            }

            .explore-card p,
            .pro-tip p,
            .panel-box p {
                overflow-wrap: anywhere;
                line-height: 1.5;
            }

            .pro-tip {
                display: flex;
                align-items: flex-start;
                gap: .7rem;
            }

            .player-row {
                min-width: 0 !important;
                gap: .45rem !important;
            }

            .player-row > span:not(.rank):not(.xp) {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .player-row .xp {
                margin-left: auto;
                white-space: nowrap;
                font-size: .75rem;
            }

            .activity-row {
                min-width: 0;
            }

            .activity-row > div:last-child {
                min-width: 0;
                overflow-wrap: anywhere;
            }

            .search-results {
                width: 100% !important;
                max-width: calc(100vw - 2rem);
            }

            .search-result-row {
                min-width: 0;
            }

            .search-result-text {
                min-width: 0;
            }

            .search-result-text .name,
            .search-result-text .desc {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }

        @media (max-width: 520px) {
            .sidebar {
                padding: .6rem !important;
            }

            .sidebar .brand {
                margin-right: .25rem !important;
            }

            .sidebar .brand > div:last-child {
                display: none;
            }

            .sidebar .nav-link {
                font-size: .72rem !important;
                padding: .5rem .6rem !important;
            }

            .main {
                padding: .8rem !important;
            }

            .right-col {
                padding: 0 .8rem .8rem !important;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr !important;
            }

            .stat-card {
                flex-direction: column;
                align-items: flex-start;
                gap: .45rem;
            }

            .topbar-right {
                justify-content: flex-start;
            }

            .section-title {
                font-size: 1rem;
            }

            .panel-box {
                padding: .85rem !important;
            }

            .player-row {
                font-size: .78rem;
            }
        }

        @media (max-width: 360px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
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
        <a href="learning_modules.php" class="nav-link">Learning Modules</a>
        <a href="practice.php" class="nav-link">Practice Mode</a>
        <a href="games.php" class="nav-link">Games</a>
        <a href="lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
        <a href="about.php" class="nav-link">About Us</a>
        <?php if (!empty($_SESSION["is_admin"])): ?>
            <a href="admin/dashboard.php" class="nav-link" style="color:var(--orange);">🛠 Admin Panel</a>
        <?php endif; ?>

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

        <?php if (!empty($_GET["error"])): ?>
            <div class="feedback-banner feedback-wrong" style="margin-bottom:1rem;"><?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

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
                    <a href="learning_modules.php" style="background:var(--blue);">Start Learning</a>
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