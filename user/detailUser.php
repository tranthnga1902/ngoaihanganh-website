<?php
// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng về login.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}


// Bao gồm tệp cấu hình và controller
include '../includes/config.php';
include '../controller/detailUserController.php';


// Lấy thông tin người dùng và danh sách đội bóng
$user = getUserInfo($_SESSION['user_id'], $conn);
$favoriteTeams = getUserFavoriteTeams($_SESSION['user_id'], $conn);
$allTeams = getAllTeams($conn);


// Bắt đầu bộ đệm đầu ra
ob_start();


// Đặt tiêu đề trang
$title = "Quản lý tài khoản";
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../css/detailUser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .manage-account-form {
            display: block;
            width: 90%;
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border: 2px solid #3498db;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            padding: 25px;
        }
        .manage-account-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            max-width: 350px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group select#country {
            min-width: 350px;
        }
        .team-selection {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        .team-checkbox:checked + label {
            border-color: #2ecc71;
            background-color: rgba(46, 204, 113, 0.1);
        }
        .team-checkbox + label img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 50%;
            background: #fff;
            padding: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .team-option {
            text-align: center;
        }
        .team-checkbox + label span {
            display: block;
            margin-top: 5px;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        .form-navigation {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .logout-btn {
            background-color: white;
            color: black;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        @media (max-width: 768px) {
            .team-selection {
                grid-template-columns: repeat(2, 1fr);
            }
            .form-group input,
            .form-group select {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="manage-account-form card" id="manageAccountForm">
            <h2><i class="fas fa-edit"></i> Chỉnh sửa thông tin</h2>
            <form id="updateProfileForm" action="../controller/detailUserController.php?action=update" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username"
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        <span id="usernameError" class="error-message"></span>
                    </div>


                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <span id="emailError" class="error-message"></span>
                    </div>


                    <div class="form-group">
                        <label for="password">Mật khẩu mới (để trống nếu không đổi)</label>
                        <input type="password" id="password" name="password">
                        <span id="passwordError" class="error-message"></span>
                    </div>


                    <div class="form-group">
                        <label>Giới tính</label>
                        <div class="radio-group">
                            <label><input type="radio" name="sex" value="Nam" <?php echo $user['sex'] === 'Nam' ? 'checked' : ''; ?>> Nam</label>
                            <label><input type="radio" name="sex" value="Nữ" <?php echo $user['sex'] === 'Nữ' ? 'checked' : ''; ?>> Nữ</label>
                            <label><input type="radio" name="sex" value="Không xác định" <?php echo $user['sex'] === 'Không xác định' ? 'checked' : ''; ?>> Không xác định</label>
                        </div>
                        <span id="sexError" class="error-message"></span>
                    </div>


                    <div class="form-group">
                        <label for="birthday">Ngày sinh</label>
                        <input type="date" id="birthday" name="birthday"
                               value="<?php echo htmlspecialchars($user['birthday']); ?>" required>
                        <span id="birthdayError" class="error-message"></span>
                    </div>


                    <div class="form-group">
                        <label for="country">Quốc gia</label>
                        <select id="country" name="country" required>
                            <option value="">Chọn quốc gia</option>
                            <!-- Quốc gia sẽ được điền bởi JavaScript -->
                        </select>
                        <span id="countryError" class="error-message"></span>
                    </div>


                    <div class="form-group">
                        <label for="phone_number">Số điện thoại</label>
                        <input type="tel" id="phone_number" name="phone_number"
                               value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                        <span id="phone_numberError" class="error-message"></span>
                    </div>
                </div>


                <div class="form-group team-selection-group">
                    <label>Đội bóng yêu thích</label>
                    <div class="team-selection">
                        <?php
                        $userTeamIds = array_column($favoriteTeams, 'team_id');
                        foreach ($allTeams as $team):
                        ?>
                            <div class="team-option">
                                <input type="checkbox"
                                       name="favoriteTeams[]"
                                       value="<?php echo $team['team_id']; ?>"
                                       id="team-<?php echo $team['team_id']; ?>"
                                       class="team-checkbox"
                                       <?php echo in_array($team['team_id'], $userTeamIds) ? 'checked' : ''; ?>>
                                <label for="team-<?php echo $team['team_id']; ?>">
                                    <img src="<?= htmlspecialchars($team['logo_url']) ?>"
                                        alt="<?= htmlspecialchars($team['name']) ?>"
                                        loading="lazy"
                                        onerror="this.src='https://via.placeholder.com/100?text=<?= substr($team['short_name'], 0, 3) ?>'">
                                    <span><?php echo htmlspecialchars($team['name']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


                <div class="form-navigation">
                    <button type="submit" class="btn submit-btn">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    <a href="../index.php" class="btn cancel-btn">s
                        <i class="fas fa-times"></i> Hủy
                    </a>


                    <a href="<?php echo BASE_URL; ?>?action=logout" class="btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </div>
            </form>
        </div>
    </div>


    <script>
        window.userData = <?php echo json_encode($user); ?>;
        window.teamsData = <?php echo json_encode($allTeams); ?>;
    </script>
    <script src="../assets/js/detailUserController.js"></script>
</body>
</html>


<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();
include '../includes/master.php';
?>

