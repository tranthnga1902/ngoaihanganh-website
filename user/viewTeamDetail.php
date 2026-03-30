<?php
require_once '../includes/config.php';
require_once '../controller/teamController.php';

$title = "Chi tiết câu lạc bộ";


if (!isset($_GET['team_id']) || !is_numeric($_GET['team_id'])) {
    header("Location: viewTeams.php");
    exit();
}


$team_id = (int)$_GET['team_id'];
$team = getTeamById($conn, $team_id);
$news = getNewsByTeam($conn, $team_id);


if (!$team) {
    header("Location: viewTeams.php");
    exit();
}


// Màu sắc theo đội với phối màu đẹp hơn
$teamColors = [
    'Manchester United' => [
        'primary' => 'rgb(164, 0, 30)', // Đỏ MU
        'secondary' => 'rgb(251, 225, 34)', // Vàng
        'text' => '#FFFFFF'
    ],
    'Liverpool FC' => [
        'primary' => '#C8102E', // Đỏ sẫm
        'secondary' => '#00B2A9', // Xanh ngọc
        'text' => '#F6EB61' // Vàng nhạt
    ],
    'Chelsea FC' => [
        'primary' => '#034694', // Xanh đậm
        'secondary' => '#DBA111', // Vàng kim loại
        'text' => '#EE242C' // Đỏ phụ
    ],
    'Arsenal FC' => [
        'primary' => '#EF0107', // Đỏ tươi
        'secondary' => '#063672', // Xanh navy
        'text' => '#FFFFFF'
    ],
    'Manchester City' => [
        'primary' => '#6CABDD', // Xanh da trời nhạt
        'secondary' => '#FFCE65', // Vàng pastel
        'text' => '#1C2C5B' // Xanh đậm
    ],
    'Tottenham Hotspur' => [
        'primary' => '#132257', // Xanh navy
        'secondary' => '#FFFFFF', // Trắng
        'text' => '#EEC73E' // Vàng
    ],
    'Newcastle United' => [
        'primary' => 'rgb(84, 83, 83)', // Đen xám
        'secondary' => 'rgb(45, 134, 211)', // Xanh da trời
        'text' => '#FFFFFF'
    ],
    'Aston Villa' => [
        'primary' => 'rgb(141, 39, 88)', // Tím đỏ
        'secondary' => 'rgb(103, 13, 54)', // Đỏ rượu đậm
        'text' => '#95BFE5' // Xanh pastel
    ],
    'Brighton & Hove Albion FC' => [
        'primary' => '#0057B8', // Xanh biển
        'secondary' => '#FFFFFF', // Trắng
        'text' => '#FFCD00' // Vàng
    ],
    'Brentford' => [
        'primary' => '#E30613', // Đỏ
        'secondary' => '#FFFFFF', // Trắng
        'text' => '#000000' // Đen
    ],
    'Fulham' => [
        'primary' => 'rgb(190, 190, 190)', // Xám
        'secondary' => 'rgb(0, 0, 0)', // Đen
        'text' => '#CC0000' // Đỏ phụ
    ],
    'Crystal Palace' => [
        'primary' => '#1B458F', // Xanh đậm
        'secondary' => '#C4122E', // Đỏ
        'text' => '#A7A5A6' // Xám
    ],
    'Everton' => [
        'primary' => '#003399', // Xanh hoàng gia
        'secondary' => '#FFFFFF', // Trắng
        'text' => '#FFCC00' // Vàng
    ],
    'West Ham United' => [
        'primary' => '#7A263A', // Đỏ rượu
        'secondary' => '#1BB1E7', // Xanh da trời
        'text' => '#F3D459' // Vàng
    ],
    'Wolverhampton Wanderers' => [
        'primary' => '#FDB913', // Vàng
        'secondary' => '#000000', // Đen
        'text' => '#FFFFFF' // Trắng
    ],
    'AFC Bournemouth' => [
        'primary' => '#DA292C', // Đỏ
        'secondary' => '#000000', // Đen
        'text' => '#B50E12' // Đỏ đậm
    ],
    'Leicester City' => [
        'primary' => '#003090', // Xanh hoàng gia
        'secondary' => '#FDBE11', // Vàng
        'text' => '#FFFFFF' // Trắng
    ],
    'Southampton' => [
        'primary' => 'rgb(128, 18, 36)', // Đỏ
        'secondary' => 'rgb(22, 49, 114)', // Xanh navy
        'text' => '#FFD700' // Vàng
    ],
    'Nottingham Forest' => [
        'primary' => 'rgb(192, 45, 79)', // Đỏ
        'secondary' => 'rgb(255, 255, 255)', // Trắng
        'text' => '#000000' // Đen
    ],
    'Ipswich Town' => [
        'primary' => '#0057B8', // Xanh lam
        'secondary' => '#FFFFFF', // Trắng
        'text' => '#FF0000' // Đỏ
    ],
    'default' => [
        'primary' => '#1a237e', // Xanh đậm
        'secondary' => '#ffc107', // Vàng
        'text' => '#ffffff' // Trắng
    ]
];


