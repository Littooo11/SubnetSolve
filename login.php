<?php
require "config.php";
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = mysqli_prepare($conn, "SELECT id, username, password, is_admin FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["is_admin"] = (bool) $user["is_admin"];

        if (isset($_POST["remember"])) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+30 days"));

            $insertToken = mysqli_prepare($conn, "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insertToken, "iss", $user["id"], $token, $expires);
            mysqli_stmt_execute($insertToken);

            setcookie("remember_token", $token, time() + (30 * 24 * 60 * 60), "/");
        }

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In - SubNetSolve</title>
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

        <h2>Welcome back, network builder.</h2>
        <p class="tagline">Jump back into subnetting challenges, binary drills, and live 1v1 matches — pick up right where you left off.</p>

        <div class="auth-feature-list">
            <div class="item"><span class="dot" style="background:rgba(59,130,246,0.15); color:var(--blue);">🎯</span> Practice at your own pace, or race the clock</div>
            <div class="item"><span class="dot" style="background:rgba(139,92,246,0.15); color:var(--purple);">⚔️</span> Challenge friends in live 1v1 or group matches</div>
            <div class="item"><span class="dot" style="background:rgba(234,179,8,0.15); color:var(--gold);">🏆</span> Earn XP, level up, and unlock badges</div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <h2>Log In</h2>
            <p class="sub">Enter your details to access your account.</p>

            <?php if ($error): ?>
                <div class="auth-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <label class="auth-remember">
                    <input type="checkbox" name="remember"> Remember me for 30 days
                </label>

                <button type="submit" class="auth-submit">Log In</button>
            </form>

            <p class="auth-footer">Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>
</body>
</html>