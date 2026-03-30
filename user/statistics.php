<?php
// Bao gồm tệp cấu hình
require_once '../includes/config.php';
require_once '../controller/statisticsController.php';
require_once '../controller/teamController.php';

// Khởi động session
session_start();

// Bắt đầu bộ đệm đầu ra
ob_start();

// Đặt tiêu đề trang
$title = "Thống kê";
?>



<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trung Tâm Thống Kê</title>
    <link rel="stylesheet" href="../assets/css/thongke.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        
        .stat-hero {
            background-color: <?php echo $background_color; ?> !important;
        }
        
    </style>
    
</head>
<body>
    <!-- Thêm header mới ngay dưới header gốc -->

    
     <!-- Container chính để giới hạn chiều rộng và căn giữa -->

<header class="stats-header">
    <div class="main-container">
        <div class="header-content">
             <div class="header-top">
            <h1>Trung Tâm Thống Kê</h1>
            <img class="page-header__sponsor-image" src="../assets/img/thongke/oracle_logo.webp" alt="logo_oracle">
            </div>
            <nav id="nav-menutk">
                <a href="<?php echo BASE_URL; ?>user/statistics.php">Tổng quan</a>
                <a href="<?php echo BASE_URL; ?>user/thongke/thongkecauthu.php">Thống Kê Cầu Thủ</a>
                <a href="<?php echo BASE_URL; ?>user/thongke/thongkeclb.php">Thống Kê Câu Lạc Bộ</a>
                <!-- <a href="<?php echo BASE_URL; ?>user/thongke/sosanh.php">Đối Đầu</a> -->
                
            </nav>
        </div>
    </div>
    <!-- <img class="logo" src="<?php echo BASE_URL; ?>assets/img/pl-main-logo.png" alt="logo" height="200px" width="200px" /> -->
</header>



