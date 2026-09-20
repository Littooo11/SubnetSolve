<?php
session_start();
require "config.php";
require "includes/lobby_engine.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: lobby.php");
    exit();
}

$gameMode = in_array($_POST["game_mode"] ?? "", ["subnetting", "binary", "both"]) ? $_POST["game_mode"] : "subnetting";
$visibility = ($_POST["visibility"] ?? "public") === "private" ? "private" : "public";
$maxPlayers = in_array((int) ($_POST["max_players"] ?? 2), [2, 4, 6, 8]) ? (int) $_POST["max_players"] : 2;
$roomName = trim($_POST["room_name"] ?? "");
$description = trim($_POST["description"] ?? "");
$password = trim($_POST["password"] ?? "");
$passwordHash = $password !== "" ? password_hash($password, PASSWORD_DEFAULT) : null;

$roomCode = generate_room_code($conn);

$insert = mysqli_prepare($conn, "INSERT INTO matches (room_code, room_name, description, password_hash, game_mode, visibility, max_players, host_user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
mysqli_stmt_bind_param($insert, "ssssssii", $roomCode, $roomName, $description, $passwordHash, $gameMode, $visibility, $maxPlayers, $userId);
mysqli_stmt_execute($insert);
$matchId = mysqli_insert_id($conn);

$joinPlayer = mysqli_prepare($conn, "INSERT INTO match_players (match_id, user_id, joined_at) VALUES (?, ?, NOW())");
mysqli_stmt_bind_param($joinPlayer, "ii", $matchId, $userId);
mysqli_stmt_execute($joinPlayer);

header("Location: match.php?code=" . urlencode($roomCode));
exit();