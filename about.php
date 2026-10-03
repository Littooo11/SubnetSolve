<?php
session_start();
$loggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
<div style="min-height:100vh; display:flex; flex-direction:column; align-items:center; padding:3rem 1.5rem;">

    <div class="auth-brand-logo" style="margin-bottom:2rem;">
        <div class="logo">S</div>
        <div>
            <h1>SubNet<span>Solve</span></h1>
            <p>Master Subnetting, Level Up!</p>
        </div>
    </div>

    <div style="width:100%; max-width:720px; background:var(--panel); border:1px solid var(--border); border-radius:var(--radius); padding:2.5rem;">

        <h2 style="margin-top:0;">About SubNetSolve</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            SubNetSolve is a gamified web-based learning platform designed to help students learn and practice IPv4 addressing and subnetting in a more interactive and engaging way. It provides learning modules, interactive subnetting challenges, a binary game, leaderboards, and multiplayer activities that allow users to develop their networking and problem-solving skills while learning at their own pace.
        </p>

        <h2 style="margin-top:2rem;">Privacy and Personal Information</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            SubNetSolve is designed to collect only the information necessary to provide its basic account and platform functions. We do not intentionally collect unnecessary personal information from our users. The primary personal information required for account registration is the user's email address, which may be used for account-related functions such as verification, password recovery, and important system notifications.
        </p>
        <p style="color:var(--text-dim); line-height:1.7;">
            Your email address and account information are handled responsibly and are not intended to be used for unrelated purposes. SubNetSolve is developed as an educational project, with user privacy and responsible handling of information considered throughout the system.
        </p>

        <h2 style="margin-top:2rem;">Our Goal</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            Our goal is to make learning IPv4 subnetting simpler, more interactive, and more enjoyable by combining educational content with game-based activities and practice. Through SubNetSolve, students can <b style="color:var(--text);">Learn, Practice, Play, and Improve</b> their networking skills.
        </p>

        <div style="margin-top:2rem; text-align:center;">
            <?php if ($loggedIn): ?>
                <a href="dashboard.php" class="btn btn-primary" style="text-decoration:none; display:inline-block; padding:0.65rem 1.4rem; border-radius:8px; background:var(--blue); color:#fff;">← Back to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="text-decoration:none; display:inline-block; padding:0.65rem 1.4rem; border-radius:8px; background:var(--blue); color:#fff;">← Back to Log In</a>
            <?php endif; ?>
        </div>
    </div>

    <p style="color:var(--text-dim); font-size:0.8rem; margin-top:2rem;">SubNetSolve — an educational capstone project.</p>
</div>
</body>
</html>