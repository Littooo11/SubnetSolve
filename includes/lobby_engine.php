<?php
// includes/lobby_engine.php

require_once "quiz_engine.php";
require_once "ipv6_engine.php";
require_once "scenario_engine.php";

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

// Generates dynamic question based on requested game mode
function generate_multiplayer_question($gameMode) {
    $mode = $gameMode;
    if ($mode === "both") {
        $modes = ["subnetting", "binary", "showdown", "hex", "ipv6", "scenario"];
        $mode = $modes[array_rand($modes)];
    }

    switch ($mode) {
        case "binary":
            $num = rand(1, 255);
            $bin = sprintf("%08b", $num);
            $options = [$bin];
            while (count($options) < 4) {
                $fake = sprintf("%08b", rand(1, 255));
                if (!in_array($fake, $options)) $options[] = $fake;
            }
            shuffle($options);
            return [
                "prompt" => "Convert decimal $num to an 8-bit binary string:",
                "options" => $options,
                "correct_index" => array_search($bin, $options)
            ];

        case "hex":
            $num = rand(10, 255);
            $hex = strtoupper(dechex($num));
            $options = [$hex];
            while (count($options) < 4) {
                $fake = strtoupper(dechex(rand(10, 255)));
                if (!in_array($fake, $options)) $options[] = $fake;
            }
            shuffle($options);
            return [
                "prompt" => "Convert decimal $num to Hexadecimal (8-bit):",
                "options" => $options,
                "correct_index" => array_search($hex, $options)
            ];

        case "ipv6":
            if (function_exists('generate_ipv6_question')) {
                return generate_ipv6_question();
            }
            // Fallback IPv6 Question
            $prefix = rand(48, 64);
            $prompt = "What is the default subnet prefix length for standard IPv6 Global Unicast assignments?";
            $options = ["/48", "/56", "/64", "/128"];
            return [
                "prompt" => $prompt,
                "options" => $options,
                "correct_index" => 2
            ];

        case "scenario":
            if (function_exists('generate_scenario_question')) {
                return generate_scenario_question();
            }
            // Fallback Scenario Question
            return [
                "prompt" => "A router needs to connect 50 hosts in a subnetwork. Which prefix length provides sufficient usable host addresses with minimal waste?",
                "options" => ["/27 (30 hosts)", "/26 (62 hosts)", "/25 (126 hosts)", "/24 (254 hosts)"],
                "correct_index" => 1
            ];

        case "showdown":
        case "subnetting":
        default:
            if (function_exists('generate_match_question')) {
                return generate_match_question($mode);
            }
            // Default subnet calculation question
            $octets = [rand(10, 220), rand(0, 255), rand(0, 255), rand(1, 250)];
            $ip = implode(".", $octets);
            $cidr = rand(24, 30);
            $maskOctet = 256 - pow(2, 32 - $cidr);
            $correctMask = "255.255.255." . $maskOctet;
            $options = [$correctMask, "255.255.255.0", "255.255.255.128", "255.255.255.240"];
            $options = array_unique($options);
            while (count($options) < 4) {
                $options[] = "255.255.255." . (256 - pow(2, rand(1, 4)));
                $options = array_unique($options);
            }
            shuffle($options);
            return [
                "prompt" => "What is the subnet mask for $ip/$cidr?",
                "options" => $options,
                "correct_index" => array_search($correctMask, $options)
            ];
    }
}

// Generates the full question set for a match and flips it to in_progress.
function start_match($conn, $matchId, $gameMode, $totalQuestions) {
    for ($i = 0; $i < $totalQuestions; $i++) {
        $q = generate_multiplayer_question($gameMode);
        $optionsJson = json_encode($q["options"]);
        $insert = mysqli_prepare($conn, "INSERT INTO match_questions (match_id, question_index, prompt, options_json, correct_index) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert, "iissi", $matchId, $i, $q["prompt"], $optionsJson, $q["correct_index"]);
        mysqli_stmt_execute($insert);
    }

    $update = mysqli_prepare($conn, "UPDATE matches SET status = 'in_progress', started_at = NOW(), current_question_started_at = NOW(), current_question_index = 0 WHERE id = ?");
    mysqli_stmt_bind_param($update, "i", $matchId);
    mysqli_stmt_execute($update);
}

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

function finish_match($conn, $matchId) {
    $playersResult = mysqli_query($conn, "SELECT user_id, score FROM match_players WHERE match_id = $matchId ORDER BY score DESC");
    $players = mysqli_fetch_all($playersResult, MYSQLI_ASSOC);
    if (empty($players)) return;

    $winnerId = $players[0]["user_id"];
    if (count($players) > 1 && $players[0]["score"] === $players[1]["score"]) $winnerId = null;

    $update = mysqli_prepare($conn, "UPDATE matches SET status = 'finished', ended_at = NOW(), winner_id = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "ii", $winnerId, $matchId);
    mysqli_stmt_execute($update);

    foreach ($players as $p) {
        $uid = $p["user_id"];
        $score = $p["score"];

        $insert = mysqli_prepare($conn, "INSERT INTO scores (user_id, match_id, game_type, points, played_at) VALUES (?, ?, 'multiplayer_match', ?, NOW())");
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