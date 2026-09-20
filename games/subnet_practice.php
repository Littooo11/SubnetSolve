<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/subnet_engine.php";
require "../includes/xp_engine.php";
require "../includes/badge_engine.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$userId   = $_SESSION["user_id"];
$username = $_SESSION["username"];

$TOTAL_QUESTIONS = 10;

// Start a fresh practice session
if (!isset($_SESSION["practice"]) || isset($_GET["restart"])) {
    $_SESSION["practice"] = [
        "q_index" => 1,
        "correct" => 0,
        "wrong"   => 0,
        "score"   => 0,
        "question" => generate_subnet_question(),
    ];
}

$state = &$_SESSION["practice"];
$feedback = null;

// Handle answer submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    $result = check_subnet_answer($state["question"], $_POST);
    $feedback = $result;

    if ($result["correct"]) {
        $state["correct"]++;
        $state["score"] += 30;
    } else {
        $state["wrong"]++;
    }

    if (isset($_POST["next"])) {
        if ($state["q_index"] >= $TOTAL_QUESTIONS) {
            // session finished - save to database, then reset
            $gameType = "subnet_dissect_practice";
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
            unset($_SESSION["practice"]);
            $sessionDone = true;
        } else {
            $state["q_index"]++;
            $state["question"] = generate_subnet_question();
            $feedback = null; // clear feedback for the new question
        }
    }
}

$q = $state["question"] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dissect an IP Address - SubNetSolve</title>
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
        <a href="#" class="nav-link">Learning Modules</a>
        <a href="../practice.php" class="nav-link active">Practice Mode</a>
        <a href="../games.php" class="nav-link">Games</a>
        <a href="../lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="../leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="../achievements.php" class="nav-link">Achievements</a>
        <a href="../profile.php" class="nav-link">Profile</a>
        <a href="../settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="game-main">
        <div class="breadcrumb"><a href="../practice.php" class="showdown-back">← Exit</a> &nbsp; Games &gt; Dissect an IP Address &gt; <b>Question <?= $state["q_index"] ?? 1 ?></b></div>

        <?php if (isset($sessionDone)): ?>
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
                    <a href="subnet_practice.php?restart=1" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Play Again</a>
                    <a href="../dashboard.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <div class="game-card">
                <span class="difficulty-tag">Difficulty: <?= $q["prefix"] >= 28 ? "Medium" : "Hard" ?></span>
                <div class="mode-tag">PRACTICE MODE</div>
                <h2>Question <?= $state["q_index"] ?> of <?= $TOTAL_QUESTIONS ?></h2>
                <h2>Given the IP address <span class="ip"><?= $q["given_ip"] ?>/<?= $q["prefix"] ?></span>, fill in the blanks.</h2>
                <p class="sub">Provide the missing information for the subnet.</p>

                <form method="POST" id="answerForm">
                    <!-- Network Address -->
                    <div class="q-row">
                        <div class="label">🔷 Network Address</div>
                        <div class="octet-group">
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" name="network_oct4" required>
                        </div>
                    </div>

                    <!-- Broadcast Address -->
                    <div class="q-row">
                        <div class="label">📡 Broadcast Address</div>
                        <div class="octet-group">
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" name="broadcast_oct4" required>
                        </div>
                    </div>

                    <!-- Usable Host Range -->
                    <div class="q-row">
                        <div class="label">👥 Usable Host Range</div>
                        <div class="octet-group">
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" name="host_start_oct4" required>
                            <span class="range-to">to</span>
                            <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                            <input class="octet-box" type="number" min="0" max="255" name="host_end_oct4" required>
                        </div>
                    </div>

                    <!-- Subnet Mask -->
                    <div class="q-row">
                        <div class="label">➕ Subnet Mask</div>
                        <div class="octet-group">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <input class="octet-box" type="number" min="0" max="255" name="mask_<?= $i ?>" required>
                                <?php if ($i < 3) echo '<span class="octet-dot">.</span>'; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Wildcard Mask -->
                    <div class="q-row">
                        <div class="label"># Wildcard Mask</div>
                        <div class="octet-group">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <input class="octet-box" type="number" min="0" max="255" name="wildcard_<?= $i ?>" required>
                                <?php if ($i < 3) echo '<span class="octet-dot">.</span>'; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Number of Usable Hosts -->
                    <div class="q-row">
                        <div class="label">🔢 Number of Usable Hosts</div>
                        <input class="hosts-input" type="number" min="0" name="usable_hosts" required style="max-width:200px;">
                    </div>

                    <?php if ($feedback): ?>
                        <div class="feedback-banner <?= $feedback['correct'] ? 'feedback-correct' : 'feedback-wrong' ?>">
                            <?= $feedback['correct'] ? '✅ Correct! Great job.' : '❌ Not quite — check the highlighted logic and try the next one.' ?>
                        </div>
                    <?php endif; ?>

                    <div class="game-actions">
                        <?php if (!$feedback): ?>
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('answerForm').reset()">Reset</button>
                            <button type="submit" name="submit_answer" value="1" class="btn btn-primary">Submit Answer</button>
                        <?php else: ?>
                            <button type="submit" name="submit_answer" value="1" class="btn btn-primary" onclick="document.getElementById('nextField').value=1">
                                <?= $state["q_index"] >= $TOTAL_QUESTIONS ? "Finish Session" : "Next Question" ?>
                            </button>
                            <input type="hidden" name="next" id="nextField" value="1">
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <aside class="right-col">
        <?php if (!isset($sessionDone)): ?>
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

        <div class="panel-box">
            <h4>🏆 Your Progress</h4>
            <div class="stat-line"><span>Correct Answers</span><span class="good"><?= $state['correct'] ?? $finalCorrect ?? 0 ?></span></div>
            <div class="stat-line"><span>Wrong Answers</span><span class="bad"><?= $state['wrong'] ?? $finalWrong ?? 0 ?></span></div>
            <div class="stat-line"><span>Score</span><span class="xp"><?= $state['score'] ?? $finalScore ?? 0 ?> XP</span></div>
        </div>
    </aside>
</div>

</body>
</html>