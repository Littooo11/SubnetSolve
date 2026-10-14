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

function teacher_scalar($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($r);
    return $row ? (float) $row[0] : 0;
}

$categoryMap = [
    "subnet_dissect_practice" => "Dissect an IP", "subnet_dissect_timed" => "Dissect an IP",
    "binary_practice" => "Binary Game", "binary_game" => "Binary Game",
    "showdown_practice" => "Subnet Showdown", "showdown_timed" => "Subnet Showdown",
    "multiplayer_1v1" => "Multiplayer 1v1",
];
$categoryColors = [
    "Dissect an IP" => "#3b82f6", 
    "Binary Game" => "#22c55e", 
    "Subnet Showdown" => "#f59e0b", 
    "Multiplayer 1v1" => "#8b5cf6", 
    "Other" => "#64748b"
];

function get_student_popularity_data($conn, $teacherId, $filterStudentId = "all") {
    global $categoryMap, $categoryColors;
    
    $rawCounts = [];
    if ($filterStudentId === "all" || empty($filterStudentId)) {
        $catQuery = mysqli_prepare($conn, "
            SELECT s.game_type, COUNT(*) AS c 
            FROM scores s 
            JOIN teacher_students ts ON ts.student_id = s.user_id 
            WHERE ts.teacher_id = ? 
            GROUP BY s.game_type
        ");
        mysqli_stmt_bind_param($catQuery, "i", $teacherId);
    } else {
        $sid = (int)$filterStudentId;
        $catQuery = mysqli_prepare($conn, "
            SELECT s.game_type, COUNT(*) AS c 
            FROM scores s 
            JOIN teacher_students ts ON ts.student_id = s.user_id 
            WHERE ts.teacher_id = ? AND ts.student_id = ? 
            GROUP BY s.game_type
        ");
        mysqli_stmt_bind_param($catQuery, "ii", $teacherId, $sid);
    }
    
    mysqli_stmt_execute($catQuery);
    $catResult = mysqli_stmt_get_result($catQuery);

    while ($row = mysqli_fetch_assoc($catResult)) {
        $label = $categoryMap[$row["game_type"]] ?? "Other";
        $rawCounts[$label] = ($rawCounts[$label] ?? 0) + (int) $row["c"];
    }
    arsort($rawCounts);
    $categoryTotal = array_sum($rawCounts) ?: 1;

    $labels = array_keys($rawCounts);
    $counts = array_values($rawCounts);
    $colors = array_map(fn($l) => $categoryColors[$l] ?? $categoryColors["Other"], $labels);

    return [
        "labels" => $labels,
        "counts" => $counts,
        "colors" => $colors,
        "total"  => $categoryTotal,
        "raw"    => $rawCounts
    ];
}

function get_teacher_activity_dataset($conn, $teacherId, $activityRange = "7_days", $filterStudentId = "all") {
    $labels = [];
    $gamesPlayed = [];
    $lessonsCompleted = [];
    $anchorDate = "2026-09-26";

    switch ($activityRange) {
        case "3_days": $numDays = 3; break;
        case "30_days": $numDays = 30; break;
        case "3_months": $numDays = 90; break;
        case "6_months": $numDays = 180; break;
        case "1_year": $numDays = 365; break;
        case "all_time":
            $start = new DateTime($anchorDate);
            $today = new DateTime();
            $numDays = max(1, $today->diff($start)->days + 1);
            break;
        case "7_days":
        default: $numDays = 7; break;
    }

    for ($i = $numDays - 1; $i >= 0; $i--) {
        $day = date("Y-m-d", strtotime("-$i days"));
        if ($day < $anchorDate) continue;

        if ($filterStudentId === "all" || empty($filterStudentId)) {
            $gSql = "SELECT COUNT(*) FROM scores s JOIN teacher_students ts ON ts.student_id = s.user_id WHERE ts.teacher_id = $teacherId AND DATE(s.played_at) = '$day'";
            $lSql = "SELECT COUNT(*) FROM user_module_progress ump JOIN teacher_students ts ON ts.student_id = ump.user_id WHERE ts.teacher_id = $teacherId AND DATE(ump.completed_at) = '$day'";
        } else {
            $sid = (int)$filterStudentId;
            $gSql = "SELECT COUNT(*) FROM scores s JOIN teacher_students ts ON ts.student_id = s.user_id WHERE ts.teacher_id = $teacherId AND ts.student_id = $sid AND DATE(s.played_at) = '$day'";
            $lSql = "SELECT COUNT(*) FROM user_module_progress ump JOIN teacher_students ts ON ts.student_id = ump.user_id WHERE ts.teacher_id = $teacherId AND ts.student_id = $sid AND DATE(ump.completed_at) = '$day'";
        }

        $gamesCount = teacher_scalar($conn, $gSql);
        $lessonsCount = teacher_scalar($conn, $lSql);

        $labels[] = date("M j", strtotime($day));
        $gamesPlayed[] = (int) $gamesCount;
        $lessonsCompleted[] = (int) $lessonsCount;
    }

    return [
        "labels" => $labels,
        "games" => $gamesPlayed,
        "lessons" => $lessonsCompleted
    ];
}

// ===== AJAX Endpoints =====
if (isset($_GET["ajax_chart"])) {
    header("Content-Type: application/json");
    echo json_encode(get_student_popularity_data($conn, $teacherId, $_GET["student_id"] ?? "all"));
    exit();
}

if (isset($_GET["ajax_activity"])) {
    header("Content-Type: application/json");
    echo json_encode(get_teacher_activity_dataset($conn, $teacherId, $_GET["activity_range"] ?? "7_days", $_GET["student_id"] ?? "all"));
    exit();
}

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

// Post/Redirect/Get handlers
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["invite_student"])) {
    $inviteEmail = trim($_POST["student_email"] ?? "");

    if (empty($inviteEmail)) {
        $_SESSION["msg"] = "Please provide a valid student email address.";
        $_SESSION["msg_type"] = "bad";
    } else {
        $findStudent = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($findStudent, "s", $inviteEmail);
        mysqli_stmt_execute($findStudent);
        $student = mysqli_stmt_get_result($findStudent)->fetch_assoc();

        if (!$student) {
            $_SESSION["msg"] = "No registered user found with that email address.";
            $_SESSION["msg_type"] = "bad";
        } else {
            $studentId = $student["id"];

            $checkRoster = mysqli_prepare($conn, "SELECT id FROM teacher_students WHERE teacher_id = ? AND student_id = ?");
            mysqli_stmt_bind_param($checkRoster, "ii", $teacherId, $studentId);
            mysqli_stmt_execute($checkRoster);
            if (mysqli_stmt_get_result($checkRoster)->fetch_assoc()) {
                $_SESSION["msg"] = "This student is already on your roster.";
                $_SESSION["msg_type"] = "bad";
            } else {
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_student_id"])) {
    $removeStudentId = (int) $_POST["remove_student_id"];

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

$message = $_SESSION["msg"] ?? "";
$messageType = $_SESSION["msg_type"] ?? "";
unset($_SESSION["msg"], $_SESSION["msg_type"]);

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

$initialChartData = get_student_popularity_data($conn, $teacherId, "all");
$initialActivityData = get_teacher_activity_dataset($conn, $teacherId, "7_days", "all");
$rawCounts = $initialChartData["raw"];
$categoryTotal = $initialChartData["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal - SubNetSolve</title>
    <link rel="stylesheet" href="../admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        body {
            margin: 0;
        padding: 0;
        background: var(--bg, #0b1224);
        color: var(--text, #e7ebf5);
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .admin-layout {
        display: grid !important;
        grid-template-columns: 250px 1fr !important;
        min-height: 100vh !important;
        width: 100% !important;
    }

    .admin-sidebar {
        width: 250px !important;
        min-width: 250px !important;
        background: var(--panel, #111a33) !important;
        border-right: 1px solid var(--border, #23304f) !important;
        padding: 1.25rem 1rem !important;
        display: flex !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
    }

    .admin-main {
        padding: 1.5rem 2rem !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }

    .btn-primary {
        background: var(--green, #22c55e);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        cursor: pointer;
    }

    .btn-secondary {
        background: var(--panel-light, #16213f);
        color: var(--text, #e7ebf5);
        border: 1px solid var(--border, #23304f);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        cursor: pointer;
    }

    .feedback-banner {
        padding: 0.8rem 1rem;
        border-radius: 8px;
        font-size: 0.88rem;
    }
    .feedback-correct { background: rgba(34,197,94,0.12); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
    .feedback-wrong { background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }

    .modal-overlay {
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        right: 0; 
        bottom: 0; 
        width: 100vw; 
        height: 100vh;
        background: rgba(0,0,0,0.75); 
        z-index: 1000; 
        align-items: center; 
        justify-content: center;
    }
    .modal-content {
        background: var(--panel, #111a33); 
        border: 1px solid var(--border, #23304f); 
        border-radius: 12px;
        padding: 1.8rem; 
        width: 90%; 
        max-width: 1000px; 
        color: var(--text, #e7ebf5);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        margin: auto;
    }
    .accuracy-badge { font-weight: bold; font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 6px; display: inline-block; }
    .acc-high { background: rgba(34,197,94,0.15); color: #22c55e; }
    .acc-mid { background: rgba(234,179,8,0.15); color: #eab308; }
    .acc-low { background: rgba(239,68,68,0.15); color: #f87171; }
    .history-table-wrapper {
        max-height: 520px; overflow-y: auto; margin-bottom: 1.2rem;
        border: 1px solid var(--border, #23304f); border-radius: 8px;
    }
    .history-table-wrapper table { width: 100%; border-collapse: collapse; }
    .history-table-wrapper th { position: sticky; top: 0; background: var(--panel-light, #16213f); z-index: 2; }
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
            <h2 style="margin:0;">Teacher Dashboard</h2>
            <p style="color:var(--text-dim); margin:0.2rem 0 0 0;">Monitor student performance and game engagements.</p>
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

    <!-- Student Activity & Gameplay Chart -->
    <div class="admin-panel" style="margin-bottom:1.5rem;">
        <div class="admin-panel-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0;">📊 Student Activity (Games & Lessons)</h3>
            <div style="display:flex; gap:0.5rem;">
                <select id="actStudentSelect" class="admin-select" style="padding:0.3rem 0.6rem; background:var(--panel-light); color:var(--text); border:1px solid var(--border); border-radius:6px; font-size:0.85rem; cursor:pointer;">
                    <option value="all">All Students</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="actTimeSelect" class="admin-select" style="padding:0.3rem 0.6rem; background:var(--panel-light); color:var(--text); border:1px solid var(--border); border-radius:6px; font-size:0.85rem; cursor:pointer;">
                    <option value="3_days">Last 3 Days</option>
                    <option value="7_days" selected>Last 7 Days</option>
                    <option value="30_days">Last 30 Days (1 Month)</option>
                    <option value="3_months">Last 3 Months</option>
                    <option value="6_months">Last 6 Months</option>
                    <option value="1_year">Last 1 Year</option>
                    <option value="all_time">All Time (Since Sep 26, 2026)</option>
                </select>
            </div>
        </div>
        <canvas id="teacherActivityChart" height="180"></canvas>
    </div>

    <!-- Section: Game Category Popularity -->
    <div class="admin-panel" style="margin-bottom:1.5rem;">
        <div class="admin-panel-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0;">🎮 Game Category Popularity</h3>
            <select id="studentFilterSelect" class="admin-select" style="padding:0.3rem 0.7rem; background:var(--panel-light); color:var(--text); border:1px solid var(--border); border-radius:6px; font-size:0.85rem; cursor:pointer;">
                <option value="all">All Assigned Students</option>
                <?php foreach ($students as $st): ?>
                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:grid; grid-template-columns: 280px 1fr; gap:2rem; align-items:center;">
            <div style="height:210px; display:flex; justify-content:center; align-items:center;">
                <canvas id="studentCategoryChart"></canvas>
            </div>
            <div id="chartLegendContainer">
                <?php foreach ($rawCounts as $label => $count):
                    $pct = round(($count / $categoryTotal) * 100);
                    $color = $categoryColors[$label] ?? $categoryColors["Other"];
                ?>
                    <div class="legend-row" style="display:flex; justify-content:space-between; align-items:center; font-size:0.88rem; padding:0.4rem 0; border-bottom:1px solid var(--border);">
                        <span><span class="legend-dot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?= $color ?>; margin-right:0.5rem;"></span><strong><?= htmlspecialchars($label) ?></strong></span>
                        <span class="pct" style="color:var(--text-dim);"><?= $count ?> plays (<?= $pct ?>%)</span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($rawCounts)): ?>
                    <p style="color:var(--text-dim); font-size:0.85rem; margin:0;">No game activity recorded for your students yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Student Roster Panel -->
    <div class="admin-panel">
        <h3>👥 Student Roster</h3>
        <?php if (empty($students)): ?>
            <p style="color:var(--text-dim); margin-top:0.5rem;">No students currently associated with your account.</p>
        <?php else: ?>
            <table class="admin-table" style="margin-top:0.8rem;">
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
</div><div id="breakdownModal" class="modal-overlay">
<div class="modal-content">
<h3 style="margin-top:0; font-size:1.3rem;" id="modalTitle">Question Breakdown</h3>
<div id="modalContainer" class="history-table-wrapper">
<p style="color:var(--text-dim); padding:1rem;">Loading question breakdown...</p>
</div>
<div style="text-align:right;">
<button class="btn btn-secondary" onclick="closeModal()">Close</button>
</div>
</div>
</div><script>
// Teacher Activity Line Chart
const teacherActCtx = document.getElementById("teacherActivityChart").getContext("2d");
let teacherActivityChart = new Chart(teacherActCtx, {
type: "line",
data: {
labels: <?= json_encode($initialActivityData["labels"]) ?>,
datasets: [
{
label: "Games Played",
data: <?= json_encode($initialActivityData["games"]) ?>,
borderColor: "#f59e0b",
backgroundColor: "rgba(245,158,11,0.15)",
fill: true,
tension: 0.3
},
{
label: "Lessons Completed",
data: <?= json_encode($initialActivityData["lessons"]) ?>,
borderColor: "#22c55e",
backgroundColor: "rgba(34,197,94,0.15)",
fill: true,
tension: 0.3
}
]
},
options: {
plugins: { legend: { display: true, labels: { color: "#8b93ab" } } },
scales: {
x: { ticks: { color: "#8b93ab" }, grid: { color: "#23304f" } },
y: { ticks: { color: "#8b93ab", precision: 0 }, grid: { color: "#23304f" }, beginAtZero: true }
}
}
});

function updateTeacherActivityChart() {
const studentId = document.getElementById("actStudentSelect").value;
const timeRange = document.getElementById("actTimeSelect").value;

fetch(`dashboard.php?ajax_activity=1&activity_range=${timeRange}&student_id=${studentId}`)
.then(r => r.json())
.then(data => {
teacherActivityChart.data.labels = data.labels;
teacherActivityChart.data.datasets[0].data = data.games;
teacherActivityChart.data.datasets[1].data = data.lessons;
teacherActivityChart.update();
});
}

document.getElementById("actStudentSelect").addEventListener("change", updateTeacherActivityChart);
document.getElementById("actTimeSelect").addEventListener("change", updateTeacherActivityChart);

// Doughnut Chart Setup
const chartCtx = document.getElementById("studentCategoryChart").getContext("2d");
let studentChart = new Chart(chartCtx, {
type: "doughnut",
data: {
labels: <?= json_encode($initialChartData["labels"]) ?>,
datasets: [{
data: <?= json_encode($initialChartData["counts"]) ?>,
backgroundColor: <?= json_encode($initialChartData["colors"]) ?>,
borderWidth: 0,
}]
},
options: {
plugins: { legend: { display: false } },
cutout: "65%",
responsive: true,
maintainAspectRatio: false
}
});

document.getElementById("studentFilterSelect").addEventListener("change", function() {
const selectedStudent = this.value;

fetch(`dashboard.php?ajax_chart=1&student_id=${selectedStudent}`)
.then(res => res.json())
.then(data => {
studentChart.data.labels = data.labels;
studentChart.data.datasets[0].data = data.counts;
studentChart.data.datasets[0].backgroundColor = data.colors;
studentChart.update();

const legendContainer = document.getElementById("chartLegendContainer");
if (data.labels.length === 0) {
legendContainer.innerHTML = '<p style="color:var(--text-dim); font-size:0.85rem; margin:0;">No game activity recorded for this selection.</p>';
} else {
let html = '';
for (let i = 0; i < data.labels.length; i++) {
const label = data.labels[i];
const count = data.counts[i];
const color = data.colors[i];
const pct = Math.round((count / data.total) * 100);
html += `
<div class="legend-row" style="display:flex; justify-content:space-between; align-items:center; font-size:0.88rem; padding:0.4rem 0; border-bottom:1px solid var(--border);">
<span><span class="legend-dot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:${color}; margin-right:0.5rem;"></span><strong>${label}</strong></span>
<span class="pct" style="color:var(--text-dim);">${count} plays (${pct}%)</span>
</div>
`;
}
legendContainer.innerHTML = html;
}
});
});

function viewStudentBreakdown(studentId, username) {
document.getElementById("modalTitle").textContent = "Question Breakdown for " + username;
const container = document.getElementById("modalContainer");
container.innerHTML = '<p style="color:var(--text-dim); padding:1rem;">Loading question breakdown...</p>';
document.getElementById("breakdownModal").style.display = "flex";

fetch("dashboard.php?fetch_student_questions=" + studentId)
.then(res => res.json())
.then(data => {
if (data.length === 0) {
container.innerHTML = '<p style="color:var(--text-dim); padding:1rem;">No question analytics logged for this student yet.</p>';
return;
}
let html = '<table class="admin-table"><tr><th style="width:160px;">Topic</th><th>Question Prompt</th><th style="width:180px;">Times Correct / Encountered</th><th style="width:110px;">Accuracy</th><th style="width:180px;">Last Answered</th></tr>';
data.forEach(q => {
let pct = Math.round((q.times_correct / q.times_encountered) * 100);
let accClass = pct >= 75 ? 'acc-high' : (pct >= 50 ? 'acc-mid' : 'acc-low');
html += `<tr>
<td><span class="status-pill in_progress">${q.game_type}</span></td>
<td style="line-height:1.4; word-break:break-word; padding-right:1rem;">${q.question_text}</td>
<td><strong style="color:var(--green);">${q.times_correct}</strong> / ${q.times_encountered}</td>
<td><span class="accuracy-badge ${accClass}">${pct}%</span></td>
<td style="font-size:0.78rem; color:var(--text-dim);">${new Date(q.last_answered_at).toLocaleString()}</td>
</tr>`;
});
html += '</table>';
container.innerHTML = html;
})
.catch(() => {
container.innerHTML = '<p style="color:#f87171; padding:1rem;">Failed to load breakdown analytics.</p>';
});
}

function closeModal() {
document.getElementById("breakdownModal").style.display = "none";
}
</script></body>
</html>