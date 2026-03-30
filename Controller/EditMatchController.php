<?php
require_once __DIR__ . '/../includes/config.php';

class EditMatchController {
    private $conn;

    public function __construct($conn) {
        if (!$conn || mysqli_connect_errno()) {
            throw new Exception('Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error());
        }
        $this->conn = $conn;
    }

    public function getMatchDetails($match_id) {
        $match_id = (int)$match_id;
        if ($match_id <= 0) {
            return ['success' => false, 'error' => 'ID trận đấu không hợp lệ'];
        }

        $match = $this->getMatchBasicInfo($match_id);
        if (!$match) {
            return ['success' => false, 'error' => 'Không tìm thấy trận đấu'];
        }

        $events = $this->getMatchEvents($match_id);
        $players = $this->getPlayersForMatch($match['home_team_id'], $match['away_team_id']);

        return [
            'success' => true,
            'data' => [
                'match' => $match,
                'events' => $events,
                'players' => $players
            ]
        ];
    }

    private function getMatchBasicInfo($match_id) {
        $query = "SELECT m.match_id, m.match_date, m.home_team_score, m.away_team_score, m.status,
                         t1.name as home_team, t1.team_id as home_team_id, t1.logo_url as home_team_logo,
                         t2.name as away_team, t2.team_id as away_team_id, t2.logo_url as away_team_logo,
                         s.name as season_name, s.season_id
                  FROM Matches m
                  JOIN Teams t1 ON m.home_team_id = t1.team_id
                  JOIN Teams t2 ON m.away_team_id = t2.team_id
                  JOIN Seasons s ON m.season_id = s.season_id
                  WHERE m.match_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $match_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $match = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $match;
    }

    private function getMatchEvents($match_id) {
        $query = "SELECT me.event_id, me.event_type, me.minute, me.is_home, me.note,
                         p.player_id, p.name as player_name, p.team_id,
                         t.name as team_name
                  FROM MatchEvents me
                  LEFT JOIN Players p ON me.player_id = p.player_id
                  LEFT JOIN Teams t ON me.team_id = t.team_id
                  WHERE me.match_id = ?
                  ORDER BY me.minute ASC";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $match_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $events;
    }

    private function getPlayersForMatch($home_team_id, $away_team_id) {
        $query = "SELECT p.player_id, p.name, t.name as team_name, t.team_id
                  FROM Players p
                  JOIN Teams t ON p.team_id = t.team_id
                  WHERE p.team_id IN (?, ?)
                  ORDER BY t.team_id, p.name";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $home_team_id, $away_team_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $players = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $players[$row['team_name']][] = $row;
        }
        mysqli_stmt_close($stmt);
        return $players;
    }

