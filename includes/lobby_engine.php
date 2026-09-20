<?php
// includes/lobby_engine.php

function generate_room_code($conn) {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // no O/0/I/1 to avoid confusion
    do {
        $code = "";
        for ($i = 0; $i < 6; $i++) $code .= $chars[rand(0, strlen($chars) - 1)];
        $check = mysqli_prepare($conn, "SELECT id FROM matches WHERE room_code = ?");
        mysqli_stmt_bind_param($check, "s", $code);
        mysqli_stmt_execute($check);
        $exists = mysqli_stmt_get_result($check)->fetch_assoc();
    } while ($exists);
    return $code;
}

// Generates the full question set for a match and flips it to in_progress.
function start_match($conn, $matchId, $gameMode, $totalQuestions) {
    for ($i = 0; $i < $totalQuestions; $i++) {
        $q = generate_match_question($gameMode);
        $optionsJson = json_encode($q["options"]);
        $insert = mysqli_prepare($conn, "INSERT INTO match_questions (match_id, question_index, prompt, options_json, correct_index) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert, "iissi", $matchId, $i, $q["prompt"], $optionsJson, $q["correct_index"]);
        mysqli_stmt_execute($insert);
    }

    $update = mysqli_prepare($conn, "UPDATE matches SET status = 'in_progress', started_at = NOW(), current_question_started_at = NOW(), current_question_index = 0 WHERE id = ?");
    mysqli_stmt_bind_param($update, "i", $matchId);
    mysqli_stmt_execute($update);
}

// Advances a match to its next question, or finishes it if that was the last one.
// Resets every player's answer state for the new round.
function advance_match($conn, $matchId) {
    $stmt = mysqli_prepare($conn, "SELECT current_question_index, total_questions FROM matches WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $matchId);
    mysqli_stmt_execute($stmt);
    $match = mysqli_stmt_get_result($stmt)->fetch_assoc();
    if (!$match) return;

    $nextIndex = (int) $match["current_question_index"] + 1;

    if ($nextIndex >= (int) $match["total_questions"]) {
        finish_match($conn, $matchId);
        return;
    }

    $update = mysqli_prepare($conn, "UPDATE matches SET current_question_index = ?, current_question_started_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($update, "ii", $nextIndex, $matchId);
    mysqli_stmt_execute($update);

    $resetPlayers = mysqli_prepare($conn, "UPDATE match_players SET current_answer_index = NULL, answered_at = NULL WHERE match_id = ?");
    mysqli_stmt_bind_param($resetPlayers, "i", $matchId);
    mysqli_stmt_execute($resetPlayers);
}

// Marks a match finished, determines the winner, and saves each player's
// result into the normal scores/XP/badge systems - same as any other game.
function finish_match($conn, $matchId) {
    $playersResult = mysqli_query($conn, "SELECT user_id, score FROM match_players WHERE match_id = $matchId ORDER BY score DESC");
    $players = mysqli_fetch_all($playersResult, MYSQLI_ASSOC);
    if (empty($players)) return;

    $winnerId = $players[0]["user_id"];
    // tie: if scores are equal, no single winner
    if (count($players) > 1 && $players[0]["score"] === $players[1]["score"]) $winnerId = null;

    $update = mysqli_prepare($conn, "UPDATE matches SET status = 'finished', ended_at = NOW(), winner_id = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "ii", $winnerId, $matchId);
    mysqli_stmt_execute($update);

    foreach ($players as $p) {
        $uid = $p["user_id"];
        $score = $p["score"];

        $insert = mysqli_prepare($conn, "INSERT INTO scores (user_id, match_id, game_type, points, played_at) VALUES (?, ?, 'multiplayer_1v1', ?, NOW())");
        mysqli_stmt_bind_param($insert, "iii", $uid, $matchId, $score);
        mysqli_stmt_execute($insert);

        $pStmt = mysqli_prepare($conn, "SELECT correct_count, wrong_count FROM match_players WHERE match_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($pStmt, "ii", $matchId, $uid);
        mysqli_stmt_execute($pStmt);
        $counts = mysqli_stmt_get_result($pStmt)->fetch_assoc();

        award_xp($conn, $uid, $score);
        $updateProg = mysqli_prepare($conn, "UPDATE user_progress SET quizzes_completed = quizzes_completed + 1, total_correct = total_correct + ?, total_wrong = total_wrong + ? WHERE user_id = ?");
        mysqli_stmt_bind_param($updateProg, "iii", $counts["correct_count"], $counts["wrong_count"], $uid);
        mysqli_stmt_execute($updateProg);

        check_and_award_badges($conn, $uid);
    }
}