<?php
// Khởi động session
session_start();

// Bao gồm tệp cấu hình
include(__DIR__ . '/../../includes/config.php');

// Bắt đầu bộ đệm đầu ra
ob_start();

// Đặt tiêu đề trang
$title = "Thống kê cầu thủ";

ob_start();
?>

<?php
// Số cầu thủ mỗi trang
$players_per_page = 10;

// Lấy trang hiện tại từ URL
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Loại thống kê
$stat_types = [
    'total_goals' => 'Bàn Thắng',
    'assists' => 'Kiến Tạo',
    'clean_sheets' => 'Giữ Sạch Lưới',
    'yellow_cards' => 'Thẻ Vàng',
    'red_cards' => 'Thẻ Đỏ'
];
$stat_type = isset($_GET['stat_type']) && array_key_exists($_GET['stat_type'], $stat_types) ? $_GET['stat_type'] : 'total_goals';
$stat_label = $stat_types[$stat_type];

// Tính offset
$offset = ($page - 1) * $players_per_page;

// Đếm tổng số cầu thủ
$count_query = "SELECT COUNT(*) AS total FROM playerstats";
$count_result = mysqli_query($conn, $count_query);
$total_players = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_players / $players_per_page);

// Truy vấn dữ liệu
$query = "
    SELECT 
        p.name AS player_name,
        t.name AS team_name,
        p.nationality AS quoc_tich,
        t.logo_url AS team_logo,
        s.name AS ten_san,
        ps.$stat_type AS stat_value
    FROM playerstats ps
    JOIN players p ON ps.player_id = p.player_id
    JOIN teams t ON p.team_id = t.team_id
    JOIN Stadiums s ON t.stadium_id = s.stadium_id
    ORDER BY ps.$stat_type DESC
    LIMIT $players_per_page OFFSET $offset
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

$players = [];
while ($row = mysqli_fetch_assoc($result)) {
    $players[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Cầu Thủ Ngoại Hạng Anh</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>../assets/css/thongke.css">
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
        
        .player-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .badge-image {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .team-info {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
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
        }
    </style>
</head>
<body>
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

    <div class="main-container">
        <div class="stats-container">
            <h2 class="stats-title">
                <i class="fas fa-chart-line"></i>
                Thống Kê Cầu Thủ - <?= $stat_label ?>
            </h2>
            
            <div class="filters-container">
                <div class="filter-box">
                    <label for="stat_type">Loại thống kê</label>
                    <select id="stat_type" name="stat_type" onchange="this.form.submit()" form="filterForm">
                        <?php foreach ($stat_types as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $stat_type == $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-box">
                    <label for="season">Mùa giải</label>
                    <select id="season" disabled>
                        <option>2024/25</option>
                    </select>
                </div>
                
                <div class="filter-box">
                    <label for="team">Câu lạc bộ</label>
                    <select id="team" disabled>
                        <option>Tất Cả Câu Lạc Bộ</option>
                    </select>
                </div>
                
                <div class="filter-box">
                    <label for="nationality">Quốc tịch</label>
                    <select id="nationality" disabled>
                        <option>Tất Cả Quốc Tịch</option>
                    </select>
                </div>
            </div>
            
            <form id="filterForm" method="GET" style="display: none;">
                <input type="hidden" name="page" value="1">
            </form>
            
            <?php if (!empty($players)): ?>
                <div class="table-responsive">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cầu Thủ</th>
                                <th>Câu Lạc Bộ</th>
                                <th>Quốc Tịch</th>
                                <th><?= $stat_label ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $index => $player): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td class="player-info">
                                        <?= htmlspecialchars($player['player_name']) ?>
                                    </td>
                                    <td>
                                        <div class="team-info">
                                            <img src="<?php echo BASE_URL; ?><?= htmlspecialchars($player['team_logo']) ?>" 
                                                 alt="<?= htmlspecialchars($player['team_name']) ?>" 
                                                 class="badge-image"
                                                 onerror="this.src='<?php echo BASE_URL; ?>assets/img/placeholder.png'">
                                            <span><?= htmlspecialchars($player['team_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($player['quoc_tich']) ?>
                                    </td>
                                    <td class="stat-value">
                                        <?= $player['stat_value'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination-container">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&stat_type=<?= $stat_type ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php 
                        // Hiển thị tối đa 5 trang xung quanh trang hiện tại
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&stat_type='.$stat_type.'">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = $i == $page ? 'active' : '';
                            echo '<li class="page-item '.$active.'"><a class="page-link" href="?page='.$i.'&stat_type='.$stat_type.'">'.$i.'</a></li>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&stat_type='.$stat_type.'">'.$total_pages.'</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&stat_type=<?= $stat_type ?>">
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
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include(__DIR__ . '/../../includes/master.php');
?>