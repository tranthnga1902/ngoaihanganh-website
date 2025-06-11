<?php
// Bắt đầu session để quản lý phiên người dùng
session_start();

// Kết nối các file cần thiết
require_once __DIR__ . '/../includes/config.php'; // File cấu hình chứa kết nối DB và BASE_URL
require_once __DIR__ . '/../Controller/manageResultsController.php'; // Controller xử lý cập nhật kết quả
require_once __DIR__ . '/../Controller/AteamController.php'; // Controller xử lý đội bóng
require_once __DIR__ . '/../Controller/EditMatchController.php'; // Controller xử lý chỉnh sửa/xóa trận đấu

// Lấy danh sách mùa giải cho dropdown lọc
$seasons_sql = "SELECT season_id, name FROM seasons ORDER BY name DESC";
$seasons_result = mysqli_query($conn, $seasons_sql);
$seasons = mysqli_fetch_all($seasons_result, MYSQLI_ASSOC);

// Lấy danh sách đội bóng cho dropdown lọc
$teams_sql = "SELECT team_id, name FROM teams ORDER BY name ASC";
$teams_result = mysqli_query($conn, $teams_sql);
$all_teams = mysqli_fetch_all($teams_result, MYSQLI_ASSOC);

// Lấy tham số lọc từ URL
$season_id = isset($_GET['season_id']) ? (int)$_GET['season_id'] : null;
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : 'Completed';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Xây dựng query lấy danh sách trận đấu
$query = "SELECT SQL_CALC_FOUND_ROWS 
                 m.match_id, 
                 m.match_date, 
                 m.home_team_score, 
                 m.away_team_score, 
                 m.status, 
                 t1.name as home_team, 
                 t1.team_id as home_team_id,
                 t1.logo_url as home_team_logo,
                 t2.name as away_team, 
                 t2.team_id as away_team_id,
                 t2.logo_url as away_team_logo,
                 s.name as season_name
          FROM Matches m
          JOIN Teams t1 ON m.home_team_id = t1.team_id
          JOIN Teams t2 ON m.away_team_id = t2.team_id
          JOIN Seasons s ON m.season_id = s.season_id
          WHERE 1=1";

// Thêm điều kiện lọc vào query
if ($season_id) {
    $query .= " AND m.season_id = $season_id";
}
if ($team_id) {
    $query .= " AND (m.home_team_id = $team_id OR m.away_team_id = $team_id)";
}
if ($status && $status !== 'All') {
    $query .= " AND m.status = '$status'";
}

// Thêm phân trang
$offset = ($page - 1) * $per_page;
$query .= " ORDER BY m.match_date DESC LIMIT $offset, $per_page";

// Thực thi query lấy danh sách trận đấu
$matches_result = mysqli_query($conn, $query);
$matches = mysqli_fetch_all($matches_result, MYSQLI_ASSOC);

// Lấy tổng số trận đấu phù hợp với bộ lọc
$total_rows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT FOUND_ROWS() as total"))['total'];
$total_pages = ceil($total_rows / $per_page);

