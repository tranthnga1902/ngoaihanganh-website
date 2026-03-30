<?php
require_once(__DIR__ . '/../includes/config.php');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/laragon/logs/php_errors.log'); // Điều chỉnh đường dẫn nếu cần
require_once(__DIR__ . '/../includes/config.php');

class RankingController
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getRounds($season_id)
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT round FROM Matches WHERE season_id = ? AND status = 'Completed' ORDER BY round");
        $stmt->bind_param("i", $season_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rounds = [];
        while ($row = $result->fetch_assoc()) {
            $rounds[] = $row['round'];
        }
        $stmt->close();
        return $rounds;
    }

    public function getRankings($season_id, $round = null)
    {
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
        if ($round !== null) {
            $query .= " AND m.round = ?";
        }
        $query .= " GROUP BY t.team_id ORDER BY points DESC, wins DESC";

        $stmt = $this->conn->prepare($query);
        if ($round !== null) {
            $stmt->bind_param("is", $season_id, $round);
        } else {
            $stmt->bind_param("i", $season_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rankings = [];
        while ($row = $result->fetch_assoc()) {
            $rankings[] = $row;
        }
        $stmt->close();
        return $rankings;
    }

    // public function getMatchDetailsByTeam($team_id, $season_id, $round = null) {
    //     $query = "
    //         SELECT m.match_id, m.home_team_id, m.away_team_id, m.home_team_score, m.away_team_score,
    //                h.name AS home_team_name, a.name AS away_team_name
    //         FROM Matches m
    //         JOIN Teams h ON m.home_team_id = h.team_id
    //         JOIN Teams a ON m.away_team_id = a.team_id
    //         WHERE (m.home_team_id = ? OR m.away_team_id = ?) AND m.season_id = ? AND m.status = 'Completed'
    //     ";
    //     if ($round !== null) {
    //         $query .= " AND m.round = ?";
    //     }
    //     $stmt = $this->conn->prepare($query);
    //     if ($round !== null) {
    //         $stmt->bind_param("iiis", $team_id, $team_id, $season_id, $round);
    //     } else {
    //         $stmt->bind_param("iii", $team_id, $team_id, $season_id);
    //     }
    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     $matches = [];
    //     while ($row = $result->fetch_assoc()) {
    //         $matches[] = $row;
    //     }
    //     $stmt->close();
    //     return $matches;
    // }

    public function getEditLogs($match_id) {
        $stmt = $this->conn->prepare("
            SELECT reason, edited_by, edit_time
            FROM EditLogs
            WHERE match_id = ?
            ORDER BY edit_time DESC
        ");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        return $logs;
    }

    // public function updateScores($matches) {
    //     $response = ['success' => false];
    //     $this->conn->begin_transaction();
    //     try {
    //         foreach ($matches as $match) {
    //             $match_id = filter_var($match['match_id'], FILTER_VALIDATE_INT);
    //             $home_team_score = filter_var($match['home_team_score'], FILTER_VALIDATE_INT);
    //             $away_team_score = filter_var($match['away_team_score'], FILTER_VALIDATE_INT);
    //             $reason = isset($match['reason']) ? filter_var($match['reason'], FILTER_SANITIZE_STRING) : '';

    //             if ($match_id === false || $home_team_score === false || $away_team_score === false) {
    //                 throw new Exception('Dữ liệu không hợp lệ');
    //             }

    //             // Cập nhật tỉ số trận đấu
    //             $stmt = $this->conn->prepare("
    //                 UPDATE Matches
    //                 SET home_team_score = ?, away_team_score = ?
    //                 WHERE match_id = ?
    //             ");
    //             $stmt->bind_param("iii", $home_team_score, $away_team_score, $match_id);
    //             $stmt->execute();
    //             $stmt->close();

    //             // Ghi log chỉnh sửa nếu có lý do
    //             if (!empty($reason)) {
    //                 $edited_by = 'admin'; // Thay bằng thông tin người dùng thực tế
    //                 $stmt = $this->conn->prepare("
    //                     INSERT INTO EditLogs (match_id, reason, edited_by, edit_time)
    //                     VALUES (?, ?, ?, NOW())
    //                 ");
    //                 $stmt->bind_param("iss", $match_id, $reason, $edited_by);
    //                 $stmt->execute();
    //                 $stmt->close();
    //             }
    //         }
    //         $this->conn->commit();
    //         $response['success'] = true;
    //     } catch (Exception $e) {
    //         $this->conn->rollback();
    //         $response['error'] = $e->getMessage();
    //     }
    //     return $response;
    // }

    public function updateScores($matches)
    {
        $response = ['success' => false, 'error' => ''];
        if (empty($matches)) {
            $response['error'] = 'Không có dữ liệu trận đấu để cập nhật';
            return $response;
        }

        // Kiểm tra engine của bảng Matches và EditLogs
        $result = $this->conn->query("SHOW TABLE STATUS WHERE Name IN ('Matches', 'EditLogs')");
        while ($row = $result->fetch_assoc()) {
            if ($row['Engine'] !== 'InnoDB') {
                $response['error'] = "Bảng {$row['Name']} không sử dụng engine InnoDB";
                return $response;
            }
        }

        $this->conn->begin_transaction();
        try {
            foreach ($matches as $index => $match) {
                $match_id = filter_var($match['match_id'], FILTER_VALIDATE_INT);
                $home_team_score = filter_var($match['home_team_score'], FILTER_VALIDATE_INT);
                $away_team_score = filter_var($match['away_team_score'], FILTER_VALIDATE_INT);
                $reason = isset($match['reason']) ? htmlspecialchars(trim($match['reason'])) : '';

                if ($match_id === false || $home_team_score === false || $away_team_score === false || $home_team_score < 0 || $away_team_score < 0) {
                    throw new Exception("Dữ liệu không hợp lệ tại trận $index: match_id=$match_id, home_team_score=$home_team_score, away_team_score=$away_team_score");
                }

                // Lấy home_team_id, away_team_id, season_id từ bảng Matches
                $stmt = $this->conn->prepare("SELECT home_team_id, away_team_id, season_id FROM Matches WHERE match_id = ?");
                $stmt->bind_param("i", $match_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $match_data = $result->fetch_assoc();
                $stmt->close();

                if (!$match_data) {
                    throw new Exception("Không tìm thấy trận đấu với match_id=$match_id");
                }

                // Cập nhật tỉ số trận đấu
                $stmt = $this->conn->prepare("
                UPDATE Matches
                SET home_team_score = ?, away_team_score = ?
                WHERE match_id = ?
            ");
                if (!$stmt) {
                    throw new Exception('Lỗi chuẩn bị truy vấn cập nhật tỉ số');
                }
                $stmt->bind_param("iii", $home_team_score, $away_team_score, $match_id);
                if (!$stmt->execute()) {
                    throw new Exception('Lỗi thực thi cập nhật tỉ số');
                }
                $stmt->close();

                // Ghi log chỉnh sửa nếu có lý do
                if (!empty($reason)) {
                    $edited_by = 'admin'; // Thay bằng thông tin người dùng thực tế
                    $team_id = $match_data['home_team_id']; // Có thể cần logic để chọn team_id phù hợp
                    $season_id = $match_data['season_id'];
                    $stmt = $this->conn->prepare("
                    INSERT INTO EditLogs (match_id, team_id, season_id, reason, edited_by, edit_time)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                    if (!$stmt) {
                        throw new Exception('Lỗi chuẩn bị truy vấn ghi log');
                    }
                    $stmt->bind_param("iiiss", $match_id, $team_id, $season_id, $reason, $edited_by);
                    if (!$stmt->execute()) {
                        throw new Exception('Lỗi thực thi ghi log');
                    }
                    $stmt->close();
                }
            }
            $this->conn->commit();
            $response['success'] = true;
        } catch (Exception $e) {
            $this->conn->rollback();
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    //     public function handleRequest() {
    //         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //             $action = isset($_POST['action']) ? $_POST['action'] : '';
    //             header('Content-Type: application/json');

    //             if ($action === 'get_team_matches') {
    //                 $team_id = filter_var($_POST['team_id'], FILTER_VALIDATE_INT);
    //                 $round = isset($_POST['round']) && $_POST['round'] !== '' ? filter_var($_POST['round'], FILTER_SANITIZE_STRING) : null;
    //                 $season_id = 1; // Giả định season_id
    //                 if ($team_id === false) {
    //                     echo json_encode([]);
    //                     exit;
    //                 }
    //                 $matches = $this->getMatchDetailsByTeam($team_id, $season_id, $round);
    //                 echo json_encode($matches);
    //                 exit;
    //             } elseif ($action === 'update_scores') {
    //                 $matches = isset($_POST['matches']) ? $_POST['matches'] : [];
    //                 $response = $this->updateScores($matches);
    //                 echo json_encode($response);
    //                 exit;
    //             }
    //         }
    //     }
    // }

    public function getMatchDetailsByTeam($team_id, $season_id, $round = null)
    {
        $query = "
        SELECT m.match_id, m.home_team_id, m.away_team_id, m.home_team_score, m.away_team_score,
               h.name AS home_team_name, a.name AS away_team_name
        FROM Matches m
        JOIN Teams h ON m.home_team_id = h.team_id
        JOIN Teams a ON m.away_team_id = a.team_id
        WHERE (m.home_team_id = ? OR m.away_team_id = ?) AND m.season_id = ? AND m.status = 'Completed'
    ";
        if ($round !== null) {
            $query .= " AND m.round = ?";
        }
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Lỗi chuẩn bị truy vấn SQL: " . $this->conn->error);
            return ['error' => 'Lỗi chuẩn bị truy vấn SQL: ' . $this->conn->error];
        }
        if ($round !== null) {
            $stmt->bind_param("iiis", $team_id, $team_id, $season_id, $round);
        } else {
            $stmt->bind_param("iii", $team_id, $team_id, $season_id);
        }
        if (!$stmt->execute()) {
            error_log("Lỗi thực thi truy vấn SQL: " . $stmt->error);
            return ['error' => 'Lỗi thực thi truy vấn SQL: ' . $stmt->error];
        }
        $result = $stmt->get_result();
        $matches = [];
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
        $stmt->close();
        return $matches;
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            header('Content-Type: application/json');

            if ($action === 'get_team_matches') {
                $team_id = filter_var($_POST['team_id'], FILTER_VALIDATE_INT);
                $round = isset($_POST['round']) && $_POST['round'] !== '' ? htmlspecialchars(trim($_POST['round'])) : null;
                $season_id = 1;
                if ($team_id === false) {
                    echo json_encode(['error' => 'ID đội bóng không hợp lệ']);
                    exit;
                }
                $matches = $this->getMatchDetailsByTeam($team_id, $season_id, $round);
                if (isset($matches['error'])) {
                    echo json_encode($matches);
                    exit;
                }
                echo json_encode($matches);
                exit;
            } elseif ($action === 'update_scores') {
                $matches = isset($_POST['matches']) ? $_POST['matches'] : [];
                $response = $this->updateScores($matches);
                echo json_encode($response);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

$controller = new RankingController();
$controller->handleRequest();
