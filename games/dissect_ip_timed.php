<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/subnet_engine.php";
require "../includes/xp_engine.php";
require "../includes/badge_engine.php";
require "../includes/easy_instructions.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$userId   = $_SESSION["user_id"];
$username = $_SESSION["username"];

$TOTAL_QUESTIONS = 10;
$TIME_PER_QUESTION = 90;
$validDifficulties = ["easy", "medium", "hard"];
$difficulty = $_GET["difficulty"] ?? null;

$needsNewSession = !isset($_SESSION["dissect_timed"]) || isset($_GET["restart"]);
$showDifficultyScreen = false;

if ($needsNewSession) {
    if (!in_array($difficulty, $validDifficulties)) {
        $showDifficultyScreen = true;
    } else {
        $_SESSION["dissect_timed"] = [
            "q_index" => 1,
            "correct" => 0,
            "wrong"   => 0,
            "score"   => 0,
            "difficulty" => $difficulty,
            "question" => generate_subnet_question($difficulty),
            "answered" => false,
            "submitted" => [],
            "feedback" => null,
        ];
    }
}

// Builds the attributes for one answer box: blank + required while answering,
// then locked, pre-filled with what was typed, and colored green/red after submitting.
function fld($name, $feedback, $submitted) {
    if ($feedback) {
        $val = htmlspecialchars((string) ($submitted[$name] ?? ""));
        $ok = $feedback["details"][$name] ?? false;
        $style = $ok
            ? "border-color:#22c55e; background:rgba(34,197,94,0.12);"
            : "border-color:#ef4444; background:rgba(239,68,68,0.12);";
        return 'name="' . $name . '" value="' . $val . '" readonly style="' . $style . '"';
    }
    return 'name="' . $name . '" required';
}

