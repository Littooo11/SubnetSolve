<?php
// includes/easy_instructions.php
// Renders the same step-by-step explanations used in Learning Modules,
// shown inline below a game's question whenever Easy difficulty is selected.
// Requires subnet_engine.php to already be loaded by the calling file.

function render_dissect_instructions($q) {
    $blockSize = $q["wildcard_parts"][3] + 1;
    $base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";
    $givenParts = explode(".", $q["given_ip"]);
    $givenLastOctet = (int) $givenParts[3];
    ob_start();
    ?>
    <div class="game-card" style="margin-top:1.2rem;">
        <div class="mode-tag">📘 HOW TO SOLVE THIS QUESTION</div>
        <div class="learn-step">
            <div class="step-title">🔷 Network Address</div>
            <p>Round the given last octet DOWN to the nearest multiple of the block size (<?= $blockSize ?> for this /<?= $q['prefix'] ?>).</p>
            <div class="worked">→ <?= $base ?><?= $givenLastOctet ?> rounds down to <?= $base ?><?= $q['network_oct4'] ?></div>
        </div>
        <div class="learn-step">
            <div class="step-title">📡 Broadcast Address</div>
            <p>Take the network address and add (block size − 1).</p>
            <div class="worked">→ <?= $q['network_oct4'] ?> + (<?= $blockSize ?> − 1) = <?= $q['broadcast_oct4'] ?></div>
        </div>
        <div class="learn-step">
            <div class="step-title">👥 Usable Host Range</div>
            <p>Every address between the network and broadcast address is usable.</p>
            <div class="worked">→ First = <?= $q['network_oct4'] ?> + 1 = <?= $q['host_start_oct4'] ?> &nbsp;|&nbsp; Last = <?= $q['broadcast_oct4'] ?> − 1 = <?= $q['host_end_oct4'] ?></div>
        </div>
        <div class="learn-step">
            <div class="step-title">➕ Subnet Mask / # Wildcard Mask</div>
            <p>A /<?= $q['prefix'] ?> mask is the first <?= $q['prefix'] ?> bits set to 1. The wildcard mask is that same mask with every bit flipped.</p>
            <div class="worked">→ Mask: <?= implode('.', $q['mask_parts']) ?> &nbsp;|&nbsp; Wildcard: <?= implode('.', $q['wildcard_parts']) ?></div>
        </div>
        <div class="learn-step">
            <div class="step-title">🔢 Number of Usable Hosts</div>
            <p>Formula: 2^(32 − prefix) − 2.</p>
            <div class="worked">→ 2^(32 − <?= $q['prefix'] ?>) − 2 = <?= $blockSize ?> − 2 = <?= $q['usable_hosts'] ?></div>
        </div>
        <p style="font-size:0.78rem; color:var(--text-dim); margin-top:0.5rem;">Want more practice problems like this? Visit <a href="<?= strpos($_SERVER['PHP_SELF'], '/games/') !== false ? '../learning/dissect_ip.php' : 'learning/dissect_ip.php' ?>" style="color:var(--blue);">Learning Modules → How to Play: Dissect an IP Address</a>.</p>
    </div>
    <?php
    return ob_get_clean();
}

function render_binary_instructions($digits) {
    $decimal = rand(1, (1 << $digits) - 1);
    $binaryStr = str_pad(decbin($decimal), $digits, "0", STR_PAD_LEFT);
    $bits = str_split($binaryStr);
    $placeValues = [];
    for ($i = $digits - 1; $i >= 0; $i--) $placeValues[] = 1 << $i;
    $terms = [];
    foreach ($bits as $i => $bit) if ($bit === "1") $terms[] = $placeValues[$i];

    ob_start();
    ?>
    <div class="game-card" style="margin-top:1.2rem;">
        <div class="mode-tag">📘 HOW TO SOLVE: BINARY TO DECIMAL</div>
        <p class="sub">Here's a separate worked example (not today's number) showing the method:</p>
        <div class="learn-step">
            <div class="step-title">Step 1 — Place values (powers of 2)</div>
            <div class="bit-breakdown">
                <?php foreach ($bits as $i => $bit): ?>
                    <div class="bit-cell"><div class="bit"><?= $bit ?></div><div class="place">×<?= $placeValues[$i] ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="learn-step">
            <div class="step-title">Step 2 — Add up the place values where the bit is 1</div>
            <div class="worked">→ <?= implode(" + ", $terms) ?> = <?= $decimal ?></div>
        </div>
        <p style="font-size:0.78rem; color:var(--text-dim); margin-top:0.5rem;">Full tutorial: <a href="<?= strpos($_SERVER['PHP_SELF'], '/games/') !== false ? '../learning/binary_game.php' : 'learning/binary_game.php' ?>" style="color:var(--blue);">Learning Modules → How to Play: Binary Game</a>.</p>
    </div>
    <?php
    return ob_get_clean();
}

