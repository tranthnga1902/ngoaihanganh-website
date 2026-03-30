<?php
require_once(__DIR__ . '/../includes/config.php');


// Hàm lấy danh sách trận đấu chưa hoàn thành
function getPendingMatches($conn) {
    $sql = "SELECT m.match_id, t1.name as team1_name, t2.name as team2_name, m.match_date, m.season_id, m.home_team_id, m.away_team_id
            FROM Matches m
            JOIN Teams t1 ON m.home_team_id = t1.team_id
            JOIN Teams t2 ON m.away_team_id = t2.team_id
            WHERE m.status = 'Scheduled'";
    $result = $conn->query($sql);
    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }
    return $matches;
}

// Hàm lấy danh sách cầu thủ
function getPlayers($conn) {
    $sql = "SELECT p.player_id, p.name, t.name as team_name, t.team_id
            FROM Players p
            JOIN Teams t ON p.team_id = t.team_id";
    $result = $conn->query($sql);
    $players = [];
    while ($row = $result->fetch_assoc()) {
        $players[$row['team_name']][] = $row;
    }
    return $players;
}

// Hàm lấy danh sách sự kiện của một trận đấu
function getMatchEvents($conn, $match_id) {
    $sql = "SELECT me.event_id, me.match_id, me.team_id, t.name as team_name, me.player_id, p.name as player_name, 
                   me.event_type, me.minute, me.is_home
            FROM MatchEvents me
            LEFT JOIN Teams t ON me.team_id = t.team_id
            LEFT JOIN Players p ON me.player_id = p.player_id
            WHERE me.match_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
    return $events;
}

// Hàm cập nhật bảng xếp hạng
function updateStandings($conn, $match_id) {
    $sql = "SELECT home_team_id, away_team_id, home_team_score, away_team_score, season_id 
            FROM Matches WHERE match_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $match = $result->fetch_assoc();

    if ($match) {
        $team1_id = $match['home_team_id'];
        $team2_id = $match['away_team_id'];
        $score1 = $match['home_team_score'];
        $score2 = $match['away_team_score'];
        $season_id = $match['season_id'];

        // Tính điểm
        $points1 = $points2 = 0;
        $won1 = $won2 = $drawn1 = $drawn2 = $lost1 = $lost2 = 0;

        if ($score1 > $score2) {
            $points1 = 3; $won1 = 1; $lost2 = 1;
        } elseif ($score1 < $score2) {
            $points2 = 3; $won2 = 1; $lost1 = 1;
        } else {
            $points1 = $points2 = 1; $drawn1 = $drawn2 = 1;
        }

        // Cập nhật standings cho team1
        $sql = "INSERT INTO Standings (season_id, team_id, points, matches_played, wins, draws, losses, goals_for, goals_against)
                VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                points = points + ?, matches_played = matches_played + 1, 
                wins = wins + ?, draws = draws + ?, losses = losses + ?,
                goals_for = goals_for + ?, goals_against = goals_against + ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiiiiiiiiiii", $season_id, $team1_id, $points1, $won1, $drawn1, $lost1, $score1, $score2,
                         $points1, $won1, $drawn1, $lost1, $score1, $score2);
        $stmt->execute();

        // Cập nhật standings cho team2
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiiiiiiiiiii", $season_id, $team2_id, $points2, $won2, $drawn2, $lost2, $score2, $score1,
                         $points2, $won2, $drawn2, $lost2, $score2, $score1);
        $stmt->execute();
    }
}

