<?php
// Khởi động session
session_start();

require_once '../includes/config.php';
require_once '../Controller/matchController.php';

// Lấy dữ liệu trận đấu từ cơ sở dữ liệu
$matches_by_date = getMatchesGroupedByDate($conn);

// Bắt đầu bộ đệm đầu ra
ob_start();

// Đặt tiêu đề trang
$title = "Lịch thi đấu";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch thi đấu</title>
    <link rel="stylesheet" href="../assets/css/components/schedule.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="schedule-container">
    <div class="schedule-header">
        <h1 class="schedule-title">Lịch thi đấu</h1>
        <div class="schedule-controls">
            <div class="date-filter">
                <input type="date" id="date-filter" class="date-input">
                <button class="filter-btn">Lọc</button>
            </div>
        </div>
    </div>
    
    <?php if (empty($matches_by_date)): ?>
        <div class="no-matches">
            <img src="../assets/images/no-matches.png" alt="No matches" class="no-matches-img">
            <p class="no-matches-text">Hiện không có trận đấu nào được lên lịch.</p>
        </div>
    <?php else: ?>
        <div class="match-days-container">
            <?php foreach ($matches_by_date as $date => $matches): ?>
                <?php
                $dateFormatted = date('l, d/m/Y', strtotime($date));
                $dayOfWeek = date('l', strtotime($date));
                ?>
                <div class="match-day-card">
                    <div class="match-day-header">
                        <div class="match-day-date">
                            <span class="day-of-week"><?php echo htmlspecialchars($dayOfWeek); ?></span>
                            <span class="full-date"><?php echo htmlspecialchars($dateFormatted); ?></span>
                        </div>
                        <div class="match-count-badge">
                            <?php echo count($matches); ?> trận
                        </div>
                    </div>
                    
                    <div class="matches-list">
                        <?php foreach ($matches as $match): ?>
                            <a href="<?php echo BASE_URL; ?>user/viewMatchesDetailBefore.php?match_id=<?php echo htmlspecialchars($match['match_id']); ?>" class="match-card-link">
                                <div class="match-card">
                                    <div class="match-teams">
                                        <div class="team home-team">
                                            <img src="<?php echo htmlspecialchars($match['home_logo']); ?>" alt="<?php echo htmlspecialchars($match['home_team']); ?>" class="team-logo">
                                            <span class="team-name"><?php echo htmlspecialchars($match['home_team']); ?></span>
                                        </div>
                                        
                                        <div class="match-info">
                                            <div class="match-time"><?php echo htmlspecialchars($match['time']); ?></div>
                                            <div class="vs-circle">VS</div>
                                        </div>
                                        
                                        <div class="team away-team">
                                            <span class="team-name"><?php echo htmlspecialchars($match['away_team']); ?></span>
                                            <img src="<?php echo htmlspecialchars($match['away_logo']); ?>" alt="<?php echo htmlspecialchars($match['away_team']); ?>" class="team-logo">
                                        </div>
                                    </div>
                                    
                                    <div class="match-details">
                                        <div class="stadium-info">
                                            <i class="stadium-icon">🏟️</i>
                                            <span><?php echo htmlspecialchars($match['stadium']); ?>, <?php echo $match['stadium_city']; ?></span>
                                        </div>
                                        <div class="view-details">
                                            <span>Chi tiết</span>
                                            <i class="arrow-icon">→</i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Simple date filter functionality
    document.querySelector('.filter-btn').addEventListener('click', function() {
        const selectedDate = document.getElementById('date-filter').value;
        if (selectedDate) {
            const dateParts = selectedDate.split('-');
            const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
            
            document.querySelectorAll('.match-day-card').forEach(card => {
                const cardDate = card.querySelector('.full-date').textContent;
                if (!cardDate.includes(formattedDate)) {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'block';
                }
            });
        } else {
            document.querySelectorAll('.match-day-card').forEach(card => {
                card.style.display = 'block';
            });
        }
    });
</script>
</body>
</html>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>