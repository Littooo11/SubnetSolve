<?php
// includes/avatars.php
// Preset avatars (no file uploads - just an emoji + color combo per user).

function get_avatar_presets() {
    return [
        ["id" => "fox",     "emoji" => "🦊", "color" => "#f59e0b"],
        ["id" => "owl",     "emoji" => "🦉", "color" => "#8b5cf6"],
        ["id" => "wolf",    "emoji" => "🐺", "color" => "#3b82f6"],
        ["id" => "panda",   "emoji" => "🐼", "color" => "#22c55e"],
        ["id" => "lion",    "emoji" => "🦁", "color" => "#eab308"],
        ["id" => "octopus", "emoji" => "🐙", "color" => "#14b8a6"],
        ["id" => "dragon",  "emoji" => "🐉", "color" => "#ef4444"],
        ["id" => "robot",   "emoji" => "🤖", "color" => "#ec4899"],
    ];
}

function get_avatar_by_id($avatarId) {
    foreach (get_avatar_presets() as $a) {
        if ($a["id"] === $avatarId) return $a;
    }
    return ["id" => "fox", "emoji" => "🦊", "color" => "#f59e0b"]; // fallback
}

// Renders a circular avatar badge as an HTML string.
function render_avatar($avatarId, $size = 34) {
    $a = get_avatar_by_id($avatarId);
    $fontSize = round($size * 0.55);
    return '<div style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; background:' . $a["color"] . '22;
        display:flex; align-items:center; justify-content:center; font-size:' . $fontSize . 'px; flex-shrink:0;
        border:1px solid ' . $a["color"] . '55;">' . $a["emoji"] . '</div>';
}
