<?php
// Khởi động session
session_start();

// Bao gồm tệp cấu hình
include '../includes/config.php';

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error()]));
}
ob_start();
// Đặt tiêu đề trang
$title = "Quản lý Thống kê";

// Xử lý AJAX requests
// Xử lý AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $season_id = isset($_POST['season_id']) ? mysqli_real_escape_string($conn, $_POST['season_id']) : null;
    
    switch ($_POST['action']) {
        case 'manual_update_player_stats':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            // Gọi thủ tục cập nhật thống kê cầu thủ cho toàn bộ mùa giải
            $query = "SELECT DISTINCT p.player_id 
                     FROM players p 
                     JOIN matchevents me ON p.player_id = me.player_id 
                     JOIN matches m ON me.match_id = m.match_id 
                     WHERE m.season_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $season_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $updated_players = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $update_query = "CALL UpdatePlayerStatsForSeason(?, ?)";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ii", $row['player_id'], $season_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $updated_players++;
                }
                mysqli_stmt_close($update_stmt);
            }
            
            echo json_encode(['success' => true, 'message' => "Đã cập nhật thống kê cho $updated_players cầu thủ"]);
            exit;
            
        case 'manual_update_team_stats':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            // Gọi thủ tục cập nhật thống kê đội bóng cho toàn bộ mùa giải
            $query = "SELECT DISTINCT team_id FROM teams";
            $result = mysqli_query($conn, $query);
            
            $updated_teams = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $update_query = "CALL UpdateTeamStatsForSeason(?, ?)";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "ii", $row['team_id'], $season_id);
                if (mysqli_stmt_execute($stmt)) {
                    $updated_teams++;
                }
                mysqli_stmt_close($stmt);
            }
            
            echo json_encode(['success' => true, 'message' => "Đã cập nhật thống kê cho $updated_teams đội bóng"]);
            exit;

        case 'update_standings':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            $result = mysqli_query($conn, "CALL UpdateStandings($season_id)");
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật bảng xếp hạng thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)]);
            }
            exit;
            
        case 'get_top_scorers':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            $limit = $_POST['limit'] ?? 10;
            $query = "SELECT p.name as player_name, t.name as team_name, ps.goals, ps.assists, ps.matches_played, ps.total_goals
                     FROM playerstats ps
                     JOIN players p ON ps.player_id = p.player_id
                     JOIN teams t ON p.team_id = t.team_id
                     WHERE ps.season_id = ?
                     ORDER BY ps.total_goals DESC, ps.goals DESC
                     LIMIT ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            echo json_encode($data);
            exit;
            
        case 'get_strongest_teams':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            $limit = $_POST['limit'] ?? 10;
            $query = "SELECT t.name as team_name, ts.points, ts.wins, ts.draws, ts.losses, 
                            ts.goals_for, ts.goals_against, (ts.goals_for - ts.goals_against) as goal_difference,
                            ts.matches_played, ts.clean_sheets
                     FROM teamstats ts
                     JOIN teams t ON ts.team_id = t.team_id
                     WHERE ts.season_id = ?
                     ORDER BY ts.points DESC, goal_difference DESC, ts.goals_for DESC
                     LIMIT ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $season_id, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            echo json_encode($data);
            exit;
            
        case 'get_team_overview':
            $team_id = $_POST['team_id'] ?? null;
            if (!$team_id || !$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp team_id và season_id']);
                exit;
            }
            
            $query = "SELECT t.name as team_name, ts.*, 
                            (ts.goals_for - ts.goals_against) as goal_difference,
                            ROUND((ts.points / (ts.matches_played * 3)) * 100, 2) as points_percentage
                     FROM teamstats ts
                     JOIN teams t ON ts.team_id = t.team_id
                     WHERE ts.team_id = ? AND ts.season_id = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $team_id, $season_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $data = mysqli_fetch_assoc($result);
            echo json_encode($data ?: []);
            exit;
            
        case 'search_statistics':
            $search_term = mysqli_real_escape_string($conn, $_POST['search_term'] ?? '');
            $type = $_POST['type'] ?? 'player';
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            if ($type == 'player') {
                // Count total records
                $count_query = "SELECT COUNT(*) as total 
                              FROM playerstats ps 
                              JOIN players p ON ps.player_id = p.player_id 
                              JOIN teams t ON p.team_id = t.team_id 
                              WHERE ps.season_id = ? 
                              AND p.name LIKE ?";
                
                $search_param = "%$search_term%";
                $stmt = mysqli_prepare($conn, $count_query);
                mysqli_stmt_bind_param($stmt, "is", $season_id, $search_param);
                mysqli_stmt_execute($stmt);
                $count_result = mysqli_stmt_get_result($stmt);
                $total_rows = mysqli_fetch_assoc($count_result)['total'];
                $total_pages = ceil($total_rows / $per_page);
                
                // Get paginated data
                $query = "SELECT p.name as player_name, t.name as team_name, ps.* 
                         FROM playerstats ps 
                         JOIN players p ON ps.player_id = p.player_id 
                         JOIN teams t ON p.team_id = t.team_id 
                         WHERE ps.season_id = ? 
                         AND p.name LIKE ?
                         ORDER BY ps.total_goals DESC, ps.goals DESC
                         LIMIT ? OFFSET ?";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "isii", $season_id, $search_param, $per_page, $offset);
                
            } else {
                // Team statistics
                $count_query = "SELECT COUNT(*) as total 
                               FROM teamstats ts 
                               JOIN teams t ON ts.team_id = t.team_id 
                               WHERE ts.season_id = ? 
                               AND t.name LIKE ?";
                
                $search_param = "%$search_term%";
                $stmt = mysqli_prepare($conn, $count_query);
                mysqli_stmt_bind_param($stmt, "is", $season_id, $search_param);
                mysqli_stmt_execute($stmt);
                $count_result = mysqli_stmt_get_result($stmt);
                $total_rows = mysqli_fetch_assoc($count_result)['total'];
                $total_pages = ceil($total_rows / $per_page);
                
                $query = "SELECT t.name as team_name, ts.*, 
                                (ts.goals_for - ts.goals_against) as goal_difference
                         FROM teamstats ts 
                         JOIN teams t ON ts.team_id = t.team_id 
                         WHERE ts.season_id = ? 
                         AND t.name LIKE ?
                         ORDER BY ts.points DESC, goal_difference DESC
                         LIMIT ? OFFSET ?";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "isii", $season_id, $search_param, $per_page, $offset);
            }
            
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            
            echo json_encode([
                'data' => $data,
                'pagination' => [
                    'total_pages' => $total_pages,
                    'current_page' => $page,
                    'total_items' => $total_rows
                ]
            ]);
            exit;

        case 'get_statistics_summary':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            
            // Lấy tổng quan thống kê
            $summary_query = "SELECT 
                (SELECT COUNT(*) FROM playerstats WHERE season_id = ?) as total_players,
                (SELECT COUNT(*) FROM teamstats WHERE season_id = ?) as total_teams,
                (SELECT SUM(goals_for) FROM teamstats WHERE season_id = ?) as total_goals,
                (SELECT COUNT(*) FROM matches WHERE season_id = ? AND status = 'Completed') as completed_matches";
            
            $stmt = mysqli_prepare($conn, $summary_query);
            mysqli_stmt_bind_param($stmt, "iiii", $season_id, $season_id, $season_id, $season_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $summary = mysqli_fetch_assoc($result);
            echo json_encode($summary);
            exit;
    }
}

