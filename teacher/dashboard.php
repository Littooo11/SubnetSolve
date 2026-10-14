<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/avatars.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}
$teacherId = $_SESSION["user_id"];

// Verify teacher access
$checkTeacher = mysqli_prepare($conn, "SELECT is_teacher FROM users WHERE id = ?");
mysqli_stmt_bind_param($checkTeacher, "i", $teacherId);
mysqli_stmt_execute($checkTeacher);
$isTeacher = (bool) mysqli_stmt_get_result($checkTeacher)->fetch_assoc()["is_teacher"];

if (!$isTeacher) {
    header("Location: ../dashboard.php?error=" . urlencode("Access restricted to approved Teacher accounts."));
    exit();
}

// ===== Handle Inviting Student (Post/Redirect/Get) =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["invite_student"])) {
    $inviteEmail = trim($_POST["student_email"] ?? "");

    if (empty($inviteEmail)) {
        $_SESSION["msg"] = "Please provide a valid student email address.";
        $_SESSION["msg_type"] = "bad";
    } else {
        // Find student account
        $findStudent = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($findStudent, "s", $inviteEmail);
        mysqli_stmt_execute($findStudent);
        $student = mysqli_stmt_get_result($findStudent)->fetch_assoc();

        if (!$student) {
            $_SESSION["msg"] = "No registered user found with that email address.";
            $_SESSION["msg_type"] = "bad";
        } else {
            $studentId = $student["id"];

            // Check if already on roster
            $checkRoster = mysqli_prepare($conn, "SELECT id FROM teacher_students WHERE teacher_id = ? AND student_id = ?");
            mysqli_stmt_bind_param($checkRoster, "ii", $teacherId, $studentId);
            mysqli_stmt_execute($checkRoster);
            if (mysqli_stmt_get_result($checkRoster)->fetch_assoc()) {
                $_SESSION["msg"] = "This student is already on your roster.";
                $_SESSION["msg_type"] = "bad";
            } else {
                // Send/reset invitation
                $invStmt = mysqli_prepare($conn, "
                    INSERT INTO teacher_invitations (teacher_id, student_id, status, created_at)
                    VALUES (?, ?, 'pending', NOW())
                    ON DUPLICATE KEY UPDATE status = 'pending', created_at = NOW()
                ");
                mysqli_stmt_bind_param($invStmt, "ii", $teacherId, $studentId);
                mysqli_stmt_execute($invStmt);

                $_SESSION["msg"] = "Invitation sent to {$inviteEmail}. The student must accept it on their dashboard.";
                $_SESSION["msg_type"] = "good";
            }
        }
    }
    header("Location: dashboard.php");
    exit();
}

// ===== Handle Removing Student (Post/Redirect/Get) =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_student_id"])) {
    $removeStudentId = (int) $_POST["remove_student_id"];

    // Delete pairing and invitation records
    $delPair = mysqli_prepare($conn, "DELETE FROM teacher_students WHERE teacher_id = ? AND student_id = ?");
    mysqli_stmt_bind_param($delPair, "ii", $teacherId, $removeStudentId);
    mysqli_stmt_execute($delPair);

    $delInv = mysqli_prepare($conn, "DELETE FROM teacher_invitations WHERE teacher_id = ? AND student_id = ?");
    mysqli_stmt_bind_param($delInv, "ii", $teacherId, $removeStudentId);
    mysqli_stmt_execute($delInv);

    $_SESSION["msg"] = "Student removed from your roster.";
    $_SESSION["msg_type"] = "good";

    header("Location: dashboard.php");
    exit();
}

// Read and clear flash messages from session
$message = $_SESSION["msg"] ?? "";
$messageType = $_SESSION["msg_type"] ?? "";
unset($_SESSION["msg"], $_SESSION["msg_type"]);

