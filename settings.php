<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";
require "includes/avatars.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

// Load User & Teacher Status
$stmt = mysqli_prepare($conn, "SELECT username, email, avatar, is_teacher, teacher_status FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
 <title>Settings - SubNetSolve</title>
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
 <div class="welcome">
 <h2>Settings</h2>
 <p>Manage your account, session, and role applications.</p>
 </div>

 <div class="explore-panel" style="max-width:520px; margin-bottom:1.25rem;">
 <h3 class="section-title">Account</h3>
 <div style="display:flex; align-items:center; gap:0.9rem; margin-bottom:1rem;">
 <?= render_avatar($user["avatar"], 50) ?>
 <div>
 <div style="font-weight:bold;"><?= htmlspecialchars($user["username"]) ?></div>
 <div style="font-size:0.85rem; color:var(--text-dim);"><?= htmlspecialchars($user["email"]) ?></div>
 </div>
 </div>
 <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:1rem;">
 To change your username, email, password, or avatar, head to your profile.
 </p>
 <a href="profile.php" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Edit Profile</a>
 </div>

 <!-- Teacher Account Application Panel -->
 <div class="explore-panel" style="max-width:520px; margin-bottom:1.25rem;">
 <h3 class="section-title">Teacher's Account Application</h3>
 
 <?php if (!empty($user["is_teacher"])): ?>
 <div style="color:var(--green); font-weight:bold; font-size:0.9rem;">
 ✓ You have an approved Teacher's Account.
 </div>
 <p style="font-size:0.85rem; color:var(--text-dim); margin-top:0.4rem;">
 You can access student performance stats from the Teacher Portal.
 </p>
 <a href="teacher/dashboard.php" class="btn btn-primary" style="text-decoration:none; display:inline-block; margin-top:0.5rem;">Go to Teacher Portal</a>

 <?php elseif (($user["teacher_status"] ?? "") === "pending"): ?>
 <div style="color:var(--gold); font-weight:bold; font-size:0.9rem;">
 ⏳ Application Pending Admin Approval
 </div>
 <p style="font-size:0.85rem; color:var(--text-dim); margin-top:0.4rem;">
 An admin is currently reviewing your listed student emails. You will receive access once approved.
 </p>

 <?php else: ?>
 <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:1rem;">
 Teachers get access to their students' game stats, learning progress, and detailed question accuracy breakdowns.
 </p>
 <a href="apply_teacher.php" class="btn btn-primary" style="text-decoration:none; display:inline-block;">Apply for a Teacher's Account</a>
 <?php endif; ?>
 </div>

 <div class="explore-panel" style="max-width:520px; border-color:rgba(239,68,68,0.3);">
 <h3 class="section-title">Session</h3>
 <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:1rem;">
 Signed in as <b><?= htmlspecialchars($user["username"]) ?></b>. Logging out will end your current session on this device.
 </p>
 <a href="logout.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block; border-color:#f87171; color:#f87171;">Log Out</a>
 </div>
 </main>
</div>
</body>
</html>