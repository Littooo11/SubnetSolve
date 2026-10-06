<?php
// includes/ipv6_engine.php
// Generates multiple-choice IPv6 questions: address type identification,
// compression/expansion, and core facts.

function ipv6_build_options($correct, $distractors) {
    $distractors = array_values(array_unique($distractors));
    shuffle($distractors);
    $options = array_slice($distractors, 0, 3);
    $options[] = $correct;
    shuffle($options);
    return ["options" => $options, "correct_index" => array_search($correct, $options)];
}

function ipv6_random_hextet() {
    return dechex(rand(0, 0xFFFF));
}

// Compresses a full 8-group address: strips leading zeros per group,
// then replaces the single longest run of all-zero groups with "::".
function ipv6_compress(array $groups) {
    $stripped = array_map(fn($g) => dechex(hexdec($g)), $groups);

    $bestStart = -1; $bestLen = 0;
    $curStart = -1; $curLen = 0;
    foreach ($stripped as $i => $g) {
        if ($g === "0") {
            if ($curStart === -1) $curStart = $i;
            $curLen++;
            if ($curLen > $bestLen) { $bestLen = $curLen; $bestStart = $curStart; }
        } else {
            $curStart = -1; $curLen = 0;
        }
    }

    if ($bestLen < 2) return implode(":", $stripped); // no run worth compressing

    $before = array_slice($stripped, 0, $bestStart);
    $after = array_slice($stripped, $bestStart + $bestLen);
    return implode(":", $before) . "::" . implode(":", $after);
}

function ipv6_expanded_padded(array $groups) {
    return implode(":", array_map(fn($g) => str_pad(dechex(hexdec($g)), 4, "0", STR_PAD_LEFT), $groups));
}

function ipv6_random_groups() {
    $groups = [];
    for ($i = 0; $i < 8; $i++) $groups[] = ipv6_random_hextet();
    return $groups;
}

// Forces a run of consecutive zero groups somewhere, so there's always
// something genuinely compressible for the question to test.
function ipv6_groups_with_zero_run() {
    $groups = ipv6_random_groups();
    $runLen = rand(2, 4);
    $start = rand(0, 8 - $runLen);
    for ($i = $start; $i < $start + $runLen; $i++) $groups[$i] = "0";
    return $groups;
}

function ipv6_address_type_question() {
    $types = [
        "Loopback"        => fn() => ["::1"],
        "Link-Local"      => fn() => ["fe80::" . ipv6_random_hextet()],
        "Multicast"       => fn() => ["ff0" . rand(1, 5) . "::" . ipv6_random_hextet()],
        "Unique Local"    => fn() => ["fd" . sprintf("%02x", rand(0, 255)) . "::" . ipv6_random_hextet()],
        "Global Unicast"  => fn() => ["2" . sprintf("%03x", rand(0, 0xFFF)) . "::" . ipv6_random_hextet()],
    ];
    $typeNames = array_keys($types);
    $correctType = $typeNames[array_rand($typeNames)];
    $address = $types[$correctType]()[0];

    $built = ipv6_build_options($correctType, array_values(array_diff($typeNames, [$correctType])));

    return [
        "prompt" => "What type of IPv6 address is $address?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function ipv6_compress_question() {
    $groups = ipv6_groups_with_zero_run();
    $correct = ipv6_compress($groups);
    $fullPadded = ipv6_expanded_padded($groups);

    // Distractors: plausible mistakes
    $strippedNoCompress = implode(":", array_map(fn($g) => dechex(hexdec($g)), $groups)); // forgot to use ::
    $wrongDoubleColon = str_replace("::", ":0:", $correct); // mangled compression
    $leadingZerosKept = implode(":", array_map(fn($g) => str_pad(dechex(hexdec($g)), 2, "0", STR_PAD_LEFT), $groups)); // didn't fully strip

    $built = ipv6_build_options($correct, [$strippedNoCompress, $wrongDoubleColon, $leadingZerosKept]);

    return [
        "prompt" => "What is the correctly compressed form of the IPv6 address $fullPadded?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function ipv6_expand_question() {
    $groups = ipv6_groups_with_zero_run();
    $compressed = ipv6_compress($groups);
    $correct = ipv6_expanded_padded($groups);

    // Distractors: wrong number of zero groups inserted
    $groupsShort = $groups; $groupsShort[array_rand($groupsShort)] = "1"; // one wrong group value
    $wrong1 = ipv6_expanded_padded($groupsShort);

    $groupsExtra = $groups;
    array_splice($groupsExtra, rand(0, 7), 0, "0"); // one extra group (9 groups - wrong count)
    $wrong2 = implode(":", array_map(fn($g) => str_pad(dechex(hexdec($g)), 4, "0", STR_PAD_LEFT), array_slice($groupsExtra, 0, 8)));

    $groupsMissing = $groups; array_pop($groupsMissing); $groupsMissing[] = "0"; $groupsMissing[0] = dechex(hexdec($groupsMissing[0]) ^ 0xF);
    $wrong3 = ipv6_expanded_padded($groupsMissing);

    $built = ipv6_build_options($correct, [$wrong1, $wrong2, $wrong3]);

    return [
        "prompt" => "What is the fully expanded form of the IPv6 address $compressed?",
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

function ipv6_fact_question() {
    $bank = [
        ["How many bits make up an IPv6 address?", "128 bits", ["32 bits", "64 bits", "256 bits"]],
        ["How many groups (hextets) are in a full IPv6 address?", "8", ["4", "6", "16"]],
        ["What does \"::\" represent in an IPv6 address?", "One or more consecutive groups of all zeros", ["A separator between network and host bits", "The end of the address", "A reserved multicast marker"]],
        ["At most how many hexadecimal digits can each IPv6 group contain?", "4", ["2", "3", "8"]],
        ["How many times can \"::\" appear in a single valid IPv6 address?", "Only once", ["Twice", "Up to four times", "As many times as needed"]],
        ["What character separates groups in an IPv6 address?", "Colon (:)", ["Dot (.)", "Dash (-)", "Space"]],
    ];
    $pick = $bank[array_rand($bank)];
    $built = ipv6_build_options($pick[1], $pick[2]);

    return [
        "prompt" => $pick[0],
        "options" => $built["options"],
        "correct_index" => $built["correct_index"],
    ];
}

// Easy leans on recognition (type/facts), harder difficulties lean on
// compression/expansion reasoning.
function generate_ipv6_question($difficulty = "medium") {
    $weights = [
        "easy"   => ["type", "type", "fact", "fact", "compress"],
        "medium" => ["type", "fact", "compress", "compress", "expand"],
        "hard"   => ["compress", "expand", "expand", "type", "fact"],
    ];
    $pool = $weights[$difficulty] ?? $weights["medium"];
    $pick = $pool[array_rand($pool)];

    switch ($pick) {
        case "type": return ipv6_address_type_question();
        case "compress": return ipv6_compress_question();
        case "expand": return ipv6_expand_question();
        case "fact": return ipv6_fact_question();
    }
}