<?php
// includes/scenario_engine.php
// Generates scenario-flavored multiple-choice subnetting questions
// (word problems framed around departments/tickets) rather than bare technical prompts.

require_once __DIR__ . "/subnet_engine.php";

$SCENARIO_DEPARTMENTS = ["Marketing", "Sales", "IT", "Engineering", "Finance", "HR", "Warehouse", "Support"];

function scenario_build_options($correct, $distractors) {
    $distractors = array_values(array_unique($distractors));
    shuffle($distractors);
    $options = array_slice($distractors, 0, 3);
    $options[] = $correct;
    shuffle($options);
    return ["options" => $options, "correct_index" => array_search($correct, $options)];
}

function scenario_dept() {
    global $SCENARIO_DEPARTMENTS;
    return $SCENARIO_DEPARTMENTS[array_rand($SCENARIO_DEPARTMENTS)];
}

// Scenario 1: "What mask satisfies this host requirement?"
// Range stays <=27 regardless of difficulty, so there's always room for 3 higher
// (smaller-host-count) distractor prefixes up to /30.
function scenario_host_requirement($difficulty) {
    $ranges = ["easy" => [24, 25], "medium" => [25, 26], "hard" => [26, 27]];
    [$min, $max] = $ranges[$difficulty] ?? $ranges["medium"];
    $correctPrefix = rand($min, $max);

    $maskLong = subnet_prefix_to_mask_long($correctPrefix);
    $hostsNeeded = max(1, (1 << (32 - $correctPrefix)) - 2);
    $correctMask = implode(".", [($maskLong >> 24) & 255, ($maskLong >> 16) & 255, ($maskLong >> 8) & 255, $maskLong & 255]);

    $higherPrefixes = range($correctPrefix + 1, 30);
    shuffle($higherPrefixes);
    $distractorPrefixes = array_slice($higherPrefixes, 0, 3);
    $distractors = array_map(function ($p) {
        $m = subnet_prefix_to_mask_long($p);
        return implode(".", [($m >> 24) & 255, ($m >> 16) & 255, ($m >> 8) & 255, $m & 255]);
    }, $distractorPrefixes);

    $built = scenario_build_options($correctMask, $distractors);
    $dept = scenario_dept();

    return [
        "prompt" => "The $dept department needs a subnet that supports at least $hostsNeeded usable host addresses for their new office. Which subnet mask provides enough addresses, with the least waste?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

// Scenario 2: "Which address is the broadcast address for this assigned subnet?"
function scenario_broadcast($difficulty) {
    $q = generate_subnet_question($difficulty);
    $base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";
    $dept = scenario_dept();

    $pool = [$base . $q["network_oct4"], $base . $q["broadcast_oct4"], $base . $q["host_start_oct4"], $base . $q["host_end_oct4"]];
    $correct = $base . $q["broadcast_oct4"];
    $distractors = array_values(array_diff($pool, [$correct]));
    $built = scenario_build_options($correct, $distractors);

    return [
        "prompt" => "The $dept department has been assigned the subnet {$q['oct1']}.{$q['oct2']}.{$q['oct3']}.{$q['network_oct4']}/{$q['prefix']}. A technician needs the broadcast address to configure a network-wide alert. Which address should they use?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

// Scenario 3: "Which of these addresses can actually be assigned to a workstation?"
function scenario_valid_host($difficulty) {
    $q = generate_subnet_question($difficulty);
    $base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";
    $dept = scenario_dept();

    $validHost = $base . rand($q["host_start_oct4"], $q["host_end_oct4"]);
    $outsideOctet = ($q["network_oct4"] + 64) % 256;
    $outsideAddr = $base . $outsideOctet;

    $distractors = [$base . $q["network_oct4"], $base . $q["broadcast_oct4"], $outsideAddr];
    $built = scenario_build_options($validHost, $distractors);

    return [
        "prompt" => "A new $dept workstation needs an IP address inside the {$q['oct1']}.{$q['oct2']}.{$q['oct3']}.{$q['network_oct4']}/{$q['prefix']} subnet. Which of these addresses can actually be assigned to it?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

// Scenario 4: "Is this server's address reachable directly from the public internet?"
// (No subnet-size concept here, so difficulty doesn't change this one's mechanics.)
function scenario_public_private() {
    $privateGenerators = [
        fn() => "10." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254),
        fn() => "172." . rand(16, 31) . "." . rand(0, 255) . "." . rand(1, 254),
        fn() => "192.168." . rand(0, 255) . "." . rand(1, 254),
    ];
    $isPrivateScenario = rand(0, 1) === 0;
    $dept = scenario_dept();

    if ($isPrivateScenario) {
        $correctIp = $privateGenerators[array_rand($privateGenerators)]();
        $prompt = "An internal $dept file server is configured with the IP address $correctIp. Which statement is true?";
        $correct = "Not directly reachable from the internet (private address)";
    } else {
        $publicFirstOctets = array_diff(range(1, 223), [10, 127, 169, 172, 192]);
        $o1 = $publicFirstOctets[array_rand($publicFirstOctets)];
        $correctIp = "$o1." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254);
        $prompt = "A $dept department's public-facing web server is configured with the IP address $correctIp. Which statement is true?";
        $correct = "Directly reachable from the internet (public address)";
    }

    $options = [
        "Directly reachable from the internet (public address)",
        "Not directly reachable from the internet (private address)",
        "This address is invalid and cannot be assigned",
        "This address only works on IPv6 networks",
    ];

    return [
        "prompt" => $prompt,
        "options" => $options,
        "correct_index" => array_search($correct, $options),
    ];
}

// Scenario 5: "Troubleshooting - what network is this misconfigured device actually on?"
function scenario_network_troubleshoot($difficulty) {
    $q = generate_subnet_question($difficulty);
    $base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";
    $dept = scenario_dept();

    $pool = [$base . $q["network_oct4"], $base . $q["broadcast_oct4"], $base . $q["host_start_oct4"], $base . $q["host_end_oct4"]];
    $correct = $base . $q["network_oct4"];
    $distractors = array_values(array_diff($pool, [$correct]));
    $built = scenario_build_options($correct, $distractors);

    return [
        "prompt" => "A $dept employee reports their computer (configured with {$q['given_ip']}/{$q['prefix']}) can't reach a shared printer. You need to confirm the actual network address their device belongs to. What is it?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

// Main entry point
function generate_scenario_question($difficulty = "medium") {
    $templates = ["host_requirement", "broadcast", "valid_host", "public_private", "network_troubleshoot"];
    $pick = $templates[array_rand($templates)];

    switch ($pick) {
        case "host_requirement": return scenario_host_requirement($difficulty);
        case "broadcast": return scenario_broadcast($difficulty);
        case "valid_host": return scenario_valid_host($difficulty);
        case "public_private": return scenario_public_private();
        case "network_troubleshoot": return scenario_network_troubleshoot($difficulty);
    }
}
