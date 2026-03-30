<?php
// Kiểm tra trạng thái session trước khi khởi động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Đảm bảo các tệp cần thiết được bao gồm
require_once '../includes/config.php';
require_once '../includes/functions.php';


// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}


// Lấy thông tin người dùng
function getUserInfo($userId, $conn) {
    $stmt = $conn->prepare("SELECT username, email, sex, birthday, country, phone_number, password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}


// Lấy danh sách đội bóng yêu thích của người dùng
function getUserFavoriteTeams($userId, $conn) {
    $stmt = $conn->prepare("SELECT t.team_id, t.name, t.short_name, t.logo_url
                           FROM teams t
                           JOIN users u ON FIND_IN_SET(t.team_id, u.favorite_teams)
                           WHERE u.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $row['logo_url'] = (!empty($row['logo_url']) && file_exists('../' . $row['logo_url']))
            ? '../' . $row['logo_url']
            : 'https://via.placeholder.com/100?text=' . substr($row['short_name'], 0, 3);
        $teams[] = $row;
    }
    $stmt->close();
    return $teams;
}


// Lấy danh sách tất cả đội bóng
function getAllTeams($conn) {
    $sql = "SELECT team_id, name, short_name, logo_url FROM teams ORDER BY name";
    $result = $conn->query($sql);
   
    $teams = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['logo_url'] = (!empty($row['logo_url']) && file_exists('../' . $row['logo_url']))
                ? '../' . $row['logo_url']
                : 'https://via.placeholder.com/100?text=' . substr($row['short_name'], 0, 3);
            $teams[] = $row;
        }
    }
    return $teams;
}


// Xử lý các hành động
$action = $_GET['action'] ?? '';


if ($action === 'logout') {
    // Đăng xuất
    session_destroy();
    header('Location: /user/login.php');
    exit;
} elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cập nhật thông tin người dùng
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'sex' => $_POST['sex'] ?? 'Không xác định',
        'birthday' => $_POST['birthday'] ?? null,
        'country' => $_POST['country'] ?? null,
        'phone_number' => $_POST['phone_number'] ?? null,
        'favorite_teams' => $_POST['favoriteTeams'] ?? []
    ];


    $errors = validateUpdateData($data, $conn, $_SESSION['user_id']);


    if (empty($errors)) {
        // Hash password nếu có thay đổi
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Giữ nguyên password cũ
            $user = getUserInfo($_SESSION['user_id'], $conn);
            $data['password'] = isset($user['password']) ? $user['password'] : ''; // Đảm bảo không NULL
        }


        // Cập nhật dữ liệu
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, sex = ?, birthday = ?, country = ?, phone_number = ?, favorite_teams = ? WHERE user_id = ?");
        $favorite_teams = !empty($data['favorite_teams']) ? implode(',', $data['favorite_teams']) : null;
        $stmt->bind_param("ssssssssi",
            $data['username'],
            $data['email'],
            $data['password'],
            $data['sex'],
            $data['birthday'],
            $data['country'],
            $data['phone_number'],
            $favorite_teams,
            $_SESSION['user_id']
        );


        if ($stmt->execute()) {
            $_SESSION['username'] = $data['username'];
            header('Location: ../user/detailUser.php?success=1');
        } else {
            header('Location: ../user/detailUser.php?error=' . urlencode('Cập nhật thất bại. Vui lòng thử lại.'));
        }
        $stmt->close();
    } else {
        $_SESSION['update_errors'] = $errors;
        header('Location: ../user/detailUser.php');
    }
    exit;
}


// Validate dữ liệu cập nhật
function validateUpdateData($data, $conn, $userId) {
    $errors = [];


    // Validate username
    if (empty($data['username'])) {
        $errors['username'] = "Vui lòng nhập tên đăng nhập";
    } elseif (strlen($data['username']) < 4) {
        $errors['username'] = "Tên đăng nhập phải có ít nhất 4 ký tự";
    } elseif (usernameExists($data['username'], $conn, $userId)) {
        $errors['username'] = "Tên đăng nhập đã tồn tại";
    }


    // Validate email
    if (empty($data['email'])) {
        $errors['email'] = "Vui lòng nhập email";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email không hợp lệ";
    } elseif (emailExists($data['email'], $conn, $userId)) {
        $errors['email'] = "Email đã được đăng ký";
    }


    // Validate password (nếu có thay đổi)
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        $errors['password'] = "Mật khẩu phải có ít nhất 6 ký tự";
    }


    // Validate ngày sinh
    if (empty($data['birthday'])) {
        $errors['birthday'] = "Vui lòng nhập ngày sinh";
    } else {
        $dob = new DateTime($data['birthday']);
        $now = new DateTime();
        $birthYear = $dob->format('Y');
        $currentYear = $now->format('Y');
        $age = $now->diff($dob)->y;


        if ($birthYear > $currentYear) {
            $errors['birthday'] = "Năm sinh không thể lớn hơn năm hiện tại";
        } elseif ($birthYear < $currentYear - 100) {
            $errors['birthday'] = "Tuổi không thể lớn hơn 100";
        } elseif ($age < 13) {
            $errors['birthday'] = "Bạn phải đủ 13 tuổi trở lên";
        }
    }


    // Validate quốc gia
    if (empty($data['country'])) {
        $errors['country'] = "Vui lòng chọn quốc gia";
    }


    // Validate số điện thoại
    if (empty($data['phone_number'])) {
        $errors['phone_number'] = "Vui lòng nhập số điện thoại";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $data['phone_number'])) {
        $errors['phone_number'] = "Số điện thoại không hợp lệ";
    }


    // Validate favorite_teams
    if (!empty($data['favorite_teams'])) {
        $teamIds = $data['favorite_teams'];
        $stmt = $conn->prepare("SELECT team_id FROM teams WHERE team_id IN (" . implode(',', array_fill(0, count($teamIds), '?')) . ")");
        $stmt->bind_param(str_repeat('i', count($teamIds)), ...$teamIds);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows !== count($teamIds)) {
            $errors['favorite_teams'] = "Một hoặc nhiều đội bóng không hợp lệ";
        }
        $stmt->close();
    }


    return $errors;
}


// Kiểm tra username tồn tại (trừ user hiện tại)
function usernameExists($username, $conn, $userId) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}


// Kiểm tra email tồn tại (trừ user hiện tại)
function emailExists($email, $conn, $userId) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $stmt->store_result();
    $result = $stmt->num_rows > 0;
    $stmt->close();
    return $result;
}
?>

