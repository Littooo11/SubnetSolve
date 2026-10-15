<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";

$myId = $_SESSION["user_id"];
$message = "";
$messageType = "";

// ===== Handle Edit User Details =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_user_id"])) {
    $editId = (int) $_POST["edit_user_id"];
    $newUsername = trim($_POST["username"] ?? "");
    $newEmail = trim($_POST["email"] ?? "");

    if ($newUsername === "" || $newEmail === "") {
        $message = "Username and email cannot be empty.";
        $messageType = "bad";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "bad";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($check, "si", $newEmail, $editId);
        mysqli_stmt_execute($check);
        if (mysqli_stmt_get_result($check)->fetch_assoc()) {
            $message = "That email is already in use by another user.";
            $messageType = "bad";
        } else {
            $update = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "ssi", $newUsername, $newEmail, $editId);
            mysqli_stmt_execute($update);
            $message = "User details updated successfully.";
            $messageType = "good";
        }
    }
}

// ===== Handle Delete User =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user_id"])) {
    $deleteId = (int) $_POST["delete_user_id"];
    if ($deleteId !== (int) $myId) {
        mysqli_query($conn, "DELETE FROM scores WHERE user_id = $deleteId");
        mysqli_query($conn, "DELETE FROM user_progress WHERE user_id = $deleteId");
        mysqli_query($conn, "DELETE FROM user_badges WHERE user_id = $deleteId");
        mysqli_query($conn, "DELETE FROM user_module_progress WHERE user_id = $deleteId");
        mysqli_query($conn, "DELETE FROM user_question_stats WHERE user_id = $deleteId");
        mysqli_query($conn, "DELETE FROM teacher_students WHERE teacher_id = $deleteId OR student_id = $deleteId");
        mysqli_query($conn, "DELETE FROM teacher_invitations WHERE teacher_id = $deleteId OR student_id = $deleteId");
        mysqli_query($conn, "DELETE FROM teacher_applications WHERE user_id = $deleteId");
        mysqli_query($conn, "DELETE FROM users WHERE id = $deleteId");
        $message = "User account and history deleted successfully.";
        $messageType = "good";
    } else {
        $message = "You cannot delete your own admin account.";
        $messageType = "bad";
    }
}

