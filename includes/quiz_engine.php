<?php
// includes/quiz_engine.php
// Generates multiple-choice (A/B/C/D) questions for "Subnet Showdown",
// covering network/broadcast address, masks, host range, IP class, and public/private.

require_once __DIR__ . "/subnet_engine.php";

// Difficulty -> prefix range used by every subnet-based question below.
function difficulty_prefix_range($difficulty) {
    $ranges = [
        "easy"   => [24, 25],
        "medium" => [26, 28],
        "hard"   => [29, 30],
    ];
    return $ranges[$difficulty] ?? $ranges["medium"];
}

// Shuffles a correct answer + 3 distractors, returns [options[], correct_index]
function build_mcq_options($correct, $distractors) {
    $distractors = array_values(array_unique($distractors));
    shuffle($distractors);
    $options = array_slice($distractors, 0, 3);
    $options[] = $correct;
    shuffle($options);
    return ["options" => $options, "correct_index" => array_search($correct, $options)];
}

// Topics 1-4: network address, broadcast address, first host, last host.
// All four reuse the same subnet context, so the 4 candidate values
// (network/broadcast/host-start/host-end) double as natural distractors.
function generate_address_question($topic, $difficulty = "medium") {
    $q = generate_subnet_question($difficulty);
    $base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";

    $pool = [
        "network"   => $base . $q["network_oct4"],
        "broadcast" => $base . $q["broadcast_oct4"],
        "first"     => $base . $q["host_start_oct4"],
        "last"      => $base . $q["host_end_oct4"],
    ];

    $labels = [
        "network"   => "What is the network address for the IP address {$q['given_ip']}/{$q['prefix']}?",
        "broadcast" => "What is the broadcast address for the IP address {$q['given_ip']}/{$q['prefix']}?",
        "first"     => "What is the first usable host address for the IP address {$q['given_ip']}/{$q['prefix']}?",
        "last"      => "What is the last usable host address for the IP address {$q['given_ip']}/{$q['prefix']}?",
    ];

    $correct = $pool[$topic];
    $distractors = array_values(array_diff($pool, [$correct]));
    $built = build_mcq_options($correct, $distractors);

    return [
        "prompt"   => $labels[$topic],
        "options"  => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_mask_question($topic, $difficulty = "medium") {
    [$min, $max] = difficulty_prefix_range($difficulty);
    $prefixes = range($min, $max);

    // Masks/wildcards only meaningfully differ across the 24-30 range, so if a
    // difficulty band is too narrow to supply 3 distractors, widen just for this question.
    if (count($prefixes) < 4) $prefixes = range(24, 30);

    $correctPrefix = $prefixes[array_rand($prefixes)];
    $otherPrefixes = array_values(array_diff($prefixes, [$correctPrefix]));
    shuffle($otherPrefixes);
    $distractorPrefixes = array_slice($otherPrefixes, 0, 3);

    $prefix_to_string = function($prefix, $wildcard = false) {
        $maskLong = subnet_prefix_to_mask_long($prefix);
        if ($wildcard) $maskLong = (~$maskLong) & 0xFFFFFFFF;
        return implode(".", [($maskLong >> 24) & 255, ($maskLong >> 16) & 255, ($maskLong >> 8) & 255, $maskLong & 255]);
    };

    $isWildcard = $topic === "wildcard";
    $correct = $prefix_to_string($correctPrefix, $isWildcard);
    $distractors = array_map(fn($p) => $prefix_to_string($p, $isWildcard), $distractorPrefixes);
    $built = build_mcq_options($correct, $distractors);

    $label = $isWildcard ? "wildcard mask" : "subnet mask";
    return [
        "prompt"   => "What is the $label for a /$correctPrefix network?",
        "options"  => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_host_count_question($difficulty = "medium") {
    [$min, $max] = difficulty_prefix_range($difficulty);
    $prefixes = range($min, $max);
    if (count($prefixes) < 4) $prefixes = range(24, 30);

    $correctPrefix = $prefixes[array_rand($prefixes)];
    $otherPrefixes = array_values(array_diff($prefixes, [$correctPrefix]));
    shuffle($otherPrefixes);
    $distractorPrefixes = array_slice($otherPrefixes, 0, 3);

    $hostsFor = fn($p) => max(0, (1 << (32 - $p)) - 2);
    $correct = (string) $hostsFor($correctPrefix);
    $distractors = array_map(fn($p) => (string) $hostsFor($p), $distractorPrefixes);
    $built = build_mcq_options($correct, $distractors);

    return [
        "prompt"   => "How many usable host addresses are available in a /$correctPrefix network?",
        "options"  => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_ip_class_question() {
    // Restrict to Class A-D so all 4 answer choices are meaningful.
    $ranges = [
        "A" => [1, 126],
        "B" => [128, 191],
        "C" => [192, 223],
        "D" => [224, 239],
    ];
    $classes = array_keys($ranges);
    $correctClass = $classes[array_rand($classes)];
    [$lo, $hi] = $ranges[$correctClass];
    $firstOctet = rand($lo, $hi);
    $ip = "$firstOctet." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254);

    $options = array_map(fn($c) => "Class $c", $classes);
    $correct = "Class $correctClass";

    return [
        "prompt"   => "What class does the IP address $ip belong to?",
        "options"  => $options,
        "correct_index" => array_search($correct, $options),
    ];
}

function generate_public_private_question() {
    // One private IP (RFC 1918) + three public-looking IPs.
    $privateGenerators = [
        fn() => "10." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254),
        fn() => "172." . rand(16, 31) . "." . rand(0, 255) . "." . rand(1, 254),
        fn() => "192.168." . rand(0, 255) . "." . rand(1, 254),
    ];
    $privateIp = $privateGenerators[array_rand($privateGenerators)]();

    $publicFirstOctets = array_diff(range(1, 223), [10, 127, 169, 172, 192]);
    $publicIps = [];
    while (count($publicIps) < 3) {
        $o1 = $publicFirstOctets[array_rand($publicFirstOctets)];
        $ip = "$o1." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254);
        if (!in_array($ip, $publicIps)) $publicIps[] = $ip;
    }

    $options = array_merge($publicIps, [$privateIp]);
    shuffle($options);

    return [
        "prompt"   => "Which of the following is a private IP address?",
        "options"  => $options,
        "correct_index" => array_search($privateIp, $options),
    ];
}

// $digits: how many bits/digits the binary number has (3, 4, or 5) - controls difficulty.
function generate_binary_mcq_question($digits = 4) {
    $max = (1 << $digits) - 1; // e.g. 4 digits -> 0-15
    $decimal = rand(0, $max);
    $binaryStr = str_pad(decbin($decimal), $digits, "0", STR_PAD_LEFT);
    $correct = (string) $decimal;

    $distractors = [];
    while (count($distractors) < 3 && count($distractors) < $max) {
        $d = rand(0, $max);
        if ($d !== $decimal && !in_array((string) $d, $distractors)) $distractors[] = (string) $d;
    }
    $built = build_mcq_options($correct, $distractors);

    return [
        "prompt"   => "What is the decimal value of the binary number $binaryStr?",
        "options"  => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

// Mode-aware entry point for multiplayer matches: 'subnetting', 'binary', or 'both'
function generate_match_question($gameMode, $difficulty = "medium") {
    if ($gameMode === "binary") return generate_binary_mcq_question(4);
    if ($gameMode === "both") {
        return rand(0, 1) === 0 ? generate_showdown_question($difficulty) : generate_binary_mcq_question(4);
    }
    return generate_showdown_question($difficulty);
}

// Main entry point: picks a random topic and generates its question.
function generate_showdown_question($difficulty = "medium") {
    $topics = ["network", "broadcast", "first", "last", "mask", "wildcard", "host_count", "ip_class", "public_private"];
    $topic = $topics[array_rand($topics)];

    switch ($topic) {
        case "network":
        case "broadcast":
        case "first":
        case "last":
            return generate_address_question($topic, $difficulty);
        case "mask":
            return generate_mask_question("mask", $difficulty);
        case "wildcard":
            return generate_mask_question("wildcard", $difficulty);
        case "host_count":
            return generate_host_count_question($difficulty);
        case "ip_class":
            return generate_ip_class_question();
        case "public_private":
            return generate_public_private_question();
    }
}