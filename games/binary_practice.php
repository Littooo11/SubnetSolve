<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Binary Game (Practice) - SubNetSolve</title>
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
        <div class="breadcrumb"><a href="../practice.php" class="showdown-back">← Exit</a> &nbsp; Practice Mode &gt; <b>Binary Conversion</b></div>

        <div class="game-card">
            <div class="mode-tag">PRACTICE MODE — NO TIMER</div>
            <h2>Guess the Decimal Value</h2>
            <p class="sub">A 4-digit binary number is shown below. Type its decimal equivalent (0-15) and press Enter.</p>

            <div class="binary-stage">
                <div class="binary-number" id="binaryDisplay">----</div>
                <div class="binary-hint">Convert this binary number to decimal</div>

                <div class="binary-input-row">
                    <input type="number" min="0" max="15" class="binary-input" id="answerInput" autocomplete="off" placeholder="?">
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
                <a href="binary_practice.php" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Play Again</a>
                <a href="../practice.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">Back to Practice Mode</a>
            </div>
        </div>
    </main>
</div>

<script>
let correct = 0, wrong = 0, score = 0, currentAnswer = 0;
const display = document.getElementById("binaryDisplay");
const input = document.getElementById("answerInput");
const feedback = document.getElementById("feedback");

function nextQuestion() {
    currentAnswer = Math.floor(Math.random() * 16); // 0-15
    display.textContent = currentAnswer.toString(2).padStart(4, "0");
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
        score += 10;
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

document.getElementById("endBtn").addEventListener("click", async () => {
    document.querySelector(".binary-stage").closest(".game-card").style.display = "none";

    let xpResult = null;
    try {
        const res = await fetch("../includes/save_score.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ game_type: "binary_practice", points: score, correct: correct, wrong: wrong })
        });
        xpResult = await res.json();
    } catch (e) { /* saving is best-effort; still show the summary */ }

    document.getElementById("finalCorrect").textContent = correct;
    document.getElementById("finalWrong").textContent = wrong;
    document.getElementById("finalScore").textContent = "+" + score + " XP";
    if (xpResult && xpResult.leveled_up) {
        const banner = document.createElement("div");
        banner.className = "feedback-banner feedback-correct";
        banner.style.marginTop = "0.8rem";
        banner.textContent = `🎉 Level Up! You're now Level ${xpResult.new_level}!`;
        document.getElementById("summaryCard").appendChild(banner);
    }
    if (xpResult && xpResult.new_badges && xpResult.new_badges.length > 0) {
        xpResult.new_badges.forEach(b => {
            const banner = document.createElement("div");
            banner.className = "feedback-banner feedback-correct";
            banner.style.marginTop = "0.8rem";
            banner.innerHTML = `${b.icon} Badge Unlocked: <b>${b.name}</b>`;
            document.getElementById("summaryCard").appendChild(banner);
        });
    }
    document.getElementById("summaryCard").style.display = "block";
});

nextQuestion();
</script>
</body>
</html>