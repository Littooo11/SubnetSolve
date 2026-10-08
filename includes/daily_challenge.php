<?php
// includes/daily_challenge.php
// Tracks "complete 3 subnetting questions correctly today" and auto-awards
// a bonus the moment the threshold is reached.

define("DAILY_CHALLENGE_TARGET", 3);
define("DAILY_CHALLENGE_BONUS_XP", 50);

// Game types that count toward the daily challenge (subnetting-related only -
// Binary/Hex/IPv6 games don't count toward a "subnetting questions" challenge).
function daily_challenge_game_types() {
    return [
        "subnet_dissect_practice", "subnet_dissect_timed",
        "showdown_practice", "showdown_timed",
        "scenario_practice", "scenario_timed",
    ];
}

// Read-only: how many correct answers (capped at the target) the user has today.
function get_daily_challenge_progress($conn, $userId) {
    $types = daily_challenge_game_types();
    $placeholders = implode(",", array_fill(0, count($types), "?"));
    $types_str = str_repeat("s", count($types));

    $sql = "SELECT COALESCE(SUM(correct_count), 0) AS total FROM scores
            WHERE user_id = ? AND DATE(played_at) = CURDATE() AND game_type IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    $params = array_merge([$userId], $types);
    $bindTypes = "i" . $types_str;
    $stmt->bind_param($bindTypes, ...$params);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()["total"];

    $claimedStmt = mysqli_prepare($conn, "SELECT last_daily_challenge_date FROM user_progress WHERE user_id = ?");
    mysqli_stmt_bind_param($claimedStmt, "i", $userId);
    mysqli_stmt_execute($claimedStmt);
    $row = mysqli_stmt_get_result($claimedStmt)->fetch_assoc();
    $claimedToday = $row && $row["last_daily_challenge_date"] === date("Y-m-d");

    return [
        "progress" => min($total, DAILY_CHALLENGE_TARGET),
        "target" => DAILY_CHALLENGE_TARGET,
        "complete" => $total >= DAILY_CHALLENGE_TARGET,
        "claimed_today" => $claimedToday,
    ];
}

// Call this right after a subnetting-related game session is saved.
// Awards the bonus XP exactly once per calendar day, the moment the target is hit.
function check_and_award_daily_challenge($conn, $userId) {
    $status = get_daily_challenge_progress($conn, $userId);

    if ($status["complete"] && !$status["claimed_today"]) {
        $today = date("Y-m-d");
        $update = mysqli_prepare($conn, "UPDATE user_progress SET last_daily_challenge_date = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($update, "si", $today, $userId);
        mysqli_stmt_execute($update);

        $xpResult = award_xp($conn, $userId, DAILY_CHALLENGE_BONUS_XP);
        return ["awarded" => true, "bonus_xp" => DAILY_CHALLENGE_BONUS_XP, "xp_result" => $xpResult];
    }

    return ["awarded" => false];
}