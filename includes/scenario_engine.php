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

// Fixed scenario question bank (VLSM and CIDR challenges)
function scenario_static_bank($difficulty = "medium") {
    $questions = [
        "easy" => [
            [
                "prompt" => "Scenario: Office Network Expansion\nA company is setting up a new office. Its HR department needs 25 usable IP addresses for computers and printers. The network administrator wants to use the smallest subnet that can support all 25 devices. Which subnet prefix should the administrator choose?",
                "options" => ["/28", "/27", "/29", "/26"],
                "correct_index" => 1
            ],
            [
                "prompt" => "Scenario: Small Business Router\nA small business configures its router with the network 192.168.1.0/24. A technician needs to know how many usable IPv4 host addresses this network normally supports. What is the correct answer?",
                "options" => ["24", "256", "254", "128"],
                "correct_index" => 2
            ],
        ],
        "medium" => [
            [
                "prompt" => "Scenario: University Computer Laboratory\nA university has been assigned the network 192.168.10.0/24. The computer laboratory needs 100 usable host addresses. The IT administrator must select a subnet that can accommodate all the computers while minimizing wasted IP addresses. Which subnet prefix is the best choice?",
                "options" => ["/26", "/24", "/25", "/27"],
                "correct_index" => 2
            ],
            [
                "prompt" => "Scenario: Company Department Allocation\nA company receives 192.168.20.0/24 for its internal network. It needs three separate subnets:\n• Sales: 100 usable hosts\n• Finance: 50 usable hosts\n• IT Support: 20 usable hosts\nWhich set of subnet prefixes meets all three requirements while using the smallest suitable subnet for each department?",
                "options" => [
                    "Sales /26, Finance /27, IT /28",
                    "Sales /25, Finance /26, IT /27",
                    "Sales /24, Finance /25, IT /26",
                    "Sales /27, Finance /26, IT /25"
                ],
                "correct_index" => 1
            ],
            [
                "prompt" => "Scenario: Network Technician Troubleshooting\nA technician sees the IP configuration 192.168.10.25/26 on a workstation. The technician must identify the subnet mask to check whether the workstation is configured correctly. Which subnet mask corresponds to /26?",
                "options" => ["255.255.255.0", "255.255.255.192", "255.255.255.224", "255.255.255.128"],
                "correct_index" => 1
            ],
            [
                "prompt" => "Scenario: Office Subnet Capacity\nA growing office has been assigned 10.10.10.0/27 for a group of computers. The IT manager plans to connect 28 computers and needs to know whether the subnet has enough usable addresses. Which statement is correct?",
                "options" => [
                    "Yes, because /27 supports 32 usable hosts",
                    "Yes, because /27 supports 30 usable hosts",
                    "No, because /27 supports only 30 total addresses",
                    "No, because /27 supports only 14 usable hosts"
                ],
                "correct_index" => 1
            ],
        ],
        "hard" => [
            [
                "prompt" => "Scenario: New Branch Office\nA business has the address block 10.0.0.0/24. It needs a subnet for 60 employee devices and another subnet for 12 network devices. The administrator assigns the employee subnet first using 10.0.0.0/26. Which network address is the correct next allocation for the 12-device subnet, using the smallest suitable subnet?",
                "options" => ["10.0.0.64/28", "10.0.0.32/28", "10.0.0.64/29", "10.0.0.0/28"],
                "correct_index" => 0
            ],
            [
                "prompt" => "Scenario: Hospital Network Design\nA hospital receives 172.16.5.0/24 for three departments:\n• Administration: 50 hosts\n• Medical staff: 25 hosts\n• Security systems: 10 hosts\nThe network engineer wants to allocate the largest subnet first and ensure that all subnets fit inside the assigned block without overlapping. Which allocation is valid and meets the minimum host requirements?",
                "options" => [
                    "Admin 172.16.5.0/26, Medical 172.16.5.64/27, Security 172.16.5.96/28",
                    "Admin 172.16.5.0/27, Medical 172.16.5.32/27, Security 172.16.5.64/28",
                    "Admin 172.16.5.0/26, Medical 172.16.5.32/27, Security 172.16.5.96/28",
                    "Admin 172.16.5.0/26, Medical 172.16.5.64/28, Security 172.16.5.80/29"
                ],
                "correct_index" => 0
            ],
            [
                "prompt" => "Scenario: ISP Address Allocation\nAn internet service provider assigns a customer the IPv4 block 203.0.113.0/28 for a small test network. The customer wants to know the total number of IP addresses in the assigned block, including reserved network and broadcast addresses. How many total addresses are included?",
                "options" => ["8", "14", "16", "32"],
                "correct_index" => 2
            ],
            [
                "prompt" => "Scenario: Multi-Branch Company Routing\nA company has multiple branch networks within 10.20.0.0/24. Its network engineer wants to advertise the entire block as one route rather than advertise four separate contiguous /26 networks. Which CIDR route summarizes the entire address block?",
                "options" => ["10.20.0.0/25", "10.20.0.0/26", "10.20.0.0/23", "10.20.0.0/24"],
                "correct_index" => 3
            ],
        ]
    ];

    $list = $questions[$difficulty] ?? $questions["medium"];
    return $list[array_rand($list)];
}

// Scenario 1: "What mask satisfies this host requirement?"
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
    $templates = ["static_bank", "host_requirement", "broadcast", "valid_host", "public_private", "network_troubleshoot"];
    $pick = $templates[array_rand($templates)];

    switch ($pick) {
        case "static_bank": return scenario_static_bank($difficulty);
        case "host_requirement": return scenario_host_requirement($difficulty);
        case "broadcast": return scenario_broadcast($difficulty);
        case "valid_host": return scenario_valid_host($difficulty);
        case "public_private": return scenario_public_private();
        case "network_troubleshoot": return scenario_network_troubleshoot($difficulty);
    }
}