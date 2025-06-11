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
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $season_id = isset($_POST['season_id']) ? mysqli_real_escape_string($conn, $_POST['season_id']) : null;
    
    switch ($_POST['action']) {
        case 'update_all_stats':
            if (!$season_id) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp season_id']);
                exit;
            }
            $result = mysqli_query($conn, "CALL UpdateAllStats('$season_id')");
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật thành công tất cả thống kê']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)]);
            }
            exit;
            
        case 'update_player_stats':
            $season_id = $_POST['season_id'];
            $result = mysqli_query($conn, "CALL UpdatePlayerStats($season_id)");
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật thành công thống kê cầu thủ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)]);
            }
            exit;
            
        case 'update_team_stats':
            $season_id = $_POST['season_id'];
            $result = mysqli_query($conn, "CALL UpdateTeamStats($season_id)");
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật thành công thống kê đội bóng']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)]);
            }
            exit;
            
        case 'get_top_scorers':
            $season_id = $_POST['season_id'];
            $limit = $_POST['limit'] ?? 10;
            $result = mysqli_query($conn, "CALL GetTopScorers($season_id, $limit)");
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            echo json_encode($data);
            exit;
            
        case 'get_strongest_teams':
            $season_id = $_POST['season_id'];
            $limit = $_POST['limit'] ?? 10;
            $result = mysqli_query($conn, "CALL GetStrongestTeams($season_id, $limit)");
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            echo json_encode($data);
            exit;
            
        case 'get_team_overview':
            $team_id = $_POST['team_id'];
            $season_id = $_POST['season_id'];
            $result = mysqli_query($conn, "CALL GetTeamOverview($team_id, $season_id)");
            $data = mysqli_fetch_assoc($result);
            echo json_encode($data);
            exit;
            
        case 'search_statistics':
            $search_term = $_POST['search_term'];
            $type = $_POST['type']; // 'player' or 'team'
            $season_id = $_POST['season_id'];
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            if ($type == 'player') {
                // Count total records
                $count_query = "SELECT COUNT(*) as total 
                              FROM playerstats ps 
                              JOIN players p ON ps.player_id = p.player_id 
                              JOIN teams t ON p.team_id = t.team_id 
                              WHERE ps.season_id = $season_id 
                              AND p.name LIKE '%$search_term%'";
                
                $count_result = mysqli_query($conn, $count_query);
                $total_rows = mysqli_fetch_assoc($count_result)['total'];
                $total_pages = ceil($total_rows / $per_page);
                
                // Get paginated data
                $query = "SELECT p.name as player_name, t.name as team_name, ps.* 
                         FROM playerstats ps 
                         JOIN players p ON ps.player_id = p.player_id 
                         JOIN teams t ON p.team_id = t.team_id 
                         WHERE ps.season_id = $season_id 
                         AND p.name LIKE '%$search_term%' 
                         ORDER BY ps.total_goals DESC
                         LIMIT $per_page OFFSET $offset";
            } else {
                // Similar for teams if needed
                $count_query = "SELECT COUNT(*) as total 
                               FROM teamstats ts 
                               JOIN teams t ON ts.team_id = t.team_id 
                               WHERE ts.season_id = $season_id 
                               AND t.name LIKE '%$search_term%'";
                
                $count_result = mysqli_query($conn, $count_query);
                $total_rows = mysqli_fetch_assoc($count_result)['total'];
                $total_pages = ceil($total_rows / $per_page);
                
                $query = "SELECT t.name as team_name, ts.* 
                         FROM teamstats ts 
                         JOIN teams t ON ts.team_id = t.team_id 
                         WHERE ts.season_id = $season_id 
                         AND t.name LIKE '%$search_term%' 
                         ORDER BY ts.points DESC
                         LIMIT $per_page OFFSET $offset";
            }
            
            $result = mysqli_query($conn, $query);
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
        <div class="row mt-3">
            <div class="col-12 text-center">
                <button class="btn btn-custom me-2" onclick="updateAllStats()">
                    <i class="fas fa-sync-alt me-2"></i>Cập nhật tất cả
                </button>
                <button class="btn btn-custom me-2" onclick="updatePlayerStats()">
                    <i class="fas fa-user me-2"></i>Cập nhật cầu thủ
                </button>
                <button class="btn btn-custom me-2" onclick="updateTeamStats()">
                    <i class="fas fa-users me-2"></i>Cập nhật đội bóng
                </button>
                <button class="btn btn-custom" onclick="searchStatistics()">
                    <i class="fas fa-search me-2"></i>Tìm kiếm
                </button>
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