// Xử lý các thao tác
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm hoặc cập nhật kết quả trận đấu
    if (isset($_POST['action']) && $_POST['action'] === 'update_result') {
        $match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
        $home_team_score = isset($_POST['home_team_score']) ? (int)$_POST['home_team_score'] : 0;
        $away_team_score = isset($_POST['away_team_score']) ? (int)$_POST['away_team_score'] : 0;
        $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Scheduled';
        $events = isset($_POST['events']) ? $_POST['events'] : [];

        // Kiểm tra match_id hợp lệ
        if ($match_id <= 0) {
            header('Location: manageResults.php?error=invalid_match_id');
            exit;
        }

        // Lấy thông tin đội nhà và đội khách
        $match_query = "SELECT home_team_id, away_team_id FROM Matches WHERE match_id = ?";
        $stmt = $conn->prepare($match_query);
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $match_result = $stmt->get_result();
        $match = $match_result->fetch_assoc();
        $stmt->close();

        if (!$match) {
            header('Location: manageResults.php?error=invalid_match');
            exit;
        }

        $home_team_id = $match['home_team_id'];
        $away_team_id = $match['away_team_id'];

        // Tính tổng số bàn thắng từ các sự kiện
        $home_goals = 0;
        $away_goals = 0;
        $has_goal_event = false;

        foreach ($events as $event) {
            $team_id = isset($event['team_id']) ? (int)$event['team_id'] : 0;
            $event_type = isset($event['event_type']) ? $event['event_type'] : '';

            if ($event_type === 'goal' || $event_type === 'penalty_scored') {
                $has_goal_event = true;
                if ($team_id == $home_team_id) {
                    $home_goals++;
                } elseif ($team_id == $away_team_id) {
                    $away_goals++;
                }
            } elseif ($event_type === 'own_goal') {
                $has_goal_event = true;
                if ($team_id == $home_team_id) {
                    $away_goals++;
                } elseif ($team_id == $away_team_id) {
                    $home_goals++;
                }
            }
        }

        // Kiểm tra sự kiện bàn thắng bắt buộc nếu có bàn thắng
        if (($home_team_score > 0 || $away_team_score > 0) && !$has_goal_event) {
            header('Location: manageResults.php?error=no_goal_events');
            exit;
        }

        // Kiểm tra so khớp tỉ số với sự kiện
        if ($home_team_score > 0 || $away_team_score > 0) {
            if ($home_goals !== $home_team_score || $away_goals !== $away_team_score) {
                header('Location: manageResults.php?error=score_mismatch');
                exit;
            }
        }

        // Cập nhật trận đấu
        $update_match_query = "UPDATE Matches SET home_team_score = ?, away_team_score = ?, status = ? WHERE match_id = ?";
        $stmt = $conn->prepare($update_match_query);
        $stmt->bind_param("iisi", $home_team_score, $away_team_score, $status, $match_id);
        $update_result = $stmt->execute();
        $stmt->close();

        if ($update_result) {
            
            // Xóa các sự kiện cũ của trận đấu
            $delete_events_query = "DELETE FROM MatchEvents WHERE match_id = ?";
            $stmt = $conn->prepare($delete_events_query);
            $stmt->bind_param("i", $match_id);
            $stmt->execute();
            $stmt->close();

            // Lưu các sự kiện mới
            $insert_event_query = "INSERT INTO MatchEvents (match_id, team_id, player_id, event_type, minute, is_home) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_event_query);

            foreach ($events as $event) {
                $team_id = isset($event['team_id']) ? (int)$event['team_id'] : 0;
                $event_type = isset($event['event_type']) ? mysqli_real_escape_string($conn, $event['event_type']) : '';
                $player_id = isset($event['player_id']) && $event['player_id'] ? (int)$event['player_id'] : null;
                $minute = isset($event['minute']) ? (int)$event['minute'] : 0;
                $is_home = isset($event['is_home']) && $event['is_home'] === '1' ? 1 : 0;

                if ($team_id && $event_type) {
                    $stmt->bind_param("iiisii", $match_id, $team_id, $player_id, $event_type, $minute, $is_home);
                    $stmt->execute();
                }
            }
            $stmt->close();

            // Cập nhật bảng xếp hạng nếu trận đấu hoàn thành
            if ($status === 'Completed') {
                updateStandings($conn, $match_id);
            }

            header('Location: manageResults.php?success=updated');
            exit;
        } else {
            header('Location: manageResults.php?error=update_failed');
            exit;
        }
    }

    // Sửa sự kiện
    if (isset($_POST['action']) && $_POST['action'] === 'edit_event') {
        $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
        $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
        $event_type = isset($_POST['event_type']) ? mysqli_real_escape_string($conn, $_POST['event_type']) : '';
        $player_id = isset($_POST['player_id']) && $_POST['player_id'] ? (int)$_POST['player_id'] : null;
        $minute = isset($_POST['minute']) ? (int)$_POST['minute'] : 0;
        $is_home = isset($_POST['is_home']) && $_POST['is_home'] === '1' ? 1 : 0;

        if ($event_id <= 0 || $match_id <= 0 || $team_id <= 0 || !$event_type) {
            header('Location: manageResults.php?error=invalid_input');
            exit;
        }

        $sql = "UPDATE MatchEvents SET team_id = ?, player_id = ?, event_type = ?, minute = ?, is_home = ? WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisiii", $team_id, $player_id, $event_type, $minute, $is_home, $event_id);
        $success = $stmt->execute();
        $stmt->close();

        // Kiểm tra lại số bàn thắng sau khi sửa
        $events = getMatchEvents($conn, $match_id);
        $home_goals = 0;
        $away_goals = 0;
        $match_query = "SELECT home_team_id, away_team_id, home_team_score, away_team_score FROM Matches WHERE match_id = ?";
        $stmt = $conn->prepare($match_query);
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $match = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $home_team_id = $match['home_team_id'];
        $away_team_id = $match['away_team_id'];
        $home_team_score = $match['home_team_score'];
        $away_team_score = $match['away_team_score'];

        foreach ($events as $event) {
            if ($event['event_type'] === 'goal' || $event['event_type'] === 'penalty_scored') {
                if ($event['team_id'] == $home_team_id) {
                    $home_goals++;
                } elseif ($event['team_id'] == $away_team_id) {
                    $away_goals++;
                }
            } elseif ($event['event_type'] === 'own_goal') {
                if ($event['team_id'] == $home_team_id) {
                    $away_goals++;
                } elseif ($event['team_id'] == $away_team_id) {
                    $home_goals++;
                }
            }
        }

        if ($home_goals != $home_team_score || $away_goals != $away_team_score) {
            header('Location: manageResults.php?error=score_mismatch_after_edit');
            exit;
        }

        header('Location: manageResults.php?success=event_updated');
        exit;
    }

    // Xóa sự kiện
    if (isset($_POST['action']) && $_POST['action'] === 'delete_event') {
        $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;

        if ($event_id <= 0 || $match_id <= 0) {
            header('Location: manageResults.php?error=invalid_input');
            exit;
        }

        // Kiểm tra nếu sự kiện là bàn thắng
        $sql = "SELECT event_type, team_id FROM MatchEvents WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $sql = "DELETE FROM MatchEvents WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            // Kiểm tra lại số bàn thắng sau khi xóa
            $events = getMatchEvents($conn, $match_id);
            $home_goals = 0;
            $away_goals = 0;
            $match_query = "SELECT home_team_id, away_team_id, home_team_score, away_team_score FROM Matches WHERE match_id = ?";
            $stmt = $conn->prepare($match_query);
            $stmt->bind_param("i", $match_id);
            $stmt->execute();
            $match = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $home_team_id = $match['home_team_id'];
            $away_team_id = $match['away_team_id'];
            $home_team_score = $match['home_team_score'];
            $away_team_score = $match['away_team_score'];

            foreach ($events as $event) {
                if ($event['event_type'] === 'goal' || $event['event_type'] === 'penalty_scored') {
                    if ($event['team_id'] == $home_team_id) {
                        $home_goals++;
                    } elseif ($event['team_id'] == $away_team_id) {
                        $away_goals++;
                    }
                } elseif ($event['event_type'] === 'own_goal') {
                    if ($event['team_id'] == $home_team_id) {
                        $away_goals++;
                    } elseif ($event['team_id'] == $away_team_id) {
                        $home_goals++;
                    }
                }
            }

            if ($home_goals != $home_team_score || $away_goals != $away_team_score) {
                header('Location: manageResults.php?error=score_mismatch_after_delete');
                exit;
            }

            header('Location: manageResults.php?success=event_deleted');
            exit;
        } else {
            header('Location: manageResults.php?error=delete_failed');
            exit;
        }
    }
}

// Lấy dữ liệu cho view
$matches = getPendingMatches($conn);
$players = getPlayers($conn);
$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$events = $match_id ? getMatchEvents($conn, $match_id) : [];
?>