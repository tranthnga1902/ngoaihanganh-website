<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';


header('Content-Type: application/json');


$email = $_GET['email'] ?? '';
$response = ['exists' => false];


if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $response['exists'] = $stmt->num_rows > 0;
    $stmt->close();
}


echo json_encode($response);
?>



