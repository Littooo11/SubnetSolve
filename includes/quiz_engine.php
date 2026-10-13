<?php
require_once __DIR__ . "/subnet_engine.php";

function difficulty_prefix_range($difficulty) {
    $ranges = [
        "easy"   => [24, 25],
        "medium" => [26, 28],
        "hard"   => [29, 30],
    ];
    return $ranges[$difficulty] ?? $ranges["medium"];
}

function build_mcq_options($correct, $distractors) {
    $distractors = array_values(array_unique($distractors));
    shuffle($distractors);
    $options = array_slice($distractors, 0, 3);
    $options[] = $correct;
    shuffle($options);
    return ["options" => $options, "correct_index" => array_search($correct, $options)];
}

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
        "prompt"        => $labels[$topic],
        "options"       => $built["options"],
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
        "prompt"        => "What is the $label for a /$correctPrefix network?",
        "options"       => $built["options"],
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
        "prompt"        => "How many usable host addresses are available in a /$correctPrefix network?",
        "options"       => $built["options"],
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
    $correct = "Class $correctClass";

    return [
        "prompt"        => "What class does the IP address $ip belong to?",
        "options"       => $options,
        "correct_index" => array_search($correct, $options),
    ];
}

function generate_public_private_question() {
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
        "prompt"        => "Which of the following is a private IP address?",
        "options"       => $options,
        "correct_index" => array_search($privateIp, $options),
    ];
}

function generate_osi_layer_question() {
    $concepts = [
        [
            "concept"     => "IP address",
            "correct"     => "Network layer",
            "distractors" => ["Application layer", "Transport layer", "Data Link layer"]
        ],
        [
            "concept"     => "TCP",
            "correct"     => "Transport layer",
            "distractors" => ["Application layer", "Network layer", "Data Link layer"]
        ],
        [
            "concept"     => "Ethernet",
            "correct"     => "Data Link layer",
            "distractors" => ["Application layer", "Transport layer", "Network layer"]
        ],
        [
            "concept"     => "HTTP",
            "correct"     => "Application layer",
            "distractors" => ["Transport layer", "Network layer", "Data Link layer"]
        ]
    ];

    $selected = $concepts[array_rand($concepts)];
    $built = build_mcq_options($selected["correct"], $selected["distractors"]);

    return [
        "prompt"        => "To which OSI layer does " . $selected["concept"] . " belong?",
        "options"       => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_vlsm_cidr_question() {
    $bank = [
        [
            "prompt"  => "What does VLSM stand for?",
            "correct" => "Variable Length Subnet Mask",
            "distractors" => ["Virtual Local Subnet Management", "Variable Link System Method", "Verified Length Subnet Mapping"]
        ],
        [
            "prompt"  => "What is the main purpose of VLSM?",
            "correct" => "To create subnets of different sizes based on host requirements",
            "distractors" => ["To increase internet speed", "To encrypt network traffic", "To assign domain names to devices"]
        ],
        [
            "prompt"  => "Which subnet prefix provides 62 usable IPv4 host addresses?",
            "correct" => "/26",
            "distractors" => ["/25", "/27", "/28"]
        ],
        [
            "prompt"  => "A department needs 50 usable host addresses. Which is the smallest suitable subnet?",
            "correct" => "/26",
            "distractors" => ["/27", "/28", "/29"]
        ],
        [
            "prompt"  => "When designing a VLSM network, which subnet should generally be allocated first?",
            "correct" => "The subnet requiring the most hosts",
            "distractors" => ["The subnet requiring the fewest hosts", "The subnet with the highest IP address", "The subnet with the longest name"]
        ],
        [
            "prompt"  => "What does CIDR stand for?",
            "correct" => "Classless Inter-Domain Routing",
            "distractors" => ["Centralized Internet Data Routing", "Classful Internal Domain Routing", "Common Internet Device Resolution"]
        ],
        [
            "prompt"  => "What does the /24 mean in 192.168.1.0/24?",
            "correct" => "The network prefix is 24 bits long",
            "distractors" => ["There are 24 usable host addresses", "The IP address contains 24 bytes", "The network has 24 subnets"]
        ],
        [
            "prompt"  => "How many total IPv4 addresses are in a /26 subnet?",
            "correct" => "64",
            "distractors" => ["32", "128", "256"]
        ],
        [
            "prompt"  => "Which subnet mask corresponds to /24?",
            "correct" => "255.255.255.0",
            "distractors" => ["255.255.0.0", "255.255.255.128", "255.255.255.192"]
        ],
        [
            "prompt"  => "How many usable host addresses are normally available in an IPv4 /27 subnet?",
            "correct" => "30",
            "distractors" => ["32", "62", "16"]
        ],
    ];

    $q = $bank[array_rand($bank)];
    $built = build_mcq_options($q["correct"], $q["distractors"]);

    return [
        "prompt"        => $q["prompt"],
        "options"       => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

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
        "prompt"        => "What is the decimal value of the binary number $binaryStr?",
        "options"       => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function generate_match_question($gameMode, $difficulty = "medium") {
    if ($gameMode === "binary") return generate_binary_mcq_question(4);
    if ($gameMode === "both") {
        return rand(0, 1) === 0 ? generate_showdown_question($difficulty) : generate_binary_mcq_question(4);
    }
    return generate_showdown_question($difficulty);
}
function generate_showdown_question($difficulty = "medium") {
    global $conn;
    
    // Attempt to pull a question from the database
    if (isset($conn)) {
        $stmt = mysqli_prepare($conn, "SELECT id, question_text AS prompt, option_a, option_b, option_c, option_d, correct_option FROM game_questions WHERE game_type = 'subnet_showdown' ORDER BY RAND() LIMIT 1");
        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt)->fetch_assoc();
            if ($res) {
                $options = [$res["option_a"], $res["option_b"], $res["option_c"], $res["option_d"]];
                $letterToIdx = ["A" => 0, "B" => 1, "C" => 2, "D" => 3];
                return [
                    "id" => (int) $res["id"],
                    "prompt" => $res["prompt"],
                    "options" => $options,
                    "correct_index" => $letterToIdx[strtoupper($res["correct_option"])] ?? 0
                ];
            }
        }
    }
    
    // Fallback to procedural generator if DB question not available
    return generate_address_question("network", $difficulty);
}

function generate_scenario_question($difficulty = "medium") {
    global $conn;
    
    if (isset($conn)) {
        $stmt = mysqli_prepare($conn, "SELECT id, question_text AS prompt, option_a, option_b, option_c, option_d, correct_option FROM game_questions WHERE game_type = 'scenario_game' ORDER BY RAND() LIMIT 1");
        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt)->fetch_assoc();
            if ($res) {
                $options = [$res["option_a"], $res["option_b"], $res["option_c"], $res["option_d"]];
                $letterToIdx = ["A" => 0, "B" => 1, "C" => 2, "D" => 3];
                return [
                    "id" => (int) $res["id"],
                    "prompt" => $res["prompt"],
                    "options" => $options,
                    "correct_index" => $letterToIdx[strtoupper($res["correct_option"])] ?? 0
                ];
            }
        }
    }
    
    // Fallback static bank
    $bank = scenario_static_bank($difficulty);
    return $bank[array_rand($bank)];
}