// Bắt đầu bộ đệm đầu ra
ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Thiết lập mã hóa và tiêu đề trang -->
    <meta charset="UTF-8">
    <title>Quản lý Kết quả Trận đấu</title>
    <!-- Kết nối các file CSS và font -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/manageResults.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Thanh bên (sidebar) -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <main class="main-content">
        <h2><i class="fas fa-futbol"></i> Quản lý Kết quả Trận đấu</h2>
        <div class="container-MR">
            <div class="item-MR">
                <!-- Bộ lọc trận đấu -->
                <div class="filter-section">
                    <h3><i class="fas fa-filter"></i> Bộ lọc trận đấu</h3>
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label for="season_id">Mùa giải</label>
                            <select id="season_id" name="season_id" class="form-control">
                                <option value="">Tất cả mùa giải</option>
                                <?php foreach ($seasons as $season): ?>
                                    <option value="<?= $season['season_id'] ?>" <?= $season_id == $season['season_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($season['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="team_id">Đội bóng</label>
                            <select id="team_id" name="team_id" class="form-control">
                                <option value="">Tất cả đội bóng</option>
                                <?php foreach ($all_teams as $team): ?>
                                    <option value="<?= $team['team_id'] ?>" <?= $team_id == $team['team_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Completed" <?= $status == 'Completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                                <option value="Scheduled" <?= $status == 'Scheduled' ? 'selected' : '' ?>>Chưa diễn ra</option>
                                <option value="All" <?= $status == 'All' ? 'selected' : '' ?>>Tất cả</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Lọc kết quả
                            </button>
                            <a href="?" class="btn btn-reset">
                                <i class="fas fa-undo"></i> Đặt lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="item-MR">
                <!-- Form cập nhật kết quả trận đấu -->
                <div class="card">
                    <h3 style="padding: 15px 20px; border-bottom: 1px solid #eee; margin: 0;">
                        <i class="fas fa-plus-circle"></i> Cập nhật kết quả trận đấu
                    </h3>
                    <div class="form-container" style="padding: 20px;">
                        <form id="resultForm" action="<?php echo BASE_URL; ?>admin/manageResults.php" method="POST">
                            <input type="hidden" name="action" value="update_result">
                            <div class="form-group">
                                <label for="match_id">Chọn trận đấu:</label>
                                <select name="match_id" id="match_id" class="form-control" required onchange="updatePlayerLists()">
                                    <option value="">-- Chọn trận --</option>
                                    <?php 
                                    // Lấy danh sách trận đấu chưa hoàn thành
                                    $uncompleted_matches_sql = "SELECT m.match_id, m.match_date, 
                                                              t1.name as team1_name, t2.name as team2_name,
                                                              m.home_team_id, m.away_team_id
                                                       FROM Matches m
                                                       JOIN Teams t1 ON m.home_team_id = t1.team_id
                                                       JOIN Teams t2 ON m.away_team_id = t2.team_id
                                                       WHERE m.status = 'Scheduled'";
                                    if ($season_id) {
                                        $uncompleted_matches_sql .= " AND m.season_id = $season_id";
                                    }
                                    if ($team_id) {
                                        $uncompleted_matches_sql .= " AND (m.home_team_id = $team_id OR m.away_team_id = $team_id)";
                                    }
                                    $uncompleted_matches_sql .= " ORDER BY m.match_date ASC";
                                    $uncompleted_matches_result = mysqli_query($conn, $uncompleted_matches_sql);
                                    $uncompleted_matches = mysqli_fetch_all($uncompleted_matches_result, MYSQLI_ASSOC);
                                    foreach ($uncompleted_matches as $match):
                                    ?>
                                        <option value="<?php echo $match['match_id']; ?>" 
                                                data-team1="<?php echo htmlspecialchars($match['team1_name']); ?>"
                                                data-team2="<?php echo htmlspecialchars($match['team2_name']); ?>"
                                                data-team1-id="<?php echo $match['home_team_id']; ?>"
                                                data-team2-id="<?php echo $match['away_team_id']; ?>">
                                            <?php echo htmlspecialchars($match['team1_name'] . ' vs ' . $match['team2_name'] . ' (' . date('d/m/Y H:i', strtotime($match['match_date'])) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tỉ số:</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="number" name="home_team_score" class="form-control" min="0" placeholder="Đội nhà" required style="text-align: center;">
                                    <span style="align-self: center;">-</span>
                                    <input type="number" name="away_team_score" class="form-control" min="0" placeholder="Đội khách" required style="text-align: center;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <select name="status" class="form-control" required>
                                    <option value="Scheduled">Chưa hoàn thành</option>
                                    <option value="Completed" selected>Hoàn thành</option>
                                </select>
                            </div>
                            <div class="event-container" style="margin-top: 20px;">
                                <h4 style="margin-bottom: 15px; color: var(--primary-dark);">
                                    <i class="fas fa-list-alt"></i> Sự kiện trận đấu
                                </h4>
                                <div id="events-container"></div>
                                <button type="button" class="btn btn-sm" onclick="addEvent()" style="background-color: #f0f0f0; color: #333; margin-top: 10px;">
                                    <i class="fas fa-plus"></i> Thêm sự kiện
                                </button>
                            </div>
                            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                                <button type="reset" class="btn btn-reset">
                                    <i class="fas fa-undo"></i> Đặt lại
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu kết quả
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    
            <!-- Hiển thị thông báo lỗi hoặc thành công -->
            <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
                <div class="alert alert-danger" style="padding: 12px; background: #fff1f0; color: #cf1322; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php
                    // Xử lý các thông báo lỗi cụ thể
                    $error_message = match ($_GET['error']) {
                        'no_goal_events' => 'Không thể lưu: Bắt buộc phải thêm ít nhất một sự kiện bàn thắng (bàn thắng, penalty hoặc phản lưới)!',
                        'score_mismatch' => 'Tỉ số không khớp với các sự kiện bàn thắng. Vui lòng kiểm tra lại!',
                        'invalid_match_id' => 'ID trận đấu không hợp lệ!',
                        'invalid_match' => 'Trận đấu không tồn tại!',
                        'update_failed' => 'Lỗi khi cập nhật kết quả. Vui lòng thử lại!',
                        'database_error' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau!',
                        'delete_failed' => 'Lỗi khi xóa trận đấu. Vui lòng thử lại!',
                        default => 'Đã xảy ra lỗi không xác định.'
                    };
                    echo htmlspecialchars($error_message);
                    ?>
                </div>
            <?php elseif (isset($_GET['success'])): ?>
                <div id="success-message" class="alert alert-success" style="padding: 12px; background: #e6f7ee; color: #00a854; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> 
                    <?php
                    switch ($_GET['success']) {
                        case 'updated': echo 'Kết quả trận đấu đã được cập nhật thành công!'; break;
                        case 'deleted': echo 'Trận đấu đã được xóa thành công!'; break;
                        default: echo 'Thao tác thành công!';
                    }
                    ?>
                </div>
            <?php endif; ?>
        
        <!-- Bảng danh sách trận đấu -->
        <div class="card" id="matches-results">
            <?php if (count($matches) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="matches-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Ngày thi đấu</th>
                                <th>Mùa giải</th>
                                <th>Đội nhà</th>
                                <th>Tỷ số</th>
                                <th>Đội khách</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $index => $match): ?>
                                <tr data-match-id="<?= $match['match_id'] ?>">
                                    <td><?= ($page - 1) * $per_page + $index + 1 ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($match['match_date'])) ?></td>
                                    <td><?= htmlspecialchars($match['season_name']) ?></td>
                                    <td>
                                        <div class="team-container reverse-logo">
                                            <?php if (!empty($match['home_team_logo'])): ?>
                                                <img src="../<?php echo htmlspecialchars($match['home_team_logo']) ?>" 
                                                     alt="<?= htmlspecialchars($match['home_team']) ?>" 
                                                     class="team-logo">
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($match['home_team']) ?></span>
                                        </div>
                                    </td>
                                    <td class="match-score">
                                        <?php if ($match['status'] == 'Completed'): ?>
                                            <strong><?= $match['home_team_score'] ?> - <?= $match['away_team_score'] ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">VS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="team-container">
                                            <?php if (!empty($match['away_team_logo'])): ?>
                                                <img src="../<?php echo htmlspecialchars($match['away_team_logo']) ?>" 
                                                     alt="<?= htmlspecialchars($match['away_team']) ?>" 
                                                     class="team-logo">
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($match['away_team']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($match['status']) ?>">
                                            <?= $match['status'] == 'Completed' ? 'Đã hoàn thành' : 'Chưa diễn ra' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Nút Sửa
                                            <button type="button" class="btn btn-sm btn-edit" data-match-id="<?= $match['match_id'] ?>">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button> -->
                                            <!-- Nút Xóa -->
                                            <button type="button" class="btn btn-sm btn-delete" data-match-id="<?= $match['match_id'] ?>">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                            <!-- Nút Xem chi tiết -->
                                            <a href="match_details.php?id=<?= $match['match_id'] ?>" class="btn btn-sm btn-view">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>Không tìm thấy trận đấu nào</h3>
                    <p>Không có trận đấu nào phù hợp với tiêu chí lọc của bạn.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

<!-- JavaScript xử lý giao diện và logic phía client -->
<script>
// Dữ liệu cầu thủ theo đội bóng, được tạo từ PHP
let playersByTeam = <?php 
    $players_sql = "SELECT p.player_id, p.name, p.team_id, t.name as team_name 
                    FROM Players p
                    JOIN Teams t ON p.team_id = t.team_id
                    ORDER BY t.name, p.name";
    $players_result = mysqli_query($conn, $players_sql);
    $players_data = mysqli_fetch_all($players_result, MYSQLI_ASSOC);
    $players_grouped = [];
    foreach ($players_data as $player) {
        $players_grouped[$player['team_id']][] = [
            'id' => $player['player_id'],
            'name' => $player['name']
        ];
    }
    echo json_encode($players_grouped);
?>;

// Khởi tạo biến toàn cục
let eventCount = 0; // Đếm số sự kiện trong form thêm mới
let editEventCount = 0; // Đếm số sự kiện trong modal chỉnh sửa
window.currentMatchData = {}; // Lưu dữ liệu trận đấu hiện tại
window.currentEditMatchData = {}; // Lưu dữ liệu trận đấu trong modal chỉnh sửa

// Cập nhật danh sách cầu thủ khi chọn trận đấu
function updatePlayerLists() {
    const matchSelect = document.getElementById('match_id');
    if (!matchSelect) {
        console.warn('Không tìm thấy phần tử match_id');
        return;
    }
    
    const selectedOption = matchSelect.options[matchSelect.selectedIndex];
    // Xóa tất cả sự kiện hiện có
    document.querySelectorAll('.event').forEach(el => el.remove());
    eventCount = 0;
    
    // Cập nhật dữ liệu trận đấu hiện tại
    window.currentMatchData = {
        team1Id: selectedOption ? selectedOption.getAttribute('data-team1-id') : '',
        team2Id: selectedOption ? selectedOption.getAttribute('data-team2-id') : '',
        team1Name: selectedOption ? selectedOption.getAttribute('data-team1') : '',
        team2Name: selectedOption ? selectedOption.getAttribute('data-team2') : ''
    };
}

// Thêm sự kiện mới vào form
function addEvent() {
    if (!window.currentMatchData || !window.currentMatchData.team1Id) {
        alert('Vui lòng chọn trận đấu trước khi thêm sự kiện!');
        return;
    }

    const container = document.getElementById('events-container');
    if (!container) {
        console.warn('Không tìm thấy events-container');
        return;
    }
    
    // Tạo div chứa sự kiện mới
    const eventDiv = document.createElement('div');
    eventDiv.className = 'event';
    eventDiv.style.cssText = 'background:rgb(19, 19, 19); padding: 15px; border-radius: 6px; margin-bottom: 10px;';
    
    // HTML cho sự kiện mới
    eventDiv.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 10px;">
            <div class="form-group">
                <label>Đội</label>
                <select name="events[${eventCount}][team_id]" class="form-control" 
                        onchange="updateEventPlayers(this, ${eventCount})" required>
                    <option value="">-- Chọn đội --</option>
                    <option value="${window.currentMatchData.team1Id}">${window.currentMatchData.team1Name}</option>
                    <option value="${window.currentMatchData.team2Id}">${window.currentMatchData.team2Name}</option>
                </select>
            </div>
            <div class="form-group">
                <label>Loại sự kiện</label>
                <select name="events[${eventCount}][event_type]" class="form-control" required>
                    <option value="goal">Bàn thắng</option>
                    <option value="penalty_scored">Penalty ghi bàn</option>
                    <option value="penalty_missed">Hỏng penalty</option>
                    <option value="own_goal">Phản lưới</option>
                    <option value="yellow_card">Thẻ vàng</option>
                    <option value="red_card">Thẻ đỏ</option>
                    <option value="assist">Kiến tạo</option>
                    <option value="save">Cứu thua</option>
                </select>
            </div>
            <div class="form-group">
                <label>Cầu thủ</label>
                <select name="events[${eventCount}][player_id]" class="form-control player-select-${eventCount}">
                    <option value="">-- Chọn cầu thủ --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Phút</label>
                <input type="number" name="events[${eventCount}][minute]" class="form-control" 
                       min="1" max="120" placeholder="Phút" required>
            </div>
        </div>
        <div class="form-group">
            <label>Ghi chú</label>
            <input type="text" name="events[${eventCount}][note]" class="form-control" 
                   placeholder="Ghi chú (nếu có)">
        </div>
        <button type="button" onclick="this.parentElement.remove()" 
                class="btn btn-sm" style="background-color: #ff4d4f; color: white; margin-top: 10px;">
            <i class="fas fa-trash"></i> Xóa sự kiện
        </button>
    `;
    
    container.appendChild(eventDiv);
    eventCount++;
}

// Cập nhật danh sách cầu thủ theo đội được chọn
function updateEventPlayers(teamSelect, eventIndex) {
    const teamId = teamSelect.value;
    const playerSelect = document.querySelector(`.player-select-${eventIndex}`);
    if (!playerSelect) {
        console.warn(`Không tìm thấy player-select-${eventIndex}`);
        return;
    }
    
    // Xóa danh sách cầu thủ hiện tại
    playerSelect.innerHTML = '<option value="">-- Chọn cầu thủ --</option>';
    if (teamId && playersByTeam[teamId]) {
        // Thêm các cầu thủ của đội được chọn
        playersByTeam[teamId].forEach(player => {
            const option = document.createElement('option');
            option.value = player.id;
            option.textContent = player.name;
            playerSelect.appendChild(option);
        });
    }
}

// Kiểm tra tính hợp lệ của tỉ số và sự kiện
function updateGoalCount() {
    const events = document.querySelectorAll('.event');
    let homeGoals = 0, awayGoals = 0;

    events.forEach(event => {
        const teamId = event.querySelector('select[name$="[team_id]"]')?.value || '';
        const eventType = event.querySelector('select[name$="[event_type]"]')?.value || '';
        if (eventType === 'goal' || eventType === 'penalty_scored') {
            if (teamId === window.currentMatchData.team1Id) homeGoals++;
            else if (teamId === window.currentMatchData.team2Id) awayGoals++;
        } else if (eventType === 'own_goal') {
            if (teamId === window.currentMatchData.team1Id) awayGoals++;
            else if (teamId === window.currentMatchData.team2Id) homeGoals++;
        }
    });

    const homeScoreInput = parseInt(document.querySelector('input[name="home_team_score"]').value) || 0;
    const awayScoreInput = parseInt(document.querySelector('input[name="away_team_score"]').value) || 0;

    if (homeGoals !== homeScoreInput || awayGoals !== awayScoreInput) {
        alert(`Tỉ số không khớp: Đội nhà (${homeGoals} vs ${homeScoreInput}), Đội khách (${awayGoals} vs ${awayScoreInput})`);
    }
}

// Ngăn gửi form nếu tỉ số không hợp lệ
document.getElementById('resultForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const events = document.querySelectorAll('.event');
    let homeGoals = 0, awayGoals = 0;

    events.forEach(event => {
        const teamId = event.querySelector('select[name$="[team_id]"]').value;
        const eventType = event.querySelector('select[name$="[event_type]"]').value;
        if (eventType === 'goal' || eventType === 'penalty_scored') {
            if (teamId === window.currentMatchData.team1Id) homeGoals++;
            else if (teamId === window.currentMatchData.team2Id) awayGoals++;
        } else if (eventType === 'own_goal') {
            if (teamId === window.currentMatchData.team1Id) awayGoals++;
            else if (teamId === window.currentMatchData.team2Id) homeGoals++;
        }
    });

    const homeScoreInput = parseInt(document.querySelector('input[name="home_team_score"]').value) || 0;
    const awayScoreInput = parseInt(document.querySelector('input[name="away_team_score"]').value) || 0;

    if (homeGoals !== homeScoreInput || awayGoals !== awayScoreInput) {
        alert(`Không thể lưu: Tỉ số không khớp: Đội nhà (${homeGoals} vs ${homeScoreInput}), Đội khách (${awayGoals} vs ${awayScoreInput})`);
        return;
    }

    if ((homeScoreInput > 0 || awayScoreInput > 0) && homeGoals === 0 && awayGoals === 0) {
        alert('Không thể lưu: Bắt buộc phải thêm ít nhất một sự kiện bàn thắng khi có bàn thắng được ghi!');
        return;
    }

    e.target.submit();
});

// Mở modal chỉnh sửa trận đấu
function openEditModal(match_id) {
    console.log('Opening modal for match_id:', match_id); // Debug
    fetch('<?php echo BASE_URL; ?>Controller/EditMatchController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_match_details&match_id=' + encodeURIComponent(match_id)
    })
    .then(response => {
        console.log('Fetch response:', response); // Debug
        if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
        return response.json();
    })
    .then(data => {
        console.log('Fetch data:', data); // Debug
        if (data.success && data.data && data.data.match) {
            // Reset edit event count
            editEventCount = 0;
            // Tạo modal chỉnh sửa
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000;';
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = 'background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;';
            
            modalContent.innerHTML = `
                <h3 style="margin-top: 0;"><i class="fas fa-edit"></i> Chỉnh sửa trận đấu</h3>
                <form id="editMatchForm" action="<?php echo BASE_URL; ?>Controller/EditMatchController.php" method="POST">
                    <input type="hidden" name="action" value="update_match">
                    <input type="hidden" name="match_id" value="${match_id}">
                    <div class="form-group">
                        <label>Trận đấu:</label>
                        <p>${data.data.match.home_team} vs ${data.data.match.away_team} (${new Date(data.data.match.match_date).toLocaleString('vi-VN')})</p>
                    </div>
                    <div class="form-group">
                        <label>Tỉ số:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" name="home_team_score" class="form-control" min="0" value="${data.data.match.home_team_score || 0}" required style="text-align: center;">
                            <span style="align-self: center;">-</span>
                            <input type="number" name="away_team_score" class="form-control" min="0" value="${data.data.match.away_team_score || 0}" required style="text-align: center;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái:</label>
                        <select name="status" class="form-control" required>
                            <option value="Scheduled" ${data.data.match.status === 'Scheduled' ? 'selected' : ''}>Chưa hoàn thành</option>
                            <option value="Completed" ${data.data.match.status === 'Completed' ? 'selected' : ''}>Hoàn thành</option>
                        </select>
                    </div>
                    <div class="event-container" style="margin-top: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--primary-dark);">
                            <i class="fas fa-list-alt"></i> Sự kiện trận đấu
                        </h4>
                        <div id="edit-events-container"></div>
                        <button type="button" class="btn btn-sm" onclick="addEditEvent()" style="background-color: #f0f0f0; color: #333; margin-top: 10px;">
                            <i class="fas fa-plus"></i> Thêm sự kiện
                        </button>
                    </div>
                    <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-reset" onclick="this.closest('.modal').remove()">Đóng</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                    </div>
                </form>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            // Lưu dữ liệu đội bóng cho modal chỉnh sửa
            window.currentEditMatchData = {
                team1Id: data.data.match.home_team_id,
                team2Id: data.data.match.away_team_id,
                team1Name: data.data.match.home_team,
                team2Name: data.data.match.away_team
            };

            // Điền dữ liệu sự kiện
            if (data.data.events && Array.isArray(data.data.events)) {
                data.data.events.forEach(event => {
                    addEditEvent({
                        team_id: event.team_id,
                        event_type: event.event_type,
                        player_id: event.player_id,
                        minute: event.minute,
                        note: event.note || ''
                    });
                });
            }

            // Gửi form chỉnh sửa
            modalContent.querySelector('#editMatchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Phản hồi mạng không ổn: ' + response.status);
                    return response.json();
                })
                .then(result => {
                    console.log('Submit result:', result); // Debug
                    if (result.success) {
                        window.location.href = '<?php echo BASE_URL; ?>admin/manageResults.php?success=updated';
                    } else {
                        alert('Lỗi: ' + (result.error === 'score_mismatch' ? 'Tỉ số không khớp với sự kiện!' : 
                                        result.error === 'no_goal_events' ? 'Phải có ít nhất một sự kiện bàn thắng!' : 
                                        result.error === 'invalid_match' ? 'Trận đấu không tồn tại!' :
                                        'Không thể cập nhật trận đấu!'));
                    }
                })
                .catch(error => {
                    alert('Lỗi khi lưu dữ liệu: ' + error.message);
                });
            });
        } else {
            alert('Không thể tải thông tin trận đấu: ' + (data.error || 'Dữ liệu không hợp lệ'));
        }
    })
    .catch(error => {
        alert('Lỗi khi tải dữ liệu trận đấu: ' + error.message);
    });
}


