<?php

require_once __DIR__ . '/../includes/config.php';

class ChitietController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Lấy chi tiết trận đấu dựa trên match_id
     * @param int $match_id ID của trận đấu
     * @return array Mảng chứa thông tin trận đấu, sự kiện, và cầu thủ
     */
    public function getMatchDetails($match_id) {
        // Khởi tạo mảng kết quả
        $response = [
            'success' => false,
            'data' => [],
            'message' => ''
        ];

        // Kiểm tra match_id hợp lệ
        if ($match_id <= 0) {
            $response['message'] = 'ID trận đấu không hợp lệ';
            return $response;
        }

        // Truy vấn thông tin trận đấu
        $query = "
            SELECT 
                m.match_id,
                m.home_team_id,
                m.away_team_id,
                m.home_team_score,
                m.away_team_score,
                m.match_date,
                m.stadium,
                m.status,
                t1.name AS home_team,
                t1.logo_url AS home_team_logo,
                t2.name AS away_team,
                t2.logo_url AS away_team_logo,
                s.name AS season_name
            FROM matches m
            LEFT JOIN teams t1 ON m.home_team_id = t1.team_id
            LEFT JOIN teams t2 ON m.away_team_id = t2.team_id
            LEFT JOIN seasons s ON m.season_id = s.season_id
            WHERE m.match_id = ?
        ";

        // Sử dụng prepared statement để tránh SQL Injection
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) {
            $response['message'] = 'Lỗi truy vấn cơ sở dữ liệu: ' . mysqli_error($this->conn);
            error_log($response['message']);
            return $response;
        }

        mysqli_stmt_bind_param($stmt, 'i', $match_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result || mysqli_num_rows($result) == 0) {
            $response['message'] = 'Không tìm thấy trận đấu';
            mysqli_stmt_close($stmt);
            return $response;
        }

        $match = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Truy vấn các sự kiện trận đấu
        $events_query = "
            SELECT 
                me.event_id,
                me.event_type,
                me.minute,
                me.note,
                me.is_home,
                p.name AS player_name,
                t.name AS team_name
            FROM match_events me
            LEFT JOIN players p ON me.player_id = p.player_id
            LEFT JOIN teams t ON me.team_id = t.team_id
            WHERE me.match_id = ?
            ORDER BY me.minute ASC
        ";

        $stmt = mysqli_prepare($this->conn, $events_query);
        mysqli_stmt_bind_param($stmt, 'i', $match_id);
        mysqli_stmt_execute($stmt);
        $events_result = mysqli_stmt_get_result($stmt);

        $events = [];
        while ($row = mysqli_fetch_assoc($events_result)) {
            $events[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Truy vấn danh sách cầu thủ của cả hai đội
        $players_query = "
            SELECT 
                p.player_id,
                p.name AS player_name,
                t.name AS team_name,
                CASE 
                    WHEN p.team_id = ? THEN 'home'
                    WHEN p.team_id = ? THEN 'away'
                    ELSE 'unknown'
                END AS team_type
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.team_id
            WHERE p.team_id IN (?, ?)
        ";

        $stmt = mysqli_prepare($this->conn, $players_query);
        mysqli_stmt_bind_param($stmt, 'iiii', $match['home_team_id'], $match['away_team_id'], $match['home_team_id'], $match['away_team_id']);
        mysqli_stmt_execute($stmt);
        $players_result = mysqli_stmt_get_result($stmt);

        $players = [];
        while ($row = mysqli_fetch_assoc($players_result)) {
            $players[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Gán dữ liệu vào response
        $response['success'] = true;
        $response['data'] = [
            'match' => $match,
            'events' => $events,
            'players' => $players
        ];

        return $response;
    }
}
?>