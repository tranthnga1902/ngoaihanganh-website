<?php
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../Controller/standingController.php');

// Khởi tạo kết nối cơ sở dữ liệu và standingController
try {
    $standingController = new standingController($conn);
} catch (Exception $e) {
    error_log("Lỗi khởi tạo standingController: " . $e->getMessage());
    die("<p class='text-red-500'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Khởi động session
session_start();



// Đặt tiêu đề trang
$title = "Bảng xếp hạng";

// Lấy dữ liệu cần thiết
$seasons = $standingController->getSeasons();
$latestMatchweek = $standingController->getLatestMatchweek('2024/2025');
$matchweeks = $standingController->getMatchweeks('2024/2025');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seasonName = $_POST['season'] ?? '2024/2025';
    $matchweek = $_POST['matchweek'] ?? null;
    $filterType = $_POST['filter_type'] ?? 'all';
    if (isset($_POST['reset_filters'])) {
        $seasonName = '2024/2025';
        $matchweek = null;
        $filterType = 'all';
    }
    $matchweek = $matchweek !== '' ? filter_var($matchweek, FILTER_SANITIZE_NUMBER_INT) : null;
    $standings = $standingController->getStandings($seasonName, $matchweek, $filterType);
} else {
    $seasonName = '2024/2025';
    $matchweek = null;
    $filterType = 'all';
    $standings = $standingController->getStandings($seasonName, $matchweek, $filterType);
}

// Bắt đầu bộ đệm đầu ra
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premier League Standings</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

        .container {
            margin: 0px auto;
        }
        .form-circle {
            display: inline-block;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            margin-right: 3px;
            text-align: center;
            line-height: 22px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .form-w { background-color: #10B981; } /* Win - Emerald 500 */
        .form-d { background-color: #6B7280; } /* Draw - Gray 500 */
        .form-l { background-color: #EF4444; } /* Loss - Red 500 */
        
        tbody tr:hover {
            background-color: #EDE9FE; /* Purple 100 */
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        .standing-banner {
            background: linear-gradient(135deg,rgb(35, 4, 49) 0%,rgb(95, 30, 138) 100%);
            color: white;
            font-size: 3rem;
            font-weight: 700;
            padding: 1.5rem 2rem;
            text-align: center;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .club-name {
            font-weight: 600;
            color: #1F2937; /* Gray 800 */
        }
        
        .table-header {
            background-color:#37003c; 
            color: white;
        }
        
        .filter-section {
            background-color: #F9FAFB; /* Gray 50 */
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .filter-label {
            font-weight: 600;
            color: #4B5563; /* Gray 600 */
            margin-right: 0.5rem;
        }
        
        .filter-select {
            border: 1px solid #D1D5DB; /* Gray 300 */
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            background-color: white;
            box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .filter-button {
            background-color: #37003c; /* Blue 800 */
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .filter-button:hover {
            background-color: #37003c; /* Blue 900 */
            transform: translateY(-1px);
        }
        
        .reset-button {
            background-color: #E5E7EB; /* Gray 200 */
            color: #4B5563; /* Gray 600 */
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .reset-button:hover {
            background-color: #D1D5DB; /* Gray 300 */
        }
        
        .club-page-button {
            background-color: #37003c; /* Purple 600 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .club-page-button:hover {
            background-color: #6D28D9; /* Purple 700 */
        }
        
        .position-cell {
            font-weight: 700;
            color: #37003c; /* Blue 800 */
        }
        
        .points-cell {
            font-weight: 700;
            color: #37003c; /* Blue 800 */
        }
        /* Container chung */
.chung {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0px;

margin-bottom: 10px
}

/* Container header ngang */
.header-container-stan {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 10px;
    margin-right: 20px;
    margin-left: 0px;
}

/* Phần tiêu đề bên trái */
.title-section {
    flex: 1;
    min-width: 100px;
    margin-bottom: 50px;
}

.title-section h1 {
    font-size: 30px;
    color: #37003c;
    margin-bottom: 5px;
    font-weight: 800;
}

.title-section p {
    color: #666;
    font-size: 18px;
}

/* Phần bộ lọc bên phải */
.filter-section {
    flex: 2;
    min-width: 1000px;
}

.filter-form {
    display: flex;
    align-items: center;
    gap: 15px;
}
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Results Banner -->
        <div class="standing-banner">
            <i class="fas fa-trophy mr-3"></i> BẢNG XẾP HẠNG
        </div>

        <div class="chung">
            <div class="header-container-stan">
                <!-- Phần tiêu đề bên trái -->
                <div class="title-section">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Premier League</h1>
                    <p class="text-gray-600">Cập nhật lần cuối: <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <!-- Phần bộ lọc bên phải -->
                <div class="filter-section">
                    <form method="POST" class="filter-form">
                        <div class="filter-group">
                            <span class="filter-label">Mùa giải:</span>
                            <select name="season" class="filter-select">
                                <?php foreach ($seasons as $season): ?>
                                    <option value="<?php echo htmlspecialchars($season); ?>" <?php echo $season === $seasonName ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($season); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <span class="filter-label">Vòng đấu:</span>
                            <select name="matchweek" class="filter-select">
                                <?php foreach ($matchweeks as $week): ?>
                                    <option value="<?php echo htmlspecialchars($week ?? ''); ?>" <?php echo $week === $matchweek ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($week ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <span class="filter-label">Loại:</span>
                            <select name="filter_type" class="filter-select">
                                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                <option value="home" <?php echo $filterType === 'home' ? 'selected' : ''; ?>>Sân nhà</option>
                                <option value="away" <?php echo $filterType === 'away' ? 'selected' : ''; ?>>Sân khách</option>
                            </select>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="filter-button">
                                <i class="fas fa-filter mr-2"></i> Áp dụng
                            </button>
                            <button type="submit" name="reset_filters" class="reset-button">
                                <i class="fas fa-redo mr-2"></i> Đặt lại
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Bảng xếp hạng -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="table-header">
                        <th class="py-3 px-4 text-left font-semibold text-white">#</th>
                        <th class="py-3 px-4 text-left font-semibold text-white">Đội bóng</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Trận</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Thắng</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Hòa</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Thua</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">BT</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">BB</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">HS</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Điểm</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Phong độ</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Trận tới</th>
                        <th class="py-3 px-4 text-center font-semibold text-white">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($standings as $team): ?>
                        <tr class="hover:bg-purple-50">
                            <td class="py-3 px-4 position-cell"><?php echo $team['position']; ?></td>
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($team['logo_url']); ?>" alt="<?php echo htmlspecialchars($team['team_name']); ?>" class="w-8 h-8 mr-3">
                                    <span class="club-name"><?php echo htmlspecialchars($team['team_name']); ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center"><?php echo $team['matches_played']; ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $team['wins']; ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $team['draws']; ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $team['losses']; ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $team['goals_for']; ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $team['goals_against']; ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $team['goal_difference']; ?></td>
                            <td class="py-3 px-4 text-center points-cell"><?php echo $team['points']; ?></td>
                            <td class="py-3 px-4 text-center">
                                <?php foreach ($team['form'] as $result): ?>
                                    <span class="form-circle form-<?php echo strtolower($result); ?>">
                                        <?php echo $result; ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($team['next_match']): ?>
                                    <div class="flex flex-col items-center">
                                        <div class="flex items-center">
                                            <img src="<?php echo BASE_URL . htmlspecialchars($team['next_match']['home_team_id'] == $team['team_id'] ? $team['next_match']['away_logo'] : $team['next_match']['home_logo']); ?>" 
                                                 alt="Next Opponent" 
                                                 class="w-6 h-6">
                                        </div>
                                        <span class="text-xs text-gray-500 mt-1">
                                            <?php echo date('d/m', strtotime($team['next_match']['match_date'])); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button class="club-page-button">
                                    <i class="fas fa-chevron-right mr-1"></i> Xem
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include(__DIR__ . '/../includes/master.php');
?>