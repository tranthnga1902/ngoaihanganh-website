<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../Controller/EditMatchController.php';


// Khởi động session
session_start();



// Lấy match_id từ URL và validate
$matchId = isset($_GET['match_id']) && is_numeric($_GET['match_id']) && (int)$_GET['match_id'] > 0 ? (int)$_GET['match_id'] : null;

if (!$matchId) {
    $_SESSION['error'] = 'ID trận đấu không hợp lệ.';
    header('Location: matchResults.php');
    exit();
}

// Khởi tạo controller và lấy chi tiết trận đấu
try {
    $controller = new EditMatchController($conn);
    $matchDetails = $controller->getMatchDetails($matchId);
    
    if (!$matchDetails['success']) {
        $_SESSION['error'] = $matchDetails['error'];
        header('Location: matchResults.php');
        exit();
    }

    $match = $matchDetails['data']['match'];
    $events = $matchDetails['data']['events'];
} catch (Exception $e) {
    $_SESSION['error'] = 'Lỗi khi lấy thông tin trận đấu: ' . htmlspecialchars($e->getMessage());
    header('Location: matchResults.php');
    exit();
}

// Đặt tiêu đề trang
$title = "Thông tin trận đấu - " . htmlspecialchars($match['home_team']) . " vs " . htmlspecialchars($match['away_team']);

// Bắt đầu bộ đệm đầu ra
ob_start();
?>



<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../assets/css/MatchesDetail.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="matchResults.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Quay lại danh sách kết quả
        </a>

        <!-- Match Header -->
        <div class="match-header">
            <div class="match-teams">
                <!-- Home Team -->
                <div class="team-info">
                    <div class="team-logo">
                        <img src="<?= htmlspecialchars($match['home_team_logo'] ? BASE_URL . $match['home_team_logo'] : BASE_URL . 'images/default_logo.png') ?>" 
                             alt="<?= htmlspecialchars($match['home_team']) ?>">
                    </div>
                    <div class="team-name"><?= htmlspecialchars($match['home_team']) ?></div>
                </div>

                <!-- Score -->
                <div class="final-score">
                    <div class="score">
                        <?= htmlspecialchars($match['home_team_score']) ?> - <?= htmlspecialchars($match['away_team_score']) ?>
                    </div>
                    <div class="match-status"><?= htmlspecialchars($match['status']) ?></div>
                </div>

                <!-- Away Team -->
                <div class="team-info away">
                    <div class="team-logo">
                        <img src="<?= htmlspecialchars($match['away_team_logo'] ? BASE_URL . $match['away_team_logo'] : BASE_URL . 'images/default_logo.png') ?>" 
                             alt="<?= htmlspecialchars($match['away_team']) ?>">
                    </div>
                    <div class="team-name"><?= htmlspecialchars($match['away_team']) ?></div>
                </div>
            </div>

            <!-- Match Details -->
            <div class="match-details">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="detail-label">Ngày thi đấu</div>
                    <div class="detail-value">
                        <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="detail-label">Giờ thi đấu</div>
                    <div class="detail-value">
                        <?= date('H:i', strtotime($match['match_date'])) ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="detail-label">Mùa giải</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($match['season_name']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="events-section">
            <div class="events-header">
                <i class="fas fa-list-ul" style="color: #667eea; font-size: 1.5rem;"></i>
                <h2>Diễn biến trận đấu</h2>
            </div>

            <?php if (empty($events)): ?>
                <div class="no-events">
                    <i class="fas fa-futbol"></i>
                    <h3>Chưa có sự kiện nào</h3>
                    <p>Hiện tại chưa có thông tin về các sự kiện trong trận đấu này.</p>
                </div>
            <?php else: ?>
                <div class="events-timeline">
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <div class="event-content">
                                <div class="event-time">
                                    <?= htmlspecialchars($event['minute']) ?>'
                                </div>

                                <div class="event-icon <?= htmlspecialchars(getEventIconClass($event['event_type'])) ?>">
                                    <i class="<?= htmlspecialchars(getEventIcon($event['event_type'])) ?>"></i>
                                </div>

                                <div class="event-details">
                                    <div class="event-description">
                                        <?= getEventDescription($event) ?>
                                    </div>
                                    <div class="event-team">
                                        <?= htmlspecialchars($event['team_name']) ?>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    

    <script>
        // Thêm hiệu ứng scroll smooth cho timeline
        document.addEventListener('DOMContentLoaded', function() {
            const eventItems = document.querySelectorAll('.event-item');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateX(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            eventItems.forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                item.style.transition = 'all 0.6s ease';
                observer.observe(item);
            });
        });
    </script>
</body>
</html>

<?php
// Các hàm helper cho events
function getEventIcon($eventType) {
    switch (strtolower($eventType)) {
        case 'goal':
        case 'penalty_scored':
            return 'fas fa-futbol';
        case 'yellow_card':
            return 'fas fa-square';
        case 'red_card':
            return 'fas fa-square';
        case 'substitution':
            return 'fas fa-exchange-alt';
        case 'penalty_missed':
        case 'penalty':
            return 'fas fa-bullseye';
        case 'own_goal':
            return 'fas fa-futbol';
        case 'assist':
            return 'fas fa-hands-helping';
        case 'save':
            return 'fas fa-shield-alt';
        default:
            return 'fas fa-circle';
    }
}

function getEventIconClass($eventType) {
    switch (strtolower($eventType)) {
        case 'goal':
        case 'penalty_scored':
        case 'own_goal':
            return 'goal';
        case 'yellow_card':
            return 'card';
        case 'red_card':
            return 'red-card';
        case 'substitution':
            return 'substitution';
        case 'penalty_missed':
        case 'penalty':
            return 'penalty';
        case 'assist':
            return 'goal';
        case 'save':
            return 'save';
        default:
            return 'goal';
    }
}

function getEventDescription($event) {
    $description = '';
    
    switch (strtolower($event['event_type'])) {
        case 'goal':
            $description = '<i class="fas fa-futbol"></i> Bàn thắng';
            break;
        case 'penalty_scored':
            $description = '<i class="fas fa-bullseye"></i> Phạt đền (Ghi bàn)';
            break;
        case 'penalty_missed':
            $description = '<i class="fas fa-bullseye"></i> Phạt đền (Sút hỏng)';
            break;
        case 'own_goal':
            $description = '<i class="fas fa-futbol"></i> Phản lưới nhà';
            break;
        case 'yellow_card':
            $description = '<i class="fas fa-square" style="color: #ffd700;"></i> Thẻ vàng';
            break;
        case 'red_card':
            $description = '<i class="fas fa-square" style="color: #ff0000;"></i> Thẻ đỏ';
            break;
        case 'substitution':
            $description = '<i class="fas fa-exchange-alt"></i> Thay người';
            break;
        case 'assist':
            $description = '<i class="fas fa-hands-helping"></i> Kiến tạo';
            break;
        case 'save':
            $description = '<i class="fas fa-shield-alt"></i> Cứu thua';
            break;
        default:
            $description = htmlspecialchars($event['event_type']);
    }
    
    if (!empty($event['player_name'])) {
        $description .= ' - ' . htmlspecialchars($event['player_name']);
    }
    
    if (!empty($event['note'])) {
        $description .= '<br><small>' . htmlspecialchars($event['note']) . '</small>';
    }
    
    return $description;
}
?>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();


// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>

