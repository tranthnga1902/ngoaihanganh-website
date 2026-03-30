<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Hàm lấy danh sách người dùng với bộ lọc
function getUsers($conn, $role = null, $search = null) {
    $sql = "SELECT u.*, u.favorite_teams 
            FROM users u 
            WHERE 1=1";
    
    if ($role) {
        $role = mysqli_real_escape_string($conn, $role);
        $sql .= " AND u.role = '$role'";
    }
    if ($search) {
        $search = mysqli_real_escape_string($conn, $search);
        $sql .= " AND u.username LIKE '%$search%'";
    }
    
    $result = mysqli_query($conn, $sql);
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Lấy tên đội bóng từ favorite_teams (vẫn cần để hiển thị trong bảng)
    foreach ($users as &$user) {
        if (!empty($user['favorite_teams'])) {
            $team_ids = explode(',', $user['favorite_teams']);
            $team_names = [];
            foreach ($team_ids as $team_id) {
                $team_id = (int)$team_id;
                $team_sql = "SELECT name FROM teams WHERE team_id = $team_id";
                $team_result = mysqli_query($conn, $team_sql);
                $team = mysqli_fetch_assoc($team_result);
                if ($team) {
                    $team_names[] = $team['name'];
                }
            }
            $user['favorite_team_names'] = implode(', ', $team_names);
        } else {
            $user['favorite_team_names'] = 'Chưa chọn';
        }
    }
    unset($user);

    return $users;
}

// Xử lý tìm kiếm gợi ý (autocomplete)
if (isset($_GET['term'])) {
    $term = mysqli_real_escape_string($conn, $_GET['term']);
    $sql = "SELECT username FROM users WHERE username LIKE '%$term%' LIMIT 10";
    $result = mysqli_query($conn, $sql);
    $suggestions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = ['username' => $row['username']];
    }
    header('Content-Type: application/json');
    echo json_encode(['suggestions' => $suggestions]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_user':
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = mysqli_real_escape_string($conn, $_POST['role']);

            // Kiểm tra trùng email
            $check_email_sql = "SELECT user_id FROM users WHERE email = ?";
            $check_email_stmt = mysqli_prepare($conn, $check_email_sql);
            mysqli_stmt_bind_param($check_email_stmt, "s", $email);
            mysqli_stmt_execute($check_email_stmt);
            mysqli_stmt_store_result($check_email_stmt);
            if (mysqli_stmt_num_rows($check_email_stmt) > 0) {
                $_SESSION['error'] = "Email đã tồn tại. Vui lòng sử dụng email khác.";
                header("Location: ../admin/manageUsers.php");
                exit;
            }
            mysqli_stmt_close($check_email_stmt);

            // Kiểm tra trùng số điện thoại (nếu có nhập số điện thoại)
            if (!empty($phone_number)) {
                $check_phone_sql = "SELECT user_id FROM users WHERE phone_number = ?";
                $check_phone_stmt = mysqli_prepare($conn, $check_phone_sql);
                mysqli_stmt_bind_param($check_phone_stmt, "s", $phone_number);
                mysqli_stmt_execute($check_phone_stmt);
                mysqli_stmt_store_result($check_phone_stmt);
                if (mysqli_stmt_num_rows($check_phone_stmt) > 0) {
                    $_SESSION['error'] = "Số điện thoại đã tồn tại. Vui lòng sử dụng số điện thoại khác.";
                    header("Location: ../admin/manageUsers.php");
                    exit;
                }
                mysqli_stmt_close($check_phone_stmt);
            }

            // Thêm người dùng mới
            $sql = "INSERT INTO users (username, email, phone_number, password, role, is_locked) 
                    VALUES (?, ?, ?, ?, ?, 0)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $phone_number, $password, $role);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Thêm người dùng thành công.";
            } else {
                $_SESSION['error'] = "Thêm người dùng thất bại.";
            }
            mysqli_stmt_close($stmt);
            header("Location: ../admin/manageUsers.php");
            exit;

        case 'lock_user':
            $user_id = (int)$_POST['user_id'];
            $lock_reason = mysqli_real_escape_string($conn, $_POST['lock_reason']);

            $sql = "UPDATE users SET is_locked = 1, lock_reason = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $lock_reason, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Khóa người dùng thành công.";
            } else {
                $_SESSION['error'] = "Khóa người dùng thất bại.";
            }
            mysqli_stmt_close($stmt);
            header("Location: ../admin/manageUsers.php");
            exit;

        case 'unlock_user':
            $user_id = (int)$_POST['user_id'];

            $sql = "UPDATE users SET is_locked = 0, lock_reason = NULL WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Mở khóa người dùng thành công.";
            } else {
                $_SESSION['error'] = "Mở khóa người dùng thất bại.";
            }
            mysqli_stmt_close($stmt);
            header("Location: ../admin/manageUsers.php");
            exit;

        default:
            header("Location: ../admin/manageUsers.php");
            exit;
    }
}
?>