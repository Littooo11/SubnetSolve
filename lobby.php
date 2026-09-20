<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "config.php";
require "includes/avatars.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION["user_id"];

$tab = $_GET["tab"] ?? "public";
$error = $_GET["error"] ?? "";

// Handle "Cancel Lobby" (host deletes their own still-waiting lobby)
if (isset($_POST["cancel_lobby_id"])) {
    $cancelId = (int) $_POST["cancel_lobby_id"];
    $check = mysqli_prepare($conn, "SELECT id FROM matches WHERE id = ? AND host_user_id = ? AND status = 'waiting'");
    mysqli_stmt_bind_param($check, "ii", $cancelId, $userId);
    mysqli_stmt_execute($check);
    if (mysqli_stmt_get_result($check)->fetch_assoc()) {
        mysqli_query($conn, "DELETE FROM match_players WHERE match_id = $cancelId");
        mysqli_query($conn, "DELETE FROM match_questions WHERE match_id = $cancelId");
        mysqli_query($conn, "DELETE FROM matches WHERE id = $cancelId");
    }
    header("Location: lobby.php?tab=mine");
    exit();
}

$publicLobbies = [];
if ($tab === "public") {
    $result = mysqli_query($conn, "SELECT m.*, (SELECT COUNT(*) FROM match_players mp WHERE mp.match_id = m.id) AS player_count
        FROM matches m WHERE m.visibility = 'public' AND m.status IN ('waiting','in_progress')
        ORDER BY m.created_at DESC LIMIT 30");
    $publicLobbies = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$myLobbies = [];
if ($tab === "mine") {
    $result = mysqli_query($conn, "SELECT m.*, (SELECT COUNT(*) FROM match_players mp WHERE mp.match_id = m.id) AS player_count
        FROM matches m
        WHERE m.host_user_id = $userId OR m.id IN (SELECT match_id FROM match_players WHERE user_id = $userId)
        ORDER BY m.created_at DESC LIMIT 30");
    $myLobbies = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function mode_label($m) {
    return ["subnetting" => "Subnetting", "binary" => "Binary", "both" => "Both"][$m] ?? $m;
}
function status_label($s) {
    return ["waiting" => "Waiting", "in_progress" => "In Game", "finished" => "Finished"][$s] ?? $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Multiplayer Lobby - SubNetSolve</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="layout lobby-layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Master Subnetting, Level Up!</p>
            </div>
        </div>
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="#" class="nav-link">Learning Modules</a>
        <a href="practice.php" class="nav-link">Practice Mode</a>
        <a href="games.php" class="nav-link">Games</a>
        <a href="lobby.php" class="nav-link active">Multiplayer Lobby</a>
        <a href="leaderboards.php" class="nav-link">Leaderboards</a>
        <a href="achievements.php" class="nav-link">Achievements</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </aside>

    <main class="main">
        <div class="welcome">
            <h2>Multiplayer Lobby</h2>
            <p>Create or join a 1v1 match and test your skills in real time.</p>
        </div>

        <?php if ($error): ?>
            <div class="feedback-banner feedback-wrong" style="margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="tab-row" style="justify-content:space-between;">
            <div style="display:flex; gap:0.5rem;">
                <a href="lobby.php?tab=public" class="tab-btn <?= $tab === 'public' ? 'active' : '' ?>">Public Lobbies</a>
                <a href="lobby.php?tab=code" class="tab-btn <?= $tab === 'code' ? 'active' : '' ?>">Join with Code</a>
                <a href="lobby.php?tab=mine" class="tab-btn <?= $tab === 'mine' ? 'active' : '' ?>">My Lobbies</a>
            </div>
            <a href="lobby.php?tab=<?= $tab ?>" class="tab-btn">🔄 Refresh</a>
        </div>

        <div class="explore-panel">
            <?php if ($tab === "public"): ?>
                <?php if (empty($publicLobbies)): ?>
                    <p style="color:var(--text-dim); font-size:0.9rem;">No public lobbies right now — create one!</p>
                <?php else: ?>
                <table class="lb-table">
                    <tr><th>Room Code</th><th>Mode</th><th>Players</th><th>Status</th><th></th></tr>
                    <?php foreach ($publicLobbies as $l):
                        $full = $l["player_count"] >= $l["max_players"];
                    ?>
                        <tr>
                            <td><span class="room-code-badge"><?= htmlspecialchars($l["room_code"]) ?></span></td>
                            <td><?= $l["max_players"] == 2 ? "1v1" : $l["max_players"] . "p" ?> - <?= mode_label($l["game_mode"]) ?></td>
                            <td><?= $l["player_count"] ?>/<?= $l["max_players"] ?></td>
                            <td><span class="status-pill <?= $l["status"] ?>"><?= status_label($l["status"]) ?></span></td>
                            <td>
                                <?php if (!$full && $l["status"] === "waiting"): ?>
                                    <a href="join.php?code=<?= htmlspecialchars($l["room_code"]) ?>" class="btn btn-primary" style="text-decoration:none; padding:0.4rem 0.9rem; font-size:0.8rem;">Join</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled style="font-size:0.8rem;">Full</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>

            <?php elseif ($tab === "code"): ?>
                <form method="POST" action="join.php" class="profile-form" style="max-width:360px;">
                    <div class="form-group">
                        <label>Room Code</label>
                        <input type="text" name="code" placeholder="e.g. 7F3K2Q" required style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Password (if required)</label>
                        <input type="password" name="password" placeholder="Leave blank if none">
                    </div>
                    <button type="submit" class="btn btn-primary">Join Lobby</button>
                </form>

            <?php else: ?>
                <?php if (empty($myLobbies)): ?>
                    <p style="color:var(--text-dim); font-size:0.9rem;">You haven't created or joined any lobbies yet.</p>
                <?php else: ?>
                <table class="lb-table">
                    <tr><th>Room Code</th><th>Mode</th><th>Players</th><th>Status</th><th></th></tr>
                    <?php foreach ($myLobbies as $l): ?>
                        <tr>
                            <td><span class="room-code-badge"><?= htmlspecialchars($l["room_code"]) ?></span></td>
                            <td><?= $l["max_players"] == 2 ? "1v1" : $l["max_players"] . "p" ?> - <?= mode_label($l["game_mode"]) ?></td>
                            <td><?= $l["player_count"] ?>/<?= $l["max_players"] ?></td>
                            <td><span class="status-pill <?= $l["status"] ?>"><?= status_label($l["status"]) ?></span></td>
                            <td style="display:flex; gap:0.4rem;">
                                <?php if ($l["status"] !== "finished"): ?>
                                    <a href="match.php?code=<?= htmlspecialchars($l["room_code"]) ?>" class="btn btn-primary" style="text-decoration:none; padding:0.4rem 0.8rem; font-size:0.78rem;">Open</a>
                                <?php else: ?>
                                    <a href="match.php?code=<?= htmlspecialchars($l["room_code"]) ?>" class="btn btn-secondary" style="text-decoration:none; padding:0.4rem 0.8rem; font-size:0.78rem;">Results</a>
                                <?php endif; ?>
                                <?php if ($l["status"] === "waiting" && (int) $l["host_user_id"] === (int) $userId): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel this lobby?')">
                                        <input type="hidden" name="cancel_lobby_id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding:0.4rem 0.8rem; font-size:0.78rem; border-color:#f87171; color:#f87171;">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <aside class="right-col" style="width:340px;">
        <div class="panel-box">
            <h4>➕ Create Lobby</h4>
            <p style="font-size:0.8rem; color:var(--text-dim); margin-top:-0.3rem;">Set up your match and share the room code with your opponent.</p>

            <form method="POST" action="create_lobby.php" class="profile-form">
                <div class="form-group">
                    <label>Game Mode</label>
                    <div class="mode-pill-row" id="modePillRow">
                        <div class="mode-pill selected" data-value="subnetting"><span class="icon">🌐</span>Subnetting</div>
                        <div class="mode-pill" data-value="binary"><span class="icon">01</span>Binary</div>
                        <div class="mode-pill" data-value="both"><span class="icon">🏆</span>Both</div>
                    </div>
                    <input type="hidden" name="game_mode" id="gameModeInput" value="subnetting">
                </div>

                <div class="form-group">
                    <label>Lobby Size</label>
                    <div class="mode-pill-row" id="sizePillRow" style="grid-template-columns:repeat(4,1fr);">
                        <div class="mode-pill selected" data-value="2">2<br>Players</div>
                        <div class="mode-pill" data-value="4">4<br>Players</div>
                        <div class="mode-pill" data-value="6">6<br>Players</div>
                        <div class="mode-pill" data-value="8">8<br>Players</div>
                    </div>
                    <input type="hidden" name="max_players" id="maxPlayersInput" value="2">
                </div>

                <div class="form-group">
                    <label>Match Type</label>
                    <div class="type-pill-row" id="typePillRow">
                        <div class="mode-pill selected" data-value="public">👥 Public</div>
                        <div class="mode-pill" data-value="private">🔒 Private</div>
                    </div>
                    <input type="hidden" name="visibility" id="visibilityInput" value="public">
                </div>

                <div class="form-group">
                    <label>Room Name (Optional)</label>
                    <input type="text" name="room_name" placeholder="e.g. Team Alpha">
                </div>

                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" name="description" placeholder="e.g. Good luck!" maxlength="100">
                </div>

                <div class="form-group">
                    <label>Password (Optional)</label>
                    <input type="password" name="password" placeholder="Leave empty for no password">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Create Lobby</button>
            </form>
        </div>
    </aside>
</div>

<script>
function wirePillRow(rowId, inputId) {
    const row = document.getElementById(rowId);
    row.querySelectorAll(".mode-pill").forEach(pill => {
        pill.addEventListener("click", () => {
            row.querySelectorAll(".mode-pill").forEach(p => p.classList.remove("selected"));
            pill.classList.add("selected");
            document.getElementById(inputId).value = pill.dataset.value;
        });
    });
}
wirePillRow("modePillRow", "gameModeInput");
wirePillRow("sizePillRow", "maxPlayersInput");
wirePillRow("typePillRow", "visibilityInput");
</script>
</body>
</html>