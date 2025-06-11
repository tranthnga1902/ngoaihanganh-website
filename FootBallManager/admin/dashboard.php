<?php
// Khởi động session
session_start();

require_once __DIR__ . '/../includes/config.php'; // File cấu hình chứa kết nối DB và BASE_URL

// Đặt tiêu đề trang
$title = "Thống kê";

// Kiểm tra quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login.php');
//     exit();
// }


// Lấy dữ liệu thống kê nhanh
function getQuickStats($conn) {
    $stats = ['users' => 0, 'news' => 0, 'teams' => 0, 'players' => 0];
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM Users"); $stats['users'] = mysqli_fetch_assoc($result)['total'];
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM News"); $stats['news'] = mysqli_fetch_assoc($result)['total'];
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM Teams"); $stats['teams'] = mysqli_fetch_assoc($result)['total'];
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM Players"); $stats['players'] = mysqli_fetch_assoc($result)['total'];
    return $stats;
}

// Hàm lấy thống kê cầu thủ
function getPlayerStats($conn, $field, $orderBy, $limit = 10) {
    
    $query = "
        SELECT
            p.name AS ten_cau_thu,
            t.name AS ten_doi,
            p.photo_url AS photo,
            ps.total_goals AS gia_tri
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        
        ORDER BY ps.total_goals DESC
        LIMIT {$limit}
    ";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Hàm lấy tổng bàn thắng của đội
function getTeamGoals($conn, $limit = 20) {
    $sql = "
        SELECT
            t.name AS ten_doi,
            t.logo_url AS logo,
            s.name AS ten_san,
            SUM(ps.goals) AS tong_ban_thang
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        JOIN Stadiums s ON t.stadium_id = s.stadium_id
        
        GROUP BY t.team_id, t.name
        ORDER BY tong_ban_thang DESC
        LIMIT {$limit}
    ";
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}



// Lấy trận đấu đã hoàn thành (3 trận gần nhất)
function getCompletedMatches($conn) {
    $current_date = date('Y-m-d H:i:s');
    $query = "SELECT m.match_id, m.match_date, m.home_team_score, m.away_team_score, t1.name as home_team, t2.name as away_team
              FROM Matches m
              JOIN Teams t1 ON m.home_team_id = t1.team_id
              JOIN Teams t2 ON m.away_team_id = t2.team_id
              WHERE m.match_date < '$current_date'
              ORDER BY m.match_date DESC LIMIT 100";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Lấy trận đấu đã lên lịch (3 trận sắp tới)
function getScheduledMatches($conn) {
    $current_date = date('Y-m-d H:i:s');
    $query = "SELECT m.match_id, m.match_date, t1.name as home_team, t2.name as away_team
              FROM Matches m
              JOIN Teams t1 ON m.home_team_id = t1.team_id
              JOIN Teams t2 ON m.away_team_id = t2.team_id
              WHERE m.match_date > '$current_date'
              ORDER BY m.match_date ASC LIMIT 20";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Lấy BXH CLB (top 5)
function getStandings($conn, $season_id) {
    return getTeamGoals($conn, 20); // Sử dụng getTeamGoals với limit = 20
}

// Lấy 2 cầu thủ xuất sắc nhất
function getTopTwoPlayers($conn, $season_id) {
    return getPlayerStats($conn, 'total_goals', 'total_goals', 2); // Lấy top 2 dựa trên goals
}

// Lấy dữ liệu hiệu suất tấn công
function getAttackPerformance($conn) {
    $intervals = [[0, 15], [16, 30], [31, 45], [46, 60], [61, 75], [76, 90]];
    $total_goals = []; $penalty_percent = [];
    foreach ($intervals as $interval) {
        $start = $interval[0]; $end = $interval[1];
        $query_goals = "SELECT COUNT(*) as total FROM MatchEvents WHERE event_type = 'goal' AND minute BETWEEN $start AND $end";
        $result_goals = mysqli_query($conn, $query_goals); $goals = mysqli_fetch_assoc($result_goals)['total'];
        $query_penalty = "SELECT COUNT(*) as total FROM MatchEvents WHERE event_type = 'penalty_scored' AND minute BETWEEN $start AND $end";
        $result_penalty = mysqli_query($conn, $query_penalty); $penalties = mysqli_fetch_assoc($result_penalty)['total'];
        $total_goals[] = $goals;
        $penalty_percent[] = $goals > 0 ? ($penalties / $goals) * 100 : 0;
    }
    return ['total_goals' => $total_goals, 'penalty_percent' => $penalty_percent];
}

// Lấy dữ liệu hiệu quả chuyền bóng (top 50)
function getPassEfficiency($conn, $season_id) {
    $sql = "SELECT p.name, ps.passes, ps.key_passes
              FROM PlayerStats ps
              JOIN Players p ON ps.player_id = p.player_id
              WHERE ps.season_id = $season_id AND ps.passes > 0
              ORDER BY ps.passes DESC LIMIT 50";
    $result = mysqli_query($conn, $sql);
    $players = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $data = [];
    foreach ($players as $player) {
        $pass_accuracy = $player['passes'] >= 0 ? ($player['key_passes'] / $player['passes']) * 100 : 0;
        $data[] = ['x' => $player['passes'], 'y' => $pass_accuracy, 'r' => 10, 'name' => $player['name']];
    }
    return $data;
}

// Lấy tin tức mới nhất (top 3)
function getLatestNews($conn) {
    $query = "SELECT n.title, n.publish_date, c.category_name, n.image_url
              FROM News n
              JOIN Categories c ON n.category_id = c.category_id
              ORDER BY n.publish_date DESC LIMIT 3";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Lấy dữ liệu
$season_id = 1; // Giả định season_id hiện tại
$quick_stats = getQuickStats($conn);
$completed_matches = getCompletedMatches($conn);
$scheduled_matches = getScheduledMatches($conn);
$standings = getStandings($conn, $season_id);
$top_two_players = getTopTwoPlayers($conn, $season_id);
$attack_performance = getAttackPerformance($conn);
$pass_efficiency = getPassEfficiency($conn, $season_id);
$latest_news = getLatestNews($conn);




ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Football Manager</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/dashboard.js" defer></script>
    <style>
        .user-info {
            
            background-color: var(--card-bg);
            background-image: url('<?php echo BASE_URL; ?>assets/img/icons/nen_user.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px var(--shadow);
            border-radius: 10px;
            margin: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 250px;
        }
    </style>
</head>
<body>
    <!-- Thanh thông tin người dùng -->
    

    <!-- Main Layout -->
    <div class="main-layout">
        <!--Sidebar-->
        <?php include '../includes/sidebar.php'?>
        <!-- Main Content -->
        <main class="main-content">
            <!-- Thanh thẻ thống kê -->
             <!-- Thanh thông tin người dùng -->
            <div class="user-info">
        </div>
            <section class="quick-stats">
                <h2>Tổng Quan</h2>
                <div class="stats-grid">
                    <div class="stat-card"><h3>Người dùng</h3><p><?php echo $quick_stats['users']; ?></p></div>
                    <div class="stat-card"><h3>Bài viết</h3><p><?php echo $quick_stats['news']; ?></p></div>
                    <div class="stat-card"><h3>CLB</h3><p><?php echo $quick_stats['teams']; ?></p></div>
                    <div class="stat-card"><h3>Cầu thủ</h3><p><?php echo $quick_stats['players']; ?></p></div>
                </div>
            </section>

            <!-- Bảng -->
            <section class="match-tabs">
                <div class="tabs">
                    
                    <div class="tab" data-tab="completed">Trận đấu đã hoàn thành</div>
                    <div class="tab" data-tab="scheduled">Trận đấu đã lên lịch</div>
                    <div class="tab" data-tab="standings">Tổng bàn thắng CLB</div>
                    <div class="tab" data-tab="top-players">Top 10 cầu thủ xuất sắc</div>
                </div>

                

                <!-- Tab: Trận đấu đã hoàn thành -->
                <div class="tab-content" id="completed">
                    <table>
                        <thead><tr><th>Đội nhà</th><th>Kết quả</th><th>Đội khách</th><th>Thời gian</th></tr></thead>
                        <tbody>
                            <?php foreach ($completed_matches as $match): ?>
                                <tr>
                                    <td><?php echo $match['home_team']; ?></td>
                                    <td><?php echo $match['home_team_score'] . ' - ' . $match['away_team_score']; ?></td>
                                    <td><?php echo $match['away_team']; ?></td>
                                    <td><?php echo date('H:i d/m/Y', strtotime($match['match_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tab: Trận đấu đã lên lịch -->
                <div class="tab-content" id="scheduled">
                    <table>
                        <thead><tr><th>Đội nhà</th><th>Đội khách</th><th>Thời gian</th></tr></thead>
                        <tbody>
                            <?php foreach ($scheduled_matches as $match): ?>
                                <tr>
                                    <td><?php echo $match['home_team']; ?></td>
                                    <td><?php echo $match['away_team']; ?></td>
                                    <td><?php echo date('H:i d/m/Y', strtotime($match['match_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tab: BXH CLB -->
                <div class="tab-content" id="standings">
                    <table>
                        <thead><tr><th>STT</th><th>Tên CLB</th><th>Tổng bàn thắng</th><th>Sân nhà</th></tr></thead>
                        <tbody>
                            <?php $index = 1; foreach ($standings as $team): ?>
                                <tr>
                                    <td><?php echo $index++; ?></td>
                                    <td><img src="../<?php echo htmlspecialchars ($team['logo']) ?? BASE_URL . 'assets/img/default_logo.png'; ?>" alt="Logo" width="20"><?php echo $team['ten_doi']; ?></td>
                                    <td><?php echo $team['tong_ban_thang']; ?></td>
                                    <td><?php echo $team['ten_san']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                            <!-- Tab: Top 10 cầu thủ xuất sắc -->
                <div class="tab-content" id="top-players">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Đội</th>
                                <th>Bàn thắng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Lấy dữ liệu top 10 cầu thủ xuất sắc (sử dụng hàm getPlayerStats từ code PHP)
                            $top_10_players = getPlayerStats($conn, 'goals', 'goals', 10); // Lấy top 10 dựa trên goals
                            foreach ($top_10_players as $player):
                            ?>
                                <tr>
                                    <td>
                                        <img src="../<?php echo htmlspecialchars($player['photo']) ?? BASE_URL . 'assets/img/default_player.png'; ?>" alt="Player" width="20">
                                        <?php echo $player['ten_cau_thu']; ?>
                                    </td>
                                    <td><?php echo $player['ten_doi']; ?></td>
                                    <td><?php echo $player['gia_tri']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <!-- Thanh bên phải -->
        <aside class="sidebar-right">
            <!-- Cầu thủ xuất sắc -->
            <div class="top-players">
                <h3>Cầu thủ xuất sắc</h3>
                <?php foreach ($top_two_players as $player): ?>
                    <div class="player">
                        <img src="../<?php echo htmlspecialchars ($player['photo']) ?? BASE_URL . 'assets/img/default_player.png'; ?>" alt="Player">
                        <div>
                            <p><strong><?php echo $player['ten_cau_thu']; ?></strong></p>
                            <p><?php echo $player['gia_tri']; ?> bàn</p>
                        </div>
                    </div>
                <?php endforeach; ?>

            <!-- Biểu đồ -->
            <div class="charts">
                <div class="chart-buttons">
                    <button class="chart-btn active" data-chart="attack">Hiệu suất tấn công</button>
                    <button class="chart-btn" data-chart="pass">Hiệu quả chuyền bóng</button>
                </div>
                <canvas id="attackChart" class="chart active"></canvas>
                <canvas id="passChart" class="chart" style="display: none;"></canvas>
            </div>

            <!-- Tin tức mới nhất -->
            <div class="latest-news">
                <h3>Tin tức mới nhất</h3>
                <?php foreach ($latest_news as $news): ?>
                    <img src="../<?php echo htmlspecialchars ($news['image_url']) ?? BASE_URL . 'assets/img/default_news.png'; ?>" alt="">
                    <p>[<?php echo $news['category_name']; ?>] <?php echo $news['title']; ?> - <?php echo date('d/m/Y', strtotime($news['publish_date'])); ?></p>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>

    <!-- Footer -->
    <!-- <?php include '../includes/footer.php'; ?> -->

    <script>
        const attackPerformance = <?php echo json_encode($attack_performance); ?>;
        const passEfficiency = <?php echo json_encode($pass_efficiency); ?>;
    </script>
</body>
</html>