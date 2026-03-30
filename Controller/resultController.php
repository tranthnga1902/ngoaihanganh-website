<?php
require_once(__DIR__ . '/../includes/config.php');

function getMatchResults($seasonId = null, $teamId = null, $limit = 20, $offset = 0) {
    global $conn;

    $conditions = [];
    $params = [];
    $types = "";

    // Nếu có lọc theo mùa giải
    if ($seasonId !== null) {
        $conditions[] = "m.season_id = ?";
        $params[] = $seasonId;
        $types .= "i";
    }

    // Nếu có lọc theo đội (đội nhà hoặc đội khách)
    if ($teamId !== null) {
        $conditions[] = "(m.home_team_id = ? OR m.away_team_id = ?)";
        $params[] = $teamId;
        $params[] = $teamId;
        $types .= "ii";
    }

    // Lọc các trận có trạng thái completed
    $conditions[] = "m.status = 'completed'";

    $whereClause = "";
    if (!empty($conditions)) {
        $whereClause = "WHERE " . implode(" AND ", $conditions);
    }

    // Thêm LIMIT và OFFSET cho phân trang
    $limitClause = "LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $sql = "
        SELECT
            m.match_id,
            m.round,
            m.match_date,
            h.name AS home_team,
            h.logo_url AS home_logo,
            a.name AS away_team,
            a.logo_url AS away_logo,
            m.home_team_score,
            m.away_team_score,
            s.name AS stadium_name
        FROM Matches m
        JOIN Teams h ON m.home_team_id = h.team_id
        JOIN Teams a ON m.away_team_id = a.team_id
        JOIN Stadiums s ON m.stadium_id = s.stadium_id
        $whereClause
        ORDER BY m.match_date DESC
        $limitClause
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    // Gắn tham số nếu có
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }

    return $matches;
}

function getTotalMatches($teamId = null) {
    global $conn;

    $conditions = [];
    $params = [];
    $types = "";

    // Nếu có lọc theo đội (đội nhà hoặc đội khách)
    if ($teamId !== null) {
        $conditions[] = "(m.home_team_id = ? OR m.away_team_id = ?)";
        $params[] = $teamId;
        $params[] = $teamId;
        $types .= "ii";
    }

    // Lọc các trận có trạng thái completed
    $conditions[] = "m.status = 'completed'";

    $whereClause = "";
    if (!empty($conditions)) {
        $whereClause = "WHERE " . implode(" AND ", $conditions);
    }

    $sql = "
        SELECT COUNT(*) as total
        FROM Matches m
        JOIN Teams h ON m.home_team_id = h.team_id
        JOIN Teams a ON m.away_team_id = a.team_id
        JOIN Stadiums s ON m.stadium_id = s.stadium_id
        $whereClause
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    // Gắn tham số nếu có
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['total'];
}

function getTeams($conn) {
    $sql = "SELECT team_id, name FROM Teams ORDER BY name ASC";
    $result = $conn->query($sql);

    $teams = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
    }

    return $teams;
}

function getMatchDetails($matchId) {
    global $conn;

    $sql = "
        SELECT
            m.*,
            h.name AS home_team,
            h.logo_url AS home_logo,
            a.name AS away_team,
            a.logo_url AS away_logo,
            s.name AS stadium_name,
            s.location AS stadium_location,
            s.capacity AS stadium_capacity
        FROM Matches m
        JOIN Teams h ON m.home_team_id = h.team_id
        JOIN Teams a ON m.away_team_id = a.team_id
        JOIN Stadiums s ON m.stadium_id = s.stadium_id
        WHERE m.match_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function getMatchEvents($matchId) {
    global $conn;

    $sql = "
        SELECT
            e.*,
            p.name AS player_name,
            t.name AS team_name
        FROM Match_Events e
        LEFT JOIN Players p ON e.player_id = p.player_id
        LEFT JOIN Teams t ON e.team_id = t.team_id
        WHERE e.match_id = ?
        ORDER BY e.minute ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    return $events;
}

function getMatchStatistics($matchId) {
    global $conn;

    $sql = "
        SELECT
            s.*,
            t.name AS team_name
        FROM Match_Statistics s
        JOIN Teams t ON s.team_id = t.team_id
        WHERE s.match_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();

    $statistics = [];
    while ($row = $result->fetch_assoc()) {
        $statistics[] = $row;
    }

    return $statistics;
}



function getUpcomingMatches($limit = 5) {
    global $conn;

    $sql = "
        SELECT
            m.match_id,
            m.match_date,
            h.name AS home_team,
            h.logo_url AS home_logo,
            a.name AS away_team,
            a.logo_url AS away_logo,
            s.name AS stadium_name
        FROM Matches m
        JOIN Teams h ON m.home_team_id = h.team_id
        JOIN Teams a ON m.away_team_id = a.team_id
        JOIN Stadiums s ON m.stadium_id = s.stadium_id
        WHERE m.status = 'scheduled' AND m.match_date > NOW()
        ORDER BY m.match_date ASC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }

    return $matches;
}

function getTeamRecentForm($teamId, $limit = 5) {
    global $conn;

    $sql = "
        SELECT
            m.match_id,
            m.match_date,
            h.name AS home_team,
            a.name AS away_team,
            m.home_team_score,
            m.away_team_score,
            CASE 
                WHEN m.home_team_id = ? THEN 'home'
                WHEN m.away_team_id = ? THEN 'away'
            END as team_position
        FROM Matches m
        JOIN Teams h ON m.home_team_id = h.team_id
        JOIN Teams a ON m.away_team_id = a.team_id
        WHERE (m.home_team_id = ? OR m.away_team_id = ?) 
        AND m.status = 'completed'
        ORDER BY m.match_date DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param("iiiii", $teamId, $teamId, $teamId, $teamId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        // Determine result for the team
        if ($row['team_position'] == 'home') {
            if ($row['home_team_score'] > $row['away_team_score']) {
                $row['result'] = 'W'; // Win
            } elseif ($row['home_team_score'] < $row['away_team_score']) {
                $row['result'] = 'L'; // Loss
            } else {
                $row['result'] = 'D'; // Draw
            }
        } else {
            if ($row['away_team_score'] > $row['home_team_score']) {
                $row['result'] = 'W'; // Win
            } elseif ($row['away_team_score'] < $row['home_team_score']) {
                $row['result'] = 'L'; // Loss
            } else {
                $row['result'] = 'D'; // Draw
            }
        }
        $matches[] = $row;
    }

    return $matches;
}

?>