<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$suggestions = [];

if (strlen($term) >= 3) {
    $sql = "SELECT name FROM managers WHERE name LIKE ? LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    $likeTerm = "%$term%";
    mysqli_stmt_bind_param($stmt, "s", $likeTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = ['name' => $row['name']];
    }
    mysqli_stmt_close($stmt);
}

echo json_encode(['suggestions' => $suggestions]);
?>