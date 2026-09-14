<?php
// includes/save_score.php
// Generic score-saving endpoint. Any game calls this via fetch() when a session ends.
// Expects JSON POST body: { "game_type": "binary_game", "points": 150 }

session_start();
header("Content-Type: application/json");
require __DIR__ . "/../config.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$userId = $_SESSION["user_id"];
$data = json_decode(file_get_contents("php://input"), true);

$gameType = isset($data["game_type"]) ? substr(trim($data["game_type"]), 0, 50) : "general";
$points   = isset($data["points"]) ? (int) $data["points"] : 0;

if ($points < 0) $points = 0;

$stmt = mysqli_prepare($conn, "INSERT INTO scores (user_id, match_id, game_type, points, played_at) VALUES (?, NULL, ?, ?, NOW())");
mysqli_stmt_bind_param($stmt, "isi", $userId, $gameType, $points);
mysqli_stmt_execute($stmt);

$update = mysqli_prepare($conn, "UPDATE user_progress
    SET total_xp = total_xp + ?, quizzes_completed = quizzes_completed + 1
    WHERE user_id = ?");
mysqli_stmt_bind_param($update, "ii", $points, $userId);
mysqli_stmt_execute($update);

echo json_encode(["success" => true, "points" => $points]);