<?php
require_once(__DIR__ . '/../includes/config.php');

class standingController {
    private $conn;

    // Constructor với kết nối cơ sở dữ liệu
    public function __construct($conn) {
        if (!$conn instanceof mysqli || $conn->connect_error) {
            error_log("Kết nối cơ sở dữ liệu không hợp lệ: " . ($conn->connect_error ?? 'Không có kết nối'));
            throw new Exception("Kết nối cơ sở dữ liệu không hợp lệ");
        }
        $this->conn = $conn;
        // Đảm bảo múi giờ cơ sở dữ liệu khớp với config
        $this->conn->query("SET time_zone = '+07:00'");
    }

    // Lấy season_id từ tên mùa giải
    private function getSeasonId($seasonName) {
        $stmt = $this->conn->prepare("SELECT season_id FROM Seasons WHERE name = ?");
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn getSeasonId: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("s", $seasonName);
        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn getSeasonId: " . $stmt->error);
            return null;
        }
        $result = $stmt->get_result();
        $season = $result->fetch_assoc();
        return $season ? $season['season_id'] : null;
    }

    // Lấy bảng xếp hạng với các bộ lọc
    public function getStandings($seasonName = '2024/2025', $matchweek = null, $filterType = 'all') {
        // Xác thực loại bộ lọc
        if (!in_array($filterType, ['all', 'home', 'away'])) {
            $filterType = 'all';
        }

        // Lấy season_id
        $seasonId = $this->getSeasonId($seasonName);
        if (!$seasonId) {
            error_log("Không tìm thấy mùa giải: $seasonName");
            return [];
        }

        // Chuẩn hóa $matchweek
        $matchweekOriginal = $matchweek;
        $matchweekNormalized = null;
        if ($matchweek !== null && $matchweek !== '') {
            // Loại bỏ tiền tố "Round " và lấy số
            if (preg_match('/^Round\s*(\d+)/i', $matchweek, $matches)) {
                $matchweekNormalized = $matches[1]; // Ví dụ: "Round 1" -> "1"
            } else {
                $matchweekNormalized = $matchweek; // Giữ nguyên nếu không có tiền tố "Round"
            }
        }

        // Ghi log để debug
        error_log("getStandings: seasonName=$seasonName, matchweekOriginal=$matchweekOriginal, matchweekNormalized=$matchweekNormalized, filterType=$filterType");

        // Xây dựng truy vấn bảng xếp hạng
        $query = "
            SELECT
                t.team_id,
                t.name AS team_name,
                t.logo_url,
                COUNT(m.match_id) AS matches_played,
                COUNT(CASE WHEN (m.home_team_id = t.team_id AND m.home_team_score > m.away_team_score)
                           OR (m.away_team_id = t.team_id AND m.away_team_score > m.home_team_score) THEN 1 END) AS wins,
                COUNT(CASE WHEN (m.home_team_id = t.team_id AND m.home_team_score < m.away_team_score)
                           OR (m.away_team_id = t.team_id AND m.away_team_score < m.home_team_score) THEN 1 END) AS losses,
                COUNT(CASE WHEN m.home_team_score = m.away_team_score AND m.status = 'Completed' THEN 1 END) AS draws,
                SUM(CASE WHEN m.home_team_id = t.team_id THEN m.home_team_score ELSE 0 END) +
                SUM(CASE WHEN m.away_team_id = t.team_id THEN m.away_team_score ELSE 0 END) AS goals_for,
                SUM(CASE WHEN m.home_team_id = t.team_id THEN m.away_team_score ELSE 0 END) +
                SUM(CASE WHEN m.away_team_id = t.team_id THEN m.home_team_score ELSE 0 END) AS goals_against,
                (SUM(CASE WHEN m.home_team_id = t.team_id THEN m.home_team_score ELSE 0 END) +
                 SUM(CASE WHEN m.away_team_id = t.team_id THEN m.away_team_score ELSE 0 END) -
                 SUM(CASE WHEN m.home_team_id = t.team_id THEN m.away_team_score ELSE 0 END) -
                 SUM(CASE WHEN m.away_team_id = t.team_id THEN m.home_team_score ELSE 0 END)) AS goal_difference,
                (COUNT(CASE WHEN (m.home_team_id = t.team_id AND m.home_team_score > m.away_team_score)
                           OR (m.away_team_id = t.team_id AND m.away_team_score > m.home_team_score) THEN 1 END) * 3 +
                 COUNT(CASE WHEN m.home_team_score = m.away_team_score AND m.status = 'Completed' THEN 1 END)) AS points
            FROM Teams t
            LEFT JOIN Matches m ON (t.team_id = m.home_team_id OR t.team_id = m.away_team_id)
            WHERE m.season_id = ? AND m.status = 'Completed'
        ";

        // Thêm điều kiện lọc home/away
        if ($filterType !== 'all') {
            $query .= " AND (t.team_id = m." . ($filterType === 'home' ? 'home_team_id' : 'away_team_id') . ")";
        }

        // Thêm bộ lọc vòng đấu
        if ($matchweek !== null && $matchweek !== '') {
            $query .= " AND (TRIM(m.round) = ? OR TRIM(m.round) = ?)";
        }

        $query .= "
            GROUP BY t.team_id, t.name, t.logo_url
            ORDER BY points DESC, wins DESC
        ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn getStandings: " . $this->conn->error);
            return [];
        }

        // Ràng buộc tham số
        if ($matchweek !== null && $matchweek !== '') {
            $stmt->bind_param('iss', $seasonId, $matchweekOriginal, $matchweekNormalized);
        } else {
            $stmt->bind_param('i', $seasonId);
        }

        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn getStandings: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $standings = [];
        while ($row = $result->fetch_assoc()) {
            $standings[] = $row;
        }

        // Ghi log kết quả
        error_log("getStandings: Số hàng trả về: " . count($standings));

        // Thêm vị trí, phong độ và trận tiếp theo
        $position = 1;
        foreach ($standings as &$team) {
            $team['position'] = $position++;
            $team['form'] = $this->getTeamForm($team['team_id'], $seasonId, $matchweek);
            $team['next_match'] = $this->getNextMatch($team['team_id'], $seasonId);
        }

        return $standings;
    }

    // Lấy phong độ đội bóng (5 trận gần nhất)
    private function getTeamForm($teamId, $seasonId, $matchweek = null) {
        // Chuẩn hóa $matchweek
        $matchweekOriginal = $matchweek;
        $matchweekNormalized = null;
        if ($matchweek !== null && $matchweek !== '') {
            if (preg_match('/^Round\s*(\d+)/i', $matchweek, $matches)) {
                $matchweekNormalized = $matches[1];
            } else {
                $matchweekNormalized = $matchweek;
            }
        }

        $query = "
            SELECT home_team_id, away_team_id, home_team_score, away_team_score, status
            FROM Matches
            WHERE (home_team_id = ? OR away_team_id = ?)
                AND season_id = ?
                AND status = 'Completed'
        ";
        if ($matchweek !== null && $matchweek !== '') {
            $query .= " AND (TRIM(round) = ? OR TRIM(round) = ?)";
        }
        $query .= " ORDER BY match_date DESC LIMIT 5";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn getTeamForm: " . $this->conn->error);
            return [];
        }

        if ($matchweek !== null && $matchweek !== '') {
            $stmt->bind_param("iiiss", $teamId, $teamId, $seasonId, $matchweekOriginal, $matchweekNormalized);
        } else {
            $stmt->bind_param("iii", $teamId, $teamId, $seasonId);
        }

        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn getTeamForm: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $form = [];

        while ($match = $result->fetch_assoc()) {
            if ($match['home_team_id'] == $teamId) {
                if ($match['home_team_score'] > $match['away_team_score']) {
                    $form[] = 'W';
                } elseif ($match['home_team_score'] == $match['away_team_score']) {
                    $form[] = 'D';
                } else {
                    $form[] = 'L';
                }
            } else {
                if ($match['away_team_score'] > $match['home_team_score']) {
                    $form[] = 'W';
                } elseif ($match['away_team_score'] == $match['home_team_score']) {
                    $form[] = 'D';
                } else {
                    $form[] = 'L';
                }
            }
        }

        return array_reverse($form); // Trận gần nhất hiển thị cuối để khớp với giao diện
    }

    // // Lấy trận đấu tiếp theo của đội
    // private function getNextMatch($teamId, $seasonId) {
    //     $query = "
    //         SELECT 
    //             m.match_id, 
    //             m.home_team_id, 
    //             m.away_team_id, 
    //             m.match_date, 
    //             t1.logo_url AS home_logo, 
    //             t2.logo_url AS away_logo
    //         FROM Matches m
    //         JOIN Teams t1 ON m.home_team_id = t1.team_id
    //         JOIN Teams t2 ON m.away_team_id = t2.team_id
    //         WHERE (m.home_team_id = ? OR m.away_team_id = ?)
    //             AND m.season_id = ?
    //             AND m.status = 'Scheduled'
    //             AND CONVERT_TZ(m.match_date, @@session.time_zone, '+07:00') > ?
    //         ORDER BY m.match_date ASC
    //         LIMIT 1
    //     ";
    //     $currentDateTime = date('Y-m-d H:i:s');
    //     $stmt = $this->conn->prepare($query);
    //     if (!$stmt) {
    //         error_log("Lỗi chuẩn bị truy vấn getNextMatch: " . $this->conn->error);
    //         return null;
    //     }
    //     $stmt->bind_param("iiis", $teamId, $teamId, $seasonId, $currentDateTime);

    //     if (!$stmt->execute()) {
    //         error_log("Lỗi thực thi truy vấn getNextMatch: " . $stmt->error);
    //         return null;
    //     }

    //     $result = $stmt->get_result();
    //     return $result->fetch_assoc();
    // }


    public function getNextMatch($teamId, $seasonId, $matchweek = null) {
    $query = "
        SELECT 
            m.match_id, 
            m.home_team_id, 
            m.away_team_id, 
            m.match_date, 
            t1.logo_url AS home_logo, 
            t2.logo_url AS away_logo,
            t1.name AS home_team_name, 
            t2.name AS away_team_name
        FROM Matches m
        JOIN Teams t1 ON m.home_team_id = t1.team_id
        JOIN Teams t2 ON m.away_team_id = t2.team_id
        WHERE (m.home_team_id = ? OR m.away_team_id = ?)
            AND m.season_id = ?
    ";
    
    // Chuẩn hóa matchweek
    $matchweekOriginal = $matchweek;
    $matchweekNormalized = null;
    if ($matchweek !== null && $matchweek !== '') {
        if (preg_match('/^Round\s*(\d+)/i', $matchweek, $matches)) {
            $matchweekNormalized = $matches[1]; // Ví dụ: "Round 1" -> "1"
        } else {
            $matchweekNormalized = $matchweek;
        }
        $query .= " AND (TRIM(m.round) = ? OR TRIM(m.round) = ?)";
    }
    
    $query .= " ORDER BY m.match_date ASC LIMIT 1";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) {
        error_log("Lỗi chuẩn bị truy vấn getNextMatch: " . $this->conn->error);
        return null;
    }

    // Ràng buộc tham số
    if ($matchweek !== null && $matchweek !== '') {
        $stmt->bind_param("iiiss", $teamId, $teamId, $seasonId, $matchweekOriginal, $matchweekNormalized);
    } else {
        $stmt->bind_param("iii", $teamId, $teamId, $seasonId);
    }

    if (!$stmt->execute()) {
        error_log("Lỗi thực thi truy vấn getNextMatch: " . $stmt->error);
        return null;
    }

    $result = $stmt->get_result();
    $nextMatch = $result->fetch_assoc();
    error_log("getNextMatch: teamId=$teamId, seasonId=$seasonId, matchweek=$matchweek, result=" . json_encode($nextMatch));
    return $nextMatch;
}
    // Lấy danh sách mùa giải
    public function getSeasons() {
        $query = "SELECT name FROM Seasons ORDER BY start_date DESC";
        $result = $this->conn->query($query);
        if (!$result) {
            error_log("Lỗi truy vấn getSeasons: " . $this->conn->error);
            return [];
        }

        $seasons = [];
        while ($row = $result->fetch_assoc()) {
            $seasons[] = $row['name'];
        }
        return $seasons;
    }

    // Lấy danh sách vòng đấu
    public function getMatchweeks($seasonName = '2024/2025') {
        $seasonId = $this->getSeasonId($seasonName);
        if (!$seasonId) {
            error_log("Không tìm thấy mùa giải trong getMatchweeks: $seasonName");
            return [];
        }

        $query = "SELECT DISTINCT round FROM Matches WHERE season_id = ? AND round IS NOT NULL AND status = 'Completed' ORDER BY round";
        // $query = "SELECT DISTINCT round FROM Matches WHERE season_id = ? ORDER BY round ASC";
        // $query = "SELECT DISTINCT round FROM Matches WHERE season_id = ? AND round IS NOT NULL ORDER BY CAST(REGEXP_REPLACE(round, '[^0-9]', '') AS UNSIGNED) ASC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn getMatchweeks: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $seasonId);

        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn getMatchweeks: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $matchweeks = ['' => 'All round'];
        while ($row = $result->fetch_assoc()) {
            $round = $row['round'];
            if ($round !== null) {
                $matchweeks[$round] = $round; // Hiển thị trực tiếp giá trị round
            }
        }
        return $matchweeks;
    }

    // Lấy vòng đấu gần nhất đã hoàn thành
    public function getLatestMatchweek($seasonName = '2024/2025') {
        $seasonId = $this->getSeasonId($seasonName);
        if (!$seasonId) {
            error_log("Không tìm thấy mùa giải trong getLatestMatchweek: $seasonName");
            return null;
        }

        $query = "
            SELECT MAX(round)
            FROM Matches
            WHERE season_id = ?
                AND status = 'Completed'
                AND CONVERT_TZ(match_date, @@session.time_zone, '+07:00') <= ?
                AND round IS NOT NULL
        ";
        $currentDateTime = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn getLatestMatchweek: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("is", $seasonId, $currentDateTime);

        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn getLatestMatchweek: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_row();
        return $row[0] ?? null;
    }

    // Lấy chi tiết trận đấu
    public function getMatchDetails($matchId) {
        $query = "
            SELECT 
                m.match_id, 
                m.home_team_id, 
                m.away_team_id, 
                m.home_team_score, 
                m.away_team_score, 
                m.status, 
                m.season_id, 
                m.round, 
                m.match_date,
                t1.name AS home_team_name, 
                t2.name AS away_team_name
            FROM Matches m
            JOIN Teams t1 ON m.home_team_id = t1.team_id
            JOIN Teams t2 ON m.away_team_id = t2.team_id
            WHERE m.match_id = ?
        ";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn getMatchDetails: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $matchId);

        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn getMatchDetails: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Cập nhật kết quả trận đấu
    public function updateMatchResult($matchId, $homeScore, $awayScore) {
        // Xác thực đầu vào
        if (!is_numeric($homeScore) || !is_numeric($awayScore) || $homeScore < 0 || $awayScore < 0) {
            error_log("Giá trị bàn thắng không hợp lệ: homeScore=$homeScore, awayScore=$awayScore");
            return ['success' => false, 'message' => 'Giá trị bàn thắng không hợp lệ'];
        }

        // Lấy chi tiết trận đấu
        $match = $this->getMatchDetails($matchId);
        if (!$match) {
            error_log("Không tìm thấy trận đấu: matchId=$matchId");
            return ['success' => false, 'message' => 'Không tìm thấy trận đấu'];
        }

        // Cập nhật kết quả trận đấu
        $query = "
            UPDATE Matches
            SET home_team_score = ?, away_team_score = ?, status = 'Completed'
            WHERE match_id = ?
        ";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn updateMatchResult: " . $this->conn->error);
            return ['success' => false, 'message' => 'Cập nhật kết quả trận đấu thất bại'];
        }
        $stmt->bind_param("iii", $homeScore, $awayScore, $matchId);
        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn updateMatchResult: " . $stmt->error);
            return ['success' => false, 'message' => 'Cập nhật kết quả trận đấu thất bại'];
        }

        return ['success' => true, 'message' => 'Cập nhật kết quả trận đấu thành công'];
    }

    // Tạo biểu mẫu chỉnh sửa kết quả trận đấu với Tailwind
    public function renderEditMatchForm($matchId) {
        $match = $this->getMatchDetails($matchId);
        if (!$match) {
            error_log("Không tìm thấy trận đấu trong renderEditMatchForm: matchId=$matchId");
            return "<p class='text-red-500'>Không tìm thấy trận đấu.</p>";
        }

        $form = "
            <div class='edit-match-form bg-white p-6 rounded shadow-md max-w-md mx-auto'>
                <h2 class='text-xl font-bold mb-4'>Chỉnh sửa kết quả trận đấu</h2>
                <p class='mb-2'><strong>" . htmlspecialchars($match['home_team_name']) . "</strong> vs <strong>" . htmlspecialchars($match['away_team_name']) . "</strong></p>
                <p class='mb-2'>Vòng đấu: " . htmlspecialchars($match['round']) . "</p>
                <p class='mb-4'>Ngày: " . date('d/m/Y H:i', strtotime($match['match_date'])) . "</p>
                <form action='" . htmlspecialchars(BASE_URL) . "user/update_match.php' method='POST' class='space-y-4'>
                    <input type='hidden' name='match_id' value='" . $match['match_id'] . "'>
                    <div>
                        <label class='block text-sm font-medium'>Số bàn thắng đội nhà (" . htmlspecialchars($match['home_team_name']) . "):</label>
                        <input type='number' name='home_score' value='" . ($match['home_team_score'] ?? 0) . "' min='0' required class='border p-2 rounded w-full'>
                    </div>
                    <div>
                        <label class='block text-sm font-medium'>Số bàn thắng đội khách (" . htmlspecialchars($match['away_team_name']) . "):</label>
                        <input type='number' name='away_score' value='" . ($match['away_team_score'] ?? 0) . "' min='0' required class='border p-2 rounded w-full'>
                    </div>
                    <button type='submit' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>Cập nhật kết quả</button>
                </form>
            </div>
        ";

        return $form;
    }
}
?>