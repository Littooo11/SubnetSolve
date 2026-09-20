<?php
session_start();
require "config.php";
require "includes/quiz_engine.php";
require "includes/xp_engine.php";
require "includes/badge_engine.php";
require "includes/lobby_engine.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];
$matchId = (int) ($_POST["match_id"] ?? 0);
$selected = isset($_POST["selected"]) && $_POST["selected"] !== "" ? (int) $_POST["selected"] : -1;

$matchStmt = mysqli_prepare($conn, "SELECT * FROM matches WHERE id = ?");
mysqli_stmt_bind_param($matchStmt, "i", $matchId);
mysqli_stmt_execute($matchStmt);
$match = mysqli_stmt_get_result($matchStmt)->fetch_assoc();

if (!$match || $match["status"] !== "in_progress") {
    header("Location: lobby.php");
    exit();
}

$playerStmt = mysqli_prepare($conn, "SELECT * FROM match_players WHERE match_id = ? AND user_id = ?");
mysqli_stmt_bind_param($playerStmt, "ii", $matchId, $userId);
mysqli_stmt_execute($playerStmt);
$player = mysqli_stmt_get_result($playerStmt)->fetch_assoc();

// Not a participant, or already answered this round (e.g. double-submit, or
// the timeout in match_state.php already marked them) - just go back quietly.
if (!$player || $player["current_answer_index"] !== null) {
    header("Location: match.php?code=" . urlencode($match["room_code"]));
    exit();
}

$qStmt = mysqli_prepare($conn, "SELECT correct_index FROM match_questions WHERE match_id = ? AND question_index = ?");
mysqli_stmt_bind_param($qStmt, "ii", $matchId, $match["current_question_index"]);
mysqli_stmt_execute($qStmt);
$question = mysqli_stmt_get_result($qStmt)->fetch_assoc();

$isCorrect = $question && $selected === (int) $question["correct_index"];

if ($isCorrect) {
    $TIME_PER_QUESTION = 45;
    $elapsed = $match["current_question_started_at"] ? time() - strtotime($match["current_question_started_at"]) : 0;
    $remaining = max(0, $TIME_PER_QUESTION - $elapsed);
    $speedBonus = round(($remaining / $TIME_PER_QUESTION) * 10); // up to +10 for a fast correct answer
    $points = 20 + $speedBonus;

    $update = mysqli_prepare($conn, "UPDATE match_players SET score = score + ?, correct_count = correct_count + 1, current_answer_index = ?, answered_at = NOW() WHERE match_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($update, "iiii", $points, $selected, $matchId, $userId);
    mysqli_stmt_execute($update);
} else {
    $update = mysqli_prepare($conn, "UPDATE match_players SET wrong_count = wrong_count + 1, current_answer_index = ?, answered_at = NOW() WHERE match_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($update, "iii", $selected, $matchId, $userId);
    mysqli_stmt_execute($update);
}

// If everyone in the match has now answered this round, advance immediately
// rather than waiting for the timeout.
$unansweredCheck = mysqli_query($conn, "SELECT COUNT(*) AS c FROM match_players WHERE match_id = $matchId AND current_answer_index IS NULL");
$unansweredCount = (int) mysqli_fetch_assoc($unansweredCheck)["c"];

if ($unansweredCount === 0) {
    advance_match($conn, $matchId);
}

header("Location: match.php?code=" . urlencode($match["room_code"]));
exit();