<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/scenario_engine.php";
require "../includes/xp_engine.php";
require "../includes/badge_engine.php";
require "../includes/daily_challenge.php";
require "../includes/avatars.php";
require "../includes/easy_instructions.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$userId = $_SESSION["user_id"];
$username = $_SESSION["username"];

$avStmt = mysqli_prepare($conn, "SELECT avatar FROM users WHERE id = ?");
mysqli_stmt_bind_param($avStmt, "i", $userId);
mysqli_stmt_execute($avStmt);
$myAvatar = mysqli_stmt_get_result($avStmt)->fetch_assoc()["avatar"] ?? "fox";

$TOTAL_QUESTIONS = 10;
$validDifficulties = ["easy", "medium", "hard"];
$difficulty = $_GET["difficulty"] ?? null;

$needsNewSession = !isset($_SESSION["scenario_practice"]) || isset($_GET["restart"]);
$showDifficultyScreen = false;

if ($needsNewSession) {
    if (!in_array($difficulty, $validDifficulties)) {
        $showDifficultyScreen = true;
    } else {
        $_SESSION["scenario_practice"] = [
            "q_index" => 1,
            "correct" => 0,
            "wrong"   => 0,
            "score"   => 0,
            "streak"  => 0,
            "difficulty" => $difficulty,
            "question" => generate_scenario_question($difficulty),
            "answered" => false,
            "selected"  => null,
        ];
    }
}