function render_hex_instructions($digits) {
    $decimal = rand(1, (1 << ($digits * 4)) - 1);
    $hexStr = strtoupper(str_pad(dechex($decimal), $digits, "0", STR_PAD_LEFT));
    $hexDigits = str_split($hexStr);
    $placeValues = [];
    for ($i = $digits - 1; $i >= 0; $i--) $placeValues[] = 16 ** $i;
    $terms = [];
    foreach ($hexDigits as $i => $d) $terms[] = hexdec($d) . "×" . $placeValues[$i];

    ob_start();
    ?>
    <div class="game-card" style="margin-top:1.2rem;">
        <div class="mode-tag">📘 HOW TO SOLVE: HEX TO DECIMAL</div>
        <p class="sub">Here's a separate worked example (not today's number) showing the method:</p>
        <div class="learn-step">
            <div class="step-title">Step 1 — Convert each hex digit, then multiply by its place value (powers of 16)</div>
            <div class="bit-breakdown">
                <?php foreach ($hexDigits as $i => $d): ?>
                    <div class="bit-cell"><div class="bit"><?= $d ?></div><div class="place">= <?= hexdec($d) ?> ×<?= $placeValues[$i] ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="learn-step">
            <div class="step-title">Step 2 — Add up the results</div>
            <div class="worked">→ <?= implode(" + ", $terms) ?> = <?= $decimal ?></div>
        </div>
        <p style="font-size:0.78rem; color:var(--text-dim); margin-top:0.5rem;">Full tutorial: <a href="<?= strpos($_SERVER['PHP_SELF'], '/games/') !== false ? '../learning/hex_conversion.php' : 'learning/hex_conversion.php' ?>" style="color:var(--blue);">Learning Modules → How to Play: Hex Conversion</a>.</p>
    </div>
    <?php
    return ob_get_clean();
}

function render_ipv6_instructions() {
    ob_start();
    ?>
    <div class="game-card" style="margin-top:1.2rem;">
        <div class="mode-tag">📘 HOW TO PLAY: IPV6 GAME</div>
        <div class="learn-step">
            <div class="step-title">The basics</div>
            <p>An IPv6 address is 128 bits, written as 8 groups of up to 4 hex digits each, separated by colons — e.g. <span class="formula">2001:0db8:0000:0000:0000:ff00:0042:8329</span>.</p>
        </div>
        <div class="learn-step">
            <div class="step-title">Compression rules</div>
            <p>Leading zeros in each group can be dropped (<span class="formula">0db8</span> → <span class="formula">db8</span>). The single longest run of consecutive all-zero groups can be replaced with <span class="formula">::</span> — but only once per address.</p>
            <div class="worked">→ 2001:db8::ff00:42:8329</div>
        </div>
        <div class="learn-step">
            <div class="step-title">Address types</div>
            <p>Loopback is always <span class="formula">::1</span>. Link-local addresses start with <span class="formula">fe80:</span>. Multicast starts with <span class="formula">ff</span>. Unique local starts with <span class="formula">fc</span> or <span class="formula">fd</span>. Everything else starting with <span class="formula">2</span> or <span class="formula">3</span> is typically global unicast.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Subnet Showdown and Scenario-Based Questions cover a rotating mix of topics
// (network address, masks, host count, IP class, public/private, scenarios),
// so this is a general "how this game works" guide rather than one fixed formula.
function render_mcq_instructions($gameName) {
    ob_start();
    ?>
    <div class="game-card" style="margin-top:1.2rem;">
        <div class="mode-tag">📘 HOW TO PLAY: <?= strtoupper(htmlspecialchars($gameName)) ?></div>
        <div class="learn-step">
            <div class="step-title">The format</div>
            <p>Each round shows one question with four answer choices (A-D). Only one is correct — click it, then click Submit.</p>
        </div>
        <div class="learn-step">
            <div class="step-title">What gets asked</div>
            <p>Questions rotate between topics: network address, broadcast address, subnet mask, wildcard mask, usable host range, number of usable hosts, IP address class, and public vs. private addresses.</p>
        </div>
        <div class="learn-step">
            <div class="step-title">Scoring</div>
            <p>Correct answers earn points that grow the longer your streak runs — a wrong answer resets the streak back to zero. On Easy difficulty, subnet-related questions use larger, simpler blocks (/24-/25).</p>
        </div>
        <p style="font-size:0.78rem; color:var(--text-dim); margin-top:0.5rem;">Need a refresher on the underlying subnetting math? Check <a href="<?= strpos($_SERVER['PHP_SELF'], '/games/') !== false ? '../learning_modules.php' : 'learning_modules.php' ?>" style="color:var(--blue);">Learning Modules</a> for the step-by-step breakdowns.</p>
    </div>
    <?php
    return ob_get_clean();
}