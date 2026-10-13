<?php
// includes/subnet_engine.php
// Core subnetting math, reusable by any game mode (Practice Mode, timed matches, etc.)

function subnet_prefix_to_mask_long($prefix) {
    return $prefix == 0 ? 0 : (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
}

// Generates a random subnetting question.
// Restricted to /24-/30 so only the last octet varies across
// network/broadcast/mask/wildcard - keeps the UI simple (matches the mockup).
function generate_subnet_question($difficulty = "medium") {
    $ranges = [
        "easy"   => [24, 25],
        "medium" => [26, 28],
        "hard"   => [29, 30],
    ];
    [$min, $max] = $ranges[$difficulty] ?? $ranges["medium"];
    $prefix = rand($min, $max);
    $maskLong = subnet_prefix_to_mask_long($prefix);
    $wildcardLong = (~$maskLong) & 0xFFFFFFFF;
    $blockSize = $wildcardLong + 1; // number of addresses per subnet

    $oct1 = 192;
    $oct2 = 168;
    $oct3 = rand(0, 255);

    $numBlocks = intdiv(256, $blockSize);
    $networkOct4 = rand(0, $numBlocks - 1) * $blockSize;
    $broadcastOct4 = $networkOct4 + $blockSize - 1;

    // pick a random address inside this subnet to show the user (often the network address itself)
    $givenOct4 = rand($networkOct4, $broadcastOct4);

    $usableHosts = max(0, $blockSize - 2);
    $hostStartOct4 = $blockSize > 2 ? $networkOct4 + 1 : $networkOct4;
    $hostEndOct4   = $blockSize > 2 ? $broadcastOct4 - 1 : $broadcastOct4;

    $maskParts = [
        ($maskLong >> 24) & 255, ($maskLong >> 16) & 255,
        ($maskLong >> 8) & 255, $maskLong & 255
    ];
    $wildcardParts = [
        ($wildcardLong >> 24) & 255, ($wildcardLong >> 16) & 255,
        ($wildcardLong >> 8) & 255, $wildcardLong & 255
    ];

    return [
        "given_ip"        => "$oct1.$oct2.$oct3.$givenOct4",
        "prefix"          => $prefix,
        "oct1"            => $oct1,
        "oct2"            => $oct2,
        "oct3"            => $oct3,
        "network_oct4"   => $networkOct4,
        "broadcast_oct4" => $broadcastOct4,
        "host_start_oct4" => $hostStartOct4,
        "host_end_oct4"   => $hostEndOct4,
        "usable_hosts"    => $usableHosts,
        "mask_parts"      => $maskParts,
        "wildcard_parts"  => $wildcardParts,
    ];
}

// Compares submitted answers against the correct question data.
// $submitted is the raw $_POST array.
function check_subnet_answer($q, $submitted) {
    $correct = true;
    $details = [];

    $checks = [
        "network_oct4"   => (int) ($submitted["network_oct4"] ?? -1),
        "broadcast_oct4" => (int) ($submitted["broadcast_oct4"] ?? -1),
        "host_start_oct4" => (int) ($submitted["host_start_oct4"] ?? -1),
        "host_end_oct4"   => (int) ($submitted["host_end_oct4"] ?? -1),
        "usable_hosts"    => (int) ($submitted["usable_hosts"] ?? -1),
    ];

    foreach (["network_oct4", "broadcast_oct4", "host_start_oct4", "host_end_oct4", "usable_hosts"] as $field) {
        $ok = $checks[$field] === (int) $q[$field];
        $details[$field] = $ok;
        if (!$ok) $correct = false;
    }

    // mask + wildcard, each 4 octets
    for ($i = 0; $i < 4; $i++) {
        $maskOk = (int) ($submitted["mask_$i"] ?? -1) === (int) $q["mask_parts"][$i];
        $wildOk = (int) ($submitted["wildcard_$i"] ?? -1) === (int) $q["wildcard_parts"][$i];
        $details["mask_$i"] = $maskOk;
        $details["wildcard_$i"] = $wildOk;
        if (!$maskOk || !$wildOk) $correct = false;
    }

    return ["correct" => $correct, "details" => $details];
}

// Fetch a single random question from the database and dynamically shuffle options.
function get_db_question($conn, $gameType) {
    if (!$conn) return null;

    $stmt = mysqli_prepare($conn, "SELECT * FROM game_questions WHERE game_type = ? ORDER BY RAND() LIMIT 1");
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "s", $gameType);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if (!$row) return null;

    // Map database column choices
    $optionMap = [
        "A" => $row["option_a"],
        "B" => $row["option_b"],
        "C" => $row["option_c"],
        "D" => $row["option_d"]
    ];
    $correctText = $optionMap[strtoupper($row["correct_option"])] ?? $row["option_a"];

    // Collect choices into an array and shuffle them dynamically
    $options = [$row["option_a"], $row["option_b"], $row["option_c"], $row["option_d"]];
    shuffle($options);

    return [
        "id"            => $row["id"],
        "prompt"        => $row["question_text"],
        "options"       => $options,
        "correct_index" => array_search($correctText, $options),
        "explanation"   => $row["explanation"] ?? ""
    ];
}
function record_user_question_answer($conn, $userId, $questionId, $isCorrect) {
    if (!$conn || !$userId || !$questionId) return;

    $correctInc = $isCorrect ? 1 : 0;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_question_stats (user_id, question_id, times_encountered, times_correct, last_answered_at)
        VALUES (?, ?, 1, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            times_encountered = times_encountered + 1,
            times_correct = times_correct + ?,
            last_answered_at = NOW()
    ");
    mysqli_stmt_bind_param($stmt, "iiii", $userId, $questionId, $correctInc, $correctInc);
    mysqli_stmt_execute($stmt);
}