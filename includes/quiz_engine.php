<?php
// includes/quiz_engine.php
// Generates multiple-choice (A/B/C/D) questions for "Subnet Showdown",
// covering network/broadcast address, masks, host range, IP class, and public/private.

require_once __DIR__ . "/subnet_engine.php";

// Difficulty -> prefix range used by every subnet-based question below.
function difficulty_prefix_range($difficulty) {
    $ranges = [
        "easy" => [24, 25],
        "medium" => [26, 28],
        "hard" => [29, 30],
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
function generate_address_question($topic, $difficulty = "medium") {
    $q = generate_subnet_question($difficulty);
    $base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";

    $pool = [
        "network" => $base . $q["network_oct4"],
        "broadcast" => $base . $q["broadcast_oct4"],
        "first" => $base . $q["host_start_oct4"],
        "last" => $base . $q["host_end_oct4"],
    ];

    $labels = [
        "network" => "What is the network address for the IP address {$q['given_ip']}/{$q['prefix']}?",
        "broadcast" => "What is the broadcast address for the IP address {$q['given_ip']}/{$q['prefix']}?",
        "first" => "What is the first usable host address for the IP address {$q['given_ip']}/{$q['prefix']}?",
        "last" => "What is the last usable host address for the IP address {$q['given_ip']}/{$q['prefix']}?",
    ];

    $correct = $pool[$topic];
    $distractors = array_values(array_diff($pool, [$correct]));
    $built = build_mcq_options($correct, $distractors);

    return [
        "prompt" => $labels[$topic],
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_mask_question($topic, $difficulty = "medium") {
    [$min, $max] = difficulty_prefix_range($difficulty);
    $prefixes = range($min, $max);

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
        "prompt" => "What is the $label for a /$correctPrefix network?",
        "options" => $built["options"],
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
        "prompt" => "How many usable host addresses are available in a /$correctPrefix network?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_ip_class_question() {
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

    return [
        "prompt" => "What class does the IP address $ip belong to?",
        "options" => $options,
        "correct_index" => array_search("Class $correctClass", $options),
    ];
}

function generate_showdown_question($difficulty = "medium") {
    global $conn;
    if (isset($conn) && function_exists('get_db_question')) {
        $dbQ = get_db_question($conn, "subnet_showdown");
        if ($dbQ) return $dbQ;
    }

    $topics = ["network", "broadcast", "first", "last", "mask", "wildcard", "hosts", "class"];
    $topic = $topics[array_rand($topics)];

    switch ($topic) {
        case "network":
        case "broadcast":
        case "first":
        case "last":
            return generate_address_question($topic, $difficulty);
        case "mask":
        case "wildcard":
            return generate_mask_question($topic, $difficulty);
        case "hosts":
            return generate_host_count_question($difficulty);
        case "class":
            return generate_ip_class_question();
        default:
            return generate_address_question("network", $difficulty);
    }
}

if (!function_exists('generate_scenario_question')) {
    function generate_scenario_question($difficulty = "medium") {
        global $conn;
        if (isset($conn) && function_exists('get_db_question')) {
            $dbQ = get_db_question($conn, "scenario_game");
            if ($dbQ) return $dbQ;
        }
        return [
            "id" => 0,
            "prompt" => "Scenario: Office Network Allocation\nA department needs 50 usable host addresses. Which subnet mask provides enough addresses with minimal waste?",
            "options" => ["255.255.255.128", "255.255.255.192", "255.255.255.224", "255.255.255.240"],
            "correct_index" => 1,
            "explanation" => "A /26 mask (255.255.255.192) supports 62 usable hosts."
        ];
    }
}