// Mảng liên kết mạng xã hội của các CLB
$socialLinks = [
    'facebook' => $team['facebook'] ?: '#',
    'twitter' => $team['twitter'] ?: '#',
    'instagram' => $team['instagram'] ?: '#',
    'youtube' => $team['youtube'] ?: '#',
    'website' => $team['website'] ?: '#',
];
// Lấy màu sắc cho đội bóng hiện tại
$colors = $teamColors[$team['name']] ?? $teamColors['default'];


ob_start();
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết câu lạc bộ - <?php echo htmlspecialchars($team['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/components/card.css">
       <style>
        :root {
            --team-primary: <?php echo $colors['primary']; ?>;
            --team-secondary: <?php echo $colors['secondary']; ?>;
            --team-text: <?php echo $colors['text']; ?>;
        }
    </style>
</head>
<body>
   


    <div class="container">
        <h2 style="color: var(--team-primary);"><?php echo htmlspecialchars($team['name']); ?></h2>
        <div class="team-detail" style="border-color: var(--team-primary);">
            <!-- Thông tin cơ bản -->
            <div class="team-info">
                <!-- Ảnh logo đội -->
                <div class="team-logo-wrapper">
                    <img src="../<?php echo htmlspecialchars($team['logo_url']); ?>" alt="<?php echo htmlspecialchars($team['name']); ?>" class="team-logo-large">
                   
                    <!-- Các liên kết mạng xã hội -->
                    <div class="social-links">
                        <a href="<?php echo htmlspecialchars($socialLinks['facebook']); ?>" class="social-icon facebook" title="Facebook" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?php echo htmlspecialchars($socialLinks['twitter']); ?>" class="social-icon twitter" title="Twitter" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="<?php echo htmlspecialchars($socialLinks['instagram']); ?>" class="social-icon instagram" title="Instagram" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="<?php echo htmlspecialchars($socialLinks['youtube']); ?>" class="social-icon youtube" title="YouTube" target="_blank"><i class="fab fa-youtube"></i></a>
                        <a href="<?php echo htmlspecialchars($socialLinks['website']); ?>" class="social-icon website" title="Website" target="_blank"><i class="fas fa-globe"></i></a>
                    </div>
                </div>
               
                <div class="team-info-details">
                    <p><strong>Tên viết tắt:</strong> <?php echo htmlspecialchars($team['short_name'] ?? ''); ?></p>
                    <p><strong>Thành phố:</strong> <?php echo htmlspecialchars($team['city'] ?? ''); ?></p>
                    <p><strong>Năm thành lập:</strong> <?php echo htmlspecialchars($team['founded_year'] ?? ''); ?></p>
                    <p><strong>Sân nhà:</strong> <?php echo htmlspecialchars($team['stadium_name'] ?? ''); ?></p>
                    <?php if ($team['stadium_photo']): ?>
                        <img src="../<?php echo htmlspecialchars($team['stadium_photo']); ?>" alt="<?php echo htmlspecialchars($team['stadium_name']); ?>" class="stadium-photo">
                    <?php endif; ?>
                    <p><strong>Sức chứa sân:</strong> <?php echo number_format($team['stadium_capacity']); ?> chỗ</p>
                    <p><strong>Địa chỉ sân:</strong> <?php echo htmlspecialchars($team['stadium_address']); ?>, <?php echo htmlspecialchars($team['stadium_city']); ?></p>
                    <p><strong>Năm xây dựng sân:</strong> <?php echo htmlspecialchars($team['stadium_built_year'] ?? ''); ?></p>
                </div>
               
                <!-- Ảnh HLV -->
                <div class="manager-wrapper">
                    <?php if ($team['manager_photo']): ?>
                    <img src="../<?php echo htmlspecialchars($team['manager_photo'] ?? ''); ?>" alt="" class="manager_photo">
                    <?php endif; ?>
                    <p><strong>HLV:</strong> <?php echo htmlspecialchars($team['manager_name'] ?? ''); ?></p>
                    <div class="manager-tooltip">
                        <p><strong></strong> <?php echo htmlspecialchars($team['manager_infor'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
           
            <hr style="background-color: var(--team-primary);">
           
            <div class="content-wrapper">
                <!-- Các liên kết nhanh như trong hình mẫu -->
                <div class="quick-links">
                    <h3 style="color: var(--team-primary);">Liên kết nhanh</h3>
                    <ul>
                        <li><a href="#" class="quick-link"><i class="fas fa-external-link-alt"></i> Visit Official Website</a></li>
                        <li><a href="#" class="quick-link"><i class="fas fa-shopping-bag"></i> Official Club Shop</a></li>
                        <li><a href="#" class="quick-link"><i class="fas fa-ticket-alt"></i> Buy Matchday Hospitality</a></li>
                        <li><a href="#" class="quick-link"><i class="fas fa-id-card"></i> Arsenal Digital Membership</a></li>
                        <li><a href="#" class="quick-link"><i class="fas fa-ticket-alt"></i> Buy Arsenal Tickets</a></li>
                        <li><a href="#" class="quick-link"><i class="fas fa-info-circle"></i> Club Ticket Information</a></li>
                    </ul>
                </div>
               
                <!-- Tin tức liên quan -->
                <div class="news-section">
                    <h3 style="color: var(--team-primary);">Tin tức về <?php echo htmlspecialchars($team['name']); ?></h3>
                    <div class="news-list">
                        <?php if (empty($news)): ?>
                            <p>Không có tin tức nào về câu lạc bộ này.</p>
                        <?php else: ?>
                            <?php foreach ($news as $article): ?>
                                <?php
                                    $fullContent = htmlspecialchars($article['content']);
                                    $shortContent = substr($fullContent, 0, 200);
                                    $newsId = $article['news_id'];
                                ?>
                                <div class="news-item">
                                    <div class="news-content">
                                        <h4><a href="viewNewsDetail.php?news_id=<?php echo $newsId; ?>"><?php echo htmlspecialchars($article['title']); ?></a></h4>
                                        <p id="short-<?php echo $newsId; ?>"><?php echo $shortContent; ?>...</p>
                                        <p id="full-<?php echo $newsId; ?>" style="display: none;"><?php echo nl2br($fullContent); ?></p>
                                        <button class="toggle-btn" data-news-id="<?php echo $newsId; ?>" onclick="toggleContent(<?php echo $newsId; ?>)" style="color: var(--team-primary);">Xem thêm</button>
                                        <p><strong>Ngày đăng:</strong> <?php echo date('d/m/Y', strtotime($article['publish_date'])); ?> |
                                        <strong>Lượt xem:</strong> <span class="views-count" data-news-id="<?php echo $newsId; ?>"><?php echo $article['views']; ?></span></p>
                                    </div>
                                    <?php if ($article['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="news-image">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
    function toggleContent(id) {
        const shortPara = document.getElementById('short-' + id);
        const fullPara = document.getElementById('full-' + id);
        const btn = event.target;


        if (shortPara.style.display === 'none') {
            shortPara.style.display = 'block';
            fullPara.style.display = 'none';
            btn.textContent = 'Xem thêm';
        } else {
            shortPara.style.display = 'none';
            fullPara.style.display = 'block';
            btn.textContent = 'Thu gọn';
        }
    }
   
   


    </script>
    <script src="../assets/js/news.js"></script>
</body>
</html>


<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();


// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>
