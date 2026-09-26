<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";

$myId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_admin_id"])) {
    $targetId = (int) $_POST["toggle_admin_id"];
    if ($targetId !== (int) $myId) { // can't remove your own admin access by accident
        mysqli_query($conn, "UPDATE users SET is_admin = 1 - is_admin WHERE id = $targetId");
    }
    header("Location: users.php");
    exit();
}

$search = trim($_GET["q"] ?? "");
$where = $search !== "" ? "WHERE u.username LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR u.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'" : "";

$result = mysqli_query($conn, "SELECT u.id, u.username, u.email, u.is_admin, u.created_at, up.level, up.career_xp, up.quizzes_completed
    FROM users u LEFT JOIN user_progress up ON up.user_id = u.id
    $where ORDER BY u.created_at DESC LIMIT 200");
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand" style="margin-bottom:1.5rem;">
            <div class="logo">S</div>
            <div><h1>SubNet<span>Solve</span></h1><p>Admin Panel</p></div>
        </div>
        <div class="admin-nav-label">MAIN NAVIGATION</div>
        <a href="dashboard.php" class="admin-nav-link">🏠 Dashboard</a>
        <a href="users.php" class="admin-nav-link active">👥 User Management</a>
        <a href="#" class="admin-nav-link">📘 Learning Modules <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">📋 Question Management <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">🎮 Game Management <span class="soon">Soon</span></a>
        <a href="../lobby.php" class="admin-nav-link">⚔️ 1v1 Multiplayer</a>
        <a href="../leaderboards.php" class="admin-nav-link">🏆 Leaderboard</a>
        <a href="reports.php" class="admin-nav-link">📊 Reports</a>
        <a href="#" class="admin-nav-link">🕓 Activity Logs <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">📣 Announcements <span class="soon">Soon</span></a>
        <a href="#" class="admin-nav-link">⚙️ System Settings <span class="soon">Soon</span></a>
        <div class="admin-nav-label">ACCOUNT</div>
        <a href="../profile.php" class="admin-nav-link">👤 Admin Profile</a>
        <a href="../dashboard.php" class="admin-nav-link">↩ Back to Site</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="margin:0 0 0.3rem;">User Management</h2>
                <p style="color:var(--text-dim); margin:0;"><?= count($users) ?> user<?= count($users) === 1 ? "" : "s" ?> found.</p>
            </div>
            <form method="GET">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search username or email..."
                    style="background:var(--panel); border:1px solid var(--border); color:var(--text); padding:0.5rem 0.9rem; border-radius:8px; font-size:0.85rem; width:260px;">
            </form>
        </div>

        <div class="admin-panel">
            <table class="admin-table">
                <tr><th>Username</th><th>Email</th><th>Joined</th><th>Level</th><th>Career XP</th><th>Games</th><th>Role</th><th></th></tr>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u["username"]) ?></td>
                        <td><?= htmlspecialchars($u["email"]) ?></td>
                        <td><?= date("M j, Y", strtotime($u["created_at"])) ?></td>
                        <td><?= $u["level"] ?? "—" ?></td>
                        <td><?= number_format($u["career_xp"] ?? 0) ?></td>
                        <td><?= $u["quizzes_completed"] ?? 0 ?></td>
                        <td>
                            <?php if ($u["is_admin"]): ?>
                                <span class="status-pill in_progress">Admin</span>
                            <?php else: ?>
                                <span class="status-pill waiting">Student</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $u["id"] !== (int) $myId): ?>
                                <form method="POST" onsubmit="return confirm('<?= $u['is_admin'] ? 'Remove admin access' : 'Grant admin access' ?> for <?= htmlspecialchars($u['username']) ?>?')">
                                    <input type="hidden" name="toggle_admin_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:0.35rem 0.7rem; font-size:0.75rem;">
                                        <?= $u["is_admin"] ? "Remove Admin" : "Make Admin" ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--text-dim); font-size:0.75rem;">(you)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </main>
</div>
</body>
</html>