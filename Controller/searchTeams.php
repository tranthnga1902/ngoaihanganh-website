<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
$response = ['suggestions' => []];

if (strlen($searchTerm) >= 3) {
    $sql = "SELECT team_id, name FROM teams WHERE name LIKE ?";
    $searchTerm = "%" . mysqli_real_escape_string($conn, $searchTerm) . "%";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $searchTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $teams = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $response['suggestions'] = $teams;
}

echo json_encode($response);
?>