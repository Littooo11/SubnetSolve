<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";
require "../includes/avatars.php";

$userId = $_SESSION["user_id"];

$errors = [];
$success = "";

function load_admin_user($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT username, email, avatar, is_admin, created_at FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->fetch_assoc();
}
$user = load_admin_user($conn, $userId);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername     = trim($_POST["username"] ?? "");
    $newEmail        = trim($_POST["email"] ?? "");
    $newAvatar       = $_POST["avatar"] ?? $user["avatar"];
    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword     = $_POST["new_password"] ?? "";

    $validAvatarIds = array_column(get_avatar_presets(), "id");
    if (!in_array($newAvatar, $validAvatarIds)) $newAvatar = $user["avatar"];

    if ($newUsername === "" || $newEmail === "") {
        $errors[] = "Username and email cannot be empty.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($check, "si", $newEmail, $userId);
        mysqli_stmt_execute($check);
        if (mysqli_stmt_get_result($check)->fetch_assoc()) {
            $errors[] = "That email is already in use by another account.";
        }
    }

    $updatingPassword = $newPassword !== "";
    if ($updatingPassword) {
        $pwCheck = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($pwCheck, "i", $userId);
        mysqli_stmt_execute($pwCheck);
        $row = mysqli_stmt_get_result($pwCheck)->fetch_assoc();

        if (!password_verify($currentPassword, $row["password"])) {
            $errors[] = "Current password is incorrect, so the password wasn't changed.";
            $updatingPassword = false;
        } elseif (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters.";
            $updatingPassword = false;
        }
    }

    if (empty($errors)) {
        if ($updatingPassword) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = mysqli_prepare($conn, "UPDATE users SET username=?, email=?, avatar=?, password=? WHERE id=?");
            mysqli_stmt_bind_param($update, "ssssi", $newUsername, $newEmail, $newAvatar, $hashed, $userId);
        } else {
            $update = mysqli_prepare($conn, "UPDATE users SET username=?, email=?, avatar=? WHERE id=?");
            mysqli_stmt_bind_param($update, "sssi", $newUsername, $newEmail, $newAvatar, $userId);
        }
        mysqli_stmt_execute($update);

        $_SESSION["username"] = $newUsername;
        $success = "Admin profile updated successfully.";
        $user = load_admin_user($conn, $userId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand" style="margin-bottom:1.5rem;">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Admin Panel</p>
            </div>
        </div>

        <div class="admin-nav-label">MAIN NAVIGATION</div>
        <a href="dashboard.php" class="admin-nav-link">🏠 Dashboard</a>
        <a href="users.php" class="admin-nav-link">👥 User Management</a>
        <a href="manage_questions.php" class="admin-nav-link">❓ Manage Questions</a>
        <a href="game_popularity.php" class="admin-nav-link">🎮 Game Category Popularity</a>
        <a href="teacher_applications.php" class="admin-nav-link">🎓 Teacher Apps</a>


        <div class="admin-nav-label">ACCOUNT</div>
        <a href="profile.php" class="admin-nav-link active">👤 Admin Profile</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>

        <div class="admin-sidebar-footer">
            <div class="brand-mini">SubNet<span style="color:var(--blue);">Solve</span></div>
            <p>Learn · Practice · Compete</p>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="margin:0 0 0.3rem;">Admin Profile</h2>
                <p style="color:var(--text-dim); margin:0;">Manage your administrator account credentials and preferences.</p>
            </div>
            <div class="admin-date-box">
                <b><?= date("M j, Y") ?></b>
                <?= date("g:i A") ?>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="feedback-banner feedback-correct" style="margin-bottom:1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
            <div class="feedback-banner feedback-wrong" style="margin-bottom:0.6rem;"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <div class="admin-panels-row" style="grid-template-columns: 320px 1fr;">
            <!-- Profile Details Card -->
            <div class="admin-panel" style="text-align: center;">
                <div style="margin-bottom: 1rem;">
                    <?= render_avatar($user["avatar"], 100) ?>
                </div>
                <h3 style="margin: 0.5rem 0 0.2rem;"><?= htmlspecialchars($user["username"]) ?></h3>
                <span class="status-pill in_progress">Administrator</span>
                
                <div style="text-align: left; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <div class="stat-line"><span>Email:</span> <b><?= htmlspecialchars($user["email"]) ?></b></div>
                    <div class="stat-line"><span>Account Type:</span> <b>Admin</b></div>
                    <div class="stat-line"><span>Member Since:</span> <b><?= date("M j, Y", strtotime($user["created_at"])) ?></b></div>
                </div>
            </div>

            <!-- Edit Information & Password Form -->
            <div class="admin-panel">
                <h3>✏️ Edit Account Credentials</h3>
                <form method="POST" style="margin-top: 1rem;">
                    <div class="form-group" style="margin-bottom: 1.2rem;">
                        <label style="display:block; margin-bottom:0.4rem; font-weight:bold;">Avatar Preset</label>
                        <div class="avatar-picker" id="avatarPicker" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <?php foreach (get_avatar_presets() as $a): ?>
                                <div class="avatar-option <?= $a["id"] === $user["avatar"] ? "selected" : "" ?>"
                                     data-id="<?= $a["id"] ?>"
                                     style="padding:0.5rem; border:2px solid <?= $a["id"] === $user["avatar"] ? $a["color"] : "transparent" ?>; background:<?= $a["color"] ?>22; border-radius:8px; cursor:pointer; font-size:1.5rem;">
                                    <?= $a["emoji"] ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="avatar" id="avatarInput" value="<?= htmlspecialchars($user["avatar"]) ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:bold;">Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user["username"]) ?>" required style="width:100%; padding:0.6rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem; font-weight:bold;">Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user["email"]) ?>" required style="width:100%; padding:0.6rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px;">
                    </div>

                    <hr style="border-color: var(--border); margin: 1.5rem 0;">

                    <h4 style="margin:0 0 1rem;">Change Password</h4>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom:0.3rem;">Current Password</label>
                        <input type="password" name="current_password" placeholder="Required only if changing password" style="width:100%; padding:0.6rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="display:block; margin-bottom:0.3rem;">New Password</label>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current password" style="width:100%; padding:0.6rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px;">
                        <small style="color:var(--text-dim);">Minimum 6 characters.</small>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding:0.7rem 1.5rem;">Save Admin Profile</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
document.querySelectorAll(".avatar-option").forEach(opt => {
    opt.addEventListener("click", () => {
        document.querySelectorAll(".avatar-option").forEach(o => {
            o.classList.remove("selected");
            o.style.borderColor = "transparent";
        });
        opt.classList.add("selected");
        opt.style.borderColor = "#3b82f6";
        document.getElementById("avatarInput").value = opt.dataset.id;
    });
});
</script>
</body>
</html>