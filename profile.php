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

$errors = [];
$success = "";

function load_user($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT username, email, avatar, created_at FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->fetch_assoc();
}
$user = load_user($conn, $userId);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername = trim($_POST["username"] ?? "");
    $newEmail    = trim($_POST["email"] ?? "");
    $newAvatar   = $_POST["avatar"] ?? $user["avatar"];
    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword      = $_POST["new_password"] ?? "";

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
        $success = "Profile updated successfully.";
        $user = load_user($conn, $userId);
    }
}

// Stats
$progStmt = mysqli_prepare($conn, "SELECT career_xp, quizzes_completed, total_correct, total_wrong, current_streak FROM user_progress WHERE user_id = ?");
mysqli_stmt_bind_param($progStmt, "i", $userId);
mysqli_stmt_execute($progStmt);
$prog = mysqli_stmt_get_result($progStmt)->fetch_assoc() ?: ["career_xp"=>0,"quizzes_completed"=>0,"total_correct"=>0,"total_wrong"=>0,"current_streak"=>0];

$totalAnswers = $prog["total_correct"] + $prog["total_wrong"];
$correctPct = $totalAnswers > 0 ? round(($prog["total_correct"] / $totalAnswers) * 100) : 0;

// Recent activity
$showAll = isset($_GET["activity"]) && $_GET["activity"] === "all";
$limit = $showAll ? 20 : 5;
$activityResult = mysqli_query($conn, "SELECT game_type, points, played_at FROM scores WHERE user_id = $userId ORDER BY played_at DESC LIMIT $limit");

$gameLabels = [
    "subnet_dissect_practice" => ["Dissect an IP Address (Practice)", "🌐"],
    "subnet_dissect_timed"    => ["Dissect an IP Address (Timed)", "🌐"],
    "binary_practice"         => ["Binary Game (Practice)", "01"],
    "binary_game"             => ["Binary Game (Timed)", "01"],
    "showdown_practice"       => ["Subnet Showdown (Practice)", "🎯"],
    "showdown_timed"          => ["Subnet Showdown", "🎯"],
    "multiplayer_1v1"         => ["1v1 Multiplayer Match", "⚔️"],
];

