<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/xp_engine.php";
require "../includes/easy_instructions.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$username = $_SESSION["username"];

$difficultyDigits = ["easy" => 2, "medium" => 3, "hard" => 4];
$difficulty = $_GET["difficulty"] ?? null;
$showDifficultyScreen = !array_key_exists($difficulty, $difficultyDigits);
$digits = $difficultyDigits[$difficulty] ?? 2;
$maxVal = (1 << ($digits * 4)) - 1; // 16^digits - 1
$pointsPerCorrect = $showDifficultyScreen ? 10 : (int) round(10 * difficulty_multiplier($difficulty));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hex Conversion (Practice) - SubNetSolve</title>
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
        <a href="../practice.php" class="nav-link active">Practice Mode</a>
        <a href="../games.php" class="nav-link">Games</a>
        <a href="../lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="../leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="../achievements.php" class="nav-link">Achievements</a>
        <a href="../profile.php" class="nav-link">Profile</a>
        <a href="../settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="game-main">
        <div class="breadcrumb"><a href="../practice.php" class="showdown-back">← Exit</a> &nbsp; Practice Mode &gt; <b>Hex Conversion</b></div>

        <?php if ($showDifficultyScreen): ?>
            <div class="game-card">
                <div class="difficulty-select">
                    <h2>Choose a Difficulty</h2>
                    <p class="sub">This controls how many hexadecimal digits you'll convert and how much XP each correct answer earns.</p>
                    <div class="difficulty-grid">
                        <a href="?difficulty=easy" class="difficulty-card easy">
                            <span class="icon">🟢</span><h4>Easy</h4><p>2-digit hex (0x00-0xFF)<br>10 XP per correct</p>
                        </a>
                        <a href="?difficulty=medium" class="difficulty-card medium">
                            <span class="icon">🟡</span><h4>Medium</h4><p>3-digit hex (0x000-0xFFF)<br>15 XP per correct</p>
                        </a>
                        <a href="?difficulty=hard" class="difficulty-card hard">
                            <span class="icon">🔴</span><h4>Difficult</h4><p>4-digit hex (0x0000-0xFFFF)<br>20 XP per correct</p>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>

        <div class="game-card">
            <div class="mode-tag">PRACTICE MODE — NO TIMER · <?= strtoupper($difficulty) ?></div>
            <h2>Guess the Decimal Value</h2>
            <p class="sub">A <?= $digits ?>-digit hexadecimal number is shown below. Type its decimal equivalent (0-<?= $maxVal ?>) and press Enter. Each correct answer earns <?= $pointsPerCorrect ?> XP.</p>

            <div class="binary-stage">
                <div class="binary-number" id="hexDisplay">0x<?= str_repeat("-", $digits) ?></div>
                <div class="binary-hint">Convert this hexadecimal number to decimal</div>

                <div class="binary-input-row">
                    <input type="number" min="0" max="<?= $maxVal ?>" class="binary-input" id="answerInput" autocomplete="off" placeholder="?">
                    <button class="btn btn-primary" id="submitBtn">Submit</button>
                </div>

                <div class="binary-feedback" id="feedback"></div>

                <div class="binary-stats-row">
                    <div class="binary-stat"><div class="num good" id="correctCount">0</div><div class="lbl">Correct</div></div>
                    <div class="binary-stat"><div class="num bad" id="wrongCount">0</div><div class="lbl">Wrong</div></div>
                    <div class="binary-stat"><div class="num xp" id="scoreCount">0</div><div class="lbl">Score</div></div>
                </div>
            </div>

            <div class="game-actions" style="justify-content:center;">
                <button class="btn btn-secondary" id="endBtn">End Session</button>
            </div>
        </div>

        <div class="game-card" id="summaryCard" style="display:none; margin-top:1rem;">
            <h2>Session Saved!</h2>
            <p class="sub">Nice work — here's how you did:</p>
            <div class="stat-line"><span>Correct Answers</span><span class="good" id="finalCorrect"></span></div>
            <div class="stat-line"><span>Wrong Answers</span><span class="bad" id="finalWrong"></span></div>
            <div class="stat-line"><span>Total Score</span><span class="xp" id="finalScore"></span></div>
            <div class="game-actions">
                <a href="hex_practice.php?difficulty=<?= $difficulty ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Play Again</a>
                <a href="../practice.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">Back to Practice Mode</a>
            </div>
        </div>

        <?php if ($difficulty === "easy"): ?>
            <?= render_hex_instructions($digits) ?>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php if (!$showDifficultyScreen): ?>
