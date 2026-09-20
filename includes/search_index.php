<?php
// includes/search_index.php
// One place listing everything the dashboard search bar can find.
// To make something new searchable later (a new game, or a lesson once
// Learning Modules exists), just add another entry to this array -
// nothing else needs to change.

function get_search_index() {
    return [
        // Games (timed)
        ["type" => "game", "name" => "Dissect an IP Address", "desc" => "Timed subnetting challenge — network address, mask, host range.", "url" => "games/dissect_ip_timed.php", "icon" => "🌐"],
        ["type" => "game", "name" => "Binary Game", "desc" => "Convert binary to decimal against a 3-minute clock.", "url" => "games/binary_game.php", "icon" => "01"],
        ["type" => "game", "name" => "Subnet Showdown", "desc" => "Multiple-choice subnetting quiz, 30 seconds per question.", "url" => "games/subnet_showdown_timed.php", "icon" => "🎯"],
        ["type" => "game", "name" => "Multiplayer Lobby", "desc" => "Create or join a live 1v1 or group match.", "url" => "lobby.php", "icon" => "⚔️"],

        // Practice modes (untimed)
        ["type" => "practice", "name" => "Dissect an IP Address (Practice)", "desc" => "Same subnetting challenge, no timer.", "url" => "games/subnet_practice.php", "icon" => "🌐"],
        ["type" => "practice", "name" => "Binary Conversion (Practice)", "desc" => "Binary-to-decimal practice, no time pressure.", "url" => "games/binary_practice.php", "icon" => "01"],
        ["type" => "practice", "name" => "Subnet Showdown (Practice)", "desc" => "Same MCQ quiz, no timer.", "url" => "games/subnet_showdown_practice.php", "icon" => "🎯"],

        // Lessons - none built yet; add entries here once Learning Modules exists, e.g.:
        // ["type" => "lesson", "name" => "IPv4 Addressing Basics", "desc" => "...", "url" => "lessons/ipv4-basics.php", "icon" => "📘"],
    ];
}