// Cập nhật danh sách cầu thủ trong modal chỉnh sửa
function updateEditEventPlayers(teamSelect, eventIndex, selectedPlayerId = null) {
    const teamId = teamSelect.value;
    const playerSelect = document.querySelector(`.edit-player-select-${eventIndex}`);
    if (!playerSelect) {
        console.warn(`Không tìm thấy edit-player-select-${eventIndex}`);
        return;
    }
    
    // Xóa danh sách cầu thủ hiện tại
    playerSelect.innerHTML = '<option value="">-- Chọn cầu thủ --</option>';
    if (teamId && playersByTeam[teamId]) {
        // Thêm các cầu thủ của đội được chọn
        playersByTeam[teamId].forEach(player => {
            const option = document.createElement('option');
            option.value = player.id;
            option.textContent = player.name;
            if (selectedPlayerId && player.id == selectedPlayerId) {
                option.selected = true;
            }
            playerSelect.appendChild(option);
        });
    }
}

function deleteMatch(match_id) {
    if (!match_id) {
        alert('Lỗi: ID trận đấu không hợp lệ!');
        console.error('match_id is empty or undefined:', match_id);
        return;
    }

    console.log('Deleting match with ID:', match_id);
    console.log('Request URL:', '<?php echo BASE_URL; ?>Controller/EditMatchController.php');

    if (!confirm('Bạn có chắc muốn xóa trận đấu này?')) {
        return;
    }

    fetch('<?php echo BASE_URL; ?>Controller/EditMatchController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete_match&match_id=' + encodeURIComponent(match_id)
    })
    .then(response => {
        console.log('Response status:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error(`Phản hồi mạng không ổn: ${response.status} ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            const row = document.querySelector(`tr[data-match-id="${match_id}"]`);
            if (row) {
                row.remove();
            }
            const container = document.querySelector('.container-MR');
            if (container) {
                const alert = document.createElement('div');
                alert.id = 'success-message';
                alert.className = 'alert alert-success';
                alert.innerHTML = '<i class="fas fa-check-circle"></i> Trận đấu đã được xóa thành công!';
                container.prepend(alert);
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
            window.location.href = '<?php echo BASE_URL; ?>admin/manageResults.php?success=deleted';
        } else {
            alert('Không thể xóa trận đấu: ' + (data.error || 'Lỗi không xác định'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Lỗi khi xóa trận đấu: ' + error.message);
    });
}

// Xử lý sự kiện khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    // Hiệu ứng hover cho bảng
    const matchesTable = document.querySelector('.matches-table');
    if (matchesTable) {
        matchesTable.addEventListener('mouseover', function(e) {
            const row = e.target.closest('tr');
            if (row) {
                row.style.backgroundColor = '#f5f5f5';
            }
        });
        matchesTable.addEventListener('mouseout', function(e) {
            const row = e.target.closest('tr');
            if (row) {
                row.style.backgroundColor = '';
            }
        });

        // Gắn sự kiện cho nút Sửa và Xóa
        matchesTable.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            const matchId = target.getAttribute('data-match-id');
            if (!matchId) {
                alert('Lỗi: Không tìm thấy ID trận đấu!');
                return;
            }

            if (target.classList.contains('btn-edit')) {
                openEditModal(matchId);
            } else if (target.classList.contains('btn-delete')) {
                deleteMatch(matchId);
            }
        });
    }
    // Đảm bảo chỉ các giá trị hợp lệ được chọn
    const validEvents = ['goal', 'assist', 'yellow_card', 'red_card', 'clean_sheet', 'penalty_scored', 'penalty_missed', 'save', 'own_goal'];

    document.querySelectorAll('select[name="event_type"]').forEach(select => {
        select.addEventListener('change', function() {
            if (!validEvents.includes(this.value)) {
                alert('Loại sự kiện không hợp lệ');
                this.value = 'goal'; // Đặt về giá trị mặc định
            }
        });
    });
    // Ẩn thông báo thành công sau 3 giây
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s';
            successMessage.style.opacity = '0';
            setTimeout(() => successMessage.remove(), 500);
        }, 3000);
    }
});
</script>

<?php
// Xuất nội dung từ bộ đệm
echo ob_get_clean();
?>