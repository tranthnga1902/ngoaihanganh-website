<?php
// Kết nối file config và controller
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../Controller/resultController.php');

// Khởi tạo session
session_start();

// Lấy danh sách đội bóng cho dropdown filter
$teams = getTeams($conn);

// Xử lý filter theo đội (nếu có)
$teamId = isset($_POST['team_id']) && $_POST['team_id'] !== '' ? (int)$_POST['team_id'] : null;

// Xử lý phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15; // Giảm số lượng hiển thị từ 20 xuống 15
$offset = ($page - 1) * $limit;

// Lấy dữ liệu trận đấu
$matches = getMatchResults(null, $teamId, $limit, $offset);
$totalMatches = getTotalMatches($teamId);
$totalPages = ceil($totalMatches / $limit);

// Tiêu đề trang
$title = "Kết quả trận đấu";

// Bắt đầu buffer
ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Trang <?= $page ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<style>
    /* Reset CSS cơ bản */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: #230127;
        color: #2d3748;
        line-height: 1.5;
    }

    /* Container chính - thu gọn lại */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Tiêu đề trang - đơn giản hóa */
    .header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }

    .header h1 {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .header p {
        opacity: 0.9;
        font-size: 0.95rem;
    }

    /* Bộ lọc - gọn gàng hơn */
    .filter-box {
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .filter-form {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-form label {
        font-weight: 500;
        color: #4a5568;
        font-size: 0.9rem;
    }

    .filter-form select {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        color: #4a5568;
        min-width: 180px;
    }

    .filter-form select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
    }

    .filter-btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }

    .filter-btn:hover {
        background: #5a6fd8;
        transform: translateY(-1px);
    }

    /* Khu vực kết quả */
    .results-box {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px #37013c;
        margin-bottom: 20px;
    }

    .results-header {
        background: #f8fafc;
        padding: 12px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .results-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
    }

    .results-count {
        color: #718096;
        font-size: 0.85rem;
    }

    /* Danh sách trận đấu - thu gọn */
    .match-list {
        padding: 15px;
    }

    .match-item {
        border: 1px solid #230127;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 12px;
        transition: all 0.2s;
        cursor: pointer;
        background: white;
    }

    .match-item:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    /* Thông tin trận đấu - header gọn */
    .match-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        font-size: 1rem;
    }

    .match-date {
        background: #edf2f7;
        color: #4a5568;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
    }

    .match-round {
        background: #e6fffa;
        color: #38b2ac;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
    }

    /* Thông tin đội - layout đơn giản */
    .teams-info {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 20px;
    }

    .team {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .team.away {
        flex-direction: row-reverse;
        text-align: right;
    }

    /* Logo đội - nhỏ gọn hơn */
    .team-logo {
        width: 70px;
        height: 70px;
 
        display: flex;
        align-items: center;
        justify-content: center;
        
    }

    .team-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .team-name {
        font-weight: 800;
        color: #2d3748;
        font-size: 1.3rem;
    }

    .team-type {
        font-size: 0.75rem;
        color: #718096;
    }

    /* Tỷ số - gọn gàng */
    .score {
        text-align: center;
        background: #37013c;
        padding: 10px 15px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    .score-numbers {
        font-size: 2rem;
        font-weight: 700;
        color:rgb(255, 255, 255);
    }

    .score-label {
        font-size: 0.7rem;
        color:rgb(255, 255, 255);
        margin-top: 2px;
    }

    /* Footer trận đấu - đơn giản */
    .match-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }

    .stadium {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #718096;
        font-size: 0.8rem;
    }

    .details-btn {
        background: #48bb78;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .details-btn:hover {
        background: #38a169;
    }

    /* Phân trang - đơn giản */
    .pagination-box {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .page-link {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #4a5568;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }

    .page-link:hover {
        border-color: #667eea;
        color: #667eea;
    }

    .page-link.active {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }

    .page-info {
        color: #718096;
        font-size: 0.85rem;
        margin: 0 10px;
    }

    /* Thông báo không có kết quả */
    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #718096;
    }

    .no-results i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #cbd5e0;
    }

    .no-results h3 {
        font-size: 1.2rem;
        margin-bottom: 8px;
        color: #4a5568;
    }

    /* Responsive - điện thoại */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-form select {
            min-width: auto;
        }

        .teams-info {
            grid-template-columns: 1fr;
            gap: 10px;
            text-align: center;
        }

        .team.away {
            flex-direction: row;
            text-align: left;
        }

        .match-footer {
            flex-direction: column;
            gap: 8px;
        }

        .header h1 {
            font-size: 1.5rem;
        }
    }
</style>

