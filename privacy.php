<?php
session_start();
require "config.php";

$loggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy Notice - SubNetSolve</title>
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

    <div style="width:100%; max-width:760px; background:var(--panel); border:1px solid var(--border); border-radius:var(--radius); padding:2.5rem;">

        <h1 style="margin-top:0; font-size:1.8rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">Privacy Policy Notice</h1>
        
        <p style="color:var(--text-dim); line-height:1.7;">
            At <b>SubNetSolve</b>, accessible as an educational web platform, protecting user privacy and handling information responsibly is a top priority. This Privacy Policy Notice outlines the types of information we collect, how it is used, and how we keep your data secure.
        </p>

        <h2 style="margin-top:2rem; font-size:1.25rem;">1. Information We Collect</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            We collect only the minimum amount of personal data required to operate basic account services and track your learning progress:
        </p>
        <ul style="color:var(--text-dim); line-height:1.7; padding-left:1.2rem;">
            <li><b>Account Credentials:</b> Your email address, username, and hashed password created upon registration.</li>
            <li><b>Platform Progress & Gameplay Stats:</b> Experience points (XP), quiz results, completed learning modules, unlocked badges/achievements, and leaderboard scores.</li>
            <li><b>Session Cookies:</b> Functional session cookies used solely to keep you logged in and preserve session state across pages.</li>
        </ul>

        <h2 style="margin-top:2rem; font-size:1.25rem;">2. How We Use Your Information</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            Your personal information is used exclusively for internal platform functionality:
        </p>
        <ul style="color:var(--text-dim); line-height:1.7; padding-left:1.2rem;">
            <li>Authenticating user logins and managing user accounts.</li>
            <li>Displaying progress, streak statistics, and avatar settings on your profile.</li>
            <li>Rending competitive leaderboards and managing multiplayer lobby matches.</li>
            <li>Facilitating account recovery and important technical notifications.</li>
        </ul>

        <h2 style="margin-top:2rem; font-size:1.25rem;">3. Data Protection & Security</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            We employ industry-standard security measures to safeguard your account:
        </p>
        <ul style="color:var(--text-dim); line-height:1.7; padding-left:1.2rem;">
            <li><b>Password Encryption:</b> Passwords are encrypted using strong cryptographic hashing (<code>password_hash()</code>) before storage and are never saved in plain text.</li>
            <li><b>No Third-Party Sharing:</b> We do not sell, trade, rent, or transfer your email address or account details to outside third parties.</li>
        </ul>

        <h2 style="margin-top:2rem; font-size:1.25rem;">4. Managing Your Information</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            You maintain full control over your profile details. You can update your email, username, or password anytime via the <b>Profile</b> page. If you wish to request the deletion of your account and associated stats, please reach out to an administrator.
        </p>

        <h2 style="margin-top:2rem; font-size:1.25rem;">5. Contact & Educational Scope</h2>
        <p style="color:var(--text-dim); line-height:1.7;">
            SubNetSolve is developed as an educational capstone project. If you have any questions or feedback regarding this privacy notice, please contact the system administration, martinezcarlo893@gmail.com.
        </p>

        <div style="margin-top:2.5rem; text-align:center;">
            <?php if ($loggedIn): ?>
                <a href="dashboard.php" class="btn btn-primary" style="text-decoration:none; display:inline-block; padding:0.65rem 1.4rem; border-radius:8px; background:var(--blue); color:#fff;">← Back to Dashboard</a>
            <?php endif; ?>
        </div>

    </div>

    <p style="color:var(--text-dim); font-size:0.8rem; margin-top:2rem;">Last updated: Oct 9th, 2026 — SubNetSolve Educational Project.</p>
</div>
</body>
</html>