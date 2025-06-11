<?php
require_once '../includes/config.php';


function getTeams($conn, $season_id = null, $team_id = null) {
    $sql = "SELECT t.team_id, t.name, t.logo_url, t.founded_year,
                   s.name AS stadium_name, t.stadium_id,
                   m.name AS coach_name,
                   (SELECT COUNT(*) FROM matches mt WHERE mt.home_team_id = t.team_id OR mt.away_team_id = t.team_id) AS match_count
            FROM teams t
            LEFT JOIN stadiums s ON t.stadium_id = s.stadium_id
            LEFT JOIN managers m ON m.team_id = t.team_id
            WHERE 1=1";
    $params = [];
    $types = "";


    if ($season_id) {
        $sql .= " AND EXISTS (SELECT 1 FROM matches mt WHERE mt.season_id = ? AND (mt.home_team_id = t.team_id OR mt.away_team_id = t.team_id))";
        $params[] = $season_id;
        $types .= "i";
    }
    if ($team_id) {
        $sql .= " AND t.team_id = ?";
        $params[] = $team_id;
        $types .= "i";
    }


    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $teams = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $teams;
}


function addTeam($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_team') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $stadium_id = (int)$_POST['stadium_id'];
        $founded_year = (int)$_POST['founded_year'];
        $coach_name = isset($_POST['coach_name']) && $_POST['coach_name'] !== '' ? mysqli_real_escape_string($conn, $_POST['coach_name']) : null;


        // Kiểm tra trùng tên
        $check_sql = "SELECT COUNT(*) FROM teams WHERE name = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $name);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_bind_result($check_stmt, $count);
        mysqli_stmt_fetch($check_stmt);
        mysqli_stmt_close($check_stmt);


        if ($count > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Tên đội bóng đã tồn tại!']);
            exit;
        }


        // Kiểm tra sân vận động tồn tại
        $check_stadium_sql = "SELECT COUNT(*) FROM stadiums WHERE stadium_id = ?";
        $check_stadium_stmt = mysqli_prepare($conn, $check_stadium_sql);
        mysqli_stmt_bind_param($check_stadium_stmt, "i", $stadium_id);
        mysqli_stmt_execute($check_stadium_stmt);
        mysqli_stmt_bind_result($check_stadium_stmt, $stadium_count);
        mysqli_stmt_fetch($check_stadium_stmt);
        mysqli_stmt_close($check_stadium_stmt);


        if ($stadium_count == 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Sân vận động không tồn tại!']);
            exit;
        }


        // Kiểm tra xem sân vận động đã được gán cho CLB nào khác chưa
        $check_stadium_usage_sql = "SELECT COUNT(*) FROM teams WHERE stadium_id = ?";
        $check_stadium_usage_stmt = mysqli_prepare($conn, $check_stadium_usage_sql);
        mysqli_stmt_bind_param($check_stadium_usage_stmt, "i", $stadium_id);
        mysqli_stmt_execute($check_stadium_usage_stmt);
        mysqli_stmt_bind_result($check_stadium_usage_stmt, $stadium_usage_count);
        mysqli_stmt_fetch($check_stadium_usage_stmt);
        mysqli_stmt_close($check_stadium_usage_stmt);


        if ($stadium_usage_count > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Sân vận động này đã được gán cho một CLB khác!']);
            exit;
        }


        // Xử lý upload logo
        $logo_url = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/teams/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $target_file = $target_dir . basename($_FILES['logo']['name']);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $new_file_name = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_file_name;


            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_url = "uploads/teams/" . $new_file_name;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Không thể upload logo!']);
                exit;
            }
        }


        // Thêm đội bóng mới
        $sql = "INSERT INTO teams (name, stadium_id, founded_year, logo_url) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "siis", $name, $stadium_id, $founded_year, $logo_url);
        $result = mysqli_stmt_execute($stmt);


        if ($result) {
            $team_id = mysqli_insert_id($conn);
            if ($coach_name) {
                $insert_manager_sql = "INSERT INTO managers (name, team_id) VALUES (?, ?)";
                $insert_manager_stmt = mysqli_prepare($conn, $insert_manager_sql);
                mysqli_stmt_bind_param($insert_manager_stmt, "si", $coach_name, $team_id);
                mysqli_stmt_execute($insert_manager_stmt);
                mysqli_stmt_close($insert_manager_stmt);
            }
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Thêm đội bóng thành công!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Thêm đội bóng thất bại: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }
}


