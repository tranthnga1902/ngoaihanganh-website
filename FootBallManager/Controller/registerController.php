<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';


// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$errors = [];
$formData = [];


// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form và chuẩn hóa
    $formData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'sex' => $_POST['sex'] ?? 'Không xác định',
        'birthday' => $_POST['birthday'] ?? null,
        'country' => $_POST['country'] ?? null,
        'phone_number' => $_POST['phone_number'] ?? null,
        'favorite_teams' => $_POST['favoriteTeams'] ?? []
    ];


    // Map dữ liệu form sang database
    $dbData = [
        'username' => $formData['username'],
        'email' => $formData['email'],
        'password' => $formData['password'],
        'sex' => $formData['sex'],
        'birthday' => $formData['birthday'],
        'country' => $formData['country'],
        'phone_number' => $formData['phone_number'],
        'favorite_teams' => !empty($formData['favorite_teams']) ? implode(',', $formData['favorite_teams']) : null
    ];


    // Debug dữ liệu đầu vào
    error_log("Dữ liệu form: " . print_r($dbData, true));


    // Validate dữ liệu
    $errors = validateRegistrationData($dbData, $conn);


    if (empty($errors)) {
        // Hash password bằng bcrypt
        $dbData['password'] = password_hash($dbData['password'], PASSWORD_DEFAULT);
       
        // Thêm user vào database
        if (registerUser($dbData, $conn)) {
            error_log("Đăng ký thành công cho user: " . $dbData['username']);
            $_SESSION['registration_success'] = true;
            header('Location: ../user/login.php');
            exit;
        } else {
            $errors['database'] = "Không thể thêm người dùng vào cơ sở dữ liệu. Vui lòng thử lại.";
            error_log("Đăng ký thất bại cho user: " . $dbData['username']);
        }
    }


    // Lưu lỗi và dữ liệu form vào session
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $formData;
    header('Location: ../user/register.php');
    exit;
}


// Hàm validate dữ liệu
function validateRegistrationData($data, $conn) {
    $errors = [];
   
    // Validate username
    if (empty($data['username'])) {
        $errors['username'] = "Vui lòng nhập tên đăng nhập";
    } elseif (strlen($data['username']) < 4) {
        $errors['username'] = "Tên đăng nhập phải có ít nhất 4 ký tự";
    } elseif (usernameExists($data['username'], $conn)) {
        $errors['username'] = "Tên đăng nhập đã tồn tại";
    }
   
    // Validate email
    if (empty($data['email'])) {
        $errors['email'] = "Vui lòng nhập email";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email không hợp lệ";
    } elseif (emailExists($data['email'], $conn)) {
        $errors['email'] = "Email đã được đăng ký";
    }
   
    // Validate password
    if (empty($data['password'])) {
        $errors['password'] = "Vui lòng nhập mật khẩu";
    } elseif (strlen($data['password']) < 6) {
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
            $errors['birthday'] = "Bạn phải đủ 13 tuổi trở lên để đăng ký";
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
        $teamIds = explode(',', $data['favorite_teams']);
        $stmt = $conn->prepare("SELECT team_id FROM teams WHERE team_id IN (" . implode(',', array_fill(0, count($teamIds), '?')) . ")");
        $stmt->bind_param(str_repeat('i', count($teamIds)), ...$teamIds);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows !== count($teamIds)) {
            $errors['favorite_teams'] = "Một hoặc nhiều đội bóng không hợp lệ";
        }
    }


    return $errors;
}


// Hàm kiểm tra username tồn tại
function usernameExists($username, $conn) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}


// Hàm kiểm tra email tồn tại
function emailExists($email, $conn) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $result = $stmt->num_rows > 0;
    $stmt->close();
    return $result;
}


// Hàm đăng ký user
function registerUser($data, $conn) {
    try {
        $stmt = $conn->prepare("INSERT INTO users
            (username, email, password, role, sex, birthday, country, phone_number, favorite_teams, avatar_url, login_attempts, is_locked, lock_until)
            VALUES (?, ?, ?, 'user', ?, ?, ?, ?, ?, ?, 0, 0, NULL)");
       
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }


        $avatar_url = isset($data['avatar_url']) ? $data['avatar_url'] : null;
        $favorite_teams = $data['favorite_teams'] ?? null;


        error_log("Dữ liệu bind: " . print_r([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => substr($data['password'], 0, 10) . '...',
            'sex' => $data['sex'],
            'birthday' => $data['birthday'],
            'country' => $data['country'],
            'phone_number' => $data['phone_number'],
            'favorite_teams' => $favorite_teams,
            'avatar_url' => $avatar_url
        ], true));


        $stmt->bind_param("sssssssss",
            $data['username'],
            $data['email'],
            $data['password'],
            $data['sex'],
            $data['birthday'],
            $data['country'],
            $data['phone_number'],
            $favorite_teams,
            $avatar_url
        );
       
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
       
        $affected_rows = $stmt->affected_rows;
        error_log("Số hàng ảnh hưởng: " . $affected_rows);
       
        $stmt->close();
        return $affected_rows > 0;
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in registerUser: " . $e->getMessage());
        return false;
    }
}


// Lấy danh sách đội bóng
function getTeamsForRegistration($conn) {
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


$teams = getTeamsForRegistration($conn);
?>

