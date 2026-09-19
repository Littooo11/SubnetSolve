<?php
// includes/xp_engine.php
// Central leveling logic. Any game/feature that awards XP should call award_xp()
// instead of updating user_progress directly, so leveling stays consistent everywhere.

// How much XP is needed to go from this level to the next.
// Grows each level so higher levels take progressively longer.
function xp_required_for_level($level) {
    return 500 + ($level - 1) * 150;
}

// Awards XP to a user, applies it to both career total and current-level progress,
// and handles leveling up (possibly multiple levels at once for a big score).
// Returns info about what happened, so the caller can show a "Level Up!" message.
function award_xp($conn, $userId, $points) {
    $stmt = mysqli_prepare($conn, "SELECT level, career_xp, total_xp, xp_to_next_level FROM user_progress WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$row) {
        // safety net: create the row if it's somehow missing
        $insert = mysqli_prepare($conn, "INSERT INTO user_progress (user_id) VALUES (?)");
        mysqli_stmt_bind_param($insert, "i", $userId);
        mysqli_stmt_execute($insert);
        $row = ["level" => 1, "career_xp" => 0, "total_xp" => 0, "xp_to_next_level" => xp_required_for_level(1)];
    }

    $level        = (int) $row["level"];
    $careerXp     = (int) $row["career_xp"] + $points;
    $totalXp      = (int) $row["total_xp"] + $points;
    $xpToNext     = (int) $row["xp_to_next_level"];
    $levelsGained = 0;

    while ($totalXp >= $xpToNext) {
        $totalXp -= $xpToNext;
        $level++;
        $levelsGained++;
        $xpToNext = xp_required_for_level($level);
    }

    $update = mysqli_prepare($conn, "UPDATE user_progress
        SET level = ?, career_xp = ?, total_xp = ?, xp_to_next_level = ?
        WHERE user_id = ?");
    mysqli_stmt_bind_param($update, "iiiii", $level, $careerXp, $totalXp, $xpToNext, $userId);
    mysqli_stmt_execute($update);

    return [
        "leveled_up"    => $levelsGained > 0,
        "levels_gained" => $levelsGained,
        "new_level"     => $level,
        "career_xp"     => $careerXp,
        "total_xp"      => $totalXp,
        "xp_to_next"    => $xpToNext,
    ];
}