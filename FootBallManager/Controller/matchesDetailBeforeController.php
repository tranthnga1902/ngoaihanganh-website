<?php
require_once '../includes/config.php';

// Hàm lấy chi tiết 1 trận đấu theo ID
function getMatchDetails($conn, $match_id) {
    $sql = "SELECT 
                m.match_id,
                m.match_date,
                DATE_FORMAT(m.match_date, '%H:%i') AS match_time,
                s.name AS stadium,
                s.city AS stadium_city,
                ht.name AS home_team,
                ht.team_id AS home_team_id,
                IFNULL(ht.logo_url, 'uploads/teams/default_logo.png') AS home_logo,
                at.name AS away_team,
                at.team_id AS away_team_id,
                IFNULL(at.logo_url, 'uploads/teams/default_logo.png') AS away_logo
            FROM matches m
            JOIN teams ht ON m.home_team_id = ht.team_id
            JOIN teams at ON m.away_team_id = at.team_id
            JOIN stadiums s ON m.stadium_id = s.stadium_id
            WHERE m.match_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc(); // Trả về dữ liệu dạng mảng
}

// Hàm lấy danh sách cầu thủ theo team_id với vị trí đội hình
function getTeamPlayers($conn, $team_id) {
    $sql = "SELECT 
                player_id,
                name AS name,
                position,
                jersey_number,
                photo_url,
                CASE position
                    WHEN 'Thủ môn' THEN 1
                    WHEN 'Hậu vệ' THEN 2
                    WHEN 'Tiền vệ' THEN 3
                    WHEN 'Tiền đạo' THEN 4
                    ELSE 5
                END AS formation_order
            FROM players
            WHERE team_id = ?
            ORDER BY formation_order, jersey_number";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $players = [];
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
    
    return $players; // Trả về mảng các cầu thủ
}

// Hàm lấy thông tin huấn luyện viên theo team_id
function getTeamManager($conn, $team_id) {
    $sql = "SELECT 
                name,
                photo_url
            FROM managers
            WHERE team_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc() ?: ['name' => 'Chưa có thông tin', 'photo_url' => 'uploads/managers/default_manager.png']; // Trả về thông tin huấn luyện viên với fallback
}
?>