<?php
require_once '../includes/config.php';

function addStadium($conn) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $capacity = (int)$_POST['capacity'];
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $built_year = (int)$_POST['built_year'];

    // Kiểm tra trùng tên
    $check_sql = "SELECT COUNT(*) FROM stadiums WHERE name = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $name);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_bind_result($check_stmt, $count);
    mysqli_stmt_fetch($check_stmt);
    mysqli_stmt_close($check_stmt);

    if ($count > 0) {
        return ['status' => 'error', 'message' => 'Tên sân vận động đã tồn tại!'];
    }

    // Xử lý upload ảnh sân
    $photo_url = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/stadiums/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . basename($_FILES['photo']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $new_file_name = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_url = "uploads/stadiums/" . $new_file_name;
        }
    }

    $sql = "INSERT INTO stadiums (name, capacity, address, city, built_year, photo_url) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sissis", $name, $capacity, $address, $city, $built_year, $photo_url);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        $stadium_id = mysqli_insert_id($conn);
        return ['status' => 'success', 'message' => 'Sân vận động tạo mới thành công!', 'stadium_id' => $stadium_id];
    } else {
        return ['status' => 'error', 'message' => 'Thêm sân vận động thất bại: ' . mysqli_error($conn)];
    }
}

// function updateStadium($conn) {
//     $stadium_id = (int)$_POST['stadium_id'];
//     $name = mysqli_real_escape_string($conn, $_POST['name']);
//     $capacity = (int)$_POST['capacity'];
//     $address = mysqli_real_escape_string($conn, $_POST['address']);
//     $city = mysqli_real_escape_string($conn, $_POST['city']);
//     $built_year = (int)$_POST['built_year'];
//     $current_photo = isset($_POST['current_photo']) ? $_POST['current_photo'] : null;

//     // Kiểm tra trùng tên (trừ sân hiện tại)
//     $check_sql = "SELECT COUNT(*) FROM stadiums WHERE name = ? AND stadium_id != ?";
//     $check_stmt = mysqli_prepare($conn, $check_sql);
//     mysqli_stmt_bind_param($check_stmt, "si", $name, $stadium_id);
//     mysqli_stmt_execute($check_stmt);
//     mysqli_stmt_bind_result($check_stmt, $count);
//     mysqli_stmt_fetch($check_stmt);
//     mysqli_stmt_close($check_stmt);

//     if ($count > 0) {
//         return ['status' => 'error', 'message' => 'Tên sân vận động đã tồn tại!'];
//     }

//     $photo_url = $current_photo;
//     if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
//         $target_dir = "../uploads/stadiums/";
//         if (!is_dir($target_dir)) {
//             mkdir($target_dir, 0755, true);
//         }
//         $target_file = $target_dir . basename($_FILES['photo']['name']);
//         $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
//         $new_file_name = uniqid() . '.' . $imageFileType;
//         $target_file = $target_dir . $new_file_name;

//         if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
//             $photo_url = "uploads/stadiums/" . $new_file_name;
//             if ($current_photo && file_exists("../" . $current_photo)) {
//                 unlink("../" . $current_photo);
//             }
//         }
//     }

//     $sql = "UPDATE stadiums SET name = ?, capacity = ?, address = ?, city = ?, built_year = ?, photo_url = ? WHERE stadium_id = ?";
//     $stmt = mysqli_prepare($conn, $sql);
//     mysqli_stmt_bind_param($stmt, "sissisi", $name, $capacity, $address, $city, $built_year, $photo_url, $stadium_id);
//     $result = mysqli_stmt_execute($stmt);
//     mysqli_stmt_close($stmt);

//     if ($result) {
//         return ['status' => 'success', 'message' => 'Cập nhật sân vận động thành công!', 'stadium_id' => $stadium_id];
//     } else {
//         return ['status' => 'error', 'message' => 'Cập nhật sân vận động thất bại: ' . mysqli_error($conn)];
//     }
// }

