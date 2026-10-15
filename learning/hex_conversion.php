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
$isModuleCompleted = in_array("hex_conversion", $completedModules);

// Fresh random 2-digit hex number every load - purely for teaching
$digits = 2;
$decimal = rand(1, (1 << ($digits * 4)) - 1);
$hexStr = strtoupper(str_pad(dechex($decimal), $digits, "0", STR_PAD_LEFT));
$hexDigits = str_split($hexStr);
$placeValues = [];
for ($i = $digits - 1; $i >= 0; $i--) $placeValues[] = 16 ** $i;

// Lesson mastery question
if (!isset($_SESSION["hex_module_q"]) || isset($_POST["reset_question"])) {
    $mDec = rand(1, (1 << ($digits * 4)) - 1);
    $mHex = strtoupper(str_pad(dechex($mDec), $digits, "0", STR_PAD_LEFT));
    $_SESSION["hex_module_q"] = ["hex" => $mHex, "decimal" => $mDec];
} else {
    $masteryQ = $_SESSION["hex_module_q"];
    $mHex = $masteryQ["hex"];
    $mDec = $masteryQ["decimal"];
}

$feedback = "";
$feedbackClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    $userAnswer = (int) ($_POST["user_answer"] ?? -1);
    if ($userAnswer === $mDec) {
        record_module_completion($conn, $userId, "hex_conversion");
        $isModuleCompleted = true;
        $feedback = "Correct! You completed the Hex Conversion lesson (+20% progress).";
        $feedbackClass = "good";
        unset($_SESSION["hex_module_q"]);
    } else {
        $feedback = "Incorrect. Hex {$mHex} equals decimal {$mDec}. Try another question!";
        $feedbackClass = "bad";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>How to Play: Hex Conversion - SubNetSolve</title>
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
        <div class="breadcrumb"><a href="../learning_modules.php" class="showdown-back">← Exit</a> &nbsp; Learning Modules &gt; <b>Hex Conversion</b></div>

        <div class="game-card">
            <h2>How to Solve: Hexadecimal to Decimal</h2>
            <p class="sub">Hex has 16 possible digits per position: 0-9, then A=10, B=11, C=12, D=13, E=14, F=15. Reading left to right for a <?= $digits ?>-digit number, the place values are powers of 16: <span class="formula"><?= implode(", ", $placeValues) ?></span>.</p>

            <div class="learn-step">
                <div class="step-title">Step 1 — Convert each hex digit to its decimal value</div>
                <p>If a digit is a letter (A-F), convert it to its decimal value first. Here's our example, <span class="formula"><?= $hexStr ?></span>:</p>
                <div class="bit-breakdown">
                    <?php foreach ($hexDigits as $i => $d): ?>
                    <div class="bit-cell">
                        <div class="bit"><?= $d ?></div>
                        <div class="place">= <?= hexdec($d) ?> ×<?= $placeValues[$i] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="learn-step">
                <div class="step-title">Step 2 — Multiply each digit's decimal value by its place value, then add them up</div>
                <div class="worked">
                    → <?php
                    $terms = [];
                    foreach ($hexDigits as $i => $d) {
                        $terms[] = hexdec($d) . "×" . $placeValues[$i];
                    }
                    echo implode(" + ", $terms) . " = " . $decimal;
                    ?>
                </div>
            </div>

            <div class="learn-step">
                <div class="step-title">That's it!</div>
                <p>Hex <span class="formula"><?= $hexStr ?></span> equals decimal <span class="formula"><?= $decimal ?></span>. The real Hex Conversion game just asks you to do this quickly, over and over, against a timer.</p>
            </div>

            <div class="learn-step"> 
                <h2>External Resources & References</h2>
                <p>
                    <b>1. W3Schools — What is a Hexadecimal Number?</b><br>
                    <a href="https://www.w3schools.com/programming/prog_hexadecimal_numbers.php" target="_blank" rel="noopener noreferrer">Read the lesson</a>
                </p>
                <p>
                    <b>2. SparkFun Learn — Hexadecimal</b><br>
                    <a href="https://learn.sparkfun.com/tutorials/hexadecimal/converting-tofrom-decimal" target="_blank" rel="noopener noreferrer">Read the tutorial</a>
                </p>
                <p>
                    <b>3. All About Circuits — Octal and Hexadecimal to Decimal Conversion</b><br>
                    <a href="https://www.allaboutcircuits.com/textbook/digital/chpt-1/octal-and-hexadecimal-to-decimal-conversion/" target="_blank" rel="noopener noreferrer">Read the tutorial</a>
                </p>
                <p>
                    <b>4. DepEd Philippines — Hexadecimal to Decimal</b><br>
                    <a href="https://www.deped.gov.ph/wp-content/uploads/Q1-TLE7-ICT-LAS-15-Converting-Decimal-to-Hexadecimal-Hexadecimal-to-Decimal.pdf" target="_blank" rel="
```noopener noreferrer">Open the learning activity sheet</a>
                </p>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">LESSON MASTERY QUESTION (+20% PROGRESS)</div>
            <h2>What is the decimal value of the hex number <span class="formula"><?= $mHex ?></span>?</h2>
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
                <div class="binary-number" style="margin-bottom:1rem;"><?= $mHex ?></div>
                <input type="number" min="0" name="user_answer" class="learn-practice-input" placeholder="?" required>
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