    public function saveEvent($match_id, $team_id, $player_id, $event_type, $minute, $is_home, $event_id = 0, $note = '') {
        $validation = $this->validateEventData($match_id, $team_id, $player_id, $event_type, $minute, $is_home);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        $event_data = [
            'match_id' => $match_id,
            'team_id' => $team_id,
            'player_id' => $player_id,
            'event_type' => $event_type,
            'minute' => $minute,
            'is_home' => $is_home,
            'note' => $note
        ];

        mysqli_begin_transaction($this->conn);
        try {
            if ($event_id > 0) {
                $this->updateEvent($event_id, $event_data);
                $message = 'Cập nhật sự kiện thành công';
            } else {
                $this->addEvent($event_data);
                $message = 'Thêm sự kiện thành công';
            }

            // Cập nhật tỉ số nếu sự kiện liên quan đến bàn thắng
            if (in_array($event_type, ['goal', 'penalty_scored', 'own_goal'])) {
                $this->updateMatchScore($match_id);
            }

            // Cập nhật bảng xếp hạng nếu trận đấu đã hoàn thành
            $match_info = $this->getMatchBasicInfo($match_id);
            if ($match_info['status'] === 'Completed') {
                $this->updateStandings($match_id);
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => $message, 'match_id' => $match_id];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            error_log("Lỗi saveEvent (match_id: $match_id): " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lưu sự kiện: ' . $e->getMessage()];
        }
    }

    private function validateEventData($match_id, $team_id, $player_id, $event_type, $minute, $is_home) {
        if (!is_numeric($minute) || $minute < 0 || $minute > 120) {
            return ['valid' => false, 'message' => 'Phút phải là số từ 0 đến 120'];
        }

        $valid_event_types = ['goal', 'penalty_scored', 'penalty_missed', 'own_goal', 'yellow_card', 'red_card', 'assist', 'save'];
        if (!in_array($event_type, $valid_event_types)) {
            return ['valid' => false, 'message' => 'Loại sự kiện không hợp lệ'];
        }

        $match = $this->getMatchBasicInfo($match_id);
        if (!$match) {
            return ['valid' => false, 'message' => 'Không tìm thấy trận đấu'];
        }

        // Kiểm tra chặt chẽ mối quan hệ giữa team_id và is_home
        if ($is_home && $team_id != $match['home_team_id']) {
            return ['valid' => false, 'message' => 'Đội được chọn không phải là đội nhà'];
        }
        if (!$is_home && $team_id != $match['away_team_id']) {
            return ['valid' => false, 'message' => 'Đội được chọn không phải là đội khách'];
        }

        $requires_player = ['goal', 'penalty_scored', 'penalty_missed', 'own_goal', 'yellow_card', 'red_card', 'assist', 'save'];
        if (in_array($event_type, $requires_player) && !$player_id) {
            return ['valid' => false, 'message' => 'Cầu thủ là bắt buộc cho loại sự kiện này'];
        }

        if ($player_id && !$this->isPlayerInTeam($player_id, $team_id)) {
            return ['valid' => false, 'message' => 'Cầu thủ không thuộc đội đã chọn'];
        }

        return ['valid' => true];
    }

    private function isPlayerInTeam($player_id, $team_id) {
        $query = "SELECT COUNT(*) as count FROM Players WHERE player_id = ? AND team_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $player_id, $team_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        return $count > 0;
    }

    private function addEvent($event_data) {
        $query = "INSERT INTO MatchEvents (match_id, team_id, player_id, event_type, minute, is_home, note) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        $player_id = $event_data['player_id'] ?: null;
        mysqli_stmt_bind_param($stmt, 'iiissis', 
            $event_data['match_id'], 
            $event_data['team_id'], 
            $player_id, 
            $event_data['event_type'], 
            $event_data['minute'], 
            $event_data['is_home'],
            $event_data['note']
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi thêm sự kiện: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }

    private function updateEvent($event_id, $event_data) {
        $query = "UPDATE MatchEvents 
                  SET team_id = ?, player_id = ?, event_type = ?, minute = ?, is_home = ?, note = ?
                  WHERE event_id = ? AND match_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        $player_id = $event_data['player_id'] ?: null;
        mysqli_stmt_bind_param($stmt, 'iisisiis', 
            $event_data['team_id'], 
            $player_id, 
            $event_data['event_type'], 
            $event_data['minute'], 
            $event_data['is_home'], 
            $event_data['note'],
            $event_id, 
            $event_data['match_id']
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật sự kiện: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }

    public function deleteEvent($event_id) {
        header('Content-Type: application/json');

        if (!is_numeric($event_id) || $event_id <= 0) {
            return ['success' => false, 'message' => 'ID sự kiện không hợp lệ'];
        }

        mysqli_begin_transaction($this->conn);
        try {
            // Lấy thông tin sự kiện trước khi xóa
            $query = "SELECT match_id, event_type FROM MatchEvents WHERE event_id = ?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $event_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) === 0) {
                mysqli_stmt_close($stmt);
                return ['success' => false, 'message' => 'Sự kiện không tồn tại'];
            }
            $row = mysqli_fetch_assoc($result);
            $match_id = $row['match_id'];
            $event_type = $row['event_type'];
            mysqli_stmt_close($stmt);

            // Xóa sự kiện
            $query = "DELETE FROM MatchEvents WHERE event_id = ?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $event_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Lỗi xóa sự kiện: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);

            // Cập nhật tỉ số nếu sự kiện liên quan đến bàn thắng
            if (in_array($event_type, ['goal', 'penalty_scored', 'own_goal'])) {
                $this->updateMatchScore($match_id);
            }

            // Cập nhật bảng xếp hạng nếu trận đấu đã hoàn thành
            $match_info = $this->getMatchBasicInfo($match_id);
            if ($match_info && $match_info['status'] === 'Completed') {
                $this->updateStandings($match_id);
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Xóa sự kiện thành công', 'match_id' => $match_id];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Lỗi xóa sự kiện: ' . $e->getMessage()];
        }
    }
    public function deleteMatch($match_id) {
    mysqli_begin_transaction($this->conn);
    try {
        $match = $this->getMatchBasicInfo($match_id);
        if (!$match) {
            return ['success' => false, 'message' => 'Không tìm thấy trận đấu'];
        }

        // Xóa tất cả sự kiện liên quan đến trận đấu trước
        $query = "DELETE FROM MatchEvents WHERE match_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $match_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi xóa sự kiện của trận đấu: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        // Xóa trận đấu
        $query = "DELETE FROM Matches WHERE match_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $match_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi xóa trận đấu: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        // Nếu trận đấu đã hoàn thành, cần cập nhật lại bảng xếp hạng
        if ($match['status'] === 'Completed') {
            $this->updateStandingsForDeletedMatch($match);
        }

        mysqli_commit($this->conn);
        return ['success' => true, 'message' => 'Xóa trận đấu thành công'];
    } catch (Exception $e) {
        mysqli_rollback($this->conn);
        return ['success' => false, 'message' => 'Lỗi xóa trận đấu: ' . $e->getMessage()];
    }
}

    private function updateStandingsForDeletedMatch($match) {
        $team1_id = $match['home_team_id'];
        $team2_id = $match['away_team_id'];
        $score1 = $match['home_team_score'];
        $score2 = $match['away_team_score'];
        $season_id = $match['season_id'];

        // Tính toán điểm cần trừ đi
        $points1 = $points2 = 0;
        $won1 = $won2 = $drawn1 = $drawn2 = $lost1 = $lost2 = 0;

        if ($score1 > $score2) {
            $points1 = -3; $won1 = -1; $lost2 = -1;
        } elseif ($score1 < $score2) {
            $points2 = -3; $won2 = -1; $lost1 = -1;
        } else {
            $points1 = $points2 = -1; $drawn1 = $drawn2 = -1;
        }

        $query = "UPDATE Standings SET 
                points = points + ?, 
                matches_played = matches_played - 1, 
                wins = wins + ?, 
                draws = draws + ?, 
                losses = losses + ?,
                goals_for = goals_for - ?, 
                goals_against = goals_against - ?
                WHERE season_id = ? AND team_id = ?";

        // Cập nhật cho đội nhà
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'iiiiiiii', 
            $points1, $won1, $drawn1, $lost1, $score1, $score2, $season_id, $team1_id
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật bảng xếp hạng cho đội nhà: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        // Cập nhật cho đội khách
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'iiiiiiii', 
            $points2, $won2, $drawn2, $lost2, $score2, $score1, $season_id, $team2_id
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật bảng xếp hạng cho đội khách: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }

    private function updateMatchScore($match_id) {
        $match = $this->getMatchBasicInfo($match_id);
        if (!$match) return;

        $events = $this->getMatchEvents($match_id);
        $home_score = 0;
        $away_score = 0;

        foreach ($events as $event) {
            // Xác định đội dựa trên team_id thay vì chỉ is_home
            $is_home_team = ($event['team_id'] == $match['home_team_id']);

            switch ($event['event_type']) {
                case 'goal':
                case 'penalty_scored':
                    if ($is_home_team) {
                        $home_score++;
                    } else {
                        $away_score++;
                    }
                    break;
                case 'own_goal':
                    if ($is_home_team) {
                        $away_score++;
                    } else {
                        $home_score++;
                    }
                    break;
            }
        }

        // Giữ trạng thái Completed nếu trận đấu đã hoàn thành
        $status = $match['status'] === 'Completed' ? 'Completed' : 'Scheduled';
        
        $query = "UPDATE Matches 
                  SET home_team_score = ?, away_team_score = ?, status = ?
                  WHERE match_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'iisi', $home_score, $away_score, $status, $match_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật tỉ số: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }

    private function updateStandings($match_id) {
        $match = $this->getMatchBasicInfo($match_id);
        if (!$match) return;

        $team1_id = $match['home_team_id'];
        $team2_id = $match['away_team_id'];
        $score1 = $match['home_team_score'];
        $score2 = $match['away_team_score'];
        $season_id = $match['season_id'];

        $points1 = $points2 = 0;
        $won1 = $won2 = $drawn1 = $drawn2 = $lost1 = $lost2 = 0;

        if ($score1 > $score2) {
            $points1 = 3; $won1 = 1; $lost2 = 1;
        } elseif ($score1 < $score2) {
            $points2 = 3; $won2 = 1; $lost1 = 1;
        } else {
            $points1 = $points2 = 1; $drawn1 = $drawn2 = 1;
        }

        $query = "INSERT INTO Standings (season_id, team_id, points, matches_played, wins, draws, losses, goals_for, goals_against)
                  VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  points = points + ?, matches_played = matches_played + 1, 
                  wins = wins + ?, draws = draws + ?, losses = losses + ?,
                  goals_for = goals_for + ?, goals_against = goals_against + ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'iiiiiiiiiiiiii', 
            $season_id, $team1_id, $points1, $won1, $drawn1, $lost1, $score1, $score2,
            $points1, $won1, $drawn1, $lost1, $score1, $score2
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật bảng xếp hạng: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'iiiiiiiiiiiiii', 
            $season_id, $team2_id, $points2, $won2, $drawn2, $lost2, $score2, $score1,
            $points2, $won2, $drawn2, $lost2, $score2, $score1
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật bảng xếp hạng: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }
}

// Xử lý yêu cầu AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (!isset($conn)) {
            throw new Exception('Kết nối cơ sở dữ liệu chưa được thiết lập');
        }

