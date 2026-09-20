<?php
session_start();
header("Content-Type: application/json");
require "config.php";
require "includes/quiz_engine.php";
require "includes/xp_engine.php";
require "includes/badge_engine.php";
require "includes/lobby_engine.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$code = strtoupper(trim($_GET["code"] ?? ""));
$stmt = mysqli_prepare($conn, "SELECT * FROM matches WHERE room_code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$match = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$match) {
    http_response_code(404);
    echo json_encode(["error" => "Match not found"]);
    exit();
}

$TIME_PER_QUESTION = 45;

// Self-healing timeout: if the current question's time is up and not everyone
// has answered yet, auto-fill missing answers as wrong and advance the round.
// This means the match keeps moving even if a player closes their tab.
if ($match["status"] === "in_progress" && $match["current_question_started_at"]) {
    $elapsed = time() - strtotime($match["current_question_started_at"]);
    if ($elapsed >= $TIME_PER_QUESTION) {
        $unanswered = mysqli_query($conn, "SELECT user_id FROM match_players WHERE match_id = " . (int) $match["id"] . " AND current_answer_index IS NULL");
        while ($row = mysqli_fetch_assoc($unanswered)) {
            $mark = mysqli_prepare($conn, "UPDATE match_players SET current_answer_index = -1, wrong_count = wrong_count + 1, answered_at = NOW() WHERE match_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($mark, "ii", $match["id"], $row["user_id"]);
            mysqli_stmt_execute($mark);
        }
        advance_match($conn, $match["id"]);

        // reload match row since it may have just changed (advanced or finished)
        mysqli_stmt_execute($stmt);
        $match = mysqli_stmt_get_result($stmt)->fetch_assoc();
    }
}

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM match_players WHERE match_id = " . (int) $match["id"]);
$playerCount = (int) mysqli_fetch_assoc($countResult)["c"];

$playersResult = mysqli_query($conn, "SELECT user_id, score, correct_count, (current_answer_index IS NOT NULL) AS answered
    FROM match_players WHERE match_id = " . (int) $match["id"] . " ORDER BY score DESC");
$players = [];
while ($row = mysqli_fetch_assoc($playersResult)) {
    $players[] = [
        "user_id" => (int) $row["user_id"],
        "score" => (int) $row["score"],
        "correct_count" => (int) $row["correct_count"],
        "answered" => (bool) $row["answered"],
    ];
}

echo json_encode([
    "status" => $match["status"],
    "current_question_index" => (int) $match["current_question_index"],
    "player_count" => $playerCount,
    "players" => $players,
]);