<body>
    <div class="container">
        <!-- Tiêu đề trang -->
        <div class="header">
            <h1><i class="fas fa-futbol"></i> <?= $title ?></h1>
            <p>Theo dõi kết quả các trận đấu mới nhất</p>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-box">
            <form method="POST" action="" class="filter-form">
                <label for="team_id"><i class="fas fa-filter"></i> Lọc theo đội:</label>
                <select name="team_id" id="team_id">
                    <option value="">-- Tất cả đội --</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['team_id'] ?>" <?= ($teamId == $team['team_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="filter-btn">
                    <i class="fas fa-search"></i> Lọc
                </button>
            </form>
        </div>

        <!-- Kết quả trận đấu -->
        <div class="results-box">
            <div class="results-header">
                <h2 class="results-title">
                    <i class="fas fa-list"></i> Kết quả trận đấu
                </h2>
                <div class="results-count">
                    <?= count($matches) ?> / <?= $totalMatches ?> trận
                </div>
            </div>

            <div class="match-list">
                <?php if (empty($matches)): ?>
                    <!-- Không có kết quả -->
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Không tìm thấy kết quả</h3>
                        <p>Chưa có trận đấu nào phù hợp với bộ lọc.</p>
                    </div>
                <?php else: ?>
                    <!-- Danh sách trận đấu -->
                    <?php foreach ($matches as $match): ?>
                        <div class="match-item" data-match-id="<?= htmlspecialchars($match['match_id']) ?>">
                            <!-- Thông tin trận đấu -->
                            <div class="match-info">
                                <div class="match-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?>
                                </div>
                                <div class="match-round">
                                    Vòng <?= htmlspecialchars($match['round']) ?>
                                </div>
                            </div>

                            <!-- Thông tin các đội -->
                            <div class="teams-info">
                                <!-- Đội nhà -->
                                <div class="team">
                                    <div class="team-logo">
                                        <img src="<?= BASE_URL . htmlspecialchars($match['home_logo']) ?>" 
                                             alt="<?= htmlspecialchars($match['home_team']) ?>">
                                    </div>
                                    <div>
                                        <div class="team-name"><?= htmlspecialchars($match['home_team']) ?></div>
                                        <div class="team-type">Nhà</div>
                                    </div>
                                </div>

                                <!-- Tỷ số -->
                                <div class="score">
                                    <div class="score-numbers">
                                        <?= htmlspecialchars($match['home_team_score']) ?> - <?= htmlspecialchars($match['away_team_score']) ?>
                                    </div>
                                    <div class="score-label">KẾT QUẢ</div>
                                </div>

                                <!-- Đội khách -->
                                <div class="team away">
                                    <div class="team-logo">
                                        <img src="<?= BASE_URL . htmlspecialchars($match['away_logo']) ?>" 
                                             alt="<?= htmlspecialchars($match['away_team']) ?>">
                                    </div>
                                    <div>
                                        <div class="team-name"><?= htmlspecialchars($match['away_team']) ?></div>
                                        <div class="team-type">Khách</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer trận đấu -->
                            <div class="match-footer">
                                <div class="stadium">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($match['stadium_name']) ?>
                                </div>
                                <button class="details-btn">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Phân trang -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-box">
                <!-- Nút Trước -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $teamId ? '&team_id=' . $teamId : '' ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Trước
                    </a>
                <?php endif; ?>

                <!-- Số trang -->
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                // Hiển thị trang đầu
                if ($startPage > 1) {
                    echo '<a href="?page=1' . ($teamId ? '&team_id=' . $teamId : '') . '" class="page-link">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="page-link">...</span>';
                    }
                }
                
                // Hiển thị các trang xung quanh trang hiện tại
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $activeClass = ($i == $page) ? 'active' : '';
                    echo '<a href="?page=' . $i . ($teamId ? '&team_id=' . $teamId : '') . '" class="page-link ' . $activeClass . '">' . $i . '</a>';
                }
                
                // Hiển thị trang cuối
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="page-link">...</span>';
                    }
                    echo '<a href="?page=' . $totalPages . ($teamId ? '&team_id=' . $teamId : '') . '" class="page-link">' . $totalPages . '</a>';
                }
                ?>

                <!-- Nút Sau -->
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $teamId ? '&team_id=' . $teamId : '' ?>" class="page-link">
                        Sau <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>

                <div class="page-info">
                    Trang <?= $page ?> / <?= $totalPages ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Xử lý click vào thẻ trận đấu
        document.querySelectorAll('.match-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Không chuyển trang nếu click vào nút chi tiết
                if (!e.target.closest('.details-btn')) {
                    const matchId = this.getAttribute('data-match-id');
                    window.location.href = 'viewMatchesDetail.php?match_id=' + encodeURIComponent(matchId);
                }
            });
        });

        // Xử lý click nút chi tiết
        document.querySelectorAll('.details-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // Ngăn không cho bubble up
                const matchItem = this.closest('.match-item');
                const matchId = matchItem.getAttribute('data-match-id');
                window.location.href = 'viewMatchesDetail.php?match_id=' + encodeURIComponent(matchId);
            });
        });

        // Hiệu ứng loading khi submit form
        document.querySelector('.filter-form').addEventListener('submit', function() {
            const btn = this.querySelector('.filter-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lọc...';
            btn.disabled = true;
            
            // Khôi phục nút sau 3 giây (phòng trường hợp lỗi)
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        });

        // Cuộn lên đầu trang sau khi chuyển trang
        if (window.location.search.includes('page=')) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>

<?php
// Lấy nội dung từ buffer
$content = ob_get_clean();

// Include template chính
include(__DIR__ . '/../includes/master.php');
?>