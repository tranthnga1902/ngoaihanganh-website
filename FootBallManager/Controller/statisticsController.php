<?php
require_once(dirname(__DIR__) . '/includes/config.php');

// Hàm lấy mùa giải mới nhất
function getLatestSeasonId($conn) {
    $query = "SELECT season_id FROM seasons ORDER BY end_date DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return (int)$row['season_id']; // Chuyển thành kiểu int
    }
    error_log("No seasons found in getLatestSeasonId");
    return null; // Trả về null nếu không tìm thấy mùa giải
}

// Hàm lấy thống kê cầu thủ
function getPlayerStats($conn, $field, $orderBy, $limit = 10) {
    // Kiểm tra kết nối
    if (!$conn) {
        error_log("Database connection is not established in getPlayerStats");
        return [];
    }

    // Lấy season_id
    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getPlayerStats");
        return [];
    }

    // Kiểm tra giá trị của $field và $orderBy để tránh SQL injection
    $allowed_fields = ['total_goals', 'assists', 'yellow_cards', 'clean_sheets', 'penalties_scored', 'penalties_missed', 'saves'];
    if (!in_array($field, $allowed_fields) || !in_array($orderBy, $allowed_fields)) {
        error_log("Invalid field or orderBy in getPlayerStats: field=$field, orderBy=$orderBy");
        return [];
    }

    $query = "
        SELECT 
            p.name AS ten_cau_thu,
            t.name AS team_name,
            t.logo_url AS logo,
            p.player_id,
            ps.{$field} AS gia_tri
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = ?
        ORDER BY ps.{$orderBy} DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getPlayerStats: " . mysqli_error($conn));
        return [];
    }

    // Đảm bảo $season_id và $limit là số nguyên
    $season_id = (int)$season_id;
    $limit = (int)$limit;

    // Liên kết tham số
    if (!mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit)) {
        error_log("Binding parameters failed in getPlayerStats: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getPlayerStats: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getPlayerStats: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// Hàm lấy tổng bàn thắng của đội
// Hàm lấy tổng bàn thắng của đội
function getTeamGoals($conn, $limit = 10) {
    if (!$conn) {
        error_log("Database connection is not established in getTeamGoals");
        return [];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTeamGoals");
        return [];
    }

    $query = "
        SELECT 
            t.name AS team_name,
            t.team_id,
            t.logo_url AS logo,
            s.name AS ten_san,
            ts.goals_for AS tong_ban_thang
        FROM teamstats ts
        JOIN teams t ON ts.team_id = t.team_id
        LEFT JOIN stadiums s ON t.stadium_id = s.stadium_id
        WHERE ts.season_id = ?
        ORDER BY ts.goals_for DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTeamGoals: " . mysqli_error($conn));
        return [];
    }

    $season_id = (int)$season_id;
    $limit = (int)$limit;

    if (!mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit)) {
        error_log("Binding parameters failed in getTeamGoals: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTeamGoals: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTeamGoals: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// Hàm lấy tổng thẻ vàng của đội
function getTeamYellowCards($conn, $limit = 10) {
    if (!$conn) {
        error_log("Database connection is not established in getTeamYellowCards");
        return [];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTeamYellowCards");
        return [];
    }

    $query = "
        SELECT 
            t.name AS team_name,
            t.team_id,
            t.logo_url AS logo,
            s.name AS ten_san,
            SUM(ps.yellow_cards) AS tong_the_vang
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        JOIN Stadiums s ON t.stadium_id = s.stadium_id
        WHERE ps.season_id = ?
        GROUP BY t.team_id, t.name, s.name, t.logo_url
        ORDER BY tong_the_vang DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTeamYellowCards: " . mysqli_error($conn));
        return [];
    }

    $season_id = (int)$season_id;
    $limit = (int)$limit;

    if (!mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit)) {
        error_log("Binding parameters failed in getTeamYellowCards: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTeamYellowCards: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTeamYellowCards: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// Hàm lấy số trận thắng của đội
function getTeamWins($conn, $limit = 10) {
    if (!$conn) {
        error_log("Database connection is not established in getTeamWins");
        return [];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTeamWins");
        return [];
    }

    $query = "
        SELECT 
            t.name AS team_name,
            t.team_id,
            t.logo_url AS logo,
            s.name AS ten_san,
            ts.wins AS tong_thang
        FROM teamstats ts
        JOIN Teams t ON ts.team_id = t.team_id
        JOIN Stadiums s ON t.stadium_id = s.stadium_id
        WHERE ts.season_id = ?
        ORDER BY ts.wins DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTeamWins: " . mysqli_error($conn));
        return [];
    }

    $season_id = (int)$season_id;
    $limit = (int)$limit;

    if (!mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit)) {
        error_log("Binding parameters failed in getTeamWins: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTeamWins: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTeamWins: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// Hàm lấy số trận thua của đội
function getTeamLosses($conn, $limit = 10) {
    if (!$conn) {
        error_log("Database connection is not established in getTeamLosses");
        return [];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTeamLosses");
        return [];
    }

    $query = "
        SELECT 
            t.name AS team_name,
            t.team_id,
            t.logo_url AS logo,
            s.name AS ten_san,
            ts.losses AS tong_thua
        FROM teamstats ts
        JOIN Teams t ON ts.team_id = t.team_id
        JOIN Stadiums s ON t.stadium_id = s.stadium_id
        WHERE ts.season_id = ?
        ORDER BY ts.losses DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTeamLosses: " . mysqli_error($conn));
        return [];
    }

    $season_id = (int)$season_id;
    $limit = (int)$limit;

    if (!mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit)) {
        error_log("Binding parameters failed in getTeamLosses: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTeamLosses: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTeamLosses: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// Hàm lấy điểm của đội
function getTeamPoints($conn, $limit = 10) {
    if (!$conn) {
        error_log("Database connection is not established in getTeamPoints");
        return [];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTeamPoints");
        return [];
    }

    $query = "
        SELECT 
            t.name AS team_name,
            t.team_id,
            t.logo_url AS logo,
            s.name AS ten_san,
            ts.points AS tong_diem
        FROM teamstats ts
        JOIN Teams t ON ts.team_id = t.team_id
        JOIN Stadiums s ON t.stadium_id = s.stadium_id
        WHERE ts.season_id = ?
        ORDER BY ts.points DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTeamPoints: " . mysqli_error($conn));
        return [];
    }

    $season_id = (int)$season_id;
    $limit = (int)$limit;

    if (!mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit)) {
        error_log("Binding parameters failed in getTeamPoints: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTeamPoints: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTeamPoints: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// Lấy dữ liệu
$top_scorers = getPlayerStats($conn, 'total_goals', 'total_goals');
$top_assists = getPlayerStats($conn, 'assists', 'assists');
$top_yellow_cards = getPlayerStats($conn, 'yellow_cards', 'yellow_cards');
$top_cleansheets = getPlayerStats($conn, 'clean_sheets', 'clean_sheets');
$top_team_goals = getTeamGoals($conn);
$top_team_wins = getTeamWins($conn);
$top_team_losses = getTeamLosses($conn);
$top_team_points = getTeamPoints($conn);

// Top 1 cầu thủ ghi bàn
function getTopGoalScorer($conn) {
    if (!$conn) {
        error_log("Database connection is not established in getTopGoalScorer");
        return ['player_name' => 'Lỗi kết nối', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTopGoalScorer");
        return ['player_name' => 'Không có mùa giải', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
    }

    $query = "
        SELECT 
            p.name AS player_name,
            t.name AS team_name,
            t.logo_url AS logo,
            ps.total_goals AS total_goals1,
            ps.matches_played AS total_matches
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = ?
        ORDER BY total_goals1 DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTopGoalScorer: " . mysqli_error($conn));
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
    }

    $season_id = (int)$season_id;
    if (!mysqli_stmt_bind_param($stmt, "i", $season_id)) {
        error_log("Binding parameters failed in getTopGoalScorer: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTopGoalScorer: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTopGoalScorer: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
    }

    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data ?: ['player_name' => 'Không có dữ liệu', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
}

// Top 1 cầu thủ kiến tạo
function getTopAssister($conn) {
    if (!$conn) {
        error_log("Database connection is not established in getTopAssister");
        return ['player_name' => 'Lỗi kết nối', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTopAssister");
        return ['player_name' => 'Không có mùa giải', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
    }

    $query = "
        SELECT 
            p.name AS player_name,
            t.name AS team_name,
            t.logo_url AS logo,
            ps.assists AS total_assists,
            ps.matches_played AS total_matches
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = ?
        ORDER BY total_assists DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTopAssister: " . mysqli_error($conn));
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
    }

    $season_id = (int)$season_id;
    if (!mysqli_stmt_bind_param($stmt, "i", $season_id)) {
        error_log("Binding parameters failed in getTopAssister: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTopAssister: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTopAssister: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['player_name' => 'Lỗi truy vấn', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
    }

    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data ?: ['player_name' => 'Không có dữ liệu', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
}



// Top 1 đội bóng ghi bàn
function getTopScoringTeam($conn) {
    if (!$conn) {
        error_log("Database connection is not established in getTopScoringTeam");
        return ['team_name' => 'Lỗi kết nối', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
    }

    $season_id = getLatestSeasonId($conn);
    if ($season_id === null) {
        error_log("No valid season_id found for getTopScoringTeam");
        return ['team_name' => 'Không có mùa giải', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
    }

    $query = "
        SELECT 
            t.name AS team_name,
            t.logo_url AS logo,
            ts.goals_for AS total_goals_for,
            ts.matches_played AS total_matches
        FROM TeamStats ts
        JOIN Teams t ON ts.team_id = t.team_id
        WHERE ts.season_id = ?
        ORDER BY total_goals_for DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement in getTopScoringTeam: " . mysqli_error($conn));
        return ['team_name' => 'Lỗi truy vấn', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
    }

    $season_id = (int)$season_id;
    if (!mysqli_stmt_bind_param($stmt, "i", $season_id)) {
        error_log("Binding parameters failed in getTopScoringTeam: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['team_name' => 'Lỗi truy vấn', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execution failed in getTopScoringTeam: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['team_name' => 'Lỗi truy vấn', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("Getting result failed in getTopScoringTeam: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return ['team_name' => 'Lỗi truy vấn', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
    }

    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data ?: ['team_name' => 'Không có dữ liệu', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
}

// Lấy dữ liệu
$top_scorers = isset($conn) ? getPlayerStats($conn, 'total_goals', 'total_goals') : [];
$top_assists = isset($conn) ? getPlayerStats($conn, 'assists', 'assists') : [];
$top_yellow_cards = isset($conn) ? getPlayerStats($conn, 'yellow_cards', 'yellow_cards') : [];
$top_cleansheets = isset($conn) ? getPlayerStats($conn, 'clean_sheets', 'clean_sheets') : [];
$top_team_goals = isset($conn) ? getTeamGoals($conn) : [];
$top_team_wins = isset($conn) ? getTeamWins($conn) : [];
$top_team_losses = isset($conn) ? getTeamLosses($conn) : [];
$top_team_points = isset($conn) ? getTeamPoints($conn) : [];

$top_goal = isset($conn) ? getTopGoalScorer($conn) : ['player_name' => 'Lỗi kết nối', 'team_name' => '', 'logo' => '', 'total_goals1' => 0, 'total_matches' => 0];
$top_assist = isset($conn) ? getTopAssister($conn) : ['player_name' => 'Lỗi kết nối', 'team_name' => '', 'logo' => '', 'total_assists' => 0, 'total_matches' => 0];
// $top_win = isset($conn) ? getTopWinningTeam($conn) : ['team_name' => 'Lỗi kết nối', 'logo' => '', 'total_wins' => 0, 'total_matches' => 0];
$top_score = isset($conn) ? getTopScoringTeam($conn) : ['team_name' => 'Lỗi kết nối', 'logo' => '', 'total_goals_for' => 0, 'total_matches' => 0];
?>