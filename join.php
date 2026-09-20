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

$code = trim($_GET["code"] ?? $_POST["code"] ?? "");
$password = trim($_POST["password"] ?? "");
$code = strtoupper($code);

function fail($msg) {
    header("Location: lobby.php?error=" . urlencode($msg));
    exit();
}

if ($code === "") fail("Please enter a room code.");

$stmt = mysqli_prepare($conn, "SELECT * FROM matches WHERE room_code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$match = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$match) fail("No lobby found with that room code.");
if ($match["status"] === "finished") fail("That match has already ended.");

if ($match["password_hash"] && !password_verify($password, $match["password_hash"])) {
    fail("Incorrect password for this lobby.");
}

$already = mysqli_prepare($conn, "SELECT id FROM match_players WHERE match_id = ? AND user_id = ?");
mysqli_stmt_bind_param($already, "ii", $match["id"], $userId);
mysqli_stmt_execute($already);
$isParticipant = (bool) mysqli_stmt_get_result($already)->fetch_assoc();

if (!$isParticipant) {
    $countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM match_players WHERE match_id = " . (int) $match["id"]);
    $count = mysqli_fetch_assoc($countResult)["c"];

    if ($count >= $match["max_players"]) fail("This lobby is full.");

    $join = mysqli_prepare($conn, "INSERT INTO match_players (match_id, user_id, joined_at) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($join, "ii", $match["id"], $userId);
    mysqli_stmt_execute($join);
}

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM match_players WHERE match_id = " . (int) $match["id"]);
$count = mysqli_fetch_assoc($countResult)["c"];

if ($count >= $match["max_players"] && $match["status"] === "waiting") {
    start_match($conn, $match["id"], $match["game_mode"], $match["total_questions"]);
}

header("Location: match.php?code=" . urlencode($code));
exit();