<script>
// Global variables
let topScorersChart, strongestTeamsChart;
let currentPage = 1;
let totalPages = 1;
let currentSearchTerm = '';
let currentStatsType = 'player';
let currentSeasonId = null;

// Initialize charts
function initCharts() {
    const ctx1 = document.getElementById('topScorersChart').getContext('2d');
    topScorersChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Bàn thắng',
                data: [],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 10,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Top 10 Cầu thủ ghi bàn mùa 2024/2025',
                    color: '#333',
                    font: {
                        size: 18
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Số bàn thắng'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Cầu thủ'
                    }
                }
            }
        }
    });

    const ctx2 = document.getElementById('strongestTeamsChart').getContext('2d');
    strongestTeamsChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4BC0C0', '#FF6384'
                ],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                title: {
                    display: true,
                    text: 'Top 10 Đội mạnh nhất mùa 2024/2025',
                    color: '#333',
                    font: {
                        size: 18
                    }
                }
            }
        }
    });
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('#alert_container').html(alertHtml);
    
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

// Update all statistics
function updateAllStats() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    $('#loading').show();
    
    $.post('', {
        action: 'update_all_stats',
        season_id: seasonId
    }, function(response) {
        $('#loading').hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadStatistics();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        $('#loading').hide();
        showAlert('Lỗi AJAX: ' + textStatus, 'danger');
    });
}

// Update player statistics
function updatePlayerStats() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    $('#loading').show();
    
    $.post('', {
        action: 'update_player_stats',
        season_id: seasonId
    }, function(response) {
        $('#loading').hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadTopScorers();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json');
}

// Update team statistics
function updateTeamStats() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    $('#loading').show();
    
    $.post('', {
        action: 'update_team_stats',
        season_id: seasonId
    }, function(response) {
        $('#loading').hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadStrongestTeams();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json');
}

// Load top scorers
function loadTopScorers() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) return;

    $.post('', {
        action: 'get_top_scorers',
        season_id: seasonId,
        limit: 10
    }, function(data) {
        const labels = data.map(item => item.player_name);
        const goals = data.map(item => parseInt(item.total_goals));
        
        topScorersChart.data.labels = labels;
        topScorersChart.data.datasets[0].data = goals;
        topScorersChart.update();
    }, 'json');
}

// Load strongest teams
function loadStrongestTeams() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) return;

    $.post('', {
        action: 'get_strongest_teams',
        season_id: seasonId,
        limit: 10
    }, function(data) {
        const labels = data.map(item => item.team_name);
        const points = data.map(item => parseInt(item.points));
        
        strongestTeamsChart.data.labels = labels;
        strongestTeamsChart.data.datasets[0].data = points;
        strongestTeamsChart.update();
    }, 'json');
}

// Search statistics with pagination
function searchStatistics(page = 1) {
    const seasonId = $('#season_select').val() || currentSeasonId;
    const searchTerm = $('#search_input').val();
    const type = $('#stats_type').val();
    
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    // Update global variables
    currentPage = page;
    currentSearchTerm = searchTerm;
    currentStatsType = type;
    currentSeasonId = seasonId;

    $('#loading').show();
    
    $.post('', {
        action: 'search_statistics',
        season_id: seasonId,
        search_term: searchTerm,
        type: type,
        page: page
    }, function(response) {
        $('#loading').hide();
        displaySearchResults(response.data, type);
        updatePagination(response.pagination);
    }, 'json');
}

