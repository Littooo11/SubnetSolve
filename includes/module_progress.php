<?php
// includes/module_progress.php
// Helper functions to track and render Learning Module progress

function record_module_completion($conn, $userId, $moduleKey) {
    // 1. Insert completion record
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO user_module_progress (user_id, module_key) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $userId, $moduleKey);
    $executed = mysqli_stmt_execute($stmt);

    // 2. Sync lessons_completed count in user_progress table for the dashboard
    $syncStmt = mysqli_prepare($conn, "UPDATE user_progress SET lessons_completed = (SELECT COUNT(DISTINCT module_key) FROM user_module_progress WHERE user_id = ?) WHERE user_id = ?");
    mysqli_stmt_bind_param($syncStmt, "ii", $userId, $userId);
    mysqli_stmt_execute($syncStmt);

    return $executed;
}

function get_completed_modules($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT module_key FROM user_module_progress WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $completed = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $completed[] = $row["module_key"];
    }
    return $completed;
}

function calculate_learning_progress($conn, $userId) {
    $completed = get_completed_modules($conn, $userId);
    $count = count($completed);
    $percentage = min(100, $count * 20);
    return [
        "completed_count" => $count,
        "total_modules" => 5,
        "percentage" => $percentage,
        "completed_keys" => $completed
    ];
}
?>