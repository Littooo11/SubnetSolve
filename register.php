<?php
require "config.php";
session_start();

// Already logged in? No need to register again
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
        // check if email already exists
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
                // create a starting progress row for this new user, all zeros
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
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Create an Account</h2>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>