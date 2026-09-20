<?php
session_start();
require "config.php";
require "includes/quiz_engine.php";
require "includes/lobby_engine.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];
$code = strtoupper(trim($_GET["code"] ?? ""));

$stmt = mysqli_prepare($conn, "SELECT * FROM matches WHERE room_code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$match = mysqli_stmt_get_result($stmt)->fetch_assoc();

if ($match && $match["status"] === "waiting" && (int) $match["host_user_id"] === (int) $userId) {
    $countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM match_players WHERE match_id = " . (int) $match["id"]);
    $count = (int) mysqli_fetch_assoc($countResult)["c"];

    if ($count >= 2) {
        start_match($conn, $match["id"], $match["game_mode"], $match["total_questions"]);
    }
}

header("Location: match.php?code=" . urlencode($code));
exit();