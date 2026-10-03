<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Fresh random 2-digit hex number every load - purely for teaching, not scored.
$digits = 2;
$decimal = rand(1, (1 << ($digits * 4)) - 1); // avoid 0x00
$hexStr = strtoupper(str_pad(dechex($decimal), $digits, "0", STR_PAD_LEFT));
$hexDigits = str_split($hexStr);
$placeValues = [];
for ($i = $digits - 1; $i >= 0; $i--) $placeValues[] = 16 ** $i; // e.g. [16, 1]
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
                <p>If a digit is a letter (A-F), convert it to its decimal value first. Here's our example, <span class="formula">0x<?= $hexStr ?></span>:</p>
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
                <p>Hex <span class="formula">0x<?= $hexStr ?></span> equals decimal <span class="formula"><?= $decimal ?></span>. The real Hex Conversion game just asks you to do this quickly, over and over, against a timer.</p>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">TRY IT YOURSELF</div>
            <h2>What is the decimal value of the hex number 0x<?= $hexStr ?>?</h2>
            <p class="sub">Not scored — take your time. Click "Check Answer" to see if you're right.</p>

            <div style="text-align:center; padding:1rem;">
                <div class="binary-number" style="margin-bottom:1rem;">0x<?= $hexStr ?></div>
                <input type="number" min="0" class="learn-practice-input" id="answerInput" data-answer="<?= $decimal ?>" placeholder="?">
                <div id="feedback" style="margin-top:0.8rem; font-size:0.9rem;"></div>
            </div>

            <div class="game-actions" style="justify-content:center;">
                <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
                <button type="button" class="btn btn-primary" id="checkBtn">Check Answer</button>
                <a href="hex_conversion.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">New Number</a>
            </div>
        </div>
    </main>
</div>

<script>
const input = document.getElementById("answerInput");
const feedback = document.getElementById("feedback");

document.getElementById("checkBtn").addEventListener("click", () => {
    input.classList.remove("correct", "incorrect");
    if (input.value.trim() === "") return;

    if (parseInt(input.value, 10) === parseInt(input.dataset.answer, 10)) {
        input.classList.add("correct");
        feedback.textContent = "✅ Correct!";
        feedback.style.color = "var(--green)";
    } else {
        input.classList.add("incorrect");
        feedback.textContent = "❌ Not quite — check the place-value breakdown above.";
        feedback.style.color = "#f87171";
    }
});

document.getElementById("resetBtn").addEventListener("click", () => {
    input.value = "";
    input.classList.remove("correct", "incorrect");
    feedback.textContent = "";
});
</script>
</body>
</html>