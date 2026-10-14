<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";

// Handle Application Approval (Post/Redirect/Get)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["approve_app_id"])) {
    $appId = (int)$_POST["approve_app_id"];

    // 1. Fetch application details
    $stmt = mysqli_prepare($conn, "SELECT ta.*, u.username FROM teacher_applications ta JOIN users u ON u.id = ta.user_id WHERE ta.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appId);
    mysqli_stmt_execute($stmt);
    $app = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if ($app) {
        $teacherId = $app["user_id"];

        // 2. Mark user as an approved teacher
        $updateUser = mysqli_prepare($conn, "UPDATE users SET is_teacher = 1, teacher_status = 'approved' WHERE id = ?");
        mysqli_stmt_bind_param($updateUser, "i", $teacherId);
        mysqli_stmt_execute($updateUser);

        // 3. Send invitations to listed student emails
        $studentEmails = array_map('trim', explode(',', $app["student_emails"]));
        $invitedCount = 0;

        foreach ($studentEmails as $email) {
            if ($email === "") continue;
            $findStudent = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($findStudent, "s", $email);
            mysqli_stmt_execute($findStudent);
            $student = mysqli_stmt_get_result($findStudent)->fetch_assoc();

            if ($student) {
                $studentId = $student["id"];

                // Insert invitation for the student (does not automatically link until student accepts)
                $invStmt = mysqli_prepare($conn, "
                    INSERT INTO teacher_invitations (teacher_id, student_id, status, created_at)
                    VALUES (?, ?, 'pending', NOW())
                    ON DUPLICATE KEY UPDATE status = 'pending', created_at = NOW()
                ");
                mysqli_stmt_bind_param($invStmt, "ii", $teacherId, $studentId);
                if (mysqli_stmt_execute($invStmt)) {
                    $invitedCount++;
                }
            }
        }

        // 4. Update application status
        $updateApp = mysqli_prepare($conn, "UPDATE teacher_applications SET status = 'approved' WHERE id = ?");
        mysqli_stmt_bind_param($updateApp, "i", $appId);
        mysqli_stmt_execute($updateApp);

        $_SESSION["msg"] = "Approved teacher account for {$app['username']}. Sent invitations to {$invitedCount} student account(s).";
        $_SESSION["msg_type"] = "good";
    }

    header("Location: teacher_applications.php");
    exit();
}

// Handle Application Rejection (Post/Redirect/Get)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reject_app_id"])) {
    $appId = (int)$_POST["reject_app_id"];
    
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM teacher_applications WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appId);
    mysqli_stmt_execute($stmt);
    $app = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if ($app) {
        $updateUser = mysqli_prepare($conn, "UPDATE users SET teacher_status = 'rejected' WHERE id = ?");
        mysqli_stmt_bind_param($updateUser, "i", $app["user_id"]);
        mysqli_stmt_execute($updateUser);

        $updateApp = mysqli_prepare($conn, "UPDATE teacher_applications SET status = 'rejected' WHERE id = ?");
        mysqli_stmt_bind_param($updateApp, "i", $appId);
        mysqli_stmt_execute($updateApp);

        $_SESSION["msg"] = "Application rejected.";
        $_SESSION["msg_type"] = "bad";
    }

    header("Location: teacher_applications.php");
    exit();
}

// Retrieve session flash message and clear it
$message = $_SESSION["msg"] ?? "";
$messageType = $_SESSION["msg_type"] ?? "";
unset($_SESSION["msg"], $_SESSION["msg_type"]);

// Fetch pending applications
$pendingApps = mysqli_fetch_all(mysqli_query($conn, "
    SELECT ta.*, u.username, u.email 
    FROM teacher_applications ta 
    JOIN users u ON u.id = ta.user_id 
    WHERE ta.status = 'pending' 
    ORDER BY ta.applied_at DESC
"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Applications - Admin Panel</title>
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
        <a href="users.php" class="admin-nav-link">👥 User Management</a>
        <a href="manage_questions.php" class="admin-nav-link">❓ Manage Questions</a>
        <a href="game_popularity.php" class="admin-nav-link">🎮 Game Category Popularity</a>
        <a href="teacher_applications.php" class="admin-nav-link active">🎓 Teacher Apps</a>
        <div class="admin-nav-label">ACCOUNT</div>
        <a href="../profile.php" class="admin-nav-link">👤 Admin Profile</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="margin:0 0 0.3rem;">Teacher Applications</h2>
                <p style="color:var(--text-dim); margin:0;">Review pending applications and send class invitations to listed students.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="feedback-banner <?= $messageType === 'good' ? 'feedback-correct' : 'feedback-wrong' ?>" style="margin-bottom:1rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="admin-panel">
            <?php if (empty($pendingApps)): ?>
                <p style="color:var(--text-dim);">No pending teacher applications right now.</p>
            <?php else: ?>
                <table class="admin-table">
                    <tr>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Listed Student Emails</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($pendingApps as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app["username"]) ?></strong></td>
                        <td><?= htmlspecialchars($app["email"]) ?></td>
                        <td style="max-width:300px; word-break:break-word; font-size:0.8rem; color:var(--text-dim);">
                            <?= htmlspecialchars($app["student_emails"]) ?>
                        </td>
                        <td><?= date("M j, Y, g:i A", strtotime($app["applied_at"])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="approve_app_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="padding:0.25rem 0.6rem; font-size:0.75rem;">Approve & Send Invites</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this application?')">
                                <input type="hidden" name="reject_app_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn" style="padding:0.25rem 0.6rem; font-size:0.75rem; background:#ef4444; color:#fff;">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>