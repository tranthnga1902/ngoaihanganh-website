<?php

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Kiểm tra quyền admin
// function checkAdmin() {
//     if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//         redirect('../user/login.php');
//     }
// }

// Thêm cầu thủ
function addPlayer($conn, $data, $image) {
    //checkAdmin();

    $name = mysqli_real_escape_string($conn, $data['name']);
    $jersey_number = (int)$data['jersey_number'];
    $position = mysqli_real_escape_string($conn, $data['position']);
    $birth_date = $data['birth_date'];
    $nationality = mysqli_real_escape_string($conn, $data['nationality']);
    $height = (float)$data['height']; // Sử dụng float để hỗ trợ số thập phân
    $weight = (float)$data['weight']; // Sử dụng float để hỗ trợ số thập phân
    $team_id = (int)$data['team_id'];

    // Kiểm tra giá trị height và weight (phù hợp với DECIMAL(5,2))
    if ($height < 0 || $height > 999.99) {
        $_SESSION['error'] = "Chiều cao không hợp lệ! Phải từ 0 đến 999.99.";
        return false;
    }
    if ($weight < 0 || $weight > 999.99) {
        $_SESSION['error'] = "Cân nặng không hợp lệ! Phải từ 0 đến 999.99.";
        return false;
    }

    $photo_url = null;
    if ($image['error'] == 0) {
        $target_dir = "../uploads/players/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
            error_log("Thư mục $target_dir được tạo.");
        }

        $image_name = time() . "_" . basename($image['name']);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            if ($image['size'] <= 5000000) {
                if (move_uploaded_file($image['tmp_name'], $target_file)) {
                    $photo_url = "uploads/players/" . $image_name;
                    error_log("Upload thành công: $photo_url");
                } else {
                    $_SESSION['error'] = "Thêm cầu thủ mới thất bại: Lỗi khi di chuyển file ảnh.";
                    error_log("Lỗi di chuyển file: " . error_get_last()['message']);
                    return false;
                }
            } else {
                $_SESSION['error'] = "Thêm cầu thủ mới thất bại: Kích thước file vượt quá 5MB.";
                return false;
            }
        } else {
            $_SESSION['error'] = "Thêm cầu thủ mới thất bại: Định dạng file không hợp lệ! Chỉ chấp nhận: " . implode(", ", $allowed_types);
            return false;
        }
    }

    $sql = "INSERT INTO Players (name, jersey_number, position, birth_date, nationality, height, weight, photo_url, team_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    // Thay 'i' thành 'd' cho height và weight
    mysqli_stmt_bind_param($stmt, "sisssddss", $name, $jersey_number, $position, $birth_date, $nationality, $height, $weight, $photo_url, $team_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Thêm cầu thủ mới thành công!";
        return true;
    } else {
        $_SESSION['error'] = "Thêm cầu thủ mới thất bại: " . mysqli_error($conn);
        return false;
    }
}

// Sửa cầu thủ
function updatePlayer($conn, $data, $image) {
    //checkAdmin();

    $player_id = (int)$data['player_id'];
    $name = mysqli_real_escape_string($conn, $data['name']);
    $jersey_number = (int)$data['jersey_number'];
    $position = mysqli_real_escape_string($conn, $data['position']);
    $birth_date = $data['birth_date'];
    $nationality = mysqli_real_escape_string($conn, $data['nationality']);
    $height = (float)$data['height']; // Sử dụng float để hỗ trợ số thập phân
    $weight = (float)$data['weight']; // Sử dụng float để hỗ trợ số thập phân
    $team_id = (int)$data['team_id'];
    $current_image = $data['current_image'];

    // Kiểm tra giá trị height và weight (phù hợp với DECIMAL(5,2))
    if ($height < 0 || $height > 999.99) {
        $_SESSION['error'] = "Chiều cao không hợp lệ! Phải từ 0 đến 999.99.";
        return false;
    }
    if ($weight < 0 || $weight > 999.99) {
        $_SESSION['error'] = "Cân nặng không hợp lệ! Phải từ 0 đến 999.99.";
        return false;
    }

    $photo_url = $current_image;
    if ($image['error'] == 0) {
        $target_dir = "../uploads/players/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . "_" . basename($image['name']);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types) && $image['size'] <= 5000000) {
            if (move_uploaded_file($image['tmp_name'], $target_file)) {
                $photo_url = "uploads/players/" . $image_name;
                if ($current_image && file_exists("../$current_image")) {
                    unlink("../$current_image");
                }
            } else {
                $_SESSION['error'] = "Lỗi khi upload hình ảnh!";
                return false;
            }
        } else {
            $_SESSION['error'] = "Hình ảnh không hợp lệ hoặc quá lớn!";
            return false;
        }
    }

    $sql = "UPDATE Players SET name = ?, jersey_number = ?, position = ?, birth_date = ?, nationality = ?, height = ?, weight = ?, photo_url = ?, team_id = ? WHERE player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    // Thay 'i' thành 'd' cho height và weight
    mysqli_stmt_bind_param($stmt, "sisssddssi", $name, $jersey_number, $position, $birth_date, $nationality, $height, $weight, $photo_url, $team_id, $player_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Cập nhật cầu thủ thành công!";
        return true;
    } else {
        $_SESSION['error'] = "Lỗi khi cập nhật cầu thủ: " . mysqli_error($conn);
        return false;
    }
}

