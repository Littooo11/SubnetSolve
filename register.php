
<?php
require "config.php";
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (($_POST["privacy_agreement"] ?? "") !== "yes") {
        $error = "You must agree to the Privacy Policy Notice before registering.";
    } elseif ($username === "" || $email === "" || $password === "") {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "An account with that email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO users (username, email, password, created_at)
                 VALUES (?, ?, ?, NOW())"
            );
            mysqli_stmt_bind_param(
                $insert,
                "sss",
                $username,
                $email,
                $hashed
            );

            if (mysqli_stmt_execute($insert)) {
                $newUserId = mysqli_insert_id($conn);

                $progressInsert = mysqli_prepare(
                    $conn,
                    "INSERT INTO user_progress (user_id) VALUES (?)"
                );
                mysqli_stmt_bind_param(
                    $progressInsert,
                    "i",
                    $newUserId
                );
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SubNetSolve</title>

    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="auth.css">

    <style>
        .privacy-agreement {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 18px 0;
            font-size: 13px;
            line-height: 1.5;
            color: #cbd5e1;
        }

        .privacy-agreement input[type="checkbox"] {
            margin-top: 3px;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: #3b82f6;
            width: 16px;
            height: 16px;
        }

        .privacy-agreement label {
            cursor: pointer;
        }

        .privacy-agreement a {
            color: #60a5fa;
            text-decoration: underline;
        }

        .privacy-agreement a:hover {
            color: #93c5fd;
        }

        /* Privacy agreement popup */
        .privacy-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .privacy-modal.show {
            display: flex;
        }

        .privacy-modal-content {
            width: 100%;
            max-width: 400px;
            padding: 28px;
            border-radius: 14px;
            background: #111827;
            color: #f9fafb;
            border: 1px solid #374151;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        .privacy-modal-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }

        .privacy-modal-content h3 {
            margin: 0 0 10px;
            font-size: 20px;
        }

        .privacy-modal-content p {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .privacy-modal-content button {
            width: 100%;
            padding: 11px 16px;
            border: none;
            border-radius: 8px;
            background: #3b82f6;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .privacy-modal-content button:hover {
            background: #2563eb;
        }
    </style>
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

        <p class="tagline">
            Create your account and get access to every practice mode,
            timed challenge, and live match — free to play, start to finish.
        </p>

        <div class="auth-feature-list">
            <div class="item">
                <span class="dot"
                    style="background:rgba(34,197,94,0.15); color:var(--green);">
                    🌐
                </span>
                Dissect an IP Address — network, mask, host range
            </div>

            <div class="item">
                <span class="dot"
                    style="background:rgba(59,130,246,0.15); color:var(--blue);">
                    01
                </span>
                Binary Game — sharpen your conversions
            </div>

            <div class="item">
                <span class="dot"
                    style="background:rgba(249,115,22,0.15); color:var(--orange);">
                    🎯
                </span>
                Subnet Showdown — multiple-choice quiz battles
            </div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">

            <h2>Create an Account</h2>
            <p class="sub">Sign up to start tracking your progress.</p>

            <?php if ($error): ?>
                <div class="auth-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registerForm">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="NetMaster07"
                        required
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 6 characters"
                        required
                        minlength="6"
                    >
                </div>

                <!-- Privacy Policy Agreement -->
                <div class="privacy-agreement">
                    <input
                        type="checkbox"
                        id="privacy_agreement"
                        name="privacy_agreement"
                        value="yes"
                        <?= (($_POST['privacy_agreement'] ?? '') === 'yes') ? 'checked' : '' ?>
                    >

                    <label for="privacy_agreement">
                        I agree to the
                        <a
                            href="privacy.php"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Privacy Policy Notice</a>.
                    </label>
                </div>

                <!-- Register button remains clickable -->
                <button
                    type="submit"
                    class="auth-submit"
                    id="registerButton"
                >
                    Register
                </button>

            </form>

            <p class="auth-footer">
                Already have an account?
                <a href="login.php">Log in</a>
            </p>

            <p class="auth-footer" style="margin-top:0.5rem;">
                <a href="about.php">About Us</a>
            </p>

        </div>
    </div>
</div>

<!-- Privacy Agreement Popup -->
<div
    class="privacy-modal"
    id="privacyModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="privacyModalTitle"
    aria-describedby="privacyModalMessage"
>
    <div class="privacy-modal-content">

        <div class="privacy-modal-icon">🔒</div>

        <h3 id="privacyModalTitle">Privacy Agreement Required</h3>

        <p id="privacyModalMessage">
            Please check the box to agree to the Privacy Policy Notice
            before registering for SubNetSolve.
        </p>

        <button type="button" id="closePrivacyModal">
            Okay, I Understand
        </button>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");
    const checkbox = document.getElementById("privacy_agreement");
    const modal = document.getElementById("privacyModal");
    const closeButton = document.getElementById("closePrivacyModal");

    function openPrivacyModal() {
        modal.classList.add("show");
        closeButton.focus();
    }

    function closePrivacyModal() {
        modal.classList.remove("show");
    }

    form.addEventListener("submit", function (event) {
        if (!checkbox.checked) {
            event.preventDefault();
            openPrivacyModal();
        }
    });

    closeButton.addEventListener("click", function () {
        closePrivacyModal();
        checkbox.focus();
    });

    // Close the popup when clicking the dark background.
    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            closePrivacyModal();
        }
    });

    // Allow the Escape key to close the popup.
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal.classList.contains("show")) {
            closePrivacyModal();
        }
    });
});
</script>

</body>
</html>