function updateTeam($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_team') {
        $team_id = (int)$_POST['team_id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $stadium_id = (int)$_POST['stadium_id'];
        $founded_year = (int)$_POST['founded_year'];
        $coach_name = isset($_POST['coach_name']) && $_POST['coach_name'] !== '' ? mysqli_real_escape_string($conn, $_POST['coach_name']) : null;
        $current_logo = isset($_POST['current_logo']) ? $_POST['current_logo'] : null;


        // Kiểm tra sân vận động tồn tại
        $check_stadium_sql = "SELECT COUNT(*) FROM stadiums WHERE stadium_id = ?";
        $check_stadium_stmt = mysqli_prepare($conn, $check_stadium_sql);
        mysqli_stmt_bind_param($check_stadium_stmt, "i", $stadium_id);
        mysqli_stmt_execute($check_stadium_stmt);
        mysqli_stmt_bind_result($check_stadium_stmt, $stadium_count);
        mysqli_stmt_fetch($check_stadium_stmt);
        mysqli_stmt_close($check_stadium_stmt);


        if ($stadium_count == 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Sân vận động không tồn tại!']);
            exit;
        }


        // Lấy sân vận động hiện tại của CLB
        $current_stadium_sql = "SELECT stadium_id FROM teams WHERE team_id = ?";
        $current_stadium_stmt = mysqli_prepare($conn, $current_stadium_sql);
        mysqli_stmt_bind_param($current_stadium_stmt, "i", $team_id);
        mysqli_stmt_execute($current_stadium_stmt);
        $current_stadium_result = mysqli_stmt_get_result($current_stadium_stmt);
        $current_stadium = mysqli_fetch_assoc($current_stadium_result)['stadium_id'] ?? null;
        mysqli_stmt_close($current_stadium_stmt);


        // Kiểm tra xem sân vận động mới có đang được CLB khác sử dụng không
        if ($stadium_id !== $current_stadium) {
            $check_stadium_usage_sql = "SELECT COUNT(*) FROM teams WHERE stadium_id = ? AND team_id != ?";
            $check_stadium_usage_stmt = mysqli_prepare($conn, $check_stadium_usage_sql);
            mysqli_stmt_bind_param($check_stadium_usage_stmt, "ii", $stadium_id, $team_id);
            mysqli_stmt_execute($check_stadium_usage_stmt);
            mysqli_stmt_bind_result($check_stadium_usage_stmt, $stadium_usage_count);
            mysqli_stmt_fetch($check_stadium_usage_stmt);
            mysqli_stmt_close($check_stadium_usage_stmt);


            if ($stadium_usage_count > 0) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Sân vận động này đã được gán cho một CLB khác!']);
                exit;
            }
        }


        // Xử lý upload logo mới (nếu có)
        $logo_url = $current_logo;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/teams/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $target_file = $target_dir . basename($_FILES['logo']['name']);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $new_file_name = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_file_name;


            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_url = "uploads/teams/" . $new_file_name;
                if ($current_logo && file_exists("../" . $current_logo)) {
                    unlink("../" . $current_logo);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Không thể upload logo mới!']);
                exit;
            }
        }


        // Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
        mysqli_begin_transaction($conn);


        try {
            // Cập nhật đội bóng
            $sql = "UPDATE teams SET name = ?, stadium_id = ?, founded_year = ?, logo_url = ? WHERE team_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siisi", $name, $stadium_id, $founded_year, $logo_url, $team_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Cập nhật đội bóng thất bại: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);


            // Xử lý huấn luyện viên
            $check_manager_sql = "SELECT manager_id FROM managers WHERE team_id = ? LIMIT 1";
            $check_manager_stmt = mysqli_prepare($conn, $check_manager_sql);
            mysqli_stmt_bind_param($check_manager_stmt, "i", $team_id);
            mysqli_stmt_execute($check_manager_stmt);
            mysqli_stmt_store_result($check_manager_stmt);
            $has_manager = mysqli_stmt_num_rows($check_manager_stmt) > 0;
            $manager_id = null;
            if ($has_manager) {
                mysqli_stmt_bind_result($check_manager_stmt, $manager_id);
                mysqli_stmt_fetch($check_manager_stmt);
            }
            mysqli_stmt_close($check_manager_stmt);


            if ($coach_name) {
                if ($has_manager) {
                    $update_manager_sql = "UPDATE managers SET name = ? WHERE manager_id = ?";
                    $update_manager_stmt = mysqli_prepare($conn, $update_manager_sql);
                    mysqli_stmt_bind_param($update_manager_stmt, "si", $coach_name, $manager_id);
                    if (!mysqli_stmt_execute($update_manager_stmt)) {
                        throw new Exception('Cập nhật HLV thất bại: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_close($update_manager_stmt);
                } else {
                    $insert_manager_sql = "INSERT INTO managers (name, team_id) VALUES (?, ?)";
                    $insert_manager_stmt = mysqli_prepare($conn, $insert_manager_sql);
                    mysqli_stmt_bind_param($insert_manager_stmt, "si", $coach_name, $team_id);
                    if (!mysqli_stmt_execute($insert_manager_stmt)) {
                        throw new Exception('Thêm HLV thất bại: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_close($insert_manager_stmt);
                }
            } else {
                if ($has_manager) {
                    $delete_manager_sql = "DELETE FROM managers WHERE manager_id = ?";
                    $delete_manager_stmt = mysqli_prepare($conn, $delete_manager_sql);
                    mysqli_stmt_bind_param($delete_manager_stmt, "i", $manager_id);
                    if (!mysqli_stmt_execute($delete_manager_stmt)) {
                        throw new Exception('Xóa HLV thất bại: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_close($delete_manager_stmt);
                }
            }


            // Commit transaction
            mysqli_commit($conn);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Cập nhật đội bóng thành công!']);
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            mysqli_rollback($conn);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}


function deleteTeam($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_team') {
        $team_id = (int)$_POST['team_id'];


        // Kiểm tra ràng buộc trước khi xóa
        $check_sql = "SELECT COUNT(*) FROM matches WHERE home_team_id = ? OR away_team_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $team_id, $team_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_bind_result($check_stmt, $count);
        mysqli_stmt_fetch($check_stmt);
        mysqli_stmt_close($check_stmt);


        if ($count > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Không thể xóa đội bóng vì có ràng buộc với lịch thi đấu!']);
            exit;
        }


        // Xóa team_id khỏi managers
        $update_manager_sql = "UPDATE managers SET team_id = NULL WHERE team_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_manager_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $team_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);


        // Lấy logo_url trước khi xóa
        $logo_sql = "SELECT logo_url FROM teams WHERE team_id = ?";
        $logo_stmt = mysqli_prepare($conn, $logo_sql);
        mysqli_stmt_bind_param($logo_stmt, "i", $team_id);
        mysqli_stmt_execute($logo_stmt);
        mysqli_stmt_bind_result($logo_stmt, $logo_url);
        mysqli_stmt_fetch($logo_stmt);
        mysqli_stmt_close($logo_stmt);


        // Xóa đội bóng
        $sql = "DELETE FROM teams WHERE team_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $team_id);
        $result = mysqli_stmt_execute($stmt);


        if ($result) {
            // Xóa logo nếu tồn tại
            if ($logo_url && file_exists("../" . $logo_url)) {
                unlink("../" . $logo_url);
            }
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Xóa đội bóng thành công!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Xóa đội bóng thất bại: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }
}


function checkTeamDelete($conn) {
    if (isset($_GET['action']) && $_GET['action'] === 'check_team_delete' && isset($_GET['team_id'])) {
        $team_id = (int)$_GET['team_id'];
        $sql = "SELECT COUNT(*) FROM matches WHERE home_team_id = ? OR away_team_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $team_id, $team_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);


        echo $count > 0 ? 'constrained' : 'safe';
        exit;
    }
}


function getStadiums($conn) {
    if (isset($_GET['action']) && $_GET['action'] === 'get_stadiums') {
        $sql = "SELECT stadium_id, name FROM stadiums ORDER BY name ASC";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        $stadiums = mysqli_fetch_all($result, MYSQLI_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($stadiums);
        exit;
    }
}


function getTeamsData($conn) {
    if (isset($_GET['action']) && $_GET['action'] === 'get_teams') {
        $season_id = isset($_GET['season_id']) ? (int)$_GET['season_id'] : null;
        $team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
        $teams = getTeams($conn, $season_id, $team_id);
        header('Content-Type: application/json');
        echo json_encode($teams);
        exit;
    }
}


// Xử lý hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_team':
                addTeam($conn);
                break;
            case 'update_team':
                updateTeam($conn);
                break;
            case 'delete_team':
                deleteTeam($conn);
                break;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'check_team_delete':
                checkTeamDelete($conn);
                break;
            case 'get_stadiums':
                getStadiums($conn);
                break;
            case 'get_teams':
                getTeamsData($conn);
                break;
        }
    }
}
?>