// ===== AJAX Handler: Fetch Detailed Question Stats & Game History =====
if (isset($_GET["fetch_detailed_history_user_id"])) {
    header("Content-Type: application/json");
    $uid = (int) $_GET["fetch_detailed_history_user_id"];

    // Fetch Question Stats
    $qStmt = mysqli_prepare($conn, "
        SELECT gq.game_type, gq.question_text, uqs.times_encountered, uqs.times_correct, uqs.last_answered_at
        FROM user_question_stats uqs
        JOIN game_questions gq ON gq.id = uqs.question_id
        WHERE uqs.user_id = ?
        ORDER BY uqs.last_answered_at DESC
    ");
    mysqli_stmt_bind_param($qStmt, "i", $uid);
    mysqli_stmt_execute($qStmt);
    $questionStats = mysqli_fetch_all(mysqli_stmt_get_result($qStmt), MYSQLI_ASSOC);

    // Fetch Sessions History
    $sStmt = mysqli_prepare($conn, "
        SELECT game_type, points, played_at 
        FROM scores 
        WHERE user_id = ? 
        ORDER BY played_at DESC LIMIT 30
    ");
    mysqli_stmt_bind_param($sStmt, "i", $uid);
    mysqli_stmt_execute($sStmt);
    $gameSessions = mysqli_fetch_all(mysqli_stmt_get_result($sStmt), MYSQLI_ASSOC);

    echo json_encode([
        "questions" => $questionStats,
        "sessions" => $gameSessions
    ]);
    exit();
}

$search = trim($_GET["q"] ?? "");
$where = $search !== "" ? "WHERE u.username LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR u.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'" : "";

$result = mysqli_query($conn, "SELECT u.id, u.username, u.email, u.is_admin, u.is_teacher, u.created_at, 
    up.level, up.career_xp, up.quizzes_completed, up.total_correct, up.total_wrong
    FROM users u LEFT JOIN user_progress up ON up.user_id = u.id
    $where ORDER BY u.created_at DESC LIMIT 200");
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
    <style>
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.75); z-index: 100; align-items: center; justify-content: center;
        }
        .modal-content {
            background: var(--panel); border: 1px solid var(--border); border-radius: 12px;
            padding: 1.8rem; width: 95%; max-width: 1100px; color: var(--text);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .accuracy-badge { font-weight: bold; font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 6px; display: inline-block; }
        .acc-high { background: rgba(34,197,94,0.15); color: var(--green); }
        .acc-mid { background: rgba(234,179,8,0.15); color: var(--gold); }
        .acc-low { background: rgba(239,68,68,0.15); color: #f87171; }
        .tab-btn-modal {
            padding: 0.5rem 1.1rem; border-radius: 8px; background: var(--panel-light);
            border: 1px solid var(--border); color: var(--text-dim); cursor: pointer; margin-right: 0.5rem;
            font-size: 0.88rem; transition: all 0.2s ease;
        }
        .tab-btn-modal.active { background: var(--blue); color: #fff; font-weight: bold; border-color: var(--blue); }
        .history-table-wrapper {
            max-height: 550px; overflow-y: auto; margin-bottom: 1.2rem;
            border: 1px solid var(--border); border-radius: 8px;
        }
        .history-table-wrapper table { width: 100%; border-collapse: collapse; }
        .history-table-wrapper th { position: sticky; top: 0; background: var(--panel-light); z-index: 2; }
    </style>
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
        <a href="manage_questions.php" class="admin-nav-link">❓ Manage Questions</a>
        <a href="game_popularity.php" class="admin-nav-link">🎮 Game Category Popularity</a>
        <a href="teacher_applications.php" class="admin-nav-link">🎓 Teacher Apps</a>
        <div class="admin-nav-label">ACCOUNT</div>
        <a href="profile.php" class="admin-nav-link">👤 Admin Profile</a>
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

        <?php if ($message): ?>
            <div class="feedback-banner <?= $messageType === 'good' ? 'feedback-correct' : 'feedback-wrong' ?>" style="margin-bottom:1rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="admin-panel">
            <table class="admin-table">
                <tr>
                    <th>User Details</th>
                    <th>Joined</th>
                    <th>Level / XP</th>
                    <th>Questions Answered</th>
                    <th>Accuracy</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($users as $u): 
                    $correct = (int) ($u["total_correct"] ?? 0);
                    $wrong = (int) ($u["total_wrong"] ?? 0);
                    $totalAnswered = $correct + $wrong;
                    $accPct = $totalAnswered > 0 ? round(($correct / $totalAnswered) * 100) : 0;
                    $accClass = $accPct >= 75 ? 'acc-high' : ($accPct >= 50 ? 'acc-mid' : 'acc-low');
                ?>
                <tr>
                    <td>
                        <strong style="color:#fff;"><?= htmlspecialchars($u["username"]) ?></strong><br>
                        <span style="font-size:0.78rem; color:var(--text-dim);"><?= htmlspecialchars($u["email"]) ?></span>
                    </td>
                    <td><?= date("M j, Y", strtotime($u["created_at"])) ?></td>
                    <td>Lvl <?= $u["level"] ?? 1 ?> · <?= number_format($u["career_xp"] ?? 0) ?> XP</td>
                    <td>
                        <span style="color:var(--green); font-weight:bold;"><?= $correct ?></span> correct / 
                        <span style="color:#f87171; font-weight:bold;"><?= $wrong ?></span> wrong<br>
                        <span style="font-size:0.75rem; color:var(--text-dim);">(Total: <?= $totalAnswered ?>)</span>
                    </td>
                    <td>
                        <span class="accuracy-badge <?= $accClass ?>"><?= $accPct ?>%</span>
                    </td>
                    <td>
                        <?php if ($u["is_admin"]): ?>
                            <span class="status-pill in_progress">Admin</span>
                        <?php elseif ($u["is_teacher"]): ?>
                            <span class="status-pill in_progress" style="background:rgba(34,197,94,0.15); color:var(--green);">Teacher</span>
                        <?php else: ?>
                            <span class="status-pill waiting">Student</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.3rem; flex-wrap:wrap;">
                            <button class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" 
                                    onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>')">
                                ✏️ Edit
                            </button>

                            <button class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" 
                                    onclick="viewDetailedHistory(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                📜 Question History
                            </button>

                            <?php if ((int) $u["id"] !== (int) $myId): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete account for <?= htmlspecialchars($u['username']) ?>? This action cannot be undone.')">
                                    <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn" style="padding:0.25rem 0.5rem; font-size:0.75rem; background:#ef4444; color:#fff;">
                                        🗑 Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--text-dim); font-size:0.75rem; padding-top:0.2rem;">(you)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </main>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content" style="max-width:500px;">
        <h3 style="margin-top:0;">Edit User Account</h3>
        <form method="POST">
            <input type="hidden" id="edit_user_id" name="edit_user_id">
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; color:var(--text-dim); margin-bottom:0.3rem;">Username</label>
                <input type="text" id="edit_username" name="username" required style="width:100%; padding:0.5rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px;">
            </div>
            <div style="margin-bottom:1.2rem;">
                <label style="display:block; font-size:0.85rem; color:var(--text-dim); margin-bottom:0.3rem;">Email Address</label>
                <input type="email" id="edit_email" name="email" required style="width:100%; padding:0.5rem; background:var(--panel-light); border:1px solid var(--border); color:var(--text); border-radius:6px;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Expanded Detailed Question & Game History Modal -->
<div id="historyModal" class="modal-overlay">
    <div class="modal-content">
        <h3 style="margin-top:0; font-size:1.3rem;" id="historyTitle">Detailed Performance History</h3>
        
        <div style="margin-bottom:1.2rem;">
            <button class="tab-btn-modal active" id="tabQuestionsBtn" onclick="switchModalTab('questions')">❓ Question Accuracy Breakdown</button>
            <button class="tab-btn-modal" id="tabSessionsBtn" onclick="switchModalTab('sessions')">🎮 Game Sessions</button>
        </div>

        <div id="questionsTab" class="history-table-wrapper">
            <p style="color:var(--text-dim); padding:1rem;">Loading question history...</p>
        </div>

        <div id="sessionsTab" class="history-table-wrapper" style="display:none;">
            <p style="color:var(--text-dim); padding:1rem;">Loading session history...</p>
        </div>

        <div style="text-align:right;">
            <button type="button" class="btn btn-secondary" onclick="closeHistoryModal()">Close</button>
        </div>
    </div>
</div>

<script>
function openEditModal(id, username, email) {
    document.getElementById("edit_user_id").value = id;
    document.getElementById("edit_username").value = username;
    document.getElementById("edit_email").value = email;
    document.getElementById("editModal").style.display = "flex";
}
function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

function switchModalTab(tab) {
    if (tab === 'questions') {
        document.getElementById("questionsTab").style.display = "block";
        document.getElementById("sessionsTab").style.display = "none";
        document.getElementById("tabQuestionsBtn").classList.add("active");
        document.getElementById("tabSessionsBtn").classList.remove("active");
    } else {
        document.getElementById("questionsTab").style.display = "none";
        document.getElementById("sessionsTab").style.display = "block";
        document.getElementById("tabSessionsBtn").classList.add("active");
        document.getElementById("tabQuestionsBtn").classList.remove("active");
    }
}

function viewDetailedHistory(userId, username) {
    document.getElementById("historyTitle").textContent = "Performance & Question History for " + username;
    const qTab = document.getElementById("questionsTab");
    const sTab = document.getElementById("sessionsTab");
    
    qTab.innerHTML = '<p style="color:var(--text-dim); padding:1rem;">Loading per-question encounter analytics...</p>';
    sTab.innerHTML = '<p style="color:var(--text-dim); padding:1rem;">Loading game session history...</p>';
    switchModalTab('questions');
    document.getElementById("historyModal").style.display = "flex";

    fetch("users.php?fetch_detailed_history_user_id=" + userId)
        .then(res => res.json())
        .then(data => {
            // Render Per-Question Performance
            if (data.questions.length === 0) {
                qTab.innerHTML = '<p style="color:var(--text-dim); padding:1rem;">No individual question data logged for this user yet.</p>';
            } else {
                let qHtml = '<table class="admin-table"><tr><th style="width:160px;">Topic</th><th>Question Prompt</th><th style="width:180px;">Times Correct / Encountered</th><th style="width:110px;">Accuracy</th><th style="width:180px;">Last Answered</th></tr>';
                data.questions.forEach(q => {
                    let pct = Math.round((q.times_correct / q.times_encountered) * 100);
                    let accClass = pct >= 75 ? 'acc-high' : (pct >= 50 ? 'acc-mid' : 'acc-low');
                    qHtml += `<tr>
                        <td><span class="status-pill in_progress">${q.game_type}</span></td>
                        <td style="line-height:1.4; word-break:break-word; padding-right:1rem;">${q.question_text}</td>
                        <td><strong style="color:var(--green);">${q.times_correct}</strong> / ${q.times_encountered}</td>
                        <td><span class="accuracy-badge ${accClass}">${pct}%</span></td>
                        <td style="font-size:0.78rem; color:var(--text-dim);">${new Date(q.last_answered_at).toLocaleString()}</td>
                    </tr>`;
                });
                qHtml += '</table>';
                qTab.innerHTML = qHtml;
            }

            // Render Game Sessions
            if (data.sessions.length === 0) {
                sTab.innerHTML = '<p style="color:var(--text-dim); padding:1rem;">No overall game sessions found for this user.</p>';
            } else {
                let sHtml = '<table class="admin-table"><tr><th>Game Mode</th><th>Points Earned</th><th>Date Played</th></tr>';
                data.sessions.forEach(s => {
                    sHtml += `<tr>
                        <td><span class="status-pill waiting">${s.game_type}</span></td>
                        <td><strong style="color:var(--gold);">+${s.points} XP</strong></td>
                        <td style="font-size:0.78rem; color:var(--text-dim);">${new Date(s.played_at).toLocaleString()}</td>
                    </tr>`;
                });
                sHtml += '</table>';
                sTab.innerHTML = sHtml;
            }
        })
        .catch(() => {
            qTab.innerHTML = '<p style="color:#f87171; padding:1rem;">Failed to load question analytics.</p>';
        });
}

function closeHistoryModal() {
    document.getElementById("historyModal").style.display = "none";
}
</script>
</body>
</html>