        $controller = new EditMatchController($conn);

        if (!isset($_POST['action'])) {
            throw new Exception('Không có hành động được chỉ định');
        }

        $response = null;
        switch ($_POST['action']) {
            case 'add':
                if (!isset($_POST['match_id'], $_POST['team_id'], $_POST['event_type'], $_POST['minute'], $_POST['is_home'])) {
                    throw new Exception('Thiếu các trường bắt buộc');
                }
                $match_id = (int)$_POST['match_id'];
                $team_id = (int)$_POST['team_id'];
                $player_id = !empty($_POST['player_id']) ? (int)$_POST['player_id'] : null;
                $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
                $minute = (int)$_POST['minute'];
                $is_home = (int)$_POST['is_home'];
                $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';

                $response = $controller->saveEvent($match_id, $team_id, $player_id, $event_type, $minute, $is_home, 0, $note);
                break;

            case 'edit':
                if (!isset($_POST['event_id'], $_POST['match_id'], $_POST['team_id'], $_POST['event_type'], $_POST['minute'], $_POST['is_home'])) {
                    throw new Exception('Thiếu các trường bắt buộc');
                }
                $event_id = (int)$_POST['event_id'];
                $match_id = (int)$_POST['match_id'];
                $team_id = (int)$_POST['team_id'];
                $player_id = !empty($_POST['player_id']) ? (int)$_POST['player_id'] : null;
                $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
                $minute = (int)$_POST['minute'];
                $is_home = (int)$_POST['is_home'];
                $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';

                $response = $controller->saveEvent($match_id, $team_id, $player_id, $event_type, $minute, $is_home, $event_id, $note);
                break;

            case 'delete':
                if (!isset($_POST['event_id']) || (int)$_POST['event_id'] <= 0) {
                    throw new Exception('ID sự kiện không hợp lệ');
                }
                $response = $controller->deleteEvent((int)$_POST['event_id']);
                break;

            case 'get_events':
                if (!isset($_POST['match_id']) || (int)$_POST['match_id'] <= 0) {
                    throw new Exception('ID trận đấu không hợp lệ');
                }
                $response = $controller->getMatchDetails((int)$_POST['match_id']);
                if ($response['success']) {
                    $response = ['success' => true, 'events' => $response['data']['events']];
                }
                break;

            case 'get_match_details':
                if (!isset($_POST['match_id']) || (int)$_POST['match_id'] <= 0) {
                    throw new Exception('ID trận đấu không hợp lệ');
                }
                $response = $controller->getMatchDetails((int)$_POST['match_id']);
                break;

            case 'delete_match':
                if (!isset($_POST['match_id']) || (int)$_POST['match_id'] <= 0) {
                    throw new Exception('ID trận đấu không hợp lệ');
                }
                $response = $controller->deleteMatch((int)$_POST['match_id']);
                break;

            default:
                throw new Exception('Hành động không hợp lệ');
        }

        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        exit;
    }
    
}