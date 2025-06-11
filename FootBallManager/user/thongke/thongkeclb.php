<?php
// Khởi động session
session_start();

// Bao gồm tệp cấu hình
include(__DIR__ . '/../../includes/config.php');

// Bắt đầu bộ đệm đầu ra
ob_start();

// Đặt tiêu đề trang
$title = "Thống kê câu lạc bộ";

// Hàm lấy season_id mới nhất
function getLatestSeasonId($conn) {
    $query = "SELECT season_id FROM seasons ORDER BY start_date DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['season_id'];
    }
    return null;
}

ob_start();
?>

<?php
// Số câu lạc bộ mỗi trang
$teams_per_page = 10;

// Lấy trang hiện tại từ URL
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Loại thống kê
$stat_types = [
    'goals' => 'Bàn Thắng',
    'wins' => 'Trận Thắng',
    'losses' => 'Trận Thua',
    'yellow_cards' => 'Thẻ Vàng',
    'red_cards' => 'Thẻ Đỏ'
];
$stat_type = isset($_GET['stat_type']) && array_key_exists($_GET['stat_type'], $stat_types) ? $_GET['stat_type'] : 'goals';
$stat_label = $stat_types[$stat_type];

// Lấy season_id mới nhất
$season_id = getLatestSeasonId($conn);
if ($season_id === null) {
    die("Không tìm thấy mùa giải hợp lệ.");
}

// Tính offset
$offset = ($page - 1) * $teams_per_page;

// Truy vấn dữ liệu dựa trên loại thống kê
if ($stat_type == 'goals') {
    $query = "
        SELECT 
            t.name AS team_name,
            t.logo_url AS team_logo,
            ts.goals_for AS stat_value
        FROM teamstats ts
        JOIN Teams t ON ts.team_id = t.team_id
        WHERE ts.season_id = $season_id AND ts.goals_for > 0
        ORDER BY ts.goals_for DESC
        LIMIT $teams_per_page OFFSET $offset
    ";
    $count_query = "
        SELECT COUNT(*) AS total
        FROM teamstats ts
        WHERE ts.season_id = $season_id AND ts.goals_for > 0
    ";
} elseif ($stat_type == 'yellow_cards') {
    $query = "
        SELECT 
            t.name AS team_name,
            t.logo_url AS team_logo,
            SUM(ps.yellow_cards) AS stat_value
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = $season_id
        GROUP BY t.team_id, t.name
        HAVING stat_value > 0
        ORDER BY stat_value DESC
        LIMIT $teams_per_page OFFSET $offset
    ";
    $count_query = "
        SELECT COUNT(DISTINCT t.team_id) AS total
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = $season_id AND ps.yellow_cards > 0
    ";
} elseif ($stat_type == 'red_cards') {
    $query = "
        SELECT 
            t.name AS team_name,
            t.logo_url AS team_logo,
            SUM(ps.red_cards) AS stat_value
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = $season_id
        GROUP BY t.team_id, t.name
        HAVING stat_value > 0
        ORDER BY stat_value DESC
        LIMIT $teams_per_page OFFSET $offset
    ";
    $count_query = "
        SELECT COUNT(DISTINCT t.team_id) AS total
        FROM PlayerStats ps
        JOIN Players p ON ps.player_id = p.player_id
        JOIN Teams t ON p.team_id = t.team_id
        WHERE ps.season_id = $season_id AND ps.red_cards > 0
    ";
} elseif ($stat_type == 'wins') {
    $query = "
        SELECT 
            t.name AS team_name,
            t.logo_url AS team_logo,
            ts.wins AS stat_value
        FROM teamstats ts
        JOIN Teams t ON ts.team_id = t.team_id
        WHERE ts.season_id = $season_id AND ts.wins > 0
        ORDER BY ts.wins DESC
        LIMIT $teams_per_page OFFSET $offset
    ";
    $count_query = "
        SELECT COUNT(*) AS total
        FROM teamstats ts
        WHERE ts.season_id = $season_id AND ts.wins > 0
    ";
} elseif ($stat_type == 'losses') {
    $query = "
        SELECT 
            t.name AS team_name,
            t.logo_url AS team_logo,
            ts.losses AS stat_value
        FROM teamstats ts
        JOIN Teams t ON ts.team_id = t.team_id
        WHERE ts.season_id = $season_id AND ts.losses > 0
        ORDER BY ts.losses DESC
        LIMIT $teams_per_page OFFSET $offset
    ";
    $count_query = "
        SELECT COUNT(*) AS total
        FROM teamstats ts
        WHERE ts.season_id = $season_id AND ts.losses > 0
    ";
}

