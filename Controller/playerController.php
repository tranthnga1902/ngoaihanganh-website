<?php
require_once '../includes/config.php';


// Lấy danh sách tất cả CLB để hiển thị trong thanh chọn
function getAllTeams($conn) {
    $sql = "SELECT team_id, name FROM Teams ORDER BY name ASC";
    $result = $conn->query($sql);
    $teams = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
    }
    return $teams;
}


// Sửa hàm tìm kiếm cầu thủ theo CLB để dùng team_id thay vì name
function searchPlayersByTeamId($conn, $team_id) {
    $sql = "
        SELECT p.player_id, p.name, p.position, p.jersey_number, p.nationality, p.photo_url, p.nationality_flag_url, t.name AS team_name
        FROM Players p
        LEFT JOIN Teams t ON p.team_id = t.team_id
        WHERE p.team_id = ?
        ORDER BY p.name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Lỗi prepare: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $team_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


// Xử lý yêu cầu gợi ý (chỉ giữ gợi ý cho tên cầu thủ)
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'suggestPlayer' && isset($_GET['query'])) {
        $query = $_GET['query'];
        $suggestions = suggestPlayersByName($conn, $query);
        header('Content-Type: application/json');
        echo json_encode($suggestions);
        exit;
    }
}


// Lấy danh sách tất cả cầu thủ
function getAllPlayers($conn) {
    $sql = "
        SELECT p.player_id, p.name, p.position, p.jersey_number, p.photo_url, p.nationality, p.nationality_flag_url, t.name AS team_name
        FROM Players p
        LEFT JOIN Teams t ON p.team_id = t.team_id
        ORDER BY p.name ASC";
    $result = $conn->query($sql);
    $players = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
    }
    return $players;
}


// Tìm kiếm cầu thủ theo tên
function searchPlayersByName($conn, $search_query) {
    $search_query = mysqli_real_escape_string($conn, $search_query);
    $sql = "
        SELECT p.player_id, p.name, p.position, p.jersey_number, p.photo_url, p.nationality, p.nationality_flag_url, t.name AS team_name
        FROM Players p
        LEFT JOIN Teams t ON p.team_id = t.team_id
        WHERE p.name LIKE ?
        ORDER BY p.name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Lỗi prepare: " . mysqli_error($conn));
    }
    $search_pattern = "%$search_query%";
    mysqli_stmt_bind_param($stmt, "s", $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}




// API gợi ý tên cầu thủ
function suggestPlayersByName($conn, $search_query) {
    $search_query = mysqli_real_escape_string($conn, $search_query);
    $sql = "SELECT name FROM Players WHERE name LIKE ? LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    $search_pattern = "%$search_query%";
    mysqli_stmt_bind_param($stmt, "s", $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $suggestions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    return array_column($suggestions, 'name');
}




// Xử lý yêu cầu gợi ý
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'suggestPlayer' && isset($_GET['query'])) {
        $query = $_GET['query'];
        $suggestions = suggestPlayersByName($conn, $query);
        header('Content-Type: application/json');
        echo json_encode($suggestions);
        exit;
    }
}


// Lấy thông tin chi tiết của một cầu thủ theo player_id
function getPlayerDetail($conn, $player_id) {
    $sql = "
        SELECT p.player_id, p.name, p.position, p.jersey_number, p.nationality, p.photo_url, p.nationality_flag_url,
               p.date_of_birth, p.height, p.weight, t.name AS team_name
        FROM Players p
        LEFT JOIN Teams t ON p.team_id = t.team_id
        WHERE p.player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Lỗi prepare: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $player_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result->fetch_assoc();
}




// Xử lý yêu cầu chi tiết cầu thủ (nếu có)
if (isset($_GET['player_id'])) {
    $player_id = (int)$_GET['player_id'];
    $player = getPlayerDetail($conn, $player_id);
    if ($player) {
        // Trả về dữ liệu dưới dạng mảng để sử dụng trong view
        $response = ['success' => true, 'player' => $player];
    } else {
        $response = ['success' => false, 'message' => 'Không tìm thấy cầu thủ'];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


// Nếu không có action nào, trả về danh sách tất cả cầu thủ
$players = getAllPlayers($conn);
$teams = getAllTeams($conn);


?>

