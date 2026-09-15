<?php
// includes/rank_tiers.php

function get_rank_tiers() {
    // Ordered highest to lowest - first match wins.
    return [
        ["name" => "Grand Master", "min" => 8000, "color" => "#a78bfa"],
        ["name" => "Master",       "min" => 6000, "color" => "#eab308"],
        ["name" => "Diamond",      "min" => 4000, "color" => "#38bdf8"],
        ["name" => "Platinum",     "min" => 2500, "color" => "#2dd4bf"],
        ["name" => "Gold",         "min" => 1500, "color" => "#facc15"],
        ["name" => "Silver",       "min" => 1000, "color" => "#cbd5e1"],
        ["name" => "Bronze",       "min" => 500,  "color" => "#d97706"],
        ["name" => "Iron",         "min" => 0,    "color" => "#94a3b8"],
    ];
}

function get_rank_tier_for_xp($xp) {
    $tiers = get_rank_tiers();
    foreach ($tiers as $tier) {
        if ($xp >= $tier["min"]) return $tier;
    }
    return end($tiers);
}

function render_tier_badge($xp) {
    $t = get_rank_tier_for_xp($xp);
    return '<span style="background:' . $t["color"] . '22; color:' . $t["color"] . '; border:1px solid ' . $t["color"] . '55;
        padding:0.25rem 0.6rem; border-radius:6px; font-size:0.75rem; font-weight:bold;">' . htmlspecialchars($t["name"]) . '</span>';
}