// Achievement preview (first 6 by requirement level)
$badgesResult = mysqli_query($conn, "SELECT * FROM badges ORDER BY criteria_value LIMIT 6");
$earnedStmt = mysqli_prepare($conn, "SELECT badge_id FROM user_badges WHERE user_id = ?");
mysqli_stmt_bind_param($earnedStmt, "i", $userId);
mysqli_stmt_execute($earnedStmt);
$earnedIds = array_column(mysqli_stmt_get_result($earnedStmt)->fetch_all(MYSQLI_ASSOC), "badge_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="layout">
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
        <a href="profile.php" class="nav-link active">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Profile</h2>
            <p>View and manage your account information, stats, and achievements.</p>
        </div>

        <?php if ($success): ?>
            <div class="feedback-banner feedback-correct" style="margin-bottom:1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
            <div class="feedback-banner feedback-wrong" style="margin-bottom:0.6rem;"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <div class="profile-hero">
            <div class="profile-hero-left">
                <?= render_avatar($user["avatar"], 90) ?>
                <div>
                    <div class="name-row">
                        <h2><?= htmlspecialchars($user["username"]) ?></h2>
                        <span class="role-tag">Student</span>
                    </div>
                    <div class="email"><?= htmlspecialchars($user["email"]) ?></div>
                    <div class="member-since">Member since: <?= date("M j, Y", strtotime($user["created_at"])) ?></div>
                </div>
            </div>
            <button class="btn btn-secondary" id="editToggleBtn1" type="button">✏️ Edit Profile</button>
        </div>

        <div class="stat-grid-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(234,179,8,0.15); color:var(--gold);">⭐</div>
                <div><div class="value"><?= number_format($prog["career_xp"]) ?></div><div class="label">Total Score</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.15); color:var(--blue);">🏆</div>
                <div><div class="value"><?= $prog["quizzes_completed"] ?></div><div class="label">Games Played</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(34,197,94,0.15); color:var(--green);">✓</div>
                <div><div class="value"><?= $correctPct ?>%</div><div class="label">Correct Answers</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(249,115,22,0.15); color:var(--orange);">🔥</div>
                <div><div class="value"><?= $prog["current_streak"] ?></div><div class="label">Current Streak</div></div>
            </div>
        </div>

        <div class="explore-panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
                <h3 class="section-title" style="margin:0;">🕓 Recent Activity</h3>
                <?php if (!$showAll): ?>
                    <a href="profile.php?activity=all" style="font-size:0.8rem; color:var(--blue); text-decoration:none;">View All →</a>
                <?php else: ?>
                    <a href="profile.php" style="font-size:0.8rem; color:var(--blue); text-decoration:none;">Show Less</a>
                <?php endif; ?>
            </div>

            <?php if (mysqli_num_rows($activityResult) === 0): ?>
                <p style="color:var(--text-dim); font-size:0.85rem;">No activity yet — play a game to see it here.</p>
            <?php else: ?>
                <?php while ($a = mysqli_fetch_assoc($activityResult)):
                    $meta = $gameLabels[$a["game_type"]] ?? [$a["game_type"], "🎮"];
                ?>
                    <div class="activity-list-row">
                        <div class="icon-box" style="background:rgba(59,130,246,0.15); color:var(--blue);"><?= $meta[1] ?></div>
                        <div>
                            <div class="title"><?= htmlspecialchars($meta[0]) ?></div>
                            <div class="time"><?= date("M j, Y • g:i A", strtotime($a["played_at"])) ?></div>
                        </div>
                        <div class="points">+<?= $a["points"] ?> points</div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>

    <aside class="right-col">
        <div class="panel-box">
            <h4>👤 Profile Information</h4>
            <div class="stat-line"><span>Username</span><span><?= htmlspecialchars($user["username"]) ?></span></div>
            <div class="stat-line"><span>Email</span><span><?= htmlspecialchars($user["email"]) ?></span></div>
            <div class="stat-line"><span>Role</span><span>Student</span></div>
            <button class="btn btn-primary" id="editToggleBtn2" type="button" style="width:100%; margin-top:0.5rem;">Edit Profile</button>
        </div>

        <div class="panel-box">
            <h4>🏆 Achievements <a href="achievements.php" style="font-size:0.75rem; color:var(--blue); text-decoration:none;">View All</a></h4>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.6rem;">
                <?php while ($b = mysqli_fetch_assoc($badgesResult)):
                    $earned = in_array($b["id"], $earnedIds);
                ?>
                    <div style="text-align:center; opacity:<?= $earned ? "1" : "0.35" ?>;">
                        <div style="font-size:1.6rem;"><?= $b["icon"] ?></div>
                        <div style="font-size:0.68rem; color:var(--text-dim); line-height:1.2;"><?= htmlspecialchars($b["name"]) ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </aside>
</div>

<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:50; align-items:center; justify-content:center;">
    <div style="background:var(--panel); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; width:420px; max-width:90vw; max-height:85vh; overflow-y:auto;">
        <h3 style="margin-top:0;">Edit Profile</h3>
        <form method="POST" class="profile-form">
            <div class="form-group">
                <label>Avatar</label>
                <div class="avatar-picker" id="avatarPicker">
                    <?php foreach (get_avatar_presets() as $a): ?>
                        <div class="avatar-option <?= $a["id"] === $user["avatar"] ? "selected" : "" ?>"
                             data-id="<?= $a["id"] ?>"
                             style="background:<?= $a["color"] ?>22; border-color:<?= $a["id"] === $user["avatar"] ? $a["color"] : "transparent" ?>;">
                            <?= $a["emoji"] ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="avatar" id="avatarInput" value="<?= htmlspecialchars($user["avatar"]) ?>">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user["username"]) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user["email"]) ?>" required>
            </div>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Required only if changing your password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Leave blank to keep current password">
                <small>Minimum 6 characters.</small>
            </div>
            <div class="game-actions">
                <button type="button" class="btn btn-secondary" id="closeModalBtn">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById("editModal");
function openModal() { modal.style.display = "flex"; }
function closeModal() { modal.style.display = "none"; }

document.getElementById("editToggleBtn1").addEventListener("click", openModal);
document.getElementById("editToggleBtn2").addEventListener("click", openModal);
document.getElementById("closeModalBtn").addEventListener("click", closeModal);
modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });

<?php if (!empty($errors)): ?>
openModal();
<?php endif; ?>

document.querySelectorAll(".avatar-option").forEach(opt => {
    opt.addEventListener("click", () => {
        document.querySelectorAll(".avatar-option").forEach(o => {
            o.classList.remove("selected");
            o.style.borderColor = "transparent";
        });
        opt.classList.add("selected");
        opt.style.borderColor = opt.style.background.replace("22", "");
        document.getElementById("avatarInput").value = opt.dataset.id;
    });
});
</script>
</body>
</html>