function updateStadium($conn) {
    $stadium_id = (int)$_POST['stadium_id'];

    // Lấy thông tin hiện tại của sân vận động
    $current_sql = "SELECT name, capacity, address, city, built_year, photo_url FROM stadiums WHERE stadium_id = ?";
    $current_stmt = mysqli_prepare($conn, $current_sql);
    mysqli_stmt_bind_param($current_stmt, "i", $stadium_id);
    mysqli_stmt_execute($current_stmt);
    mysqli_stmt_bind_result($current_stmt, $current_name, $current_capacity, $current_address, $current_city, $current_built_year, $current_photo_url);
    mysqli_stmt_fetch($current_stmt);
    mysqli_stmt_close($current_stmt);

    // Nếu không tìm thấy sân vận động
    if (!$current_name) {
        return ['status' => 'error', 'message' => 'Sân vận động không tồn tại!'];
    }

    // Lấy dữ liệu từ form, nếu không có thì giữ nguyên giá trị cũ
    $name = !empty($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : $current_name;
    $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : $current_capacity;
    $address = !empty($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : $current_address;
    $city = !empty($_POST['city']) ? mysqli_real_escape_string($conn, $_POST['city']) : $current_city;
    $built_year = !empty($_POST['built_year']) ? (int)$_POST['built_year'] : $current_built_year;
    $current_photo = isset($_POST['current_photo']) ? $_POST['current_photo'] : $current_photo_url;

    // Kiểm tra các trường (chỉ kiểm tra nếu có dữ liệu mới)
    if (!empty($_POST['name']) && $name === $current_name) {
        // Nếu tên không thay đổi, bỏ qua kiểm tra trùng
    } else {
        $check_name_sql = "SELECT COUNT(*) FROM stadiums WHERE name = ? AND stadium_id != ?";
        $check_name_stmt = mysqli_prepare($conn, $check_name_sql);
        mysqli_stmt_bind_param($check_name_stmt, "si", $name, $stadium_id);
        mysqli_stmt_execute($check_name_stmt);
        mysqli_stmt_bind_result($check_name_stmt, $name_count);
        mysqli_stmt_fetch($check_name_stmt);
        mysqli_stmt_close($check_name_stmt);

        if ($name_count > 0) {
            return ['status' => 'error', 'message' => 'Tên sân vận động đã tồn tại!'];
        }
    }

    if (!empty($_POST['capacity']) && $capacity < 0) {
        return ['status' => 'error', 'message' => 'Sức chứa không được nhỏ hơn 0!'];
    }

    if (!empty($_POST['built_year']) && ($built_year < 1800 || $built_year > 2025)) {
        return ['status' => 'error', 'message' => 'Năm xây dựng phải từ 1800 đến 2025!'];
    }

    $photo_url = $current_photo;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/stadiums/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . basename($_FILES['photo']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_types)) {
            return ['status' => 'error', 'message' => 'Định dạng ảnh không hợp lệ!'];
        }
        if ($_FILES['photo']['size'] > 5000000) {
            return ['status' => 'error', 'message' => 'Kích thước ảnh vượt quá 5MB!'];
        }
        $new_file_name = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_url = "uploads/stadiums/" . $new_file_name;
            if ($current_photo && file_exists("../" . $current_photo)) {
                unlink("../" . $current_photo);
            }
        } else {
            return ['status' => 'error', 'message' => 'Lỗi khi upload ảnh sân vận động!'];
        }
    }

    $sql = "UPDATE stadiums SET name = ?, capacity = ?, address = ?, city = ?, built_year = ?, photo_url = ? WHERE stadium_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sissisi", $name, $capacity, $address, $city, $built_year, $photo_url, $stadium_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        return ['status' => 'success', 'message' => 'Cập nhật sân vận động thành công!', 'stadium_id' => $stadium_id];
    } else {
        return ['status' => 'error', 'message' => 'Cập nhật sân vận động thất bại: ' . mysqli_error($conn)];
    }
}

function getStadium($conn) {
    if (isset($_GET['action']) && $_GET['action'] === 'get_stadium' && isset($_GET['stadium_id'])) {
        $stadium_id = (int)$_GET['stadium_id'];
        $sql = "SELECT * FROM stadiums WHERE stadium_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $stadium_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stadium = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        header('Content-Type: application/json');
        if ($stadium) {
            echo json_encode(['status' => 'success', 'stadium' => $stadium]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Sân vận động không tồn tại!']);
        }
        exit;
    }
}

// Xử lý AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_stadium':
                echo json_encode(addStadium($conn));
                break;
            case 'update_stadium':
                echo json_encode(updateStadium($conn));
                break;
        }
    }
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_stadium':
                getStadium($conn);
                break;
        }
    }
}
?>