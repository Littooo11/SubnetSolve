<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/ipv6_engine.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Fresh random example every load - purely for teaching, not scored.
$groups = ipv6_groups_with_zero_run();
$fullPadded = ipv6_expanded_padded($groups);
$strippedNoCompress = implode(":", array_map(fn($g) => dechex(hexdec($g)), $groups));
$correctCompressed = ipv6_compress($groups);

// A second, independent example for the address-type explanation
$typeExamples = [
    "Loopback"       => "::1",
    "Link-Local"     => "fe80::" . ipv6_random_hextet(),
    "Multicast"      => "ff02::" . ipv6_random_hextet(),
    "Unique Local"   => "fd" . sprintf("%02x", rand(0, 255)) . "::" . ipv6_random_hextet(),
    "Global Unicast" => "2" . sprintf("%03x", rand(0, 0xFFF)) . "::" . ipv6_random_hextet(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How to Play: IPv6 Game - SubNetSolve</title>
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
        <div class="breadcrumb"><a href="../learning_modules.php" class="showdown-back">← Exit</a> &nbsp; Learning Modules &gt; <b>IPv6 Game</b></div>

        <div class="game-card">
            <h2>How IPv6 Addresses Work</h2>
            <p class="sub">An IPv6 address is 128 bits, written as 8 groups of up to 4 hex digits each, separated by colons.</p>

            <div class="learn-step">
                <div class="step-title">Step 1 — The full (expanded) form</div>
                <p>Every group gets exactly 4 hex digits, even if that means leading zeros. Here's a random example:</p>
                <div class="worked">→ <?= $fullPadded ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">Step 2 — Strip leading zeros from each group</div>
                <p>Any group's leading zeros can be dropped. A group of all zeros becomes just "0" (not dropped entirely yet — that's the next step).</p>
                <div class="worked">→ <?= $strippedNoCompress ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">Step 3 — Compress the longest run of all-zero groups</div>
                <p>Find the single longest run of consecutive groups that are all zero, and replace that whole run with "::". This can only be done once per address — if there are two separate zero-runs, only the longer one gets compressed.</p>
                <div class="worked">→ <?= $correctCompressed ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">Address types, by their first group</div>
                <p>The first few bits of an IPv6 address tell you what kind of address it is:</p>
                <ul style="color:var(--text-dim); font-size:0.88rem; line-height:1.8; margin:0.5rem 0 0 1.2rem;">
                    <li><b style="color:var(--text);">Loopback</b> — always exactly <span class="formula"><?= $typeExamples["Loopback"] ?></span></li>
                    <li><b style="color:var(--text);">Link-Local</b> — starts with <span class="formula">fe80:</span>, e.g. <?= $typeExamples["Link-Local"] ?></li>
                    <li><b style="color:var(--text);">Multicast</b> — starts with <span class="formula">ff</span>, e.g. <?= $typeExamples["Multicast"] ?></li>
                    <li><b style="color:var(--text);">Unique Local</b> — starts with <span class="formula">fc</span> or <span class="formula">fd</span>, e.g. <?= $typeExamples["Unique Local"] ?></li>
                    <li><b style="color:var(--text);">Global Unicast</b> — typically starts with <span class="formula">2</span> or <span class="formula">3</span>, e.g. <?= $typeExamples["Global Unicast"] ?></li>
                </ul>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">TRY IT YOURSELF</div>
            <h2>What is the correctly compressed form of <?= $fullPadded ?>?</h2>
            <p class="sub">Not scored — take your time. Type your answer and click "Check Answer".</p>

            <div style="text-align:center; padding:1rem;">
                <div class="binary-number" style="font-size:1.6rem;"><?= $fullPadded ?></div>
                <input type="text" class="learn-practice-input" id="answerInput" data-answer="<?= htmlspecialchars($correctCompressed) ?>" placeholder="e.g. 2001:db8::1" style="width:280px; font-size:1.1rem; font-family:Consolas,monospace;">
                <div id="feedback" style="margin-top:0.8rem; font-size:0.9rem;"></div>
            </div>

            <div class="game-actions" style="justify-content:center;">
                <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
                <button type="button" class="btn btn-primary" id="checkBtn">Check Answer</button>
                <a href="ipv6_game.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">New Number</a>
            </div>
        </div>
    </main>
</div>

<script>
const input = document.getElementById("answerInput");
const feedback = document.getElementById("feedback");

document.getElementById("checkBtn").addEventListener("click", () => {
    input.classList.remove("correct", "incorrect");
    const given = input.value.trim().toLowerCase();
    if (given === "") return;

    if (given === input.dataset.answer.toLowerCase()) {
        input.classList.add("correct");
        feedback.textContent = "✅ Correct!";
        feedback.style.color = "var(--green)";
    } else {
        input.classList.add("incorrect");
        feedback.textContent = "❌ Not quite — check the steps above. The correct answer was " + input.dataset.answer;
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