// Fetch AJAX details for a specific student's question breakdown
if (isset($_GET["fetch_student_questions"])) {
    header("Content-Type: application/json");
    $studentId = (int)$_GET["fetch_student_questions"];

    $qStmt = mysqli_prepare($conn, "
        SELECT gq.game_type, gq.question_text, uqs.times_encountered, uqs.times_correct, uqs.last_answered_at
        FROM user_question_stats uqs
        JOIN game_questions gq ON gq.id = uqs.question_id
        WHERE uqs.user_id = ?
        ORDER BY uqs.last_answered_at DESC
    ");
    mysqli_stmt_bind_param($qStmt, "i", $studentId);
    mysqli_stmt_execute($qStmt);
    echo json_encode(mysqli_fetch_all(mysqli_stmt_get_result($qStmt), MYSQLI_ASSOC));
    exit();
}

// Fetch assigned students
$stmt = mysqli_prepare($conn, "
    SELECT u.id, u.username, u.email, up.level, up.career_xp, up.total_correct, up.total_wrong,
           (SELECT COUNT(DISTINCT module_key) FROM user_module_progress WHERE user_id = u.id) AS modules_completed
    FROM teacher_students ts
    JOIN users u ON u.id = ts.student_id
    LEFT JOIN user_progress up ON up.user_id = u.id
    WHERE ts.teacher_id = ?
    ORDER BY u.username ASC
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$students = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
    <style>
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 100; align-items: center; justify-content: center;
        }
        .modal-content {
            background: var(--panel); border: 1px solid var(--border); border-radius: 12px;
            padding: 1.5rem; width: 90%; max-width: 750px; color: var(--text);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand" style="margin-bottom:1.5rem;">
            <div class="logo">S</div>
            <div><h1>SubNet<span>Solve</span></h1><p>Teacher Portal</p></div>
        </div>
        <div class="admin-nav-label">TEACHER MENU</div>
        <a href="dashboard.php" class="admin-nav-link active">👥 Assigned Students</a>
        <div class="admin-nav-label">ACCOUNT</div>
        <a href="../settings.php" class="admin-nav-link">⚙ Settings</a>
        <a href="../dashboard.php" class="admin-nav-link">↩ Back to Site</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2>My Students</h2>
                <p style="color:var(--text-dim); margin:0;"><?= count($students) ?> assigned student(s).</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="feedback-banner <?= $messageType === 'good' ? 'feedback-correct' : 'feedback-wrong' ?>" style="margin-bottom:1rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Invite Student Section -->
        <div class="admin-panel" style="margin-bottom:1.5rem;">
            <h3>➕ Invite Student</h3>
            <p style="font-size:0.85rem; color:var(--text-dim); margin-top:0.2rem; margin-bottom:0.8rem;">
                Enter a registered student's email address to send them a class invitation. An invitation box will appear on their dashboard.
            </p>
            <form method="POST" style="display:flex; gap:0.6rem; max-width:550px;">
                <input type="email" name="student_email" placeholder="student@example.com" required 
                       style="flex:1; padding:0.5rem 0.8rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px; font-size:0.88rem;">
                <button type="submit" name="invite_student" class="btn btn-primary">Send Invitation</button>
            </form>
        </div>

        <div class="admin-panel">
            <?php if (empty($students)): ?>
                <p style="color:var(--text-dim);">No students currently associated with your account.</p>
            <?php else: ?>
                <table class="admin-table">
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Level / XP</th>
                        <th>Learning Modules</th>
                        <th>Overall Accuracy</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($students as $s): 
                        $correct = (int)($s["total_correct"] ?? 0);
                        $wrong = (int)($s["total_wrong"] ?? 0);
                        $tot = $correct + $wrong;
                        $acc = $tot > 0 ? round(($correct / $tot) * 100) : 0;
                        $modPct = min(100, ($s["modules_completed"] ?? 0) * 20);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s["username"]) ?></strong></td>
                        <td><?= htmlspecialchars($s["email"]) ?></td>
                        <td>Lvl <?= $s["level"] ?? 1 ?> (<?= number_format($s["career_xp"] ?? 0) ?> XP)</td>
                        <td>
                            <span style="color:var(--blue); font-weight:bold;"><?= $modPct ?>%</span> 
                            (<?= $s["modules_completed"] ?>/5 completed)
                        </td>
                        <td>
                            <strong style="color:var(--green);"><?= $acc ?>%</strong> 
                            (<?= $correct ?> / <?= $tot ?>)
                        </td>
                        <td>
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                <button onclick="viewStudentBreakdown(<?= $s['id'] ?>, '<?= htmlspecialchars($s['username'], ENT_QUOTES) ?>')" class="btn btn-secondary" style="padding:0.25rem 0.6rem; font-size:0.75rem;">
                                    📜 Question Breakdown
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove <?= htmlspecialchars($s['username']) ?> from your roster?')">
                                    <input type="hidden" name="remove_student_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn" style="padding:0.25rem 0.6rem; font-size:0.75rem; background:#ef4444; color:#fff;">
                                        🗑 Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Student Question Breakdown Modal -->
<div id="breakdownModal" class="modal-overlay">
    <div class="modal-content">
        <h3 style="margin-top:0;" id="modalTitle">Question Breakdown</h3>
        <div id="modalContainer" style="max-height:380px; overflow-y:auto; margin-bottom:1rem;">
            <p style="color:var(--text-dim);">Loading question breakdown...</p>
        </div>
        <div style="text-align:right;">
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
function viewStudentBreakdown(studentId, username) {
    document.getElementById("modalTitle").textContent = "Question Breakdown for " + username;
    const container = document.getElementById("modalContainer");
    container.innerHTML = '<p style="color:var(--text-dim);">Loading question breakdown...</p>';
    document.getElementById("breakdownModal").style.display = "flex";

    fetch("dashboard.php?fetch_student_questions=" + studentId)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<p style="color:var(--text-dim);">No question analytics logged for this student yet.</p>';
                return;
            }
            let html = '<table class="admin-table"><tr><th>Game Mode</th><th>Question</th><th>Times Correct / Encountered</th><th>Accuracy</th></tr>';
            data.forEach(q => {
                let pct = Math.round((q.times_correct / q.times_encountered) * 100);
                html += `<tr>
                    <td><span class="status-pill in_progress">${q.game_type}</span></td>
                    <td style="max-width:280px;">${q.question_text}</td>
                    <td><strong style="color:var(--green);">${q.times_correct}</strong> / ${q.times_encountered}</td>
                    <td><strong>${pct}%</strong></td>
                </tr>`;
            });
            html += '</table>';
            container.innerHTML = html;
        })
        .catch(() => {
            container.innerHTML = '<p style="color:#f87171;">Failed to load breakdown analytics.</p>';
        });
}
function closeModal() {
    document.getElementById("breakdownModal").style.display = "none";
}
</script>
</body>
</html>