// Lấy danh sách mùa giải, ưu tiên mùa 2024/2025
$seasons_query = "SELECT * FROM seasons ORDER BY CASE WHEN name = '2024/2025' THEN 0 ELSE 1 END, start_date DESC";
$seasons_result = mysqli_query($conn, $seasons_query);
if (!$seasons_result) {
    echo '<div class="alert alert-danger">Lỗi truy vấn mùa giải: ' . mysqli_error($conn) . '</div>';
}
// Lấy danh sách đội bóng
$teams_query = "SELECT * FROM teams ORDER BY name";
$teams_result = mysqli_query($conn, $teams_query);
if (!$teams_result) {
    echo '<div class="alert alert-danger">Lỗi truy vấn đội bóng: ' . mysqli_error($conn) . '</div>';
}
if (!isset($_POST['action'])) {
    include '../includes/sidebar.php';
}

ob_end_flush();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/Stat.js" defer></script>

<style>
    :root {
    --primary-dark: #2c3e50;
    --primary-darker: #1a252f;
    --accent-orange: #FF5722;
    --accent-orange-light: rgba(255, 87, 34, 0.1);
    --accent-teal: #00BCD4;
    --accent-teal-light: rgba(0, 188, 212, 0.1);
    --text-white: #FFFFFF;
    --text-gray: #ecf0f1;
    --text-gray-dark: #bdc3c7;
    --card-bg: #34495e;
    --card-hover: #3d566e;
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 80px;
    --border-radius: 10箱;
    --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

body {
    background-color: var(--primary-dark);
    color: var(--text-white);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
}
.form-label {
    color: #1a252f;
}
.main-content {
    margin-left: var(--sidebar-width);
    margin-top: 20px;
    padding: 30px;
    flex: 1;
    transition: var(--transition);
}
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20 isoforms;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.filter-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.data-table {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn-custom {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
    color: white;
    border-radius: 25px;
    padding: 10px 25px;
    transition: all 0.3s ease;
}

.btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    color: white;
}

.loading {
    display: none;
    text-align: center;
    padding: 20px;
}

.spinner-border {
    color: #667eea;
}

.alert-custom {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stats-label {
    font-size: 1.1rem;
    opacity: 0.9;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination {
    display: flex;
    list-style: none;
    padding: 0;
}

.page-item {
    margin: 0 5px;
}

.page-link {
    color: #667eea;
    background-color: white;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.page-link:hover {
    background-color: #667eea;
    color: white;
    border-color: #667eea;
}

.page-item.active .page-link {
    background-color: #667eea;
    color: white;
    border-color: #667eea;
}

.page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: white;
    border-color: #dee2e6;
}
</style>
<main class="main-content">
<!-- Nội dung chính -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="display-4 mb-4 text-center">
                <i class="fas fa-chart-line me-3"></i>Quản lý Thống kê Bóng đá
            </h1>
        </div>
    </div>

    <!-- Bộ lọc và tìm kiếm -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label fw-bold">Mùa giải</label>
                <select class="form-select" id="season_select">
                    <?php 
                    mysqli_data_seek($seasons_result, 0); // Reset con trỏ
                    while ($season = mysqli_fetch_assoc($seasons_result)): 
                        $selected = ($season['name'] == '2024/2025') ? 'selected' : '';
                    ?>
                        <option value="<?= $season['season_id'] ?>" <?= $selected ?>>
                            <?= $season['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Đội bóng</label>
                <select class="form-select" id="team_select">
                    <option value="">Tất cả đội</option>
                    <?php 
                    mysqli_data_seek($teams_result, 0); // Reset con trỏ
                    while ($team = mysqli_fetch_assoc($teams_result)): 
                    ?>
                        <option value="<?= $team['team_id'] ?>"><?= $team['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Loại thống kê</label>
                <select class="form-select" id="stats_type">
                    <option value="player">Cầu thủ</option>
                    <option value="team">Đội bóng</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Tìm kiếm</label>
                <input type="text" class="form-control" id="search_input" placeholder="Nhập tên...">
            </div>
        </div>
        
    </div>

    <!-- Thông báo -->
    <div id="alert_container"></div>

    <!-- Loading spinner -->
    <div class="loading" id="loading">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
        <p class="mt-2">Đang xử lý...</p>
    </div>

    <!-- Biểu đồ -->
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-container">
                <h5 class="mb-3 text-center">
                    <i class="fas fa-trophy me-2"></i>Top 10 Cầu thủ ghi bàn
                </h5>
                <canvas id="topScorersChart"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-container">
                <h5 class="mb-3 text-center">
                    <i class="fas fa-medal me-2"></i>Top 10 Đội mạnh nhất
                </h5>
                <canvas id="strongestTeamsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bảng dữ liệu chi tiết -->
    <div class="data-table">
        <h5 class="mb-3">
            <i class="fas fa-table me-2"></i>Dữ liệu chi tiết
        </h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="stats_table">
                <thead class="table-dark">
                    <tr id="table_header">
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Đội</th>
                        <th>Số trận</th>
                        <th>Bàn thắng</th>
                        <th>Kiến tạo</th>
                        <th>Thẻ vàng</th>
                        <th>Thẻ đỏ</th>
                    </tr>
                </thead>
                <tbody id="stats_table_body">
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            Đang tải dữ liệu cho mùa giải 2024/2025...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="pagination-container" id="pagination_container">
            <ul class="pagination" id="pagination">
                <!-- Pagination links will be inserted here by JavaScript -->
            </ul>
        </div>
    </div>
</div>
</main>

