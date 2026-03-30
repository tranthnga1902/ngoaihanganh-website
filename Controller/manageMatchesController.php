<?php
require_once(__DIR__ . '/../includes/config.php');

function createMatch($conn, $home_team_id, $away_team_id, $stadium_id, $match_date, $round) {
    // Validate inputs
    if ($home_team_id === $away_team_id) {
        return ['status' => 'error', 'message' => 'Đội nhà và đội khách không được trùng nhau'];
    }

    $match_date_dt = new DateTime($match_date);
    $now = new DateTime();
    if ($match_date_dt <= $now) {
        return ['status' => 'error', 'message' => 'Thời gian trận đấu phải trong tương lai'];
    }

    $season_id = 1; // Fixed season 2024/2025
    $sql = "INSERT INTO Matches (season_id, home_team_id, away_team_id, stadium_id, match_date, round, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiiiss", $season_id, $home_team_id, $away_team_id, $stadium_id, $match_date, $round);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Thêm trận đấu thành công'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Lỗi khi thêm trận đấu'];
        }
    }
    return ['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn'];
}

function postponeMatch($conn, $match_id, $new_date) {
    $new_date_dt = new DateTime($new_date);
    $now = new DateTime();
    if ($new_date_dt <= $now) {
        return ['status' => 'error', 'message' => 'Thời gian mới phải trong tương lai'];
    }

    $sql = "UPDATE Matches SET match_date = ?, status = 'Postponed' WHERE match_id = ? AND status != 'Completed'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $new_date, $match_id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Hoãn trận đấu thành công'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Lỗi khi hoãn trận đấu'];
        }
    }
    return ['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn'];
}

function cancelMatch($conn, $match_id) {
    $sql = "DELETE FROM Matches WHERE match_id = ? AND status != 'Completed'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $match_id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Hủy trận đấu thành công'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Lỗi khi hủy trận đấu'];
        }
    }
    return ['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn'];
}

// Handle requests
$action = $_POST['action'] ?? '';
$result = ['status' => 'error', 'message' => 'Hành động không hợp lệ'];

if ($action === 'create') {
    $home_team_id = $_POST['home_team_id'] ?? '';
    $away_team_id = $_POST['away_team_id'] ?? '';
    $stadium_id = $_POST['stadium_id'] ?? '';
    $match_date = $_POST['match_date'] ?? '';
    $round = $_POST['round'] ?? '';
    $result = createMatch($conn, $home_team_id, $away_team_id, $stadium_id, $match_date, $round);
} elseif ($action === 'postpone') {
    $match_id = $_POST['match_id'] ?? '';
    $new_date = $_POST['new_date'] ?? '';
    $result = postponeMatch($conn, $match_id, $new_date);
} elseif ($action === 'cancel') {
    $match_id = $_POST['match_id'] ?? '';
    $result = cancelMatch($conn, $match_id);
}

header("Location: " . BASE_URL . "admin/manageMatches.php?status={$result['status']}&message=" . urlencode($result['message']));
exit();
?>