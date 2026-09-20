<?php
session_start();
header("Content-Type: application/json");
require "includes/search_index.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$q = trim($_GET["q"] ?? "");
if ($q === "") {
    echo json_encode([]);
    exit();
}

$results = array_values(array_filter(get_search_index(), function ($item) use ($q) {
    return stripos($item["name"], $q) !== false || stripos($item["desc"], $q) !== false;
}));

echo json_encode(array_slice($results, 0, 8));