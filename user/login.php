<?php
// Khởi động session
session_start();




require_once '../includes/config.php';
require_once '../Controller/loginController.php';




// Xử lý đăng nhập ngay tại đây nếu có POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $loginController = new LoginController($conn);
    $loginController->authenticate($_POST['email'], $_POST['password']);
}




// Hiển thị thông báo lỗi nếu có
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}




// Bắt đầu bộ đệm đầu ra
ob_start();




// Đặt tiêu đề trang
$title = "Đăng nhập";
?>




<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <!-- <base href="http://localhost:3000/"> -->




</head>
<body>
    <div class="container">
        <header class="stats-header">
            <div class="main-container">
                <div class="header-content">
                    <div class="header-top">
                        <h1>Tài khoản của bạn</h1>
                    </div>
                </div>
            </div>
        </header>
       
        <div class="login-wrapper">
            <div class="login-column">
                <form class="login-form" method="POST">
                    <h2 class="form-title">Đăng nhập</h2>




                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="email-wrapper">
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                   
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <span class="toggle-password">Hiện</span>
                        </div>
                    </div>
                   
                    <div class="form-options">
                        <a href="forgot_password.php" class="forgot-password">Quên thông tin đăng nhập?</a>
                    </div>
                    <?php if (isset($error_message)): ?>
                        <div class="error-message" style="color: red; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>




                    <?php
                    $warning_message = '';
                    if (isset($_SESSION['login_warning'])) {
                        $warning_message = $_SESSION['login_warning'];
                        unset($_SESSION['login_warning']);
                    }
                    ?>




                    <!-- phần hiển thị error_message -->
                    <?php if (!empty($warning_message)): ?>
                    <div class="warning-message" style="color: orange; margin-bottom: 15px;">
                        ⚠️ <?php echo htmlspecialchars($warning_message); ?>
                    </div>
                    <?php endif; ?>
                   
                    <button type="submit" class="login-button">Đăng nhập</button>
                   
                </form>
            </div>
           
            <div class="register-column">
                <h2 class="register-title">Chưa có tài khoản?</h2>
                <p class="register-subtitle">Bạn đang bỏ lỡ những lợi ích sau:</p>
               
                <ul class="benefits-list">
                    <li>Dịch vụ dành riêng cho fan hâm mộ</li>
                    <li>Nội dung được cá nhân hóa</li>
                    <li>Thông tin và thông báo về câu lạc bộ yêu thích</li>
                </ul>
               
                <a href="<?php echo BASE_URL; ?>user/register.php" class="register-button">Đăng ký ngay</a>
            </div>
        </div>
    </div>




    <script>
        // Toggle hiển thị mật khẩu
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.textContent = 'Ẩn';
            } else {
                passwordInput.type = 'password';
                this.textContent = 'Hiện';
            }
        });
    </script>








</body>
</html>








<!-- Nội dung chính -->
<main>




</main>




<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();




// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>

















