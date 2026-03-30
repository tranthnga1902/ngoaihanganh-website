<?php
// Khởi động session
session_start();

// Bao gồm tệp cấu hình


require_once 'Controller/matchController.php';
require_once 'Controller/standingController.php';
require_once 'Controller/resultController.php';
require_once 'Controller/newsController.php';
require_once 'Controller/statisticsController.php';
//require_once 'Controller/videoController.php';

$controller = new standingController($conn);
$seasonName = isset($_GET['season']) ? $_GET['season'] : '2024/2025'; // Lấy từ tham số hoặc mặc định
$matchweek = isset($_GET['matchweek']) ? $_GET['matchweek'] : null;
$standings = $controller->getStandings($seasonName, $matchweek);

$newsController = new NewsController($conn);

//$videoController = new VideoController($conn);
//$videos = $videoController->getAllVideos() ?? []; // Đảm bảo $videos luôn là mảng



// Đặt tiêu đề trang
$title = "Trang Chủ - Ngoại hạng Anh";



ob_start();

// Hàm lấy từ đầu tiên trong tên CLB
function getFirstWord($string) {
    $words = explode(' ', trim($string));
    return !empty($words) ? $words[0] : $string;
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="assets/css/style-home.css">
    <style>
    </style>
    
</head>
<body>
    <!-- Banner -->
<div class="banner">
    <div class="banner-wrapper">
        <?php
        $banners = [
            'assets/img/banner/1.png',
            'assets/img/banner/2.png',
            'assets/img/banner/3.png'
        ];
        foreach ($banners as $banner) {
            echo "<div class='banner-slide'><img src='$banner' alt='Banner'></div>";
        }
        // Lặp lại slide đầu tiên để tạo hiệu ứng vòng tròn mượt mà
        echo "<div class='banner-slide'><img src='{$banners[0]}' alt='Banner'></div>";
        ?>
    </div>
    <button class="prev-btn">❮</button>
    <button class="next-btn">❯</button>
</div>
<section></section>

<!-- Lịch thi đấu vuông trong tuần này -->
<section class="s0">
        <h1>Sự Kiện Sắp Diễn Ra</h1>
        <?php if (empty($matches)): ?>
            <p class="no-matches">Không có trận đấu nào được tìm thấy. Vui lòng kiểm tra dữ liệu hoặc trạng thái trận đấu.</p>
        <?php else: ?>
            <div class="carousel">
                
                <div class="carousel-track">
                    <?php foreach ($matches as $match): ?>
                        <div class="match-card">
                            
                            <div class="date"><?php echo htmlspecialchars($match['date']); ?></div>
                            <div class="team">
                                <div class="team-home">
                                    <img src="<?php echo htmlspecialchars($match['home_logo']); ?>" alt="<?php echo htmlspecialchars($match['home_team']); ?>">
                                    <br>
                                    <span><?php echo htmlspecialchars($match['home_team']); ?></span>
                                </div>
                                
                                <div class="team-away">
                                    <img src="<?php echo htmlspecialchars($match['away_logo']); ?>" alt="<?php echo htmlspecialchars($match['away_team']); ?>">
                                    <br>
                                    <span><?php echo htmlspecialchars($match['away_team']); ?></span>
                                </div>
                            </div>

                            <div class="match-info">
                                
                                <div class="time"><?php echo htmlspecialchars($match['time']); ?></div>
                                <div class="score-placeholder">-</div>
                                <div class="stadium"><?php echo htmlspecialchars($match['stadium'] . ' - ' . $match['stadium_city']); ?></div>
                            </div>
                            
                            <a href="<?php echo BASE_URL; ?>user/viewMatchesDetailBefore.php?match_id=<?php echo htmlspecialchars($match['match_id']); ?>" class="view-more-0">XEM THÊM </a>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-button prev" disabled>◄</button>
                <button class="carousel-button back" disabled>◕</button>
                <button class="carousel-button next">►</button>
            </div>
        <?php endif; ?>
        
    </section>

    

<section class="s1"> 
    <h1 style="font-weight: 750 ; margin-left: 100px; ">Tin Tức Tổng Hợp</h1>
    <div class="page-container-1">
        
        <!-- Matches Section -->
        <div class="matches">
            
            <div class="matchweek-banner">
                <h1 class="matchweek-title">
                    <img src="assets/img/thongke/pl-main-logo.png" alt="Logo" width="40" height="40" style="object-fit: contain;">
                    Matchweek 16
                </h1>
            </div>
                <div class="match">
                    <?php if (empty($latestMatches)): ?>
                        <p class="no-matches">Không có trận đấu nào được tìm thấy.</p>
                    <?php else: ?>
                        <?php foreach ($matches_by_date as $date => $matches): ?>
                            <div class="date-group">
                                <div class="date-header"><?php echo htmlspecialchars($date); ?></div>
                                <ul>
                                    <?php foreach ($matches as $match): ?>
                                        <li class="match">
                                            <div class="match-row">
                                                <div class="team">
                                                    <span class="team-name"><?php echo htmlspecialchars(substr($match['home_team'], 0, 3)); ?></span>
                                                    <img src="<?php echo htmlspecialchars($match['home_team_logo']); ?>" alt="<?php echo htmlspecialchars($match['home_team']); ?>">
                                                </div>
                                                <div class="score-placeholder">
                                                    <?php echo htmlspecialchars($match['home_team_score'] . ' - ' . $match['away_team_score']); ?>
                                                </div>
                                                <div class="team away">
                                                    <img src="<?php echo htmlspecialchars($match['away_team_logo']); ?>" alt="<?php echo htmlspecialchars($match['away_team']); ?>">
                                                    <span class="team-name"><?php echo htmlspecialchars(substr($match['away_team'], 0, 3)); ?></span>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>user/matchResults.php?" class="view-more">Xem Thêm →</a>
                </div>     
        </div>
 
        <!-- Phần chứa nội dung tin tức chính và tin tức phụ -->
    <section class="container">
        <!-- Phần tin tức chính -->
        <section class="news-section">
            <article class="news-card">
            <!-- Thẻ tin tức chính -->
            <article class="news-card-1">
                <?php
                // Hàm hiển thị thẻ tin tức để giảm lặp lại mã
                function renderNewsCard($newsItem, $baseUrl, $wordLimit = 50, $isSideNews = false) {
                    if (!$newsItem) {
                        echo '<h2>Bài viết không tồn tại</h2><p>Không tìm thấy bài viết.</p>';
                        return;
                    }
                    $imagePath = $baseUrl . (strpos($newsItem['image_url'], '/') === 0 ? '' : '/') . ltrim($newsItem['image_url'], '/');
                    $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($newsItem['image_url'], '/');
                ?>
                    <a href="<?php echo BASE_URL; ?>user/viewNewsDetail.php?id=<?php echo htmlspecialchars($newsItem['news_id']); ?>" class="news-item-link" aria-label="Xem chi tiết bài viết <?php echo htmlspecialchars($newsItem['title']); ?>">
                        <?php if (!$isSideNews): ?>
                            <h2 class="news-title"><?php echo htmlspecialchars($newsItem['title']); ?></h2>
                        <?php endif; ?>
                        <?php if (!empty($newsItem['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($newsItem['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($newsItem['title']); ?>" class="news-image" loading="lazy">
                        
                        <?php endif; ?>
                        <div class="news-content">
                            <?php
                            $content = htmlspecialchars($newsItem['content']);
                            $words = explode(' ', $content);
                            $shortContent = implode(' ', array_slice($words, 0, $wordLimit));
                            echo nl2br($shortContent);
                            if (count($words) > $wordLimit) {
                                echo '... <a href="' . $baseUrl . 'user/viewNewsDetail.php?id=' . $newsItem['news_id'] . '" class="read-more">Đọc tiếp</a>';
                            }
                            ?>
                        </div>
                        <?php if ($isSideNews): ?>
                            <!-- <h2 class="news-title"><?php echo htmlspecialchars($newsItem['title']); ?></h2> -->
                        <?php endif; ?>
                    </a>
                <?php
                }
                
                // Lấy và hiển thị bài viết ID=6
                $newsItem = $newsController->getNewsById(6);
                $newsImages = $newsController->getNewsImages(6);
                $newsController->incrementViews(6);
                renderNewsCard($newsItem, BASE_URL, 40);
                ?>
            </article>

            <article class="news-card-2">
                <?php
                // Lấy và hiển thị bài viết ID=2
                $newsItem = $newsController->getNewsById(2);
                $newsImages = $newsController->getNewsImages(2);
                $newsController->incrementViews(2);
                renderNewsCard($newsItem, BASE_URL, 30);
                ?>
            </article>
        </article>

            <!-- Phần tin tức phụ -->
            <aside class="side-news">
                <!-- Thẻ tin tức phụ 1 -->
                <article class="side-news-card">
                    <?php
                    // Lấy và hiển thị bài viết ID=7
                    $newsItem = $newsController->getNewsById(7);
                    $newsImages = $newsController->getNewsImages(7);
                    $newsController->incrementViews(7);
                    renderNewsCard($newsItem, BASE_URL, 15, true);
                    ?>
                </article>

                <!-- Thẻ tin tức phụ 2 -->
                <article class="side-news-card">
                    <?php
                    // Lấy và hiển thị bài viết ID=1
                    $newsItem = $newsController->getNewsById(1);
                    $newsImages = $newsController->getNewsImages(1);
                    $newsController->incrementViews(1);
                    renderNewsCard($newsItem, BASE_URL, 50, true);
                    ?>
                </article>
                
            </aside>
        </section>
    </section>

        

    <div class="scoreboard-card">
            <h3 class="scoreboard-title">Premier League</h3>
            <?php if (empty($standings)): ?>
                <p style="text-align: center; color: #666;">Không có dữ liệu bảng xếp hạng nào được tìm thấy.</p>
                <p style="text-align: center; color: #999; font-size: 12px;">
                    Vui lòng kiểm tra nhật ký lỗi hoặc cơ sở dữ liệu cho mùa giải <?php echo htmlspecialchars($seasonName); ?>.
                </p>
            <?php else: ?>
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>CLB</th>
                            <th>ST</th>
                            <th>HS</th>
                            <th>Đ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $team): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($team['position'] ?? 'N/A'); ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($team['logo_url'] ?? '/images/default-logo.png'); ?>" alt="<?php echo htmlspecialchars($team['team_name'] ?? 'Unknown'); ?>" class="team-logo">
                                    <span class="team-abbr"><?php echo htmlspecialchars(getFirstWord($team['team_name'] ?? 'Unknown')); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($team['matches_played'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($team['goal_difference'] ?? '0'); ?></td>
                                <td><?php echo htmlspecialchars($team['points'] ?? '0'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- <section class="news-container">
        
        <article class="news-card">
            
            <div class="video-container">
                <iframe class="news-video" src="https://www.youtube.com/embed/sbdfjpeiG8A?si=wxTHIJZuFGLWrbIg" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen loading="lazy"></iframe>
            </div>
            <div class="news-content">
                <h2 class="news-title">Crazy One Off Stats in Premier League History!</h2>
                <p class="news-description">Trong suốt lịch sử Premier League, vô số khoảnh khắc và sự kiện đáng chú ý đã diễn ra. Gần đây, Justin Kluivert trở thành cầu thủ đầu tiên ghi hat-trick từ chấm phạt đền, bổ sung tên mình vào danh sách những thống kê kỳ lạ và đáng kinh ngạc.</p>
            </div>
        </article>
    </section> -->
    
<section class="s2">
        <div class="stat4">
            <div class="column-1">
                <img src="assets/img/thongke/salan.jpg" alt="Sân bóng đá">
            </div>
            <div class="column-2">
                <h2>Cầu Thủ Nổi Bật</h2>
                <div class="container-col2">
                    <!-- Top 1 cầu thủ ghi bàn -->
                    <div class="card-col">
                        <h3>🥇 Top 1 Ghi Bàn</h3>
                        <?php if (!empty($top_goal['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($top_goal['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
                        <?php endif; ?>
                        <p><span class="stats-icon"></span>Cầu thủ: <?php echo htmlspecialchars($top_goal['player_name'] ?? 'Không có dữ liệu', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><span class="stats-icon"></span>Đội: <?php echo htmlspecialchars($top_goal['team_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><span class="stats-icon"></span>Bàn thắng: <span class="highlight-number"><?php echo (int)($top_goal['total_goals1'] ?? 0); ?></span></p>
                        <p><span class="stats-icon"></span>Trận đấu: <span class="highlight-number"><?php echo (int)($top_goal['total_matches'] ?? 0); ?></span></p>
                    </div>

                    <!-- Top 1 cầu thủ kiến tạo -->
                    <div class="card-col">
                        <h3>🎯 Top 1 Kiến Tạo</h3>
                        <?php if (!empty($top_assist['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($top_assist['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
                        <?php endif; ?>
                        <p><span class="stats-icon"></span>Cầu thủ: <?php echo htmlspecialchars($top_assist['player_name'] ?? 'Không có dữ liệu', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><span class="stats-icon"></span>Đội: <?php echo htmlspecialchars($top_assist['team_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><span class="stats-icon"></span>Kiến tạo: <span class="highlight-number"><?php echo (int)($top_assist['total_assists'] ?? 0); ?></span></p>
                        <p><span class="stats-icon"></span>Trận đấu: <span class="highlight-number"><?php echo (int)($top_assist['total_matches'] ?? 0); ?></span></p>
                    </div>

                    <!-- Top 1 đội bóng thắng nhiều -->
                    <div class="card-col">
                        <h3>🏆 Top 1 Thắng</h3>
                        <?php if (!empty($top_team_wins[0]['logo']) && isset($top_team_wins[0])): ?>
                            <img src="<?php echo htmlspecialchars($top_team_wins[0]['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
                        <?php endif; ?>
                        <p><span class="stats-icon"></span>Đội: <?php echo htmlspecialchars($top_team_wins[0]['team_name'] ?? 'Không có dữ liệu', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><span class="stats-icon"></span>Thắng: <span class="highlight-number"><?php echo (int)($top_team_wins[0]['tong_thang'] ?? 0); ?></span></p>
                        <p><span class="stats-icon"></span>Sân nhà: <?php echo htmlspecialchars($top_team_wins[0]['ten_san'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <!-- Top 1 đội bóng ghi bàn -->
                    <div class="card-col">
                        <h3>⚽ Top 1 Ghi Bàn</h3>
                        <?php if (!empty($top_score['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($top_score['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
                        <?php endif; ?>
                        <p><span class="stats-icon"></span>Đội: <?php echo htmlspecialchars($top_score['team_name'] ?? 'Không có dữ liệu', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><span class="stats-icon"></span>Bàn thắng: <span class="highlight-number"><?php echo (int)($top_score['total_goals_for'] ?? 0); ?></span></p>
                        <p><span class="stats-icon"></span>Trận đấu: <span class="highlight-number"><?php echo (int)($top_score['total_matches'] ?? 0); ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
       

<section class="s3">
    
</section>
</body>
</html>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include 'includes/master.php';
?>