<script>
const DIGITS = <?= $digits ?>;
const MAX_VAL = <?= $maxVal ?>;
const POINTS_PER_CORRECT = <?= $pointsPerCorrect ?>;
let correct = 0, wrong = 0, score = 0, currentAnswer = 0;
let saved = false;
const display = document.getElementById("hexDisplay");
const input = document.getElementById("answerInput");
const feedback = document.getElementById("feedback");

function nextQuestion() {
    currentAnswer = Math.floor(Math.random() * (MAX_VAL + 1));
    display.textContent = "0x" + currentAnswer.toString(16).toUpperCase().padStart(DIGITS, "0");
    input.value = "";
    feedback.textContent = "";
    feedback.className = "binary-feedback";
    input.focus();
}

function submitAnswer() {
    if (input.value === "") return;
    const guess = parseInt(input.value, 10);

    if (guess === currentAnswer) {
        correct++;
        score += POINTS_PER_CORRECT;
        feedback.textContent = "✅ Correct!";
        feedback.className = "binary-feedback good";
    } else {
        wrong++;
        feedback.textContent = `❌ It was ${currentAnswer}`;
        feedback.className = "binary-feedback bad";
    }

    document.getElementById("correctCount").textContent = correct;
    document.getElementById("wrongCount").textContent = wrong;
    document.getElementById("scoreCount").textContent = score;

    setTimeout(nextQuestion, guess === currentAnswer ? 500 : 900);
}

document.getElementById("submitBtn").addEventListener("click", submitAnswer);
input.addEventListener("keydown", (e) => { if (e.key === "Enter") submitAnswer(); });

function addBanner(html, cls) {
    const banner = document.createElement("div");
    banner.className = "feedback-banner " + cls;
    banner.style.marginTop = "0.8rem";
    banner.innerHTML = html;
    document.getElementById("summaryCard").appendChild(banner);
}

document.getElementById("endBtn").addEventListener("click", async () => {
    if (saved) return;
    saved = true;
    document.querySelector(".binary-stage").closest(".game-card").style.display = "none";

    let xpResult = null;
    try {
        const res = await fetch("../includes/save_score.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ game_type: "hex_practice", points: score, correct: correct, wrong: wrong })
        });
        xpResult = await res.json();
    } catch (e) { xpResult = null; }

    document.getElementById("finalCorrect").textContent = correct;
    document.getElementById("finalWrong").textContent = wrong;
    document.getElementById("finalScore").textContent = "+" + score + " XP";

    if (!xpResult || !xpResult.success) {
        addBanner("⚠️ Your XP couldn't be saved (server error).", "feedback-wrong");
    } else {
        if (xpResult.leveled_up) addBanner(`🎉 Level Up! You're now Level ${xpResult.new_level}!`, "feedback-correct");
        if (xpResult.new_badges && xpResult.new_badges.length > 0) {
            xpResult.new_badges.forEach(b => addBanner(`${b.icon} Badge Unlocked: <b>${b.name}</b>`, "feedback-correct"));
        }
    }
    document.getElementById("summaryCard").style.display = "block";
});

window.addEventListener("pagehide", () => {
    if (saved || score <= 0) return;
    saved = true;
    const payload = JSON.stringify({ game_type: "hex_practice", points: score, correct: correct, wrong: wrong });
    navigator.sendBeacon("../includes/save_score.php", new Blob([payload], { type: "application/json" }));
});

nextQuestion();
</script>
<?php endif; ?>
</body>
</html>