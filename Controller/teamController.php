<?php
require_once '../includes/config.php';




// Lấy danh sách tất cả câu lạc bộ
function getAllTeams($conn) {
    $sql = "
        SELECT
            t.team_id,
            t.name,
            t.short_name,
            t.logo_url,
            t.city,
            t.founded_year,
            s.name AS stadium_name,
            s.photo_url AS stadium_photo
        FROM
            Teams t
            LEFT JOIN Stadiums s ON t.stadium_id = s.stadium_id
        ORDER BY
            t.name ASC
    ";
    $result = $conn->query($sql);
    $teams = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
    }
    return $teams;
}


// Lấy chi tiết một câu lạc bộ
function getTeamById($conn, $team_id) {
    $sql = "
        SELECT
            t.team_id,
            t.name,
            t.short_name,
            t.logo_url,
            t.city,
            t.founded_year,
            t.facebook,
            t.twitter,
            t.instagram,
            t.youtube,
            t.website,
            s.name AS stadium_name,
            s.photo_url AS stadium_photo,
            s.capacity AS stadium_capacity,
            s.address AS stadium_address,
            s.city AS stadium_city,
            s.built_year AS stadium_built_year,
            m.name AS manager_name,
            m.photo_url AS manager_photo,
            m.information AS manager_infor
        FROM
            Teams t
            LEFT JOIN Stadiums s ON t.stadium_id = s.stadium_id
            LEFT JOIN Managers m ON m.team_id = t.team_id
        WHERE
            t.team_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = $result->fetch_assoc();
    $stmt->close();
    return $team;
}


// Lấy tin tức liên quan đến câu lạc bộ
function getNewsByTeam($conn, $team_id) {
    $sql = "
        SELECT
            n.news_id,
            n.title,
            n.content,
            n.publish_date,
            n.author,
            n.image_url,
            n.views
        FROM
            News n
        WHERE
            n.title LIKE ?
        ORDER BY
            n.publish_date DESC
        LIMIT 5
    ";
    $team_name_pattern = "%" . getTeamName($conn, $team_id) . "%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $team_name_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = [];
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
    $stmt->close();
    return $news;
}


// Hàm phụ: Lấy tên đội để tìm kiếm tin tức
function getTeamName($conn, $team_id) {
    $sql = "SELECT name FROM Teams WHERE team_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = $result->fetch_assoc();
    $stmt->close();
    return $team ? $team['name'] : '';
}




//tìm kiếm bởi tên, thành phố, sân vận động
function searchTeamsByName($conn, $searchTerm) {
    $searchTerm = "%$searchTerm%";
    $sql = "SELECT t.*, s.name as stadium_name
            FROM Teams t
            LEFT JOIN Stadiums s ON t.stadium_id = s.stadium_id
            WHERE t.name LIKE ? OR t.city LIKE ? OR s.name LIKE ?
            ORDER BY t.name ASC";


   
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
   
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


function updateView($conn, $news_id) {
    $sql = "UPDATE news SET views = views + 1 WHERE news_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $news_id);
    return $stmt->execute();
}


?>





