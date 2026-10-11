<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require __DIR__ . "/../config.php";
require __DIR__ . "/../includes/ipv6_engine.php";
require __DIR__ . "/../includes/module_progress.php";

if (!isset($_SESSION["user_id"])) {
 header("Location: ../login.php");
 exit();
}
$userId = $_SESSION["user_id"];

$completedModules = get_completed_modules($conn, $userId);
$isModuleCompleted = in_array("ipv6_game", $completedModules);

// Fresh random example every load - purely for teaching, not scored.
$groups = ipv6_groups_with_zero_run();
$fullPadded = ipv6_expanded_padded($groups);
$strippedNoCompress = implode(":", array_map(fn($g) => dechex(hexdec($g)), $groups));
$correctCompressed = ipv6_compress($groups);

// A second, independent example for the address-type explanation
$typeExamples = [
 "Loopback" => "::1",
 "Link-Local" => "fe80::" . ipv6_random_hextet(),
 "Multicast" => "ff02::" . ipv6_random_hextet(),
 "Unique Local" => "fd" . sprintf("%02x", rand(0, 255)) . "::" . ipv6_random_hextet(),
 "Global Unicast" => "2" . sprintf("%03x", rand(0, 0xFFF)) . "::" . ipv6_random_hextet(),
];

// Lesson mastery question session handling
if (!isset($_SESSION["ipv6_module_q"]) || isset($_POST["reset_question"])) {
    $mGroups = ipv6_groups_with_zero_run();
    $mFull = ipv6_expanded_padded($mGroups);
    $mCorrect = ipv6_compress($mGroups);
    $_SESSION["ipv6_module_q"] = ["full" => $mFull, "correct" => $mCorrect];
} else {
    $masteryQ = $_SESSION["ipv6_module_q"];
    $mFull = $masteryQ["full"];
    $mCorrect = $masteryQ["correct"];
}

$feedback = "";
$feedbackClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    $userAnswer = strtolower(trim($_POST["user_answer"] ?? ""));
    if ($userAnswer === strtolower($mCorrect)) {
        record_module_completion($conn, $userId, "ipv6_game");
        $isModuleCompleted = true;
        $feedback = "Correct! You completed the IPv6 Game lesson (+20% progress).";
        $feedbackClass = "good";
        unset($_SESSION["ipv6_module_q"]);
    } else {
        $feedback = "Incorrect. Try again using the zero compression step guide above.";
        $feedbackClass = "bad";
    }
}
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
 <div class="learn-step">
 <h2>How IPv6 Addresses Work</h2>
 <p><b>1. RFC 5952 — IPv6 Address Text Representation</b><br> <a href="[link removed]" target="_blank" rel="noopener noreferrer">Read RFC 5952</a></p>

 <p>
 <b>2. RFC 4291 — IPv6 Addressing Architecture</b><br>
 <a href="[link removed]"
 target="_blank" rel="noopener noreferrer">
 Read RFC 4291
 </a>
 </p>

 <p>
 <b>3. IANA — IPv6 Special-Purpose Address Registry</b><br>
 <a href="[link removed]"
 target="_blank" rel="noopener noreferrer">
 View the IANA registry
 </a>
 </p>

 </div>
 </div>

 <div class="game-card" style="margin-top:1.2rem;">
 <div class="mode-tag">LESSON MASTERY QUESTION (+20% PROGRESS)</div>
 <h2>What is the correctly compressed form of <?= $mFull ?>?</h2>
 <p class="sub">Answer correctly to complete this lesson and earn <b>+20% progress</b>.</p>

 <?php if ($feedback): ?>
 <div class="binary-feedback <?= $feedbackClass ?>" style="margin-bottom:1rem; font-weight:bold;">
 <?= htmlspecialchars($feedback) ?>
 </div>
 <?php endif; ?>

 <?php if ($isModuleCompleted): ?>
 <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--green); padding: 1rem; border-radius: 8px; text-align: center; margin-bottom: 1rem;">
 <span style="color: var(--green); font-weight: bold;">✓ Lesson Completed (+20% Overall Progress)</span>
 </div>
 <?php endif; ?>

 <form method="POST" style="text-align:center; padding:1rem;">
 <input type="text" name="user_answer" class="learn-practice-input" placeholder="e.g. 2001:db8::1" required style="padding:0.5rem; font-size:1.1rem; width:280px; text-align:center; margin-bottom:1rem;">
 
 <div class="game-actions" style="justify-content:center;">
 <button type="submit" name="submit_answer" class="btn btn-primary">Submit Answer</button>
 <button type="submit" name="reset_question" class="btn btn-secondary" formnovalidate>New Address</button>
 </div>
 </form>
 </div>
 </main>
</div>
</body>
</html>