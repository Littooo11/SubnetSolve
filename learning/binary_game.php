<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Fresh random 4-digit binary number every load - purely for teaching, not scored.
$digits = 4;
$decimal = rand(1, (1 << $digits) - 1); // avoid 0000 so at least one bit lights up
$binaryStr = str_pad(decbin($decimal), $digits, "0", STR_PAD_LEFT);
$bits = str_split($binaryStr);
$placeValues = [];
for ($i = $digits - 1; $i >= 0; $i--) $placeValues[] = 1 << $i; // e.g. [8,4,2,1]
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
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">TRY IT YOURSELF</div>
            <h2>What is the decimal value of the binary number <?= $binaryStr ?>?</h2>
            <p class="sub">Not scored — take your time. Click "Check Answer" to see if you're right.</p>

            <div style="text-align:center; padding:1rem;">
                <div class="binary-number" style="margin-bottom:1rem;"><?= $binaryStr ?></div>
                <input type="number" min="0" class="learn-practice-input" id="answerInput" data-answer="<?= $decimal ?>" placeholder="?">
                <div id="feedback" style="margin-top:0.8rem; font-size:0.9rem;"></div>
            </div>

            <div class="game-actions" style="justify-content:center;">
                <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
                <button type="button" class="btn btn-primary" id="checkBtn">Check Answer</button>
                <a href="binary_game.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">New Number</a>
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