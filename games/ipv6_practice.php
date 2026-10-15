<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/block_admin_play.php";
require "../includes/ipv6_engine.php";
require "../includes/xp_engine.php";
require "../includes/badge_engine.php";
require "../includes/avatars.php";
require "../includes/easy_instructions.php";

if (!isset($_SESSION["user_id"])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not logged in']);
        exit();
    }
    header("Location: ../login.php");
    exit();
}
block_admins_from_playing($conn);
$userId = $_SESSION["user_id"];
$username = $_SESSION["username"];

$avStmt = mysqli_prepare($conn, "SELECT avatar FROM users WHERE id = ?");
mysqli_stmt_bind_param($avStmt, "i", $userId);
mysqli_stmt_execute($avStmt);
$myAvatar = mysqli_stmt_get_result($avStmt)->fetch_assoc()["avatar"] ?? "fox";

$TOTAL_QUESTIONS = 10;
$validDifficulties = ["easy", "medium", "hard"];
$difficulty = $_GET["difficulty"] ?? null;

// Handle AJAX requests for checking answer & next question without reloads
if ($_SERVER["REQUEST_METHOD"] === "POST" && (isset($_POST["ajax"]) || isset($_SERVER['HTTP_X_REQUESTED_WITH']))) {
    header('Content-Type: application/json');
    $action = $_POST["action"] ?? "";

    if ($action === "submit_answer") {
        $state = &$_SESSION["ipv6_practice"];
        if (!$state || $state["answered"]) {
            echo json_encode(["error" => "Invalid state"]);
            exit();
        }

        $selected = isset($_POST["selected"]) && $_POST["selected"] !== "" ? (int) $_POST["selected"] : -1;
        $state["selected"] = $selected;
        $state["answered"] = true;

        $isCorrect = ($selected === $state["question"]["correct_index"]);

        if ($isCorrect) {
            $state["correct"]++;
            $state["streak"]++;
            $base = 20 + min($state["streak"] * 2, 20);
            $state["score"] += (int) round($base * difficulty_multiplier($state["difficulty"]));
        } else {
            $state["wrong"]++;
            $state["streak"] = 0;
        }

        echo json_encode([
            "correct" => $isCorrect,
            "correct_index" => $state["question"]["correct_index"],
            "score" => $state["score"],
            "streak" => $state["streak"],
            "correct_count" => $state["correct"],
            "q_index" => $state["q_index"],
            "total_questions" => $TOTAL_QUESTIONS
        ]);
        exit();
    }

    if ($action === "next_question") {
        $state = &$_SESSION["ipv6_practice"];
        if ($state["q_index"] >= $TOTAL_QUESTIONS) {
            $gameType = "ipv6_practice";
            $stmt = mysqli_prepare($conn, "INSERT INTO scores (user_id, match_id, game_type, points, played_at) VALUES (?, NULL, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "isi", $userId, $gameType, $state["score"]);
            mysqli_stmt_execute($stmt);

            $xpResult = award_xp($conn, $userId, $state["score"]);
            $updateQuiz = mysqli_prepare($conn, "UPDATE user_progress SET quizzes_completed = quizzes_completed + 1, total_correct = total_correct + ?, total_wrong = total_wrong + ? WHERE user_id = ?");
            mysqli_stmt_bind_param($updateQuiz, "iii", $state["correct"], $state["wrong"], $userId);
            mysqli_stmt_execute($updateQuiz);

            $newBadges = check_and_award_badges($conn, $userId);

            echo json_encode([
                "completed" => true,
                "final_score" => $state["score"],
                "final_correct" => $state["correct"],
                "final_wrong" => $state["wrong"],
                "session_difficulty" => $state["difficulty"],
                "xp_result" => $xpResult,
                "new_badges" => $newBadges
            ]);
            unset($_SESSION["ipv6_practice"]);
            exit();
        } else {
            $state["q_index"]++;
            $state["question"] = generate_ipv6_question($state["difficulty"]);
            $state["answered"] = false;
            $state["selected"] = null;

            echo json_encode([
                "completed" => false,
                "q_index" => $state["q_index"],
                "question" => $state["question"]
            ]);
            exit();
        }
    }
}

$needsNewSession = !isset($_SESSION["ipv6_practice"]) || isset($_GET["restart"]);
$showDifficultyScreen = false;

if ($needsNewSession) {
    if (!in_array($difficulty, $validDifficulties)) {
        $showDifficultyScreen = true;
    } else {
        $_SESSION["ipv6_practice"] = [
            "q_index" => 1,
            "correct" => 0,
            "wrong"   => 0,
            "score"   => 0,
            "streak"  => 0,
            "difficulty" => $difficulty,
            "question" => generate_ipv6_question($difficulty),
            "answered" => false,
            "selected"  => null,
        ];
    }
}

$state = $_SESSION["ipv6_practice"] ?? null;
$q = $state["question"] ?? null;
$letters = ["A", "B", "C", "D"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IPv6 Game (Practice) - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
</head>
<body>
<div class="showdown-layout">
    <div class="showdown-profile">
        <?= render_avatar($myAvatar, 70) ?>
        <div class="name"><?= htmlspecialchars($username) ?></div>
        <div class="tag">You</div>

        <?php if (!$showDifficultyScreen): ?>
        <div class="showdown-stat-box"><div class="lbl">Score</div><div class="num" style="color:var(--gold);" id="statScore"><?= $state["score"] ?? 0 ?></div></div>
        <div class="showdown-stat-box"><div class="lbl">Correct</div><div class="num" style="color:var(--green);" id="statCorrect"><?= $state["correct"] ?? 0 ?></div></div>
        <div class="showdown-stat-box">
            <div class="lbl">Streak</div>
            <div class="num" style="color:var(--orange);" id="statStreak"><?= $state["streak"] ?? 0 ?></div>
            <div class="progress-bar" style="margin-top:0.4rem;"><div id="streakBar" style="width:<?= min(($state['streak']??0)*20,100) ?>%; background:var(--orange);"></div></div>
        </div>
        <?php endif; ?>
        <a href="../practice.php" class="btn btn-secondary" style="text-decoration:none; display:block; margin-top:0.6rem; font-size:0.82rem;">← Back</a>
    </div>

    <?php if ($showDifficultyScreen): ?>
        <div class="showdown-card">
            <div class="difficulty-select">
                <h2>Choose a Difficulty</h2>
                <p class="sub">Easy leans on address recognition and facts. Harder difficulties test compression and expansion.</p>
                <div class="difficulty-grid">
                    <a href="?difficulty=easy" class="difficulty-card easy">
                        <span class="icon">🟢</span><h4>Easy</h4><p>Address types & facts<br>XP ×1</p>
                    </a>
                    <a href="?difficulty=medium" class="difficulty-card medium">
                        <span class="icon">🟡</span><h4>Medium</h4><p>Mixed topics<br>XP ×1.5</p>
                    </a>
                    <a href="?difficulty=hard" class="difficulty-card hard">
                        <span class="icon">🔴</span><h4>Difficult</h4><p>Compress / expand focus<br>XP ×2</p>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="showdown-card" id="questionCard">
            <div class="showdown-top-row">
                <a href="../practice.php" class="showdown-back">← Exit</a>
                <div style="font-weight:bold;" id="qIndexText">Question <?= $state["q_index"] ?> / <?= $TOTAL_QUESTIONS ?></div>
                <div class="progress-dots">
                    <?php for ($i = 1; $i <= $TOTAL_QUESTIONS; $i++): ?>
                        <div class="progress-dot <?= $i < $state['q_index'] ? 'done' : ($i == $state['q_index'] ? 'current' : '') ?>" id="dot-<?= $i ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="showdown-meta-row">
                <div class="category-pill">🧊 IPv6 Game · <?= ucfirst($state["difficulty"]) ?></div>
                <div style="font-size:0.78rem; color:var(--text-dim);">No timer — Practice Mode</div>
            </div>

            <div class="showdown-question" id="promptText" style="word-break:break-word; overflow-wrap:anywhere;"><?= htmlspecialchars($q["prompt"]) ?></div>

            <div id="optionsContainer">
                <?php foreach ($q["options"] as $i => $opt): ?>
                    <div class="mcq-option" data-index="<?= $i ?>" onclick="selectOption(<?= $i ?>)">
                        <div class="mcq-radio"></div>
                        <span class="mcq-letter"><?= $letters[$i] ?>.</span>
                        <span class="mcq-text" style="word-break:break-all; overflow-wrap:anywhere; font-family:Consolas,monospace;"><?= htmlspecialchars($opt) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-primary" style="width:100%; margin-top:0.8rem;" id="actionBtn" disabled onclick="handleAction()">Submit Answer</button>
        </div>

        <div class="showdown-card" id="summaryCard" style="display:none;">
            <h2>Session Complete!</h2>
            <p style="color:var(--text-dim);">Nice work — here's how you did:</p>
            <div class="stat-line"><span>Correct Answers</span><span class="good" id="finalCorrect">0</span></div>
            <div class="stat-line"><span>Wrong Answers</span><span class="bad" id="finalWrong">0</span></div>
            <div class="stat-line"><span>Total Score</span><span class="xp" id="finalScore">+0 XP</span></div>
            <div id="summaryBanners"></div>
            <div class="game-actions" style="margin-top:1.2rem;">
                <a href="ipv6_practice.php?restart=1&difficulty=<?= $state['difficulty'] ?>" class="btn btn-primary" style="text-decoration:none; display:inline-block;" id="restartBtn">Play Again</a>
                <a href="../practice.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">Back to Practice Mode</a>
            </div>
        </div>

        <?php if ($state["difficulty"] === "easy"): ?>
            <?= render_ipv6_instructions() ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!$showDifficultyScreen): ?>
<script>
const TOTAL_QUESTIONS = <?= $TOTAL_QUESTIONS ?>;
const LETTERS = ["A", "B", "C", "D"];
let selectedIndex = -1;
let isAnswered = false;

function selectOption(i) {
    if (isAnswered) return;
    selectedIndex = i;
    document.querySelectorAll(".mcq-option").forEach(el => el.classList.remove("selected"));
    const opt = document.querySelector(`.mcq-option[data-index="${i}"]`);
    if (opt) opt.classList.add("selected");
    document.getElementById("actionBtn").disabled = false;
}

function handleAction() {
    const actionBtn = document.getElementById("actionBtn");

    if (!isAnswered) {
        actionBtn.disabled = true;

        const formData = new FormData();
        formData.append("ajax", "1");
        formData.append("action", "submit_answer");
        formData.append("selected", selectedIndex);

        fetch("ipv6_practice.php", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            isAnswered = true;
            document.querySelectorAll(".mcq-option").forEach(el => {
                const idx = parseInt(el.getAttribute("data-index"), 10);
                if (idx === data.correct_index) {
                    el.classList.add("correct");
                } else if (idx === selectedIndex) {
                    el.classList.add("incorrect");
                }
            });

            document.getElementById("statScore").textContent = data.score;
            document.getElementById("statCorrect").textContent = data.correct_count;
            document.getElementById("statStreak").textContent = data.streak;
            document.getElementById("streakBar").style.width = Math.min(data.streak * 20, 100) + "%";

            actionBtn.textContent = (data.q_index >= TOTAL_QUESTIONS) ? "Finish Session" : "Next Question";
            actionBtn.disabled = false;
        })
        .catch(err => {
            console.error("AJAX Error:", err);
            actionBtn.disabled = false;
        });
    } else {
        actionBtn.disabled = true;

        const formData = new FormData();
        formData.append("ajax", "1");
        formData.append("action", "next_question");

        fetch("ipv6_practice.php", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.completed) {
                document.getElementById("questionCard").style.display = "none";
                document.getElementById("finalCorrect").textContent = data.final_correct;
                document.getElementById("finalWrong").textContent = data.final_wrong;
                document.getElementById("finalScore").textContent = "+" + data.final_score + " XP";

                const banners = document.getElementById("summaryBanners");
                banners.innerHTML = "";

                if (data.xp_result && data.xp_result.leveled_up) {
                    banners.innerHTML += `<div class="feedback-banner feedback-correct" style="margin-top:0.8rem;">🎉 Level Up! You're now Level ${data.xp_result.new_level}!</div>`;
                }
                if (data.new_badges && data.new_badges.length > 0) {
                    data.new_badges.forEach(b => {
                        banners.innerHTML += `<div class="feedback-banner feedback-correct" style="margin-top:0.8rem;">${b.icon} Badge Unlocked: <b>${b.name}</b></div>`;
                    });
                }

                document.getElementById("summaryCard").style.display = "block";
            } else {
                isAnswered = false;
                selectedIndex = -1;
                actionBtn.textContent = "Submit Answer";
                actionBtn.disabled = true;

                document.getElementById("qIndexText").textContent = `Question ${data.q_index} / ${TOTAL_QUESTIONS}`;
                document.getElementById("promptText").textContent = data.question.prompt;

                for (let i = 1; i <= TOTAL_QUESTIONS; i++) {
                    const dot = document.getElementById(`dot-${i}`);
                    if (dot) {
                        dot.className = "progress-dot " + (i < data.q_index ? "done" : (i == data.q_index ? "current" : ""));
                    }
                }

                const optsContainer = document.getElementById("optionsContainer");
                optsContainer.innerHTML = "";
                data.question.options.forEach((opt, idx) => {
                    const safeOpt = opt.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    optsContainer.innerHTML += `
                        <div class="mcq-option" data-index="${idx}" onclick="selectOption(${idx})">
                            <div class="mcq-radio"></div>
                            <span class="mcq-letter">${LETTERS[idx]}.</span>
                            <span class="mcq-text" style="word-break:break-all; overflow-wrap:anywhere; font-family:Consolas,monospace;">${safeOpt}</span>
                        </div>
                    `;
                });
            }
        })
        .catch(err => {
            console.error("AJAX Error:", err);
            actionBtn.disabled = false;
        });
    }
}
</script>
<?php endif; ?>
</body>
</html>