if (!$showDifficultyScreen) {
    $state = &$_SESSION["dissect_timed"];
    $state["answered"] ??= false;
    $state["submitted"] ??= [];
    $state["feedback"] ??= null;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["next"]) && $state["answered"]) {
            if ($state["q_index"] >= $TOTAL_QUESTIONS) {
                $gameType = "subnet_dissect_timed";
                $stmt = mysqli_prepare($conn, "INSERT INTO scores (user_id, match_id, game_type, points, played_at) VALUES (?, NULL, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmt, "isi", $userId, $gameType, $state["score"]);
                mysqli_stmt_execute($stmt);

                $xpResult = award_xp($conn, $userId, $state["score"]);
                $updateQuiz = mysqli_prepare($conn, "UPDATE user_progress SET quizzes_completed = quizzes_completed + 1, total_correct = total_correct + ?, total_wrong = total_wrong + ? WHERE user_id = ?");
                mysqli_stmt_bind_param($updateQuiz, "iii", $state["correct"], $state["wrong"], $userId);
                mysqli_stmt_execute($updateQuiz);

                $newBadges = check_and_award_badges($conn, $userId);

                $finalScore = $state["score"];
                $finalCorrect = $state["correct"];
                $finalWrong = $state["wrong"];
                $sessionDifficulty = $state["difficulty"];
                unset($_SESSION["dissect_timed"]);
                $sessionDone = true;
            } else {
                $state["q_index"]++;
                $state["question"] = generate_subnet_question($state["difficulty"]);
                $state["answered"] = false;
                $state["submitted"] = [];
                $state["feedback"] = null;
            }
        } elseif (isset($_POST["submit_answer"]) && !$state["answered"]) {
            $result = check_subnet_answer($state["question"], $_POST);
            $state["answered"] = true;
            $state["feedback"] = $result;
            $state["submitted"] = $_POST;

            if ($result["correct"]) {
                $state["correct"]++;
                $state["score"] += (int) round(30 * difficulty_multiplier($state["difficulty"]));
            } else {
                $state["wrong"]++;
            }
        }
    }

    $feedback = $state["feedback"];
    $submitted = $state["submitted"];
    $q = $state["question"] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dissect an IP Address (Timed) - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
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
        <a href="../dashboard.php" class="nav-link">Dashboard</a>
        <a href="../learning_modules.php" class="nav-link">Learning Modules</a>
        <a href="../practice.php" class="nav-link">Practice Mode</a>
        <a href="../games.php" class="nav-link active">Games</a>
        <a href="../lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="../leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="../achievements.php" class="nav-link">Achievements</a>
        <a href="../profile.php" class="nav-link">Profile</a>
        <a href="../settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="game-main">
        <div class="breadcrumb"><a href="../games.php" class="showdown-back">← Exit</a> &nbsp; Games &gt; Dissect an IP Address <?= !$showDifficultyScreen ? "&gt; <b>Question " . ($state["q_index"] ?? 1) . "</b>" : "" ?></div>

        <?php if ($showDifficultyScreen): ?>
            <div class="game-card">
                <div class="difficulty-select">
                    <h2>Choose a Difficulty</h2>
                    <p class="sub">This controls the subnet size (prefix length) and how much XP you earn per correct answer.</p>
                    <div class="difficulty-grid">
                        <a href="?difficulty=easy" class="difficulty-card easy">
                            <span class="icon">🟢</span><h4>Easy</h4><p>/24 - /25 networks<br>XP ×1</p>
                        </a>
                        <a href="?difficulty=medium" class="difficulty-card medium">
                            <span class="icon">🟡</span><h4>Medium</h4><p>/26 - /28 networks<br>XP ×1.5</p>
                        </a>
                        <a href="?difficulty=hard" class="difficulty-card hard">
                            <span class="icon">🔴</span><h4>Difficult</h4><p>/29 - /30 networks<br>XP ×2</p>
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif (isset($sessionDone)): ?>
            <div class="game-card">
                <h2>Session Complete!</h2>
                <p class="sub">Here's how you did:</p>
                <div class="stat-line"><span>Correct Answers</span><span class="good"><?= $finalCorrect ?></span></div>
                <div class="stat-line"><span>Wrong Answers</span><span class="bad"><?= $finalWrong ?></span></div>
                <div class="stat-line"><span>Total Score</span><span class="xp">+<?= $finalScore ?> XP</span></div>
                <?php if ($xpResult["leveled_up"]): ?>
                    <div class="feedback-banner feedback-correct" style="margin-top:0.8rem;">
                        🎉 Level Up! You're now Level <?= $xpResult["new_level"] ?>!
                    </div>
                <?php endif; ?>
                <?php if (!empty($newBadges)): ?>
                    <?php foreach ($newBadges as $b): ?>
                        <div class="feedback-banner feedback-correct" style="margin-top:0.8rem;">
                            <?= $b["icon"] ?> Badge Unlocked: <b><?= htmlspecialchars($b["name"]) ?></b>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="game-actions">
                    <a href="dissect_ip_timed.php?restart=1&difficulty=<?= $sessionDifficulty ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Play Again</a>
                    <a href="../games.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">Back to Games</a>
                </div>
            </div>
        <?php else: ?>
            <div class="game-card">
                <span class="difficulty-tag">Difficulty: <?= ucfirst($state["difficulty"]) ?></span>
                <div class="mode-tag">TIMED CHALLENGE</div>
                <h2>Question <?= $state["q_index"] ?> of <?= $TOTAL_QUESTIONS ?></h2>
                <h2>Given the IP address <span class="ip"><?= $q["given_ip"] ?>/<?= $q["prefix"] ?></span>, fill in the blanks.</h2>
                <p class="sub">Provide the missing information for the subnet before time runs out.</p>

                <form method="POST" id="answerForm">
                    <div class="q-row">
                        <div class="label">🔷 Network Address</div>
                        <div class="octet-group">
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" <?= fld("network_oct4", $feedback, $submitted) ?>>
                        </div>
                    </div>

                    <div class="q-row">
                        <div class="label">📡 Broadcast Address</div>
                        <div class="octet-group">
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" <?= fld("broadcast_oct4", $feedback, $submitted) ?>>
                        </div>
                    </div>

                    <div class="q-row">
                        <div class="label">👥 Usable Host Range</div>
                        <div class="octet-group">
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" <?= fld("host_start_oct4", $feedback, $submitted) ?>>
                            <span class="range-to">to</span>
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" <?= fld("host_end_oct4", $feedback, $submitted) ?>>
                        </div>
                    </div>

                    <div class="q-row">
                        <div class="label">➕ Subnet Mask</div>
                        <div class="octet-group">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <input class="octet-box" type="number" min="0" max="255" <?= fld("mask_$i", $feedback, $submitted) ?>>
                                <?php if ($i < 3) echo '<span class="octet-dot">.</span>'; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="q-row">
                        <div class="label"># Wildcard Mask</div>
                        <div class="octet-group">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <input class="octet-box" type="number" min="0" max="255" <?= fld("wildcard_$i", $feedback, $submitted) ?>>
                                <?php if ($i < 3) echo '<span class="octet-dot">.</span>'; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="q-row">
                        <div class="label">🔢 Number of Usable Hosts</div>
                        <input class="hosts-input" type="number" min="0" <?= fld("usable_hosts", $feedback, $submitted) ?> style="max-width:200px;">
                    </div>

                    <?php if ($feedback): ?>
                        <div class="feedback-banner <?= $feedback['correct'] ? 'feedback-correct' : 'feedback-wrong' ?>">
                            <?php if ($feedback['correct']): ?>
                                ✅ Correct! Great job.
                            <?php else: ?>
                                ❌ Not quite. Correct answers — Network: .<?= $q['network_oct4'] ?>, Broadcast: .<?= $q['broadcast_oct4'] ?>,
                                Range: .<?= $q['host_start_oct4'] ?> to .<?= $q['host_end_oct4'] ?>,
                                Mask: <?= implode('.', $q['mask_parts']) ?>, Wildcard: <?= implode('.', $q['wildcard_parts']) ?>,
                                Usable hosts: <?= $q['usable_hosts'] ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="game-actions">
                        <?php if (!$feedback): ?>
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('answerForm').reset()">Reset</button>
                            <button type="submit" name="submit_answer" value="1" class="btn btn-primary">Submit Answer</button>
                        <?php else: ?>
                            <button type="submit" name="next" value="1" class="btn btn-primary">
                                <?= $state["q_index"] >= $TOTAL_QUESTIONS ? "Finish Session" : "Next Question" ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($state["difficulty"] === "easy" && $q): ?>
                <?= render_dissect_instructions($q) ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <aside class="right-col">
        <?php if (!$showDifficultyScreen && !isset($sessionDone)): ?>
        <div class="panel-box">
            <h4>⏱ Time Left</h4>
            <div class="timer-value" id="timerDisplay"><?= $feedback ? "Answered" : gmdate("i:s", $TIME_PER_QUESTION) ?></div>
        </div>

        <div class="panel-box">
            <h4>Question Progress</h4>
            <div class="progress-bar"><div style="width:<?= ($state['q_index'] / $TOTAL_QUESTIONS) * 100 ?>%"></div></div>
            <div style="font-size:0.78rem; color:var(--text-dim);"><?= $state['q_index'] ?> / <?= $TOTAL_QUESTIONS ?></div>
        </div>

        <div class="panel-box">
            <h4>💡 Hints</h4>
            <div class="hint-item">/<?= $q['prefix'] ?> means <?= $q['prefix'] ?> bits are used for the network portion.</div>
            <div class="hint-item">The subnet mask for /<?= $q['prefix'] ?> is <?= implode('.', $q['mask_parts']) ?>.</div>
        </div>
        <?php endif; ?>

        <?php if (!$showDifficultyScreen): ?>
        <div class="panel-box">
            <h4>🏆 Your Progress</h4>
            <div class="stat-line"><span>Correct Answers</span><span class="good"><?= $state['correct'] ?? $finalCorrect ?? 0 ?></span></div>
            <div class="stat-line"><span>Wrong Answers</span><span class="bad"><?= $state['wrong'] ?? $finalWrong ?? 0 ?></span></div>
            <div class="stat-line"><span>Score</span><span class="xp"><?= $state['score'] ?? $finalScore ?? 0 ?> XP</span></div>
        </div>
        <?php endif; ?>
    </aside>
</div>

<?php if (!$showDifficultyScreen && !isset($sessionDone) && !$feedback): ?>
<script>
let timeLeft = <?= $TIME_PER_QUESTION ?>;
const display = document.getElementById("timerDisplay");
const form = document.getElementById("answerForm");

const timer = setInterval(() => {
    timeLeft--;
    const m = String(Math.floor(timeLeft / 60)).padStart(2, "0");
    const s = String(timeLeft % 60).padStart(2, "0");
    display.textContent = `${m}:${s}`;

    if (timeLeft <= 0) {
        clearInterval(timer);
        // form.submit() skips the "required" check, so blanks count as a wrong answer
        const btn = document.createElement("input");
        btn.type = "hidden";
        btn.name = "submit_answer";
        btn.value = "1";
        form.appendChild(btn);
        form.submit();
    }
}, 1000);
</script>
<?php endif; ?>
</body>
</html>