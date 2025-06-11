<?php
require_once '../includes/config.php';
require_once '../controller/teamController.php';


header('Content-Type: application/json');


// Debug
error_log(print_r($_POST, true));


if (!isset($_POST['news_id']) || !is_numeric($_POST['news_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid news ID']);
    exit();
}


$news_id = (int)$_POST['news_id'];


if (updateView($conn, $news_id)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}



