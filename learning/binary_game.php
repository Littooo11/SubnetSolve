<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require __DIR__ . "/../config.php";
require __DIR__ . "/../includes/module_progress.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$completedModules = get_completed_modules($conn, $userId);
$isModuleCompleted = in_array("binary_game", $completedModules);

// Fresh random 4-digit binary number every load - purely for teaching
$digits = 4;
$decimal = rand(1, (1 << $digits) - 1);
$binaryStr = str_pad(decbin($decimal), $digits, "0", STR_PAD_LEFT);
$bits = str_split($binaryStr);
$placeValues = [];
for ($i = $digits - 1; $i >= 0; $i--) $placeValues[] = 1 << $i;

// Lesson mastery question
if (!isset($_SESSION["binary_module_q"]) || isset($_POST["reset_question"])) {
    $mDec = rand(1, (1 << $digits) - 1);
    $mBin = str_pad(decbin($mDec), $digits, "0", STR_PAD_LEFT);
    $_SESSION["binary_module_q"] = ["binary" => $mBin, "decimal" => $mDec];
} else {
    $masteryQ = $_SESSION["binary_module_q"];
    $mBin = $masteryQ["binary"];
    $mDec = $masteryQ["decimal"];
}

$feedback = "";
$feedbackClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    $userAnswer = (int) ($_POST["user_answer"] ?? -1);
    if ($userAnswer === $mDec) {
        record_module_completion($conn, $userId, "binary_game");
        $isModuleCompleted = true;
        $feedback = "Correct! You completed the Binary Game lesson (+20% progress).";
        $feedbackClass = "good";
        unset($_SESSION["binary_module_q"]);
    } else {
        $feedback = "Incorrect. Binary {$mBin} equals decimal {$mDec}. Try another question!";
        $feedbackClass = "bad";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How to Play: Binary Game - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../learning.css">
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
        <a href="../learning_modules.php" class="nav-link active">Learning Modules</a>
        <a href="../practice.php" class="nav-link">Practice Mode</a>
        <a href="../games.php" class="nav-link">Games</a>
        <a href="../lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="../leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="../achievements.php" class="nav-link">Achievements</a>
        <a href="../profile.php" class="nav-link">Profile</a>
        <a href="../settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="game-main">
        <div class="breadcrumb"><a href="../learning_modules.php" class="showdown-back">← Exit</a> &nbsp; Learning Modules &gt; <b>Binary Game</b></div>

        <div class="game-card">
            <h2>How to Solve: Binary to Decimal</h2>
            <p class="sub">Every position in a binary number has a "place value" — a power of 2. Reading left to right for a <?= $digits ?>-digit number, those place values are <span class="formula"><?= implode(", ", $placeValues) ?></span>.</p>

            <div class="learn-step">
                <div class="step-title">Step 1 — Write down the place value under each bit</div>
                <p>Here's our example number, <span class="formula"><?= $binaryStr ?></span>, with its place values:</p>
                <div class="bit-breakdown">
                    <?php foreach ($bits as $i => $bit): ?>
                    <div class="bit-cell">
                        <div class="bit"><?= $bit ?></div>
                        <div class="place">×<?= $placeValues[$i] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="learn-step">
                <div class="step-title">Step 2 — Add up the place values wherever the bit is 1</div>
                <p>Skip any position where the bit is 0 — it contributes nothing.</p>
                <div class="worked">
                    → <?php
                    $terms = [];
                    foreach ($bits as $i => $bit) {
                        if ($bit === "1") $terms[] = $placeValues[$i];
                    }
                    echo implode(" + ", $terms) . " = " . $decimal;
                    ?>
                </div>
            </div>

            <div class="learn-step">
                <div class="step-title">That's it!</div>
                <p>Binary <span class="formula"><?= $binaryStr ?></span> equals decimal <span class="formula"><?= $decimal ?></span>. The real Binary Game just asks you to do this quickly, over and over, against a timer.</p>
            </div>

            <div class="learn-step">
                <h2>External Resources & References</h2>
                <p>
                    <b>1. Khan Academy — The Binary Number System</b><br>
                    <a href="https://www.khanacademy.org/computing/computers-and-internet/xcae6f4a7ff015e7d%3Adigital-info/xcae6f4a7ff015e7d%3Abinary-numbers/v/the-binary-number-system" target="_blank" rel="noopener noreferrer">Read or watch the lesson</a>
                </p>
                <p>
                    <b>2. Khan Academy — Binary Numbers</b><br>
                    <a href="https://www.khanacademy.org/a/bits-and-binary" target="_blank" rel="noopener noreferrer">Read the article</a>
                </p>
                <p>
                    <b>3. Khan Academy — Introduction to Number Systems and Binary</b><br>
                    <a href="https://www.khanacademy.org/math/algebra-home/alg-intro-to-algebra/algebra-alternate-number-bases/v/number-systems-introduction" target="_blank" rel="noopener noreferrer">Watch the lesson</a>
                </p>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">LESSON MASTERY QUESTION (+20% PROGRESS)</div>
            <h2>What is the decimal value of the binary number <span class="formula"><?= $mBin ?></span>?</h2>
            <p class="sub">Answer correctly to complete this lesson module and increase overall progress by 20%.</p>

            <?php if ($feedback): ?>
                <div class="feedback-banner <?= $feedbackClass === 'good' ? 'feedback-correct' : 'feedback-wrong' ?>" style="margin-bottom:1rem;">
                    <?= htmlspecialchars($feedback) ?>
                </div>
            <?php endif; ?>

            <?php if ($isModuleCompleted): ?>
                <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--green); padding: 1rem; border-radius: 8px; text-align: center; margin-bottom: 1rem;">
                    <span style="color: var(--green); font-weight: bold;">✓ Lesson Completed (+20% Overall Progress)</span>
                </div>
            <?php endif; ?>

            <form method="POST" style="text-align:center; padding:1rem;">
                <div class="binary-number" style="margin-bottom:1rem;"><?= $mBin ?></div>
                <input type="number" min="0" max="15" name="user_answer" class="learn-practice-input" placeholder="?" required>
                <div class="game-actions" style="justify-content:center; margin-top:1.2rem;">
                    <button type="submit" name="submit_answer" class="btn btn-primary">Submit Answer</button>
                    <button type="submit" name="reset_question" class="btn btn-secondary" formnovalidate>New Number</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>