if (!$showDifficultyScreen) {
    $state = &$_SESSION["scenario_practice"];

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["submit_answer"]) && !$state["answered"]) {
            $selected = isset($_POST["selected"]) && $_POST["selected"] !== "" ? (int) $_POST["selected"] : -1;
            $state["selected"] = $selected;
            $state["answered"] = true;

            if ($selected === $state["question"]["correct_index"]) {
                $state["correct"]++;
                $state["streak"]++;
                $base = 20 + min($state["streak"] * 2, 20);
                $state["score"] += (int) round($base * difficulty_multiplier($state["difficulty"]));
            } else {
                $state["wrong"]++;
                $state["streak"] = 0;
            }
        } elseif (isset($_POST["next"])) {
            if ($state["q_index"] >= $TOTAL_QUESTIONS) {
                $gameType = "scenario_practice";
                $stmt = mysqli_prepare($conn, "INSERT INTO scores (user_id, match_id, game_type, points, correct_count, played_at) VALUES (?, NULL, ?, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmt, "isii", $userId, $gameType, $state["score"], $state["correct"]);
                mysqli_stmt_execute($stmt);

                $xpResult = award_xp($conn, $userId, $state["score"]);
                $dailyBonus = check_and_award_daily_challenge($conn, $userId);
                $updateQuiz = mysqli_prepare($conn, "UPDATE user_progress SET quizzes_completed = quizzes_completed + 1, total_correct = total_correct + ?, total_wrong = total_wrong + ? WHERE user_id = ?");
                mysqli_stmt_bind_param($updateQuiz, "iii", $state["correct"], $state["wrong"], $userId);
                mysqli_stmt_execute($updateQuiz);

                $newBadges = check_and_award_badges($conn, $userId);

                $finalScore = $state["score"];
                $finalCorrect = $state["correct"];
                $finalWrong = $state["wrong"];
                $sessionDifficulty = $state["difficulty"];
                unset($_SESSION["scenario_practice"]);
                $sessionDone = true;
            } else {
                $state["q_index"]++;
                $state["question"] = generate_scenario_question($state["difficulty"]);
                $state["answered"] = false;
                $state["selected"] = null;
            }
        }
    }

    $q = $state["question"] ?? null;
    $letters = ["A", "B", "C", "D"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scenario-Based Questions (Practice) - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
</head>
<body>
<div class="showdown-layout">
    <div class="showdown-profile">
        <?= render_avatar($myAvatar, 70) ?>
        <div class="name"><?= htmlspecialchars($username) ?></div>
        <div class="tag">You</div>

        <?php if (!$showDifficultyScreen && !isset($sessionDone)): ?>
        <div class="showdown-stat-box"><div class="lbl">Score</div><div class="num" style="color:var(--gold);"><?= $state["score"] ?></div></div>
        <div class="showdown-stat-box"><div class="lbl">Correct</div><div class="num" style="color:var(--green);"><?= $state["correct"] ?></div></div>
        <div class="showdown-stat-box">
            <div class="lbl">Streak</div>
            <div class="num" style="color:var(--orange);"><?= $state["streak"] ?></div>
            <div class="progress-bar" style="margin-top:0.4rem;"><div style="width:<?= min($state['streak']*20,100) ?>%; background:var(--orange);"></div></div>
        </div>
        <?php endif; ?>
        <a href="../practice.php" class="btn btn-secondary" style="text-decoration:none; display:block; margin-top:0.6rem; font-size:0.82rem;">← Back</a>
    </div>

    <?php if ($showDifficultyScreen): ?>
        <div class="showdown-card">
            <div class="difficulty-select">
                <h2>Choose a Difficulty</h2>
                <p class="sub">This controls the subnet sizes used in scenarios and how much XP you earn.</p>
                <div class="difficulty-grid">
                    <a href="?difficulty=easy" class="difficulty-card easy">
                        <span class="icon">🟢</span><h4>Easy</h4><p>Smaller subnets<br>XP ×1</p>
                    </a>
                    <a href="?difficulty=medium" class="difficulty-card medium">
                        <span class="icon">🟡</span><h4>Medium</h4><p>Mid-size subnets<br>XP ×1.5</p>
                    </a>
                    <a href="?difficulty=hard" class="difficulty-card hard">
                        <span class="icon">🔴</span><h4>Difficult</h4><p>Smaller blocks, trickier math<br>XP ×2</p>
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (isset($sessionDone)): ?>
        <div class="showdown-card">
            <h2>Session Complete!</h2>
            <p style="color:var(--text-dim);">Nice work — here's how you did:</p>
            <div class="stat-line"><span>Correct Answers</span><span class="good"><?= $finalCorrect ?></span></div>
            <div class="stat-line"><span>Wrong Answers</span><span class="bad"><?= $finalWrong ?></span></div>
            <div class="stat-line"><span>Total Score</span><span class="xp">+<?= $finalScore ?> XP</span></div>
            <?php if ($xpResult["leveled_up"]): ?>
                <div class="feedback-banner feedback-correct" style="margin-top:0.8rem;">🎉 Level Up! You're now Level <?= $xpResult["new_level"] ?>!</div>
            <?php endif; ?>
            <?php if ($dailyBonus["awarded"]): ?>
                <div class="feedback-banner feedback-correct" style="margin-top:0.8rem;">⭐ Daily Challenge Complete! +<?= $dailyBonus["bonus_xp"] ?> bonus XP</div>
            <?php endif; ?>
            <?php foreach ($newBadges as $b): ?>
                <div class="feedback-banner feedback-correct" style="margin-top:0.8rem;"><?= $b["icon"] ?> Badge Unlocked: <b><?= htmlspecialchars($b["name"]) ?></b></div>
            <?php endforeach; ?>
            <div class="game-actions">
                <a href="scenario_practice.php?restart=1&difficulty=<?= $sessionDifficulty ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Play Again</a>
                <a href="../practice.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">Back to Practice Mode</a>
            </div>
        </div>
    <?php else: ?>
        <div class="showdown-card">
            <div class="showdown-top-row">
                <a href="../practice.php" class="showdown-back">← Exit</a>
                <div style="font-weight:bold;">Question <?= $state["q_index"] ?> / <?= $TOTAL_QUESTIONS ?></div>
                <div class="progress-dots">
                    <?php for ($i = 1; $i <= $TOTAL_QUESTIONS; $i++): ?>
                        <div class="progress-dot <?= $i < $state['q_index'] ? 'done' : ($i == $state['q_index'] ? 'current' : '') ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="showdown-meta-row">
                <div class="category-pill">📋 Scenario Quiz · <?= ucfirst($state["difficulty"]) ?></div>
                <div style="font-size:0.78rem; color:var(--text-dim);">No timer — Practice Mode</div>
            </div>

            <div class="showdown-question"><?= htmlspecialchars($q["prompt"]) ?></div>

            <form method="POST" id="quizForm">
                <?php foreach ($q["options"] as $i => $opt):
                    $cls = "";
                    if ($state["answered"]) {
                        if ($i === $q["correct_index"]) $cls = "correct";
                        elseif ($i === $state["selected"]) $cls = "incorrect";
                    } elseif ($i === $state["selected"]) {
                        $cls = "selected";
                    }
                ?>
                    <div class="mcq-option <?= $cls ?>" data-index="<?= $i ?>" onclick="<?= $state['answered'] ? '' : 'selectOption(' . $i . ')' ?>">
                        <div class="mcq-radio"></div>
                        <span class="mcq-letter"><?= $letters[$i] ?>.</span>
                        <span><?= htmlspecialchars($opt) ?></span>
                    </div>
                <?php endforeach; ?>

                <input type="hidden" name="selected" id="selectedInput" value="<?= $state["selected"] ?? "" ?>">

                <?php if (!$state["answered"]): ?>
                    <button type="submit" name="submit_answer" value="1" class="btn btn-primary" style="width:100%; margin-top:0.5rem;" id="submitBtn" disabled>Submit Answer</button>
                <?php else: ?>
                    <button type="submit" name="next" value="1" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">
                        <?= $state["q_index"] >= $TOTAL_QUESTIONS ? "Finish Session" : "Next Question" ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($state["difficulty"] === "easy"): ?>
            <?= render_mcq_instructions("Scenario-Based Questions") ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function selectOption(i) {
    document.querySelectorAll(".mcq-option").forEach(el => el.classList.remove("selected"));
    document.querySelector(`.mcq-option[data-index="${i}"]`).classList.add("selected");
    document.getElementById("selectedInput").value = i;
    document.getElementById("submitBtn").disabled = false;
}
</script>
</body>
</html>