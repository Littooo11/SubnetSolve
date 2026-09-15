<?php
// includes/badge_engine.php
// Checks a user's current stats against all badge criteria and awards any newly-earned ones.

function check_and_award_badges($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT level, career_xp, quizzes_completed, current_streak FROM user_progress WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $progress = mysqli_stmt_get_result($stmt)->fetch_assoc();
    if (!$progress) return [];

    $stats = [
        "level"              => (int) $progress["level"],
        "career_xp"          => (int) $progress["career_xp"],
        "quizzes_completed"  => (int) $progress["quizzes_completed"],
        "current_streak"     => (int) $progress["current_streak"],
    ];

    // all badge definitions
    $allBadges = mysqli_query($conn, "SELECT * FROM badges");

    // badges this user already has
    $earnedStmt = mysqli_prepare($conn, "SELECT badge_id FROM user_badges WHERE user_id = ?");
    mysqli_stmt_bind_param($earnedStmt, "i", $userId);
    mysqli_stmt_execute($earnedStmt);
    $earnedResult = mysqli_stmt_get_result($earnedStmt);
    $earnedIds = [];
    while ($row = mysqli_fetch_assoc($earnedResult)) {
        $earnedIds[] = (int) $row["badge_id"];
    }

    $newlyEarned = [];

    while ($badge = mysqli_fetch_assoc($allBadges)) {
        if (in_array((int) $badge["id"], $earnedIds)) continue;

        $type = $badge["criteria_type"];
        if (!isset($stats[$type])) continue;

        if ($stats[$type] >= (int) $badge["criteria_value"]) {
            $insert = mysqli_prepare($conn, "INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())");
            mysqli_stmt_bind_param($insert, "ii", $userId, $badge["id"]);
            mysqli_stmt_execute($insert);
            $newlyEarned[] = $badge;
        }
    }

    return $newlyEarned;
}
