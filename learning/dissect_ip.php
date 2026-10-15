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
$isModuleCompleted = in_array("dissect_ip", $completedModules);

// Generate question if not set
if (!isset($_SESSION["dissect_module_q"]) || isset($_POST["reset_question"])) {
    $_SESSION["dissect_module_q"] = [
        "ip" => "192.168.1.50",
        "cidr" => 26,
        "prompt" => "What is the network address for 192.168.1.50/26?",
        "correct" => "192.168.1.0"
    ];
}

$q = $_SESSION["dissect_module_q"];
$feedback = "";
$feedbackClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    $userAnswer = trim($_POST["user_answer"] ?? "");
    if ($userAnswer === $q["correct"]) {
        record_module_completion($conn, $userId, "dissect_ip");
        $isModuleCompleted = true;
        $feedback = "Correct! You completed the Dissect an IP Address lesson (+20% progress).";
        $feedbackClass = "good";
        unset($_SESSION["dissect_module_q"]);
    } else {
        $feedback = "Incorrect. Remember to round down to the nearest multiple of the block size (Block size = 64).";
        $feedbackClass = "bad";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dissect an IP Address - SubNetSolve</title>
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
    </aside>

    <main class="game-main">
        <div class="breadcrumb"><a href="../learning_modules.php" class="showdown-back">← Exit</a> &nbsp; Learning Modules &gt; <b>Dissect an IP Address</b></div>

        <div class="game-card">
            <h2>How to Dissect an IP Address</h2>
            <p class="sub">Analyze subnet prefixes, block sizes, network IDs, and broadcast addresses step-by-step.</p>

            <div class="learn-step">
                <div class="step-title">Step 1 — Find the Block Size</div>
                <p>For a /26 prefix, the last subnet mask octet is 192. Block size = 256 - 192 = 64.</p>
            </div>

            <div class="learn-step">
                <div class="step-title">Step 2 — Determine the Network Address</div>
                <p>Find the largest multiple of the block size (64) less than or equal to the host octet (50). Here, 0 is the network boundary.</p>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">LESSON MASTERY QUESTION</div>
            <h2><?= htmlspecialchars($q['prompt']) ?></h2>
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
                <input type="text" name="user_answer" class="learn-practice-input" placeholder="e.g. 192.168.1.0" required style="padding:0.5rem; font-size:1.1rem; width:220px; text-align:center; margin-bottom:1rem;">
                
                <div class="game-actions" style="justify-content:center;">
                    <button type="submit" name="submit_answer" class="btn btn-primary">Submit Answer</button>
                    <button type="submit" name="reset_question" class="btn btn-secondary" formnovalidate>New Question</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>