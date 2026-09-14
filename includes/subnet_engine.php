<?php
// includes/subnet_engine.php
// Core subnetting math, reusable by any game mode (Practice Mode, timed matches, etc.)

function subnet_prefix_to_mask_long($prefix) {
    return $prefix == 0 ? 0 : (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
}

// Generates a random subnetting question.
// Restricted to /24-/30 so only the last octet varies across
// network/broadcast/mask/wildcard - keeps the UI simple (matches the mockup).
function generate_subnet_question() {
    $prefix = rand(24, 30);
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
        "given_ip"     => "$oct1.$oct2.$oct3.$givenOct4",
        "prefix"       => $prefix,
        "oct1"         => $oct1,
        "oct2"         => $oct2,
        "oct3"         => $oct3,
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