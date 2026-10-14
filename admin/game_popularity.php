<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
require "../config.php";
require "../includes/admin_auth.php";

// Fetch all non-admin users for the filtering selector
$userQuery = mysqli_query($conn, "SELECT id, username, email FROM users WHERE is_admin = 0 ORDER BY username ASC");
$allUsers = mysqli_fetch_all($userQuery, MYSQLI_ASSOC);

// Handle AJAX Request for Filtered Chart Data
if (isset($_GET["ajax_popularity"])) {
    header("Content-Type: application/json");
    
    $selectedUserIds = isset($_GET["user_ids"]) ? array_map('intval', explode(",", $_GET["user_ids"])) : [];

    $categoryMap = [
        "subnet_dissect_practice" => "Dissect an IP", "subnet_dissect_timed" => "Dissect an IP",
        "binary_practice" => "Binary Game", "binary_game" => "Binary Game", "binary_timed" => "Binary Game",
        "hex_practice" => "Hex Conversion", "hex_game" => "Hex Conversion", "hex_timed" => "Hex Conversion",
        "ipv6_practice" => "IPv6 Game", "ipv6_game" => "IPv6 Game", "ipv6_timed" => "IPv6 Game",
        "showdown_practice" => "Subnet Showdown", "showdown_timed" => "Subnet Showdown", "subnet_showdown" => "Subnet Showdown",
        "scenario_practice" => "Scenario Game", "scenario_game" => "Scenario Game", "scenario_timed" => "Scenario Game",
        "multiplayer_1v1" => "Multiplayer 1v1", "match_1v1" => "Multiplayer 1v1",
    ];

    $whereClause = "";
    if (!empty($selectedUserIds)) {
        $ids = implode(",", $selectedUserIds);
        $whereClause = "WHERE user_id IN ($ids)";
    }

    $sql = "SELECT game_type, COUNT(*) AS total_plays FROM scores $whereClause GROUP BY game_type";
    $result = mysqli_query($conn, $sql);

    $counts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $label = $categoryMap[$row["game_type"]] ?? "Other";
        $counts[$label] = ($counts[$label] ?? 0) + (int) $row["total_plays"];
    }

    echo json_encode([
        "labels" => array_keys($counts),
        "data" => array_values($counts)
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Category Popularity - Admin Panel</title>
    <link rel="stylesheet" href="../dash.css">
    <link rel="stylesheet" href="../game.css">
    <link rel="stylesheet" href="../admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div
``` class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand" style="margin-bottom:1.5rem;">
            <div class="logo">S</div>
            <div>
                <h1>SubNet<span>Solve</span></h1>
                <p>Admin Panel</p>
            </div>
        </div>

        <div class="admin-nav-label">MAIN NAVIGATION</div>
        <a href="dashboard.php" class="admin-nav-link">🏠 Dashboard</a>
        <a href="users.php" class="admin-nav-link">👥 User Management</a>
        <a href="manage_questions.php" class="admin-nav-link">❓ Manage Questions</a>
        <a href="game_popularity.php" class="admin-nav-link active">🎮 Game Category Popularity</a>
        <a href="teacher_applications.php" class="admin-nav-link">🎓 Teacher Apps</a>


        <div class="admin-nav-label">ACCOUNT</div>
        <a href="profile.php" class="admin-nav-link">👤 Admin Profile</a>
        <a href="../logout.php" class="admin-nav-link">🚪 Logout</a>

        <div class="admin-sidebar-footer">
            <div class="brand-mini">SubNet<span style="color:var(--blue);">Solve</span></div>
            <p>Learn · Practice · Compete</p>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="margin:0 0 0.3rem;">🎮 Game Category Popularity</h2>
                <p style="color:var(--text-dim); margin:0;">Filter and monitor game activity by specific users.</p>
            </div>
            <div class="admin-date-box">
                <b><?= date("M j, Y") ?></b>
                <?= date("g:i A") ?>
            </div>
        </div>

        <div class="admin-panels-row" style="grid-template-columns: 300px 1fr;">
            <!-- User Selection Filter Panel -->
            <div class="admin-panel">
                <h3>Select Users</h3>
                <p style="font-size:0.8rem; color:var(--text-dim);">Check users to include them in the popularity breakdown:</p>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--border); padding: 0.5rem; border-radius: 8px;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight:bold; cursor:pointer;">
                        <input type="checkbox" id="selectAll"> Select All
                    </label>
                    <hr style="border-color: var(--border); margin:0.5rem 0;">
                    <?php foreach ($allUsers as $u): ?>
                        <label style="display:block; margin: 0.3rem 0; font-size: 0.85rem; cursor:pointer;">
                            <input type="checkbox" class="user-checkbox" value="<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['username']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button id="applyFilter" class="btn btn-primary" style="width:100%; margin-top:1rem;">Apply Filter</button>
            </div>

            <!-- Graph Output Panel -->
            <div class="admin-panel">
                <h3>Popularity Breakdown</h3>
                <div style="max-width: 100%; height: 260px; position: relative; margin: 0 auto;">
                    <canvas id="popularityChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const ctx = document.getElementById("popularityChart").getContext("2d");
let popChart = new Chart(ctx, {
    type: "bar",
    data: { 
        labels: [], 
        datasets: [{ 
            label: "Plays", 
            data: [], 
            backgroundColor: "#3b82f6" 
        }] 
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: "#8b93ab" }, grid: { color: "#23304f" } },
            y: { ticks: { color: "#8b93ab", precision: 0 }, grid: { color: "#23304f" }, beginAtZero: true }
        }
    }
});

function loadData() {
    const checked = Array.from(document.querySelectorAll(".user-checkbox:checked")).map(cb => cb.value);
    const params = checked.length > 0 ? `?ajax_popularity=1&user_ids=${checked.join(",")}` : `?ajax_popularity=1`;

    fetch(params)
        .then(res => res.json())
        .then(data => {
            popChart.data.labels = data.labels;
            popChart.data.datasets[0].data = data.data;
            popChart.update();
        });
}

document.getElementById("applyFilter").addEventListener("click", loadData);
document.getElementById("selectAll").addEventListener("change", function() {
    document.querySelectorAll(".user-checkbox").forEach(cb => cb.checked = this.checked);
});

// Initial load for all users
loadData();
</script>
</body>
</html>