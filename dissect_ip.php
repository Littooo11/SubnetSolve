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
$networkOctet = (int) $q["network_oct4"];
$broadcastOctet = (int) $q["broadcast_oct4"];
$usableHostBits = 32 - (int) $q["prefix"];
$blockStarts = range(0, (int) (floor(255 / $blockSize) * $blockSize), $blockSize);
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
            <p class="sub">Follow this worked example first: <span class="ip"><?= htmlspecialchars($q['given_ip']) ?>/<?= (int) $q['prefix'] ?></span>. Then use the same steps in the practice activity below.</p>

            <div class="learn-step">
                <div class="step-title">🧭 Step 1: Understand the IP address</div>
                <p>An IP address identifies a device on a network. The number after the slash is the <b>prefix length</b>. It tells you how many bits belong to the network portion of the address.</p>
                <div class="worked">Example: <?= htmlspecialchars($q['given_ip']) ?>/<?= (int) $q['prefix'] ?> — IP address: <?= htmlspecialchars($q['given_ip']) ?>; prefix: /<?= (int) $q['prefix'] ?>.</div>
                <p>In this beginner activity, the subnet boundary is in the fourth octet (the last number). The first three numbers stay the same, so we focus on the last octet: <b><?= $givenLastOctet ?></b>.</p>
            </div>

            <div class="learn-step">
                <div class="step-title">🧱 Step 2: Find the block size</div>
                <p>The <b>block size</b> tells you how many addresses are in each subnet block. For these questions, add 1 to the last octet of the wildcard mask.</p>
                <div class="worked">Block size = <?= (int) $q['wildcard_parts'][3] ?> + 1 = <b><?= (int) $blockSize ?></b></div>
                <p>Subnet blocks start at 0 and increase by <?= (int) $blockSize ?> each time: <b><?= implode(', ', $blockStarts) ?></b>. Find the block start that is at or below <?= $givenLastOctet ?> and closest to it.</p>
            </div>

            <div class="learn-step">
                <div class="step-title">🔷 Step 3: Find the Network Address</div>
                <p>The <b>network address</b> is the first address in a block. Choose the largest block starting number that is less than or equal to the given last octet. This means rounding <b>down</b> to a multiple of the block size.</p>
                <div class="worked"><?= $givenLastOctet ?> belongs to the block that starts at <?= $networkOctet ?> → Network address: <b><?= $base . $networkOctet ?></b></div>
                <p>Tip: always round down to the start of the block. Do not round to the nearest number.</p>
            </div>

            <div class="learn-step">
                <div class="step-title">📡 Step 4: Find the Broadcast Address</div>
                <p>The <b>broadcast address</b> is the last address in the same block. Add the block size minus 1 to the network address. We subtract 1 because the network address is already counted as the first address.</p>
                <div class="worked"><?= $networkOctet ?> + (<?= (int) $blockSize ?> − 1) = <?= $broadcastOctet ?> → Broadcast address: <b><?= $base . $broadcastOctet ?></b></div>
            </div>

            <div class="learn-step">
                <div class="step-title">👥 Step 5: Find the Usable Host Range</div>
                <p>Devices use the addresses between the network address and broadcast address. Those two special addresses are not assigned to ordinary devices.</p>
                <div class="worked">First usable = network + 1 = <?= $networkOctet ?> + 1 = <b><?= (int) $q['host_start_oct4'] ?></b><br>Last usable = broadcast − 1 = <?= $broadcastOctet ?> − 1 = <b><?= (int) $q['host_end_oct4'] ?></b></div>
                <p>Full usable range: <b><?= $base . $q['host_start_oct4'] ?> – <?= $base . $q['host_end_oct4'] ?></b>.</p>
            </div>

            <div class="learn-step">
                <div class="step-title">➕ Step 6: Understand the Subnet Mask</div>
                <p>The <b>subnet mask</b> separates the network part from the host part. The /<?= (int) $q['prefix'] ?> prefix means the first <?= (int) $q['prefix'] ?> bits are network bits (1s in binary); the remaining <?= $usableHostBits ?> bits are host bits (0s in binary).</p>
                <div class="worked">/<?= (int) $q['prefix'] ?> → Subnet mask: <b><?= implode('.', $q['mask_parts']) ?></b></div>
                <p>You can learn common masks through practice; you do not need to memorize every one immediately.</p>
            </div>

            <div class="learn-step">
                <div class="step-title"># Step 7: Find the Wildcard Mask</div>
                <p>The <b>wildcard mask</b> is the inverse of the subnet mask. For each octet, subtract the subnet-mask value from 255.</p>
                <div class="worked">255 − <?= (int) $q['mask_parts'][0] ?> = <?= (int) $q['wildcard_parts'][0] ?>; 255 − <?= (int) $q['mask_parts'][1] ?> = <?= (int) $q['wildcard_parts'][1] ?>;<br>255 − <?= (int) $q['mask_parts'][2] ?> = <?= (int) $q['wildcard_parts'][2] ?>; 255 − <?= (int) $q['mask_parts'][3] ?> = <?= (int) $q['wildcard_parts'][3] ?><br>Wildcard mask: <b><?= implode('.', $q['wildcard_parts']) ?></b></div>
            </div>

            <div class="learn-step">
                <div class="step-title">🔢 Step 8: Calculate Usable Hosts</div>
                <p>First, calculate the number of host bits by subtracting the prefix from 32. Then calculate 2 to the power of that number and subtract 2 for the network and broadcast addresses.</p>
                <div class="worked">Host bits = 32 − <?= (int) $q['prefix'] ?> = <?= $usableHostBits ?><br>Usable hosts = 2<sup><?= $usableHostBits ?></sup> − 2 = <b><?= number_format((int) $q['usable_hosts']) ?></b></div>
            </div>

            <div class="learn-step">
                <div class="step-title">✅ Remember this order</div>
                <p><b>Prefix → Block size → Network address → Broadcast address → Usable host range → Subnet mask → Wildcard mask → Usable host count.</b></p>
                <p>Work through one step at a time. If you get stuck, compare your work with the example above before moving to the next step.</p>
            </div>
        </div>

        <div class="game-card" style="margin-top:1.2rem;">
            <div class="mode-tag">TRY IT YOURSELF</div>
            <h2>Given the IP address <span class="ip"><?= $q['given_ip'] ?>/<?= $q['prefix'] ?></span>, fill in the blanks.</h2>
            <p class="sub">Use the worked example above as your guide. For network, broadcast, and host-range fields, enter only the missing last number. For subnet and wildcard masks, enter all four numbers. For usable hosts, enter the total count. Click <b>Check Answers</b>: correct fields turn green and incorrect fields turn red. Blank fields are left unmarked, and this practice is not scored.</p>
            <div class="learn-step">
                <div class="step-title">💡 Tips before you start</div>
                <p>The first three octets are already filled in where needed, so type only a number in each box—do not type dots. If an answer is incorrect, revisit the matching step above. Press <b>Reset</b> to clear your entries or <b>New Question</b> to try another example.</p>
            </div>

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