<?php
require_once '../includes/config.php';

class LoginController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Hàm xác thực người dùng
    public function authenticate($email, $password) {
        // Kiểm tra tài khoản bị khóa trước
        $stmt = $this->conn->prepare("SELECT user_id, username, email, password, role, avatar_url, login_attempts, is_locked, lock_until FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra tài khoản bị khóa
            if ($user['is_locked']) {
                $lock_time = strtotime($user['lock_until']);
                if ($lock_time > time()) {
                    $remaining = ceil(($lock_time - time())/60);
                    $_SESSION['login_error'] = "Tài khoản tạm thời bị khóa. Vui lòng thử lại sau $remaining phút";
                    header("Location: login.php");
                    exit();
                } else {
                    // Mở khóa nếu hết thời gian
                    $this->unlockAccount($user['user_id']);
                }
            }
    
            // Kiểm tra mật khẩu
            $isSha256 = strlen($user['password']) === 64 && ctype_xdigit($user['password']);
            
            if ($isSha256) {
                // Mật khẩu trong cơ sở dữ liệu là SHA-256 (hệ thống cũ)
                $hashedInputPassword = hash('sha256', $password);
                if ($user['password'] === $hashedInputPassword) {
                    // Đăng nhập thành công, cập nhật mật khẩu sang bcrypt
                    $newHashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $this->updatePasswordToHashed($user['user_id'], $newHashedPassword);
                    
                    // Reset số lần thử
                    $this->resetLoginAttempts($user['user_id']);
                    
                    // Lưu thông tin người dùng vào session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['avatar_url'] = $user['avatar_url'];
    
                    // Chuyển hướng
                    header("Location: " . ($user['role'] === 'admin' ? "../admin/dashboard.php" : "../index.php"));
                    exit();
                }
            } else {
                // Mật khẩu trong cơ sở dữ liệu là bcrypt (hệ thống mới)
                if (password_verify($password, $user['password'])) {
                    // Đăng nhập thành công
                    $this->resetLoginAttempts($user['user_id']);
                    
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['avatar_url'] = $user['avatar_url'];
    
                    header("Location: " . ($user['role'] === 'admin' ? "../admin/dashboard.php" : "../index.php"));
                    exit();
                }
            }
            
            // Nếu mật khẩu không khớp
            $attempts = $user['login_attempts'] + 1;
            $this->updateLoginAttempts($user['user_id'], $attempts);
            
            if ($attempts >= 5) {
                if ($attempts == 5) {
                    $_SESSION['login_error'] = "Bạn đã nhập sai 5 lần. Lần tiếp theo tài khoản sẽ bị khóa!";
                } elseif ($attempts == 6) {
                    $this->lockAccount($user['user_id']);
                    $_SESSION['login_error'] = "Tài khoản của bạn đã bị khóa trong 30 phút do nhập sai quá nhiều lần";
                }
            } else {
                $remaining = 5 - $attempts;
                $_SESSION['login_error'] = "Email hoặc mật khẩu không chính xác. Bạn còn $remaining lần thử trước khi bị khóa";
            }
            
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Email hoặc mật khẩu không chính xác";
            header("Location: login.php");
            exit();
        }
    }
    
    // Các hàm hỗ trợ
    private function updateLoginAttempts($user_id, $attempts) {
        $stmt = $this->conn->prepare("UPDATE users SET login_attempts = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $attempts, $user_id);
        $stmt->execute();
    }
    
    private function resetLoginAttempts($user_id) {
        $stmt = $this->conn->prepare("UPDATE users SET login_attempts = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    private function lockAccount($user_id) {
        $lock_time = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $stmt = $this->conn->prepare("UPDATE users SET is_locked = TRUE, lock_until = ? WHERE user_id = ?");
        $stmt->bind_param("si", $lock_time, $user_id);
        $stmt->execute();
    }
    
    private function unlockAccount($user_id) {
        $stmt = $this->conn->prepare("UPDATE users SET is_locked = FALSE, login_attempts = 0, lock_until = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    private function updatePasswordToHashed($user_id, $hashedPassword) {
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashedPassword, $user_id);
        $stmt->execute();
    }
}

// Xử lý khi form đăng nhập được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $loginController = new LoginController($conn);
    $loginController->authenticate($_POST['email'], $_POST['password']);
}
?>