<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


function logout() {
    // Bắt đầu session nếu chưa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
   
    // Xóa tất cả session
    $_SESSION = array();
   
    // Hủy cookie session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
   
    // Hủy session
    session_destroy();
   
    // Điều hướng
    header("Location: /user/login.php");
    exit();
}




// Xử lý action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}


function preventBackButtonCaching() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Ngày trong quá khứ
}







