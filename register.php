<?php
require "config.php";
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($username === "" || $email === "" || $password === "") {
        $error = "All fields are required.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "An account with that email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_prepare($conn, "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            mysqli_stmt_bind_param($insert, "sss", $username, $email, $hashed);

            if (mysqli_stmt_execute($insert)) {
                $newUserId = mysqli_insert_id($conn);
                $progressInsert = mysqli_prepare($conn, "INSERT INTO user_progress (user_id) VALUES (?)");
                mysqli_stmt_bind_param($progressInsert, "i", $newUserId);
                mysqli_stmt_execute($progressInsert);

                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
<div class="auth-layout">
    <div class="auth-brand-panel">
        <div class="auth-brand-logo">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Master Subnetting, Level Up!</p>
            </div>
        </div>

        <h2>Start mastering IPv4, one game at a time.</h2>
        <p class="tagline">Create your account and get access to every practice mode, timed challenge, and live match — free to play, start to finish.</p>

        <div class="auth-feature-list">
            <div class="item"><span class="dot" style="background:rgba(34,197,94,0.15); color:var(--green);">🌐</span> Dissect an IP Address — network, mask, host range</div>
            <div class="item"><span class="dot" style="background:rgba(59,130,246,0.15); color:var(--blue);">01</span> Binary Game — sharpen your conversions</div>
            <div class="item"><span class="dot" style="background:rgba(249,115,22,0.15); color:var(--orange);">🎯</span> Subnet Showdown — multiple-choice quiz battles</div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <h2>Create an Account</h2>
            <p class="sub">Sign up to start tracking your progress.</p>

            <?php if ($error): ?>
                <div class="auth-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="NetMaster07" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="At least 6 characters" required minlength="6">
                </div>

                <button type="submit" class="auth-submit">Register</button>
            </form>

            <p class="auth-footer">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
</div>
</body>
</html>