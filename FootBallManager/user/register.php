<?php
// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




// Thiết lập debug
error_reporting(E_ALL);
ini_set('display_errors', 1);




// Lấy dữ liệu từ session
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];




// Xóa session sau khi sử dụng
unset($_SESSION['errors']);
unset($_SESSION['form_data']);




require_once '../includes/config.php';
require_once '../Controller/registerController.php';




// Lấy danh sách đội bóng
$teams = getTeamsForRegistration($conn);




// Bắt đầu bộ đệm đầu ra
ob_start();




// Đặt tiêu đề trang
$title = "Đăng ký";
?>




<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../assets/css/register.css">


</head>
<body>
    <div class="container">
        <header class="stats-header">
            <div class="main-container">
                <div class="header-content">
                    <div class="header-top">
                        <h1>Tạo tài khoản của bạn</h1>
                    </div>
                </div>
            </div>
        </header>




        <div class="registration-container">
            <?php if (!empty($errors)): ?>
                <div class="debug-errors" style="background:#ffebee;padding:10px;margin:10px 0;border:1px solid red;">
                    <h3>DEBUG ERRORS:</h3>
                    <pre><?php print_r($errors); ?></pre>
                </div>
            <?php endif; ?>
            <?php if (isset($errors['database'])): ?>
                <div class="error-message" style="color: #d32f2f; font-size: 0.9em; margin-bottom: 10px;">
                    <?php echo htmlspecialchars($errors['database']); ?>
                </div>
            <?php endif; ?>




            <div class="progress-bar">
                <div class="progress-step active" id="step1">
                    <div class="step-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="step-text">Cá nhân</div>
                </div>
                <div class="progress-step" id="step2">
                    <div class="step-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="step-text">Sở thích</div>
                </div>
                <div class="progress-step" id="step3">
                    <div class="step-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="step-text">Tùy chọn email</div>
                </div>
            </div>




            <form id="registrationForm" action="../Controller/registerController.php" method="POST">
                <!-- Step 1: Personal Details -->
                <div class="form-step" id="formStep1">
                    <h2>Thông tin cá nhân</h2>
                   
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username"
                            value="<?= htmlspecialchars($formData['username'] ?? '') ?>" required>
                        <span id="usernameError" class="error-message"></span>
                    </div>




                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                        <span id="emailError" class="error-message"></span>
                    </div>




                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" required class="form-input">
                            <button type="button" class="toggle-password" aria-label="Hiển thị mật khẩu">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="passwordError" class="error-message"></span>
                    </div>




                    <div class="form-group">
                        <label for="confirmPassword">Xác nhận mật khẩu</label>
                        <div class="password-input-container">
                            <input type="password" id="confirmPassword" name="confirmPassword" required class="form-input">
                            <button type="button" class="toggle-password" aria-label="Hiển thị mật khẩu">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="confirmPasswordError" class="error-message"></span>
                    </div>




                    <div class="form-group">
                        <label>Giới tính</label>
                        <div class="radio-group">
                            <label><input type="radio" name="sex" value="Nam" required>Nam</label>
                            <label><input type="radio" name="sex" value="Nữ">Nữ</label>
                            <label><input type="radio" name="sex" value="Không xác định">Không xác định</label>
                        </div>
                        <span id="sexError" class="error-message"></span>
                    </div>
                   
                    <div class="form-group">
                        <label for="birthday">Ngày sinh</label>
                        <input type="date" id="birthday" name="birthday"
                            value="<?= htmlspecialchars($formData['birthday'] ?? '') ?>" required>
                        <span id="birthdayError" class="error-message"></span>
                    </div>




                    <div class="form-group">
                        <label for="country">Quốc gia / Khu vực lưu trú</label>
                        <select id="country" name="country" required>
                            <option value="">Chọn quốc gia</option>
                        </select>
                        <span id="countryError" class="error-message"></span>
                    </div>




                    <div class="form-group">
                        <label for="phone_number">Số điện thoại</label>
                        <input type="tel" id="phone_number" name="phone_number"
                            value="<?= htmlspecialchars($formData['phone_number'] ?? '') ?>" required>
                        <span id="phone_numberError" class="error-message"></span>
                    </div>




                    <div id="step1Error" class="error-message" style="display: none; color: red; margin-bottom: 10px;">
                        Vui lòng điền đầy đủ thông tin trước khi tiếp tục
                    </div>
                   
                    <div class="form-navigation">
                        <button type="button" class="next-btn" onclick="nextStep(1)">Tiếp theo</button>
                    </div>
                </div>




                <!-- Step 2: Your Favorites -->
                <div class="form-step" id="formStep2" style="display:none;">
                    <h2>Câu lạc bộ yêu thích</h2>
                    <p>Chọn những câu lạc bộ bạn yêu thích!</p>
                   
                    <div class="team-selection">
                        <?php foreach ($teams as $team): ?>
                        <div class="team-wrapper">
                            <input type="checkbox"
                                name="favoriteTeams[]"
                                value="<?= $team['team_id'] ?>"
                                id="team-<?= $team['team_id'] ?>"
                                class="team-checkbox">
                            <label class="team-option" for="team-<?= $team['team_id'] ?>">
                                <div class="team-content">
                                    <img src="<?= htmlspecialchars($team['logo_url']) ?>"
                                        alt="<?= htmlspecialchars($team['name']) ?>"
                                        loading="lazy"
                                        onerror="this.src='https://via.placeholder.com/100?text=<?= substr($team['short_name'], 0, 3) ?>'">
                                    <span><?= htmlspecialchars($team['name']) ?></span>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>




                    <div class="form-navigation">
                        <button type="button" class="back-btn" onclick="prevStep(2)">Quay lại</button>
                        <button type="button" class="next-btn" onclick="nextStep(2)">Tiếp theo</button>
                    </div>
                </div>




                <!-- Step 3: Email Communications -->
                <div class="form-step" id="formStep3" style="display:none;">
                    <h2>Tùy chọn Email</h2>
                   
                    <div class="selected-teams">
                        <h3>Bạn muốn theo dõi các câu lạc bộ này? </h3>
                        <div id="displayFavoriteTeams"></div>
                    </div>




                    <div class="terms-section">
                        <p>Bạn có thể quản lý tùy chọn email của mình và thay đổi thông tin chúng tôi gửi cho bạn bất kỳ lúc nào bằng cách truy cập tài khoản của bạn và nhấp vào 'Cập nhật hồ sơ'.</p>
                        <p>Nếu bạn không muốn nhận thông tin liên lạc trực tiếp từ câu lạc bộ mà bạn ủng hộ nữa, bạn nên liên hệ trực tiếp với câu lạc bộ đó để thông báo cho họ. Bạn có thể tìm thêm thông tin về cách thực hiện việc này trên trang <a href="https://www.premierleague.com/withdrawing-consent-from-clubs" target="_blank">Rút lại sự đồng ý từ Câu lạc bộ</a> của chúng tôi.</p>
                       
                        <label class="terms-checkbox">
                            <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                            <p>Tôi đồng ý với <a href="https://www.premierleague.com/terms-and-conditions" target="_blank"> Điều kiện.</a></p>
                        </label>
                        <span id="termsError" class="error-message"></span>
                    </div>
                   
                    <div class="form-navigation">
                        <button type="button" class="back-btn" onclick="prevStep(3)">Quay lại</button>
                        <button type="button" class="complete-btn" onclick="validateAndSubmit()">Hoàn thành</button>
                    </div>
                </div>
            </form>
        </div>
    </div>




    <script>
        // Truyền dữ liệu teams từ PHP sang JavaScript
        window.teamsData = <?= json_encode($teams) ?>;
        // Truyền lỗi từ server sang JavaScript
        window.serverErrors = <?= json_encode($errors ?? []) ?>;
    </script>
    <script src="../assets/js/register.js"></script>
</body>
</html>




<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();
include '../includes/master.php';
?>









