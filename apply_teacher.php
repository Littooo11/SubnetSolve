<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

// Load User & Teacher Status
$stmt = mysqli_prepare($conn, "SELECT username, email, is_teacher, teacher_status FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Redirect if already teacher or application is pending
if (!empty($user["is_teacher"]) || ($user["teacher_status"] ?? "") === "pending") {
    header("Location: settings.php");
    exit();
}

$feedback = "";
$feedbackClass = "";

// Handle Application Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_application"])) {
    $studentEmails = trim($_POST["student_emails"] ?? "");

    if (empty($studentEmails)) {
        $feedback = "Please list at least one student email address.";
        $feedbackClass = "feedback-wrong";
    } else {
        // Insert application record
        $appStmt = mysqli_prepare($conn, "INSERT INTO teacher_applications (user_id, student_emails) VALUES (?, ?)");
        mysqli_stmt_bind_param($appStmt, "is", $userId, $studentEmails);
        mysqli_stmt_execute($appStmt);

        // Update user's teacher status flag to pending
        $updateUser = mysqli_prepare($conn, "UPDATE users SET teacher_status = 'pending' WHERE id = ?");
        mysqli_stmt_bind_param($updateUser, "i", $userId);
        mysqli_stmt_execute($updateUser);

        header("Location: settings.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <title>Apply for Teacher Account - SubNetSolve</title>
 <link rel="stylesheet" href="dash.css">
 <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="layout two-col">
 <aside class="sidebar">
 <div class="brand">
 <div class="logo">S</div>
 <div>
 <h1>SubNet<span>Solve</span></h1>
 <p>Master Subnetting, Level Up!</p>
 </div>
 </div>
 <a href="dashboard.php" class="nav-link">Dashboard</a>
 <a href="learning_modules.php" class="nav-link">Learning Modules</a>
 <a href="practice.php" class="nav-link">Practice Mode</a>
 <a href="games.php" class="nav-link">Games</a>
 <a href="lobby.php" class="nav-link">Multiplayer Lobby</a>
 <a href="leaderboards.php" class="nav-link">Leaderboards</a>
 <a href="achievements.php" class="nav-link">Achievements</a>
 <a href="profile.php" class="nav-link">Profile</a>
 <a href="settings.php" class="nav-link active">Settings</a>
 <a href="about.php" class="nav-link">About Us</a>
 </aside>

 <main class="main">
 <div class="breadcrumb" style="margin-bottom:1rem;">
 <a href="settings.php" style="color:var(--text-dim); text-decoration:none;">← Back to Settings</a>
 </div>

 <div class="welcome">
 <h2>Teacher's Account Application</h2>
 <p>List your students' registered email addresses so an admin can verify and assign them to your Teacher Portal.</p>
 </div>

 <?php if ($feedback): ?>
 <div class="feedback-banner <?= $feedbackClass ?>" style="margin-bottom:1.25rem; max-width:520px;"><?= htmlspecialchars($feedback) ?></div>
 <?php endif; ?>

 <div class="explore-panel" style="max-width:520px;">
 <form method="POST">
 <div class="form-group" style="margin-bottom:1.2rem;">
 <label style="display:block; font-size:0.85rem; color:var(--text-dim); margin-bottom:0.4rem; font-weight:bold;">
 Student Email Addresses (comma-separated)
 </label>
 <textarea name="student_emails" placeholder="student1@example.com, student2@example.com" required
 style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border); background:var(--panel-light); color:var(--text); font-size:0.88rem; height:120px; box-sizing:border-box; line-height:1.4;"></textarea>
<small style="color:#f87171; font-size:0.75rem; display:block; margin-top:0.2rem;">
    Note: A computer or laptop is recommended to better maximize this feature.
</small>    
 <small style="color:var(--text-dim); font-size:0.75rem; display:block; margin-top:0.4rem;">
    Ensure each student has registered an account using the listed email address so they can be associated with your account upon admin approval.
</small>
 </div>

 <div style="display:flex; gap:0.6rem;">
 <button type="submit" name="submit_application" class="btn btn-primary">Submit Application</button>
 <a href="settings.php" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
 </div>
 </form>
 </div>
 </main>
</div>
</body>
</html>