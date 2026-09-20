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
$code = strtoupper(trim($_GET["code"] ?? ""));

$stmt = mysqli_prepare($conn, "SELECT * FROM matches WHERE room_code = ?");
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$match = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$match) {
    header("Location: lobby.php");
    exit();
}
$matchId = $match["id"];

// Finished matches are history, not a live lobby - leaving shouldn't delete
// the record of a completed game. Just send them back without touching it.
if ($match["status"] === "finished") {
    header("Location: lobby.php");
    exit();
}

// Remove me from this match
$leave = mysqli_prepare($conn, "DELETE FROM match_players WHERE match_id = ? AND user_id = ?");
mysqli_stmt_bind_param($leave, "ii", $matchId, $userId);
mysqli_stmt_execute($leave);

// How many players are left?
$countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM match_players WHERE match_id = $matchId");
$remaining = (int) mysqli_fetch_assoc($countResult)["c"];

if ($remaining === 0) {
    // Both players have now left - the lobby has no reason to exist anymore, clean it up entirely.
    mysqli_query($conn, "DELETE FROM match_questions WHERE match_id = $matchId");
    mysqli_query($conn, "DELETE FROM matches WHERE id = $matchId");
} elseif ($match["status"] === "in_progress") {
    // The match was already live and someone bailed mid-game - award the
    // remaining player a forfeit win and save their result normally.
    finish_match($conn, $matchId);
}
// If the lobby was still just "waiting" and one player leaves, leave it as-is
// so the remaining player can keep waiting for a new opponent.

header("Location: lobby.php");
exit();