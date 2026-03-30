<?php

require_once '../includes/config.php';

header('Content-Type: application/json');

$searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
$response = ['suggestions' => []];

if (strlen($searchTerm) >= 3) {
    $sql = "SELECT player_id, name FROM Players WHERE name LIKE ?";
    $searchTerm = "%" . mysqli_real_escape_string($conn, $searchTerm) . "%";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $searchTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $players = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $response['suggestions'] = $players;
}

echo json_encode($response);
?>