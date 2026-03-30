<?php


$host = "localhost"; // Địa chỉ MySQL
$dbname = "football6"; // Tên database
$username = "root"; // Tên đăng nhập MySQL (Laragon mặc định là root)
$password = ""; // Mật khẩu MySQL (Laragon mặc định là rỗng)

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        error_log("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
        die("Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
    }
    // Đặt múi giờ
    date_default_timezone_set('Asia/Ho_Chi_Minh');
} catch (Exception $e) {
    error_log("Lỗi kết nối: " . $e->getMessage());
    die("Lỗi kết nối: " . $e->getMessage());
}


if (!defined('BASE_URL')) {
        // Fix HTTPS detection for ngrok / reverse proxy
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
        $protocol = $is_https ? 'https' : 'http';
    
        $document_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $script_dir = str_replace('\\', '/', realpath(dirname(__FILE__)));
        $base_folder = str_replace($document_root, '', $script_dir . "/..");
    
        $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_folder . "/";
        
    
        define('BASE_URL', $base_url);
    }
    
// Debug BASE_URL
error_log("BASE_URL: " . BASE_URL);

// Kiểm tra và sửa đường dẫn nếu có user/user
$current_url = $_SERVER['REQUEST_URI'];
if (strpos($current_url, 'user/user') !== false) {
    $corrected_url = str_replace('user/user', 'user', $current_url);
    header("Location: $corrected_url");
    exit();
}

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }
    // Đặt múi giờ
    date_default_timezone_set('Asia/Ho_Chi_Minh');
} catch (Exception $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

?>