// Xóa cầu thủ
function deletePlayer($conn, $player_id) {
    //checkAdmin();

    $player_id = (int)$player_id;

    $sql = "SELECT photo_url FROM Players WHERE player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $player_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $player = mysqli_fetch_assoc($result);

    if ($player['photo_url'] && file_exists("../" . $player['photo_url'])) {
        unlink("../" . $player['photo_url']);
    }

    $sql = "DELETE FROM Players WHERE player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $player_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Xóa cầu thủ thành công!";
        return true;
    } else {
        $_SESSION['error'] = "Lỗi khi xóa cầu thủ: " . mysqli_error($conn);
        return false;
    }
}

// Chuyển nhượng cầu thủ
function transferPlayer($conn, $player_id, $to_team_id, $transfer_date) {
    //checkAdmin();

    $player_id = (int)$player_id;
    $to_team_id = (int)$to_team_id;

    $sql = "SELECT team_id FROM Players WHERE player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $player_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $player = mysqli_fetch_assoc($result);
    $from_team_id = $player['team_id'];

    $sql = "UPDATE Players SET team_id = ? WHERE player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $to_team_id, $player_id);
    $update_success = mysqli_stmt_execute($stmt);

    if ($update_success) {
        $sql = "INSERT INTO Transfers (player_id, from_team_id, to_team_id, transfer_date) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiis", $player_id, $from_team_id, $to_team_id, $transfer_date);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Chuyển nhượng thành công!";
            return true;
        } else {
            $_SESSION['error'] = "Lỗi khi ghi lịch sử chuyển nhượng: " . mysqli_error($conn);
            return false;
        }
    } else {
        $_SESSION['error'] = "Lỗi khi cập nhật đội mới: " . mysqli_error($conn);
        return false;
    }
}

// Lấy danh sách cầu thủ
//lọc tên cầu thủ
// function getPlayers($conn, $team_id = null, $position = null, $search = null) {
//     $sql = "SELECT p.*, t.name as team_name 
//             FROM Players p 
//             LEFT JOIN Teams t ON p.team_id = t.team_id 
//             WHERE 1=1";
//     $params = [];
//     $types = "";

//     if ($team_id) {
//         $sql .= " AND p.team_id = ?";
//         $params[] = (int)$team_id;
//         $types .= "i";
//     }
//     if ($position) {
//         $sql .= " AND p.position = ?";
//         $params[] = $position;
//         $types .= "s";
//     }
//     if ($search) {
//         $sql .= " AND p.name LIKE ?";
//         $params[] = "%" . mysqli_real_escape_string($conn, $search) . "%";
//         $types .= "s";
//     }

//     $stmt = mysqli_prepare($conn, $sql);
//     if ($params) {
//         mysqli_stmt_bind_param($stmt, $types, ...$params);
//     }
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);
//     return mysqli_fetch_all($result, MYSQLI_ASSOC);
// }

    function getPlayers($conn, $team_id = null, $position = null, $search = null, $limit = null, $offset = null) {
        // Truy vấn cơ bản lấy thông tin cầu thủ và tên đội bóng (nếu có)
        $sql = "SELECT Players.*, Teams.name as team_name 
                FROM Players 
                LEFT JOIN Teams ON Players.team_id = Teams.team_id";
        
        $conditions = []; // Mảng chứa các điều kiện lọc
        if ($team_id) {
            $conditions[] = "Players.team_id = $team_id"; // Thêm điều kiện lọc theo đội bóng
        }
        if ($position) {
            $conditions[] = "Players.position = '$position'"; // Thêm điều kiện lọc theo vị trí
        }
        if ($search) {
            $conditions[] = "Players.name LIKE '%$search%'"; // Thêm điều kiện tìm kiếm theo tên
        }
        
        // Nếu có điều kiện lọc, thêm vào truy vấn
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions); // Kết hợp các điều kiện bằng AND
        }
        
        // Thêm phân trang vào truy vấn
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT $limit OFFSET $offset"; // Giới hạn số bản ghi và vị trí bắt đầu
        }
        
        $result = mysqli_query($conn, $sql); // Thực thi truy vấn
        return mysqli_fetch_all($result, MYSQLI_ASSOC); // Trả về danh sách cầu thủ dưới dạng mảng kết hợp
    }

// Lấy thống kê cầu thủ
function getPlayerStats($conn, $player_id) {
    $sql = "SELECT goals, yellow_cards, red_cards 
            FROM PlayerStats 
            WHERE player_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $player_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: ['goals' => 0, 'yellow_cards' => 0, 'red_cards' => 0];
}

// Xử lý request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_player':
                addPlayer($conn, $_POST, $_FILES['image']);
                redirect('../admin/managePlayers.php');
                break;
            case 'update_player':
                updatePlayer($conn, $_POST, $_FILES['image']);
                redirect('../admin/managePlayers.php');
                break;
            case 'delete_player':
                deletePlayer($conn, $_POST['player_id']);
                redirect('../admin/managePlayers.php');
                break;
            case 'transfer_player':
                transferPlayer($conn, $_POST['player_id'], $_POST['to_team_id'], $_POST['transfer_date']);
                redirect('../admin/managePlayers.php');
                break;
        }
    }
}


?>