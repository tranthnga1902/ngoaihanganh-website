<?php
require_once(__DIR__ . '/../includes/config.php');






// Hàm thêm base URL vào ảnh nếu chưa có
function addBaseUrl($url) {
    if (!$url) return '';
    return (strpos($url, 'http') === 0) ? $url : BASE_URL . $url;
}




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
            s.photo_url AS stadium_photo,
            s.city AS stadium_city  -- thêm thành phố sân
        FROM Teams t
        LEFT JOIN Stadiums s ON t.stadium_id = s.stadium_id
        ORDER BY t.name ASC
    ";
    $result = $conn->query($sql);
    $teams = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['logo_url'] = addBaseUrl($row['logo_url']);
            $row['stadium_photo'] = addBaseUrl($row['stadium_photo']);
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
            s.name AS stadium_name,
            s.photo_url AS stadium_photo,
            s.capacity AS stadium_capacity,
            s.address AS stadium_address,
            s.city AS stadium_city,
            s.built_year AS stadium_built_year
        FROM Teams t
        LEFT JOIN Stadiums s ON t.stadium_id = s.stadium_id
        WHERE t.team_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = $result->fetch_assoc();
    $stmt->close();




    if ($team) {
        $team['logo_url'] = addBaseUrl($team['logo_url']);
        $team['stadium_photo'] = addBaseUrl($team['stadium_photo']);
    }




    return $team;
}




// Lấy tên đội bóng
function getTeamName($conn, $team_id) {
    $team = getTeamById($conn, $team_id);
    return $team['name'] ?? '';
}




// Lấy danh sách trận đấu chưa diễn ra, nhóm theo ngày
function getMatchesGroupedByDate($conn) {
    $sql = "
        SELECT
            m.match_id,
            m.home_team_id,
            m.away_team_id,
            m.match_date,
            m.stadium_id,
            m.status,
            ht.name AS home_team_name,
            ht.logo_url AS home_logo,
            at.name AS away_team_name,
            at.logo_url AS away_logo,
            s.name AS stadium_name,
            s.city AS stadium_city
        FROM Matches m
        INNER JOIN Teams ht ON m.home_team_id = ht.team_id
        INNER JOIN Teams at ON m.away_team_id = at.team_id
        LEFT JOIN Stadiums s ON m.stadium_id = s.stadium_id
        WHERE m.match_date > NOW() AND m.status = 'scheduled'
        ORDER BY m.match_date ASC
    ";


    $result = $conn->query($sql);
    $matches_by_date = [];


    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date = date('Y-m-d', strtotime($row['match_date']));
            $match = [
                'match_id' => $row['match_id'],
                'time' => date('H:i', strtotime($row['match_date'])),
                'home_team' => $row['home_team_name'],
                'away_team' => $row['away_team_name'],
                'home_logo' => addBaseUrl($row['home_logo']),
                'away_logo' => addBaseUrl($row['away_logo']),
                'stadium' => $row['stadium_name'],
                'stadium_city' => $row['stadium_city']
            ];
            $matches_by_date[$date][] = $match;
        }
    }


    return $matches_by_date;
}


// Lấy 10 trận đấu được lên lịch gần đây nhất
function getRecentMatches($conn) {
    $sql = "
        SELECT
            m.match_id,
            m.round,
            m.home_team_id,
            m.away_team_id,
            m.match_date,
            m.stadium_id,
            m.status,
           
            ht.name AS home_team_name,
            ht.logo_url AS home_logo,
            at.name AS away_team_name,
            at.logo_url AS away_logo,
            s.name AS stadium_name,
            s.city AS stadium_city
        FROM Matches m
        INNER JOIN Teams ht ON m.home_team_id = ht.team_id
        INNER JOIN Teams at ON m.away_team_id = at.team_id
        LEFT JOIN Stadiums s ON m.stadium_id = s.stadium_id
        WHERE m.status = 'scheduled'
        ORDER BY m.match_date ASC
        LIMIT 10
    ";


    $result = $conn->query($sql);
    $matches = [];


    if ($result === false) {
        error_log("Lỗi truy vấn SQL: " . $conn->error);
        return [];
    }


    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $match = [
                'round' => $row['round'],
                'match_id' => $row['match_id'],
                'date' => 'NGÀY ' . date('d', strtotime($row['match_date'])) . ' THÁNG ' . date('m', strtotime($row['match_date'])), // Định dạng mới
                'time' => date('H:i', strtotime($row['match_date'])),
                'home_team' => $row['home_team_name'],
                'away_team' => $row['away_team_name'],
                'home_logo' => addBaseUrl($row['home_logo']),
                'away_logo' => addBaseUrl($row['away_logo']),
                'stadium' => $row['stadium_name'],
                'stadium_city' => $row['stadium_city'],
                'status' => $row['status']
            ];
            $matches[] = $match;
        }
    }


    return $matches;
}


// Lấy danh sách trận đấu
$matches = getRecentMatches($conn);


// Nhóm các trận đấu theo ngày
$matches_by_date = [];
foreach ($matches as $match) {
    $date = $match['date'];
    $matches_by_date[$date][] = $match;
}
// Lấy 10 trận đấu đã hoàn thành gần đây
function fetchLatestMatches($limit = 10) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT
                m.match_id, m.match_date, m.round,
                m.home_team_score, m.away_team_score,
                ht.name AS home_team, ht.logo_url AS home_team_logo,
                at.name AS away_team, at.logo_url AS away_team_logo,
                s.name AS stadium, s.city AS stadium_city
            FROM matches m
            JOIN teams ht ON m.home_team_id = ht.team_id
            JOIN teams at ON m.away_team_id = at.team_id
            JOIN stadiums s ON m.stadium_id = s.stadium_id
            WHERE m.status = 'completed'
            ORDER BY m.match_date DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
       
        $result = $stmt->get_result();
        $matches = [];
        while ($row = $result->fetch_assoc()) {
            $row['home_team_logo'] = addBaseUrl($row['home_team_logo']);
            $row['away_team_logo'] = addBaseUrl($row['away_team_logo']);
            $row['date'] = 'NGÀY ' . date('d', strtotime($row['match_date'])) . ' THÁNG ' . date('m', strtotime($row['match_date'])); // Định dạng mới
            $matches[] = $row;
        }
        $stmt->close();
        return $matches;
    } catch (Exception $e) {
        error_log("Lỗi khi lấy trận đấu: " . $e->getMessage());
        return [];
    }
}


// Lấy danh sách 10 trận đấu gần nhất
$latestMatches = fetchLatestMatches();


// Nhóm trận đấu theo ngày
$matches_by_date = [];
foreach ($latestMatches as $match) {
    $date = $match['date'];
    if (!isset($matches_by_date[$date])) {
        $matches_by_date[$date] = [];
    }
    $matches_by_date[$date][] = $match;
}


?>





