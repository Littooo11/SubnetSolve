<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/subnet_engine.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Fresh random example every time the page loads - purely for teaching, not scored.
$q = generate_subnet_question("easy");
$blockSize = $q["wildcard_parts"][3] + 1;
$base = "{$q['oct1']}.{$q['oct2']}.{$q['oct3']}.";
$givenParts = explode(".", $q["given_ip"]);
$givenLastOctet = (int) $givenParts[3];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How to Play: Dissect an IP Address - SubNetSolve</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../learning.css">
</head>
<body>
<div class="game-layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Master Subnetting, Level Up!</p>
            </div>
        </div>
        <a href="../dashboard.php" class="nav-link">Dashboard</a>
        <a href="../learning_modules.php" class="nav-link active">Learning Modules</a>
        <a href="../practice.php" class="nav-link">Practice Mode</a>
        <a href="../games.php" class="nav-link">Games</a>
        <a href="../lobby.php" class="nav-link">Multiplayer Lobby</a>
        <a href="../leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="../achievements.php" class="nav-link">Achievements</a>
        <a href="../profile.php" class="nav-link">Profile</a>
        <a href="../settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="game-main">
        <div class="breadcrumb"><a href="../learning_modules.php" class="showdown-back">← Exit</a> &nbsp; Learning Modules &gt; <b>Dissect an IP Address</b></div>

        <div class="game-card">
            <h2>How to Solve: Dissect an IP Address</h2>
            <p class="sub">Every field below is explained using this example: <span class="ip"><?= $q['given_ip'] ?>/<?= $q['prefix'] ?></span></p>

            <div class="learn-step">
                <div class="step-title">🔷 Network Address</div>
                <p>The network address is the IP address with every "host" bit set to 0. A /<?= $q['prefix'] ?> mask puts the block boundary every <span class="formula"><?= $blockSize ?></span> addresses in the last octet. Take the given last octet and round DOWN to the nearest multiple of <?= $blockSize ?>.</p>
                <div class="worked">→ <?= $base ?><?= $givenLastOctet ?> rounds down to <?= $base ?><?= $q['network_oct4'] ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">📡 Broadcast Address</div>
                <p>The broadcast address is the LAST address in that same block — take the network address and add (block size − 1).</p>
                <div class="formula"><?= $q['network_oct4'] ?> + (<?= $blockSize ?> − 1) = <?= $q['broadcast_oct4'] ?></div>
                <div class="worked">→ Broadcast = <?= $base ?><?= $q['broadcast_oct4'] ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">👥 Usable Host Range</div>
                <p>Every address BETWEEN the network and broadcast address can be assigned to a device — the network and broadcast addresses themselves cannot.</p>
                <div class="worked">→ First usable = <?= $q['network_oct4'] ?> + 1 = <?= $q['host_start_oct4'] ?> &nbsp;|&nbsp; Last usable = <?= $q['broadcast_oct4'] ?> − 1 = <?= $q['host_end_oct4'] ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">➕ Subnet Mask</div>
                <p>A /<?= $q['prefix'] ?> mask means the first <?= $q['prefix'] ?> bits (reading left to right) are 1s, and the rest are 0s. Converted to the familiar dotted decimal form:</p>
                <div class="worked">→ <?= implode('.', $q['mask_parts']) ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title"># Wildcard Mask</div>
                <p>The wildcard mask is simply the subnet mask with every bit flipped (inverted).</p>
                <div class="worked">→ <?= implode('.', $q['mask_parts']) ?> inverted = <?= implode('.', $q['wildcard_parts']) ?></div>
            </div>

            <div class="learn-step">
                <div class="step-title">🔢 Number of Usable Hosts</div>
                <p>The formula is <span class="formula">2^(32 − prefix) − 2</span>. We subtract 2 because the network and broadcast addresses in the block can't be assigned to a host.</p>
                <div class="worked">→ 2^(32 − <?= $q['prefix'] ?>) − 2 = <?= $blockSize ?> − 2 = <?= $q['usable_hosts'] ?></div>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">TRY IT YOURSELF</div>
            <h2>Given the IP address <span class="ip"><?= $q['given_ip'] ?>/<?= $q['prefix'] ?></span>, fill in the blanks.</h2>
            <p class="sub">Fill in what you can, then click "Check Answers" — correct fields turn green, incorrect ones turn red. Nothing here is scored, so take your time.</p>

            <div class="q-row">
                <div class="label">🔷 Network Address</div>
                <div class="octet-group">
                    <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" type="number" min="0" max="255" id="network_oct4" data-answer="<?= $q['network_oct4'] ?>">
                </div>
            </div>

            <div class="q-row">
                <div class="label">📡 Broadcast Address</div>
                <div class="octet-group">
                    <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" type="number" min="0" max="255" id="broadcast_oct4" data-answer="<?= $q['broadcast_oct4'] ?>">
                </div>
            </div>

            <div class="q-row">
                <div class="label">👥 Usable Host Range</div>
                <div class="octet-group">
                    <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" type="number" min="0" max="255" id="host_start_oct4" data-answer="<?= $q['host_start_oct4'] ?>">
                    <span class="range-to">to</span>
                    <input class="octet-box" value="<?= $q['oct1'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct2'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" value="<?= $q['oct3'] ?>" disabled><span class="octet-dot">.</span>
                    <input class="octet-box" type="number" min="0" max="255" id="host_end_oct4" data-answer="<?= $q['host_end_oct4'] ?>">
                </div>
            </div>

            <div class="q-row">
                <div class="label">➕ Subnet Mask</div>
                <div class="octet-group">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <input class="octet-box" type="number" min="0" max="255" id="mask_<?= $i ?>" data-answer="<?= $q['mask_parts'][$i] ?>">
                        <?php if ($i < 3) echo '<span class="octet-dot">.</span>'; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="q-row">
                <div class="label"># Wildcard Mask</div>
                <div class="octet-group">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <input class="octet-box" type="number" min="0" max="255" id="wildcard_<?= $i ?>" data-answer="<?= $q['wildcard_parts'][$i] ?>">
                        <?php if ($i < 3) echo '<span class="octet-dot">.</span>'; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="q-row">
                <div class="label">🔢 Number of Usable Hosts</div>
                <input class="hosts-input" type="number" min="0" id="usable_hosts" data-answer="<?= $q['usable_hosts'] ?>" style="max-width:200px;">
            </div>

            <div id="tally" class="check-tally"></div>

            <div class="game-actions">
                <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
                <button type="button" class="btn btn-primary" id="checkBtn">Check Answers</button>
                <a href="dissect_ip.php" class="btn btn-secondary" style="text-decoration:none; display:inline-block;">New Question</a>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById("checkBtn").addEventListener("click", () => {
    const inputs = document.querySelectorAll("[data-answer]");
    let correctCount = 0;

    inputs.forEach(input => {
        input.classList.remove("correct", "incorrect");
        const given = input.value.trim();
        if (given === "") return; // leave blank fields unmarked
        if (parseInt(given, 10) === parseInt(input.dataset.answer, 10)) {
            input.classList.add("correct");
            correctCount++;
        } else {
            input.classList.add("incorrect");
        }
    });

    const tally = document.getElementById("tally");
    tally.textContent = `${correctCount} / ${inputs.length} fields correct`;
    tally.className = correctCount === inputs.length ? "check-tally all-correct" : "check-tally";
});

document.getElementById("resetBtn").addEventListener("click", () => {
    document.querySelectorAll("[data-answer]").forEach(input => {
        input.value = "";
        input.classList.remove("correct", "incorrect");
    });
    document.getElementById("tally").textContent = "";
});
</script>
</body>
</html>