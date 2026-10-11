<?php
// includes/complete_lesson.php
session_start();
header("Content-Type: application/json");
require __DIR__ . "/../config.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$userId = $_SESSION["user_id"];

// Increment completed lessons count by 1 up to a max of 5
$stmt = mysqli_prepare($conn, "UPDATE user_progress SET lessons_completed = LEAST(5, lessons_completed + 1) WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

// Retrieve updated lesson progress
$check = mysqli_prepare($conn, "SELECT lessons_completed FROM user_progress WHERE user_id = ?");
mysqli_stmt_bind_param($check, "i", $userId);
mysqli_stmt_execute($check);
$progress = mysqli_stmt_get_result($check)->fetch_assoc();

$completed = (int) ($progress["lessons_completed"] ?? 0);
$percent = min(100, round(($completed / 5) * 100));

echo json_encode([
    "success" => true,
    "lessons_completed" => $completed,
    "progress_percent" => $percent
]);