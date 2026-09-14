<?php
require "config.php";
session_start();

// Already logged in? Skip the login form and go straight to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["username"] = $user["username"];

        // "Remember me" — create a long-lived token if checked
        if (isset($_POST["remember"])) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+30 days"));

            $insertToken = mysqli_prepare($conn, "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insertToken, "iss", $user["id"], $token, $expires);
            mysqli_stmt_execute($insertToken);

            // cookie lasts 30 days, survives browser close
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
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Log In</h2>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <label style="display:block; font-size:0.85rem; margin:0.5rem 0;">
                <input type="checkbox" name="remember" style="width:auto;"> Remember me
            </label>
            <button type="submit">Log In</button>
        </form>

        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>