// Display search results
function displaySearchResults(data, type) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="8" class="text-center text-muted">Không tìm thấy dữ liệu</td></tr>';
    } else {
        data.forEach((item, index) => {
            const rowNumber = (currentPage - 1) * 20 + index + 1;
            
            if (type === 'player') {
                html += `
                    <tr>
                        <td>${rowNumber}</td>
                        <td><strong>${item.player_name}</strong></td>
                        <td>${item.team_name}</td>
                        <td>${item.matches_played || 0}</td>
                        <td><span class="badge bg-success">${item.total_goals || 0}</span></td>
                        <td><span class="badge bg-info">${item.assists || 0}</span></td>
                        <td><span class="badge bg-warning">${item.yellow_cards || 0}</span></td>
                        <td><span class="badge bg-danger">${item.red_cards || 0}</span></td>
                    </tr>
                `;
            } else {
                html += `
                    <tr>
                        <td>${rowNumber}</td>
                        <td><strong>${item.team_name}</strong></td>
                        <td>-</td>
                        <td>${item.matches_played || 0}</td>
                        <td><span class="badge bg-success">${item.wins || 0}</span></td>
                        <td><span class="badge bg-info">${item.draws || 0}</span></td>
                        <td><span class="badge bg-warning">${item.losses || 0}</span></td>
                        <td><span class="badge bg-primary">${item.points || 0}</span></td>
                    </tr>
                `;
            }
        });
    }
    
    $('#stats_table_body').html(html);
    
    // Update table header
    if (type === 'team') {
        $('#table_header').html(`
            <th>STT</th>
            <th>Tên đội</th>
            <th>-</th>
            <th>Số trận</th>
            <th>Thắng</th>
            <th>Hòa</th>
            <th>Thua</th>
            <th>Điểm</th>
        `);
    } else {
        $('#table_header').html(`
            <th>STT</th>
            <th>Tên</th>
            <th>Đội</th>
            <th>Số trận</th>
            <th>Bàn thắng</th>
            <th>Kiến tạo</th>
            <th>Thẻ vàng</th>
            <th>Thẻ đỏ</th>
        `);
    }
}

// Update pagination controls
function updatePagination(pagination) {
    const { total_pages, current_page, total_items } = pagination;
    totalPages = total_pages;
    
    let html = '';
    const maxVisiblePages = 5; // Number of visible page links
    
    if (total_pages <= 1) {
        $('#pagination_container').hide();
        return;
    }
    
    $('#pagination_container').show();
    
    // Previous button
    html += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${current_page - 1})" aria-label="Previous">
            <span aria-hidden="true">«</span>
        </a>
    </li>`;
    
    // Calculate start and end page numbers
    let startPage = Math.max(1, current_page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(total_pages, startPage + maxVisiblePages - 1);
    
    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page and ellipsis if needed
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
        </li>`;
    }
    
    // Last page and ellipsis if needed
    if (endPage < total_pages) {
        if (endPage < total_pages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${total_pages})">${total_pages}</a></li>`;
    }
    
    // Next button
    html += `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${current_page + 1})" aria-label="Next">
            <span aria-hidden="true">»</span>
        </a>
    </li>`;
    
    $('#pagination').html(html);
    
    // Show items count
    const startItem = (current_page - 1) * 20 + 1;
    const endItem = Math.min(current_page * 20, total_items);
    $('#pagination_container').append(`
        <div class="ms-3 d-flex align-items-center">
            <span class="text-muted">Hiển thị ${startItem}-${endItem} của ${total_items}</span>
        </div>
    `);
}

// Change page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    searchStatistics(page);
}

// Load all statistics
function loadStatistics() {
    loadTopScorers();
    loadStrongestTeams();
    searchStatistics();
}

// Initialize when page loads
$(document).ready(function() {
    initCharts();
    
    // Lấy season_id mặc định từ season_select và tải dữ liệu ngay lập tức
    const defaultSeasonId = $('#season_select').val();
    if (defaultSeasonId) {
        currentSeasonId = defaultSeasonId;
        loadStatistics();
    } else {
        showAlert('Không tìm thấy mùa giải 2024/2025!', 'warning');
        $('#stats_table_body').html('<tr><td colspan="8" class="text-center text-muted">Không tìm thấy dữ liệu cho mùa giải 2024/2025</td></tr>');
        $('#pagination_container').hide();
    }

    // Event listeners
    $('#season_select').change(function() {
        const seasonId = $(this).val();
        if (seasonId) {
            currentSeasonId = seasonId;
            loadStatistics();
        } else {
            showAlert('Vui lòng chọn một mùa giải!', 'warning');
            $('#stats_table_body').html('<tr><td colspan="8" class="text-center text-muted">Chọn mùa giải để xem thống kê</td></tr>');
            topScorersChart.data.labels = [];
            topScorersChart.data.datasets[0].data = [];
            topScorersChart.update();
            strongestTeamsChart.data.labels = [];
            strongestTeamsChart.data.datasets[0].data = [];
            strongestTeamsChart.update();
            $('#pagination_container').hide();
        }
    });

    $('#stats_type').change(function() {
        if (currentSeasonId) {
            searchStatistics();
        }
    });

    $('#search_input').on('keyup', function() {
        if (currentSeasonId) {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => searchStatistics(1), 500);
        }
    });
});
</script>