// Đếm tổng số đội
$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    die("Lỗi truy vấn đếm: " . mysqli_error($conn));
}
$total_teams = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_teams / $teams_per_page);

// Lấy dữ liệu
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

$teams = [];
while ($row = mysqli_fetch_assoc($result)) {
    $teams[] = $row;
}

// Lấy danh sách mùa giải
$season_query = "SELECT season_id, name FROM seasons ORDER BY start_date DESC";
$season_result = mysqli_query($conn, $season_query);
$seasons = [];
while ($row = mysqli_fetch_assoc($season_result)) {
    $seasons[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Câu Lạc Bộ Ngoại Hạng Anh - <?= $stat_label ?></title>
    <link rel="stylesheet" href="../assets/css/thongke.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/thongke.css">
    <style>
        :root {
            --primary-color: #38003c;
            --secondary-color: #00ff85;
            --accent-color: #e90052;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--dark-text);
        }
        
        
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .stats-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-title i {
            color: var(--accent-color);
        }
        
        .filters-container {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-box {
            position: relative;
            min-width: 200px;
        }
        
        .filter-box label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .filter-box select {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: white;
            font-size: 0.95rem;
            appearance: none;
            transition: all 0.3s;
        }
        
        .filter-box select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(0, 255, 133, 0.2);
        }
        
        .filter-box::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 55%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--light-text);
        }
        
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1.5rem;
        }
        
        .stats-table thead th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 600;
            text-align: center;
            position: sticky;
            top: 0;
        }
        
        .stats-table th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .stats-table th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .stats-table tbody tr {
            transition: all 0.2s;
        }
        
        .stats-table tbody tr:hover {
            background-color: rgba(56, 0, 60, 0.05);
            transform: translateX(5px);
        }
        
        .stats-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            text-align: center;
            vertical-align: middle;
        }
        
        .team-info {
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: flex-start;
            padding-left: 2rem;
        }
        
        .badge-image {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .rank {
            font-weight: 700;
            color: var(--accent-color);
            font-size: 1.2rem;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .pagination {
            display: flex;
            gap: 0.5rem;
        }
        
        .page-item {
            list-style: none;
        }
        
        .page-link {
            display: block;
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.disabled {
            color: #ccc;
            pointer-events: none;
            border-color: #eee;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--light-text);
            font-size: 1.1rem;
        }
        
        .stat-icon {
            color: var(--secondary-color);
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            #nav-menutk {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-box {
                min-width: 100%;
            }
            
            .stats-table {
                display: block;
                overflow-x: auto;
            }
            
            .team-info {
                padding-left: 0;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="stats-header">
        <div class="header-content">
            <div class="header-top">
                <h1>Trung Tâm Thống Kê</h1>
                <img class="page-header__sponsor-image" src="<?php echo BASE_URL; ?>assets/img/thongke/oracle_logo.webp" alt="logo_oracle">
            </div>
            <nav id="nav-menutk">
                <a href="<?php echo BASE_URL; ?>user/statistics.php"><i class="fas fa-chart-pie"></i> Tổng quan</a>
                <a href="<?php echo BASE_URL; ?>user/thongke/thongkecauthu.php"><i class="fas fa-user"></i> Cầu Thủ</a>
                <a href="<?php echo BASE_URL; ?>user/thongke/thongkeclb.php" class="active"><i class="fas fa-users"></i> Câu Lạc Bộ</a>
                <!-- <a href="<?php echo BASE_URL; ?>user/thongke/sosanh.php"><i class="fas fa-chess"></i> Đối Đầu</a> -->
            </nav>
        </div>
    </header>

    <div class="main-container">
        <div class="stats-container">
            <h2 class="stats-title">
                <i class="fas fa-chart-bar"></i>
                Thống Kê Câu Lạc Bộ - <?= $stat_label ?>
            </h2>
            
            <div class="filters-container">
                <div class="filter-box">
                    <label for="stat_type"><i class="fas fa-filter"></i> Loại thống kê</label>
                    <select id="stat_type" name="stat_type" onchange="this.form.submit()" form="filterForm">
                        <?php foreach ($stat_types as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $stat_type == $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-box">
                    <label for="season_id"><i class="fas fa-calendar"></i> Mùa giải</label>
                    <select id="season_id" name="season_id" onchange="this.form.submit()" form="filterForm">
                        <?php foreach ($seasons as $season): ?>
                            <option value="<?= $season['season_id'] ?>" <?= $season_id == $season['season_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($season['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <form id="filterForm" method="GET" style="display: none;">
                <input type="hidden" name="page" value="1">
            </form>
            
            <?php if (!empty($teams)): ?>
                <div class="table-responsive">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th width="10%">#</th>
                                <th width="60%">Câu Lạc Bộ</th>
                                <th width="30%">
                                    <i class="fas <?php 
                                        echo $stat_type == 'goals' ? 'fa-futbol' : 
                                            ($stat_type == 'wins' ? 'fa-trophy' : 
                                            ($stat_type == 'losses' ? 'fa-sad-tear' : 
                                            ($stat_type == 'yellow_cards' ? 'fa-square yellow-card' : 'fa-square red-card'))); 
                                    ?> stat-icon"></i>
                                    <?= $stat_label ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $index => $team): ?>
                                <tr>
                                    <td class="rank"><?= $offset + $index + 1 ?></td>
                                    <td>
                                        <div class="team-info">
                                            <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($team['team_logo']) ?>" 
                                                 alt="<?= htmlspecialchars($team['team_name']) ?>" 
                                                 class="badge-image"
                                                 onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.png'">
                                            <span><?= htmlspecialchars($team['team_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="stat-value">
                                        <?= $team['stat_value'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination-container">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&stat_type=<?= $stat_type ?>&season_id=<?= $season_id ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php 
                        // Hiển thị tối đa 5 trang xung quanh trang hiện tại
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&stat_type='.$stat_type.'&season_id='.$season_id.'">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = $i == $page ? 'active' : '';
                            echo '<li class="page-item '.$active.'"><a class="page-link" href="?page='.$i.'&stat_type='.$stat_type.'&season_id='.$season_id.'">'.$i.'</a></li>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&stat_type='.$stat_type.'&season_id='.$season_id.'">'.$total_pages.'</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&stat_type=<?= $stat_type ?>&season_id=<?= $season_id ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="page-info">
                        Trang <?= $page ?> / <?= $total_pages ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; color: var(--light-text);"></i>
                    <p>Không có dữ liệu thống kê nào được tìm thấy</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Thêm hiệu ứng khi chọn filter
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                this.style.borderColor = '#00ff85';
                this.style.boxShadow = '0 0 0 3px rgba(0, 255, 133, 0.2)';
                setTimeout(() => {
                    this.style.borderColor = '#ddd';
                    this.style.boxShadow = 'none';
                }, 1000);
            });
        });
        
        // Highlight menu item
        document.querySelector(`#nav-menutk a[href="<?= $_SERVER['PHP_SELF'] ?>"]`).classList.add('active');
    </script>
</body>
</html>

<?php
mysqli_free_result($result);
mysqli_free_result($count_result);
mysqli_free_result($season_result);
mysqli_close($conn);
?>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include(__DIR__ . '/../../includes/master.php');
?>