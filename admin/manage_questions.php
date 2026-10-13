<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";

$feedback = "";
$feedbackClass = "";

// Handle Delete Question
if (isset($_GET["delete_id"])) {
    $deleteId = (int)$_GET["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM game_questions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $deleteId);
    if (mysqli_stmt_execute($stmt)) {
        $feedback = "Question deleted successfully.";
        $feedbackClass = "good";
    }
}

// Handle Add / Edit Question
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_question"])) {
    $qId = !empty($_POST["question_id"]) ? (int)$_POST["question_id"] : null;
    $gameType = trim($_POST["game_type"]);
    $questionText = trim($_POST["question_text"]);
    $optionA = trim($_POST["option_a"]);
    $optionB = trim($_POST["option_b"]);
    $optionC = trim($_POST["option_c"]);
    $optionD = trim($_POST["option_d"]);
    $correctOption = strtoupper(trim($_POST["correct_option"]));
    $explanation = trim($_POST["explanation"]);

    if ($qId) {
        $stmt = mysqli_prepare($conn, "UPDATE game_questions SET game_type=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?, explanation=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssssssi", $gameType, $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption, $explanation, $qId);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO game_questions (game_type, question_text, option_a, option_b, option_c, option_d, correct_option, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssss", $gameType, $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption, $explanation);
    }

    if (mysqli_stmt_execute($stmt)) {
        $feedback = $qId ? "Question updated successfully!" : "New question added successfully!";
        $feedbackClass = "good";
    }
}

// Fetch all questions for listing
$filterType = $_GET["filter_type"] ?? "";
$whereClause = $filterType ? "WHERE game_type = '" . mysqli_real_escape_string($conn, $filterType) . "'" : "";
$questionsResult = mysqli_query($conn, "SELECT * FROM game_questions $whereClause ORDER BY id DESC");
$questions = mysqli_fetch_all($questionsResult, MYSQLI_ASSOC);

// Fetch editing question if requested
$editQ = null;
if (isset($_GET["edit_id"])) {
    $editId = (int)$_GET["edit_id"];
    $editStmt = mysqli_prepare($conn, "SELECT * FROM game_questions WHERE id = ?");
    mysqli_stmt_bind_param($editStmt, "i", $editId);
    mysqli_stmt_execute($editStmt);
    $editQ = mysqli_stmt_get_result($editStmt)->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Game Questions - Admin</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand" style="margin-bottom:1.5rem;">
            <div class="logo">S</div>
            <div><h1>SubNet<span>Solve</span></h1><p>Admin Panel</p></div>
        </div>
        <div class="admin-nav-label">MAIN NAVIGATION</div>
        <a href="dashboard.php" class="admin-nav-link">🏠 Dashboard</a>
        <a href="users.php" class="admin-nav-link">👥 User Management</a>
        <a href="manage_questions.php" class="admin-nav-link active">❓ Manage Questions</a>
        <a href="game_popularity.php" class="admin-nav-link">🎮 Game Category Popularity</a>
        <div class="admin-nav-label">ACCOUNT</div>
        <a href="profile.php" class="admin-nav-link">👤 Admin Profile</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <h2>Manage Game Questions</h2>
        </div>

        <?php if ($feedback): ?>
            <div class="feedback-banner <?= $feedbackClass === 'good' ? 'feedback-correct' : 'feedback-wrong' ?>" style="margin-bottom:1rem;">
                <?= htmlspecialchars($feedback) ?>
            </div>
        <?php endif; ?>

        <!-- Form to Add/Edit Questions -->
        <div class="admin-panel" style="margin-bottom:2rem;">
            <h3><?= $editQ ? "Edit Question #" . $editQ['id'] : "Add New Question" ?></h3>
            <form method="POST" style="display:grid; gap:0.8rem; margin-top:1rem;">
                <input type="hidden" name="question_id" value="<?= $editQ['id'] ?? '' ?>">
                
                <div>
                    <label>Game Topic:</label>
                    <select name="game_type" class="admin-select" style="width:100%; padding:0.5rem;" required>
                        <option value="subnet_showdown" <?= ($editQ['game_type'] ?? '') === 'subnet_showdown' ? 'selected' : '' ?>>Subnet Showdown</option>
                        <option value="scenario_game" <?= ($editQ['game_type'] ?? '') === 'scenario_game' ? 'selected' : '' ?>>Scenario-Based Questions</option>
                    </select>
                </div>

                <div>
                    <label>Question Prompt:</label>
                    <textarea name="question_text" rows="3" style="width:100%; background:var(--panel-light); color:var(--text); border:1px solid var(--border); border-radius:6px; padding:0.5rem;" required><?= htmlspecialchars($editQ['question_text'] ?? '') ?></textarea>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
                    <div>
                        <label>Option A:</label>
                        <input type="text" name="option_a" value="<?= htmlspecialchars($editQ['option_a'] ?? '') ?>" style="width:100%; background:var(--panel-light); color:var(--text); border:1px solid var(--border); padding:0.4rem; border-radius:6px;" required>
                    </div>
                    <div>
                        <label>Option B:</label>
                        <input type="text" name="option_b" value="<?= htmlspecialchars($editQ['option_b'] ?? '') ?>" style="width:100%; background:var(--panel-light); color:var(--text); border:1px solid var(--border); padding:0.4rem; border-radius:6px;" required>
                    </div>
                    <div>
                        <label>Option C:</label>
                        <input type="text" name="option_c" value="<?= htmlspecialchars($editQ['option_c'] ?? '') ?>" style="width:100%; background:var(--panel-light); color:var(--text); border:1px solid var(--border); padding:0.4rem; border-radius:6px;" required>
                    </div>
                    <div>
                        <label>Option D:</label>
                        <input type="text" name="option_d" value="<?= htmlspecialchars($editQ['option_d'] ?? '') ?>" style="width:100%; background:var(--panel-light); color:var(--text); border:1px solid var(--border); padding:0.4rem; border-radius:6px;" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.8rem;">
                    <div>
                        <label>Correct Choice:</label>
                        <select name="correct_option" class="admin-select" style="width:100%; padding:0.5rem;" required>
                            <option value="A" <?= ($editQ['correct_option'] ?? '') === 'A' ? 'selected' : '' ?>>Option A</option>
                            <option value="B" <?= ($editQ['correct_option'] ?? '') === 'B' ? 'selected' : '' ?>>Option B</option>
                            <option value="C" <?= ($editQ['correct_option'] ?? '') === 'C' ? 'selected' : '' ?>>Option C</option>
                            <option value="D" <?= ($editQ['correct_option'] ?? '') === 'D' ? 'selected' : '' ?>>Option D</option>
                        </select>
                    </div>
                    <div>
                        <label>Explanation (Optional):</label>
                        <input type="text" name="explanation" value="<?= htmlspecialchars($editQ['explanation'] ?? '') ?>" style="width:100%; background:var(--panel-light); color:var(--text); border:1px solid var(--border); padding:0.4rem; border-radius:6px;">
                    </div>
                </div>

                <div style="margin-top:0.5rem;">
                    <button type="submit" name="save_question" class="btn btn-primary"><?= $editQ ? "Update Question" : "Save Question" ?></button>
                    <?php if ($editQ): ?>
                        <a href="manage_questions.php" class="btn btn-secondary" style="text-decoration:none;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Question List Table -->
        <div class="admin-panel">
            <h3>Existing Questions</h3>
            <table class="admin-table" style="margin-top:1rem;">
                <tr>
                    <th>ID</th>
                    <th>Topic</th>
                    <th>Question</th>
                    <th>Correct Option</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($questions as $q): ?>
                <tr>
                    <td><?= $q["id"] ?></td>
                    <td><span class="status-pill in_progress"><?= htmlspecialchars($q["game_type"]) ?></span></td>
                    <td><?= htmlspecialchars($q["question_text"]) ?></td>
                    <td><strong><?= $q["correct_option"] ?></strong></td>
                    <td>
                        <a href="manage_questions.php?edit_id=<?= $q['id'] ?>" class="btn btn-secondary" style="padding:0.2rem 0.5rem; font-size:0.75rem; text-decoration:none;">Edit</a>
                        <a href="manage_questions.php?delete_id=<?= $q['id'] ?>" onclick="return confirm('Delete this question?')" class="btn" style="padding:0.2rem 0.5rem; font-size:0.75rem; background:#ef4444; color:#fff; text-decoration:none;">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </main>
</div>
</body>
</html>