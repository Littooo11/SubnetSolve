<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/module_progress.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$completedModules = get_completed_modules($conn, $userId);
$isModuleCompleted = in_array("subnet_showdown", $completedModules);

if (!isset($_SESSION["showdown_module_q"]) || isset($_POST["reset_question"])) {
    $_SESSION["showdown_module_q"] = [
        "prompt" => "What is the subnet mask for a /26 network?",
        "options" => ["255.255.255.0", "255.255.255.128", "255.255.255.192", "255.255.255.224"],
        "correct_index" => 2
    ];
}

$q = $_SESSION["showdown_module_q"];
$feedback = "";
$feedbackClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    $selectedIndex = (int) ($_POST["selected_option"] ?? -1);
    if ($selectedIndex === $q["correct_index"]) {
        record_module_completion($conn, $userId, "subnet_showdown");
        $isModuleCompleted = true;
        $feedback = "Correct! You completed the Subnet Showdown lesson (+20% progress).";
        $feedbackClass = "good";
        unset($_SESSION["showdown_module_q"]);
    } else {
        $feedback = "Incorrect option selected. Please review subnet masks and try again.";
        $feedbackClass = "bad";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>How to Play: Subnet Showdown - SubNetSolve</title>
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
        <div class="breadcrumb"><a href="../learning_modules.php" class="showdown-back">← Exit</a> &nbsp; Learning Modules &gt; <b>Subnet Showdown</b></div>

        <div class="game-card">
            <h2>Subnetting Concepts</h2>
            <p class="sub">Subnet Showdown tests key subnet concepts including prefixes, network addresses, and masks.</p>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">LESSON MASTERY QUESTION</div>
            <h2><?= htmlspecialchars($q['prompt']) ?></h2>
            <p class="sub">Select the correct choice to complete this lesson (+20% progress).</p>

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

            <form method="POST">
                <div style="display: flex; flex-direction: column; gap: 0.8rem; margin: 1rem 0;">
                    <?php foreach ($q['options'] as $idx => $opt): ?>
                        <label style="background: var(--panel-light); border: 1px solid var(--border); padding: 0.8rem; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="selected_option" value="<?= $idx ?>" required>
                            <?= htmlspecialchars($opt) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

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