<!-- Container chính cho phần body -->
    <div class="main-container">
        <h2>2024/25 Thống kê cầu thủ</h2>
        <div class="stat-container">
            <!-- Bàn Thắng -->
            <div class="top">
                <h3>Bàn Thắng</h3>
                <div class="stat-box">
            
                    <ol class="stat-list">
                        <?php foreach ($top_scorers as $index => $player): ?>
                            <?php if ($index === 0): ?>
                                <li class="stat-hero" style="background-color: <?php echo $team_colors[$player['team_name']] ?? '#f8f9fa'; ?>">
                                    <a href="<?php echo BASE_URL; ?>user/viewPlayerDetail.php?player_id=<?= htmlspecialchars($player['player_id']) ?>" class="w-100">
                                        
                                        <span class="rank"><?= $index + 1 ?></span>
                                        <span class="logo">
                                            <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($player['logo']) ?>" alt="<?= htmlspecialchars($player['team_name']) ?>" class="badge-image">
                                        </span>
                                        <div class="player-info">
                                            
                                            <span class="player"><?= htmlspecialchars($player['ten_cau_thu']) ?></span>
                                            <span class="team"><?= htmlspecialchars($player['team_name']) ?></span>
                                        </div>
                                        <span class="value"><?= $player['gia_tri'] ?></span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>user/viewPlayerDetail.php?player_id=<?= htmlspecialchars($player['player_id']) ?>" class="w-100">
                                        <span class="rank"><?= $index + 1 ?></span>
                                        <span class="logo">
                                            <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($player['logo']) ?>" alt="<?= htmlspecialchars($player['team_name']) ?>" class="badge-image">
                                        </span>
                                            <div class="player-info">
                                                

                                                <span class="player"><?= htmlspecialchars($player['ten_cau_thu']) ?></span>
                                                <span class="team"><?= htmlspecialchars($player['team_name']) ?></span>
                                            </div>
                                        <span class="value"><?= $player['gia_tri'] ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkecauthu.php?stat_type=goals" class="view-more">Xem Thêm →</a>
                </div>
            </div>

            <!-- Kiến Tạo -->
            <div class="top">
                <h3>Kiến Tạo</h3>
                <div class="stat-box">
                    <ol>
                        <?php foreach ($top_assists as $index => $player): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/viewPlayerDetail.php?player_id=<?= htmlspecialchars($player['player_id']) ?>" class="w-100">
                                    <span class="rank"><?= $index + 1 ?></span>
                                    <span class="logo">
                                        <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($player['logo']) ?>" alt="<?= htmlspecialchars($player['team_name']) ?>" class="badge-image">
                                    </span>
                                    <div class="player-info">
                                                    <span class="player"><?= htmlspecialchars($player['ten_cau_thu']) ?></span>
                                                    <span class="team"><?= htmlspecialchars($player['team_name']) ?></span>
                                                    
                                        </div>
                                    <span class="value"><?= $player['gia_tri'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkecauthu.php?stat_type=assists" class="view-more">Xem Thêm →</a>
                </div>
            </div>

            <!-- Thẻ Vàng -->
            <div class="top">
                <h3>Thẻ Vàng</h3>
                <div class="stat-box">
                    <ol>
                        <?php foreach ($top_yellow_cards as $index => $player): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/viewPlayerDetail.php?player_id=<?= htmlspecialchars($player['player_id']) ?>" class="w-100">
                                    <span class="rank"><?= $index + 1 ?></span>
                                    <span class="logo">
                                        <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($player['logo']) ?>" alt="<?= htmlspecialchars($player['team_name']) ?>" class="badge-image">
                                    </span>
                                    <div class="player-info">
                                        <span class="player"><?= htmlspecialchars($player['ten_cau_thu']) ?></span>
                                        <span class="team"><?= htmlspecialchars($player['team_name']) ?></span>                   
                                    </div>
                                    <span class="value"><?= $player['gia_tri'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkecauthu.php?stat_type=yellow_cards" class="view-more">Xem Thêm →</a>
                </div>
            </div>

            <!-- Giữ Sạch Lưới -->
            <div class="top">
                <h3>Giữ Sạch Lưới</h3>
                <div class="stat-box">
                    <ol>
                        <?php foreach ($top_cleansheets as $index => $player): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/viewPlayerDetail.php?player_id=<?= htmlspecialchars($player['player_id']) ?>" class="w-100">
                                    <span class="rank"><?= $index + 1 ?></span>
                                    <span class="logo">
                                        <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($player['logo']) ?>" alt="<?= htmlspecialchars($player['team_name']) ?>" class="badge-image">
                                    </span>
                                    <div class="player-info">
                                        <span class="player"><?= htmlspecialchars($player['ten_cau_thu']) ?></span>
                                        <span class="team"><?= htmlspecialchars($player['team_name']) ?></span>                 
                                    </div>
                                    <span class="value"><?= $player['gia_tri'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkecauthu.php?stat_type=clean_sheets" class="view-more">Xem Thêm →</a>
                </div>
            </div>
        </div>

        <h2>Thống Kê Câu Lạc Bộ Ngoại Hạng Anh</h2>
        <div class="stat-container">
            <!-- Bàn Thắng -->
            <div class="top">
            <h3>Bàn Thắng</h3>
            <div class="stat-box">
                <ol class="stat-list">
                    <?php foreach ($top_team_goals as $index => $team): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>user/viewTeamDetail.php?team_id=<?= htmlspecialchars($team['team_id']) ?>" class="w-100">
                                <span class="rank"><?= $index + 1 ?></span>
                                <span class="logo">
                                        <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($team['logo']) ?>" alt="<?= htmlspecialchars($team['team_name']) ?>" class="badge-image" onerror="this.src='https://via.placeholder.com/25';">
                                </span>
                                <div class="team-info"> 
                                    <span class="player"><?= htmlspecialchars($team['team_name']) ?></span>
                                    <span class="san"><?= htmlspecialchars($team['ten_san']) ?></span>
                                </div>
                                <span class="value"><?= $team['tong_ban_thang'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <a href="<?php echo BASE_URL; ?>user/thongke/thongkeclb.php?stat_type=goals" class="view-more">Xem Thêm →</a>
            </div>
            </div>

            <!-- Trận Thắng -->
            <div class="top">
                <h3>Trận Thắng</h3>
                <div class="stat-box">
                    <ol>
                        <?php foreach ($top_team_wins as $index => $team): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/viewTeamDetail.php?team_id=<?= htmlspecialchars($team['team_id']) ?>" class="w-100">
                                <span class="rank"><?= $index + 1 ?></span>
                                <span class="logo">
                                    <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($team['logo']) ?>" alt="<?= htmlspecialchars($team['team_name']) ?>" class="badge-image" onerror="this.src='https://via.placeholder.com/25';">
                                </span>
                                <div class="team-info"> 
                                    <span class="player"><?= htmlspecialchars($team['team_name']) ?></span>
                                    <span class="san"><?= htmlspecialchars($team['ten_san']) ?></span>
                                </div>
                                
                                <span class="value"><?= $team['tong_thang'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkeclb.php?stat_type=wins" class="view-more">Xem Thêm →</a>
                </div>
            </div>

            <!-- Trận Thua -->
            <div class="top">
                <h3>Trận Thua</h3>
                <div class="stat-box">
                    <ol>
                        <?php foreach ($top_team_losses as $index => $team): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/viewTeamDetail.php?team_id=<?= htmlspecialchars($team['team_id']) ?>" class="w-100">
                                <span class="rank"><?= $index + 1 ?></span>
                                <span class="logo">
                                    <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($team['logo']) ?>" alt="<?= htmlspecialchars($team['team_name']) ?>" class="badge-image" onerror="this.src='https://via.placeholder.com/25';">
                                </span>
                                <div class="team-info"> 
                                    <span class="player"><?= htmlspecialchars($team['team_name']) ?></span>
                                    <span class="san"><?= htmlspecialchars($team['ten_san']) ?></span>
                                </div>
                                
                                <span class="value"><?= $team['tong_thua'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkeclb.php?stat_type=losses" class="view-more">Xem Thêm →</a>
                </div>
            </div>

            <!-- Điểm -->
            <div class="top">
                <h3>Điểm</h3>
                <div class="stat-box">
                    <ol>
                        <?php foreach ($top_team_points as $index => $team): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/viewTeamDetail.php?team_id=<?= htmlspecialchars($team['team_id']) ?>" class="w-100">
                                <span class="rank"><?= $index + 1 ?></span>
                                <span class="logo">
                                    <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($team['logo']) ?>" alt="<?= htmlspecialchars($team['team_name']) ?>" class="badge-image" onerror="this.src='https://via.placeholder.com/25';">
                                </span>
                                <div class="team-info"> 
                                    <span class="player"><?= htmlspecialchars($team['team_name']) ?></span>
                                    <span class="san"><?= htmlspecialchars($team['ten_san']) ?></span>
                                </div>
                                
                                
                                <span class="value"><?= $team['tong_diem'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <a href="<?php echo BASE_URL; ?>user/thongke/thongkeclb.php?stat_type=yellow_cards" class="view-more">Xem Thêm →</a>
                </div>
            </div>
        </div>

        <div id="them">
            <a href="#">xem thêm các thống kê</a>
        </div>
    </div>

    
        
    

        
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include(__DIR__ . '/../includes/master.php');
?>
