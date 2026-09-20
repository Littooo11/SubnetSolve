<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";
require "includes/avatars.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];
$username = $_SESSION["username"];

$code = strtoupper(trim($_GET["code"] ?? ""));
if ($code === "") { header("Location: lobby.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM matches WHERE room_code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$match = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$match) { header("Location: lobby.php?error=" . urlencode("That lobby doesn't exist.")); exit(); }

// Make sure I'm actually a participant - if not, route through join.php (handles password checks etc.)
$meCheck = mysqli_prepare($conn, "SELECT * FROM match_players WHERE match_id = ? AND user_id = ?");
mysqli_stmt_bind_param($meCheck, "ii", $match["id"], $userId);
mysqli_stmt_execute($meCheck);
$me = mysqli_stmt_get_result($meCheck)->fetch_assoc();

if (!$me) {
    header("Location: join.php?code=" . urlencode($code));
    exit();
}

$TIME_PER_QUESTION = 45;

// All players in this match (for opponent display + live leaderboard)
$playersResult = mysqli_query($conn, "SELECT mp.*, u.username, u.avatar FROM match_players mp
    JOIN users u ON u.id = mp.user_id WHERE mp.match_id = " . (int) $match["id"] . " ORDER BY mp.score DESC");
$players = mysqli_fetch_all($playersResult, MYSQLI_ASSOC);

$opponent = null;
foreach ($players as $p) {
    if ((int) $p["user_id"] !== (int) $userId) { $opponent = $p; break; }
}

$currentQuestion = null;
if ($match["status"] === "in_progress") {
    $qStmt = mysqli_prepare($conn, "SELECT * FROM match_questions WHERE match_id = ? AND question_index = ?");
    mysqli_stmt_bind_param($qStmt, "ii", $match["id"], $match["current_question_index"]);
    mysqli_stmt_execute($qStmt);
    $currentQuestion = mysqli_stmt_get_result($qStmt)->fetch_assoc();
}

$myAnswered = $me["current_answer_index"] !== null;
$letters = ["A", "B", "C", "D"];

$elapsed = $match["current_question_started_at"] ? time() - strtotime($match["current_question_started_at"]) : 0;
$timeRemaining = max(0, $TIME_PER_QUESTION - $elapsed);

function mode_label($m) {
    return ["subnetting" => "Subnetting", "binary" => "Binary", "both" => "Both"][$m] ?? $m;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($match["room_name"] ?: "Match #" . $code) ?> - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="game-layout">
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
        <a href="lobby.php" class="nav-link active">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="game-main">
        <div class="match-header">
            <div>
                <a href="leave_match.php?code=<?= urlencode($code) ?>" class="showdown-back" onclick="return confirm('Leave this match?')">← Leave Match</a>
                <div style="font-size:0.9rem; margin-top:0.2rem;">
                    <?= $match["visibility"] === "private" ? "Private" : "Public" ?> Lobby #<?= htmlspecialchars($code) ?>
                    · 1v1 <?= mode_label($match["game_mode"]) ?>
                </div>
            </div>

            <?php if ($opponent && (int) $match["max_players"] === 2): ?>
            <div class="vs-block">
                <div class="vs-player"><?= render_avatar($me["avatar"], 32) ?> <div><?= htmlspecialchars($username) ?><span class="pscore">Score: <span data-score-for="<?= $userId ?>"><?= $me["score"] ?></span></span></div></div>
                <div class="vs-text">VS</div>
                <div class="vs-player"><?= render_avatar($opponent["avatar"], 32) ?> <div><?= htmlspecialchars($opponent["username"]) ?><span class="pscore">Score: <span data-score-for="<?= $opponent['user_id'] ?>"><?= $opponent["score"] ?></span></span></div></div>
            </div>
            <?php endif; ?>

            <?php if ($match["status"] === "in_progress"): ?>
            <div class="match-timer-box">
                <div class="lbl">Time Left</div>
                <div class="val" id="timerVal"><?= sprintf("%02d:%02d", intdiv($timeRemaining, 60), $timeRemaining % 60) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($match["status"] === "waiting"): ?>
            <div class="showdown-card">
                <div class="waiting-room">
                    <div class="spinner"></div>
                    <h3>Waiting for <?= $match["max_players"] > 2 ? "players" : "opponent" ?> to join...</h3>
                    <p style="color:var(--text-dim);">Share this room code:</p>
                    <div class="room-code-badge" style="font-size:1.3rem; padding:0.5rem 1.2rem;"><?= htmlspecialchars($code) ?></div>
                    <p style="color:var(--text-dim); font-size:0.85rem; margin-top:1.5rem;"><?= count($players) ?> / <?= $match["max_players"] ?> players joined</p>

                    <div style="display:flex; justify-content:center; gap:0.8rem; margin:1.2rem 0; flex-wrap:wrap;">
                        <?php foreach ($players as $p): ?>
                            <div style="text-align:center;">
                                <?= render_avatar($p["avatar"], 44) ?>
                                <div style="font-size:0.75rem; margin-top:0.3rem;"><?= htmlspecialchars($p["username"]) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ((int) $match["host_user_id"] === (int) $userId && count($players) >= 2): ?>
                        <a href="start_now.php?code=<?= urlencode($code) ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Start Match Now</a>
                        <p style="color:var(--text-dim); font-size:0.75rem; margin-top:0.5rem;">Or wait for more players to join first.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($match["status"] === "in_progress" && $currentQuestion): ?>
            <div class="showdown-card">
                <div class="showdown-top-row">
                    <div style="font-weight:bold;">Question <?= $match["current_question_index"] + 1 ?> / <?= $match["total_questions"] ?></div>
                    <div class="progress-dots">
                        <?php for ($i = 0; $i < $match["total_questions"]; $i++): ?>
                            <div class="progress-dot <?= $i < $match['current_question_index'] ? 'done' : ($i == $match['current_question_index'] ? 'current' : '') ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="showdown-meta-row">
                    <div class="category-pill">🌐 Subnet Showdown</div>
                </div>

                <div class="showdown-question"><?= htmlspecialchars($currentQuestion["prompt"]) ?></div>

                <?php if ($myAnswered): ?>
                    <div class="feedback-banner feedback-correct" style="margin-bottom:1rem;">✅ Answer submitted — waiting for your opponent...</div>
                    <?php foreach (json_decode($currentQuestion["options_json"], true) as $i => $opt): ?>
                        <div class="mcq-option <?= $i === (int) $me['current_answer_index'] ? 'selected' : '' ?>" style="pointer-events:none;">
                            <div class="mcq-radio"></div><span class="mcq-letter"><?= $letters[$i] ?>.</span><span><?= htmlspecialchars($opt) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <form method="POST" action="match_answer.php" id="answerForm">
                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                        <?php foreach (json_decode($currentQuestion["options_json"], true) as $i => $opt): ?>
                            <div class="mcq-option" data-index="<?= $i ?>" onclick="selectOption(<?= $i ?>)">
                                <div class="mcq-radio"></div><span class="mcq-letter"><?= $letters[$i] ?>.</span><span><?= htmlspecialchars($opt) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <input type="hidden" name="selected" id="selectedInput" value="">
                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;" id="submitBtn" disabled>Submit Answer</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="system-msg">💬 System: Match started! Good luck!</div>

        <?php elseif ($match["status"] === "finished"): ?>
            <div class="showdown-card">
                <h2><?php
                    if ($match["winner_id"] === null) echo "It's a Tie!";
                    elseif ((int) $match["winner_id"] === (int) $userId) echo "🏆 You Won!";
                    else echo "Match Over";
                ?></h2>
                <?php foreach ($players as $p): ?>
                    <div class="stat-line"><span><?= htmlspecialchars($p["username"]) ?> <?= (int) $p["user_id"] === (int) $userId ? "(You)" : "" ?></span><span class="xp"><?= $p["score"] ?> pts</span></div>
                <?php endforeach; ?>
                <div class="game-actions">
                    <a href="lobby.php" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Back to Lobby</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <aside class="right-col">
        <div class="panel-box">
            <h4><span class="live-dot"></span>Live Leaderboard</h4>
            <?php foreach ($players as $i => $p): ?>
                <div class="live-lb-row" data-row-for="<?= $p['user_id'] ?>">
                    <span class="rank"><?= $i + 1 ?></span>
                    <?= render_avatar($p["avatar"], 28) ?>
                    <span class="plname"><?= htmlspecialchars($p["username"]) ?></span>
                    <span class="plscore" data-score-for="<?= $p['user_id'] ?>"><?= $p["score"] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<script>
function selectOption(i) {
    document.querySelectorAll(".mcq-option").forEach(el => el.classList.remove("selected"));
    document.querySelector(`.mcq-option[data-index="${i}"]`).classList.add("selected");
    document.getElementById("selectedInput").value = i;
    document.getElementById("submitBtn").disabled = false;
}

<?php if ($match["status"] !== "finished"): ?>
// Poll the server; reload the page whenever something relevant changes
// (opponent joined, opponent answered, question advanced, match finished).
let knownQuestionIndex = <?= (int) $match["current_question_index"] ?>;
let knownStatus = "<?= $match["status"] ?>";
let knownPlayerCount = <?= count($players) ?>;

setInterval(() => {
    fetch("match_state.php?code=<?= urlencode($code) ?>")
        .then(r => r.json())
        .then(data => {
            // Live score updates - happens every poll, no reload needed
            if (data.players) {
                data.players.forEach(p => {
                    const el = document.querySelector(`[data-score-for="${p.user_id}"]`);
                    if (el) el.textContent = p.score;
                });
            }

            // Reload only when something needs a fresh render (new question, match ended, someone joined)
            if (data.status !== knownStatus || data.current_question_index !== knownQuestionIndex || data.player_count !== knownPlayerCount) {
                window.location.reload();
            }
        })
        .catch(() => {});
}, 2000);

<?php if ($match["status"] === "in_progress" && !$myAnswered): ?>
let timeLeft = <?= $timeRemaining ?>;
const timerEl = document.getElementById("timerVal");
setInterval(() => {
    timeLeft--;
    if (timeLeft < 0) timeLeft = 0;
    const m = String(Math.floor(timeLeft / 60)).padStart(2, "0");
    const s = String(timeLeft % 60).padStart(2, "0");
    if (timerEl) timerEl.textContent = `${m}:${s}`;
    if (timeLeft <= 10 && timerEl) timerEl.classList.add("low");
}, 1000);
<?php endif; ?>
<?php endif; ?>
</script>
</body>
</html>