<?php
session_start();
require_once '../includes/config.php';
require_once '../Controller/matchesDetailBeforeController.php';

// Lấy ID trận đấu từ URL
$match_id = isset($_GET['match_id']) && is_numeric($_GET['match_id']) ? intval($_GET['match_id']) : null;

// Kiểm tra match_id hợp lệ
if ($match_id === null) {
    die("Không tìm thấy ID trận đấu!");
}

$match = getMatchDetails($conn, $match_id);

// Nếu không có trận đấu, hiển thị thông báo
if (!$match) {
    die("Trận đấu không tồn tại!");
}

// Lấy thông tin huấn luyện viên
$home_manager = getTeamManager($conn, $match['home_team_id']);
$away_manager = getTeamManager($conn, $match['away_team_id']);

// Lấy danh sách cầu thủ
$home_players = getTeamPlayers($conn, $match['home_team_id']);
$away_players = getTeamPlayers($conn, $match['away_team_id']);

ob_start();
$title = "Chi tiết trận đấu";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></title>
    <link rel="stylesheet" href="../assets/css/viewMatchesDetailBefore.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="match-detail-container">
    <!-- Phần header thông tin trận đấu -->
    <div class="match-header">
        <div class="team-logos">
            <div class="team-logo-container">
                <img src="../<?= htmlspecialchars($match['home_logo']) ?>" alt="<?= htmlspecialchars($match['home_team']) ?> logo" class="team-logo" onerror="this.src='uploads/teams/default_logo.png';">
                <div class="team-name"><?= htmlspecialchars($match['home_team']) ?></div>
            </div>
            
            <div class="match-info">
                <div class="match-time">
                    <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                    <i class="far fa-clock"></i> <?= htmlspecialchars($match['match_time']) ?>
                </div>
                <div class="stadium-info">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($match['stadium']) ?>, <?= htmlspecialchars($match['stadium_city']) ?>
                </div>
            </div>
            
            <div class="team-logo-container">
                <img src="../<?= htmlspecialchars($match['away_logo']) ?>" alt="<?= htmlspecialchars($match['away_team']) ?> logo" class="team-logo" onerror="this.src='uploads/teams/default_logo.png';">
                <div class="team-name"><?= htmlspecialchars($match['away_team']) ?></div>
            </div>
        </div>
    </div>

    <!-- Phần hiển thị đội hình -->
    <div class="lineup-container">
        <div class="team-lineup home-team">
            <div class="team-header">
                <h2><?= htmlspecialchars($match['home_team']) ?></h2>
            </div>
            
            <div class="manager-info">
                <div class="manager-title">Huấn luyện viên:</div>
                <div class="manager-container">
                    <?php if ($home_manager['photo_url'] && !empty($home_manager['photo_url'])): ?>
                        <img src="../<?= htmlspecialchars($home_manager['photo_url']) ?>" alt="<?= htmlspecialchars($home_manager['name'] ?? 'Chưa có thông tin') ?> avatar" class="manager-avatar">
                    <?php else: ?>
                        <div class="manager-no-image"></div>
                    <?php endif; ?>
                    <div class="manager-name"><?= htmlspecialchars($home_manager['name'] ?? 'Chưa có thông tin') ?></div>
                </div>
            </div>
            
            <div class="players-section">
                <h3><i class="fas fa-tshirt"></i> Thủ môn</h3>
                <?php $goalkeeper = array_filter($home_players, fn($p) => $p['position'] == 'Thủ môn'); 
                foreach ($goalkeeper as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
                
                <h3><i class="fas fa-shield-alt"></i> Hậu vệ</h3>
                <?php $defenders = array_filter($home_players, fn($p) => $p['position'] == 'Hậu vệ'); 
                foreach ($defenders as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
                
                <h3><i class="fas fa-running"></i> Tiền vệ</h3>
                <?php $midfielders = array_filter($home_players, fn($p) => $p['position'] == 'Tiền vệ'); 
                foreach ($midfielders as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
                
                <h3><i class="fas fa-futbol"></i> Tiền đạo</h3>
                <?php $forwards = array_filter($home_players, fn($p) => $p['position'] == 'Tiền đạo'); 
                foreach ($forwards as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="team-lineup away-team">
            <div class="team-header">
                <h2><?= htmlspecialchars($match['away_team']) ?></h2>
            </div>
            
            <div class="manager-info">
                <div class="manager-title">Huấn luyện viên:</div>
                <div class="manager-container">
                    <?php if ($away_manager['photo_url'] && !empty($away_manager['photo_url'])): ?>
                        <img src="../<?= htmlspecialchars($away_manager['photo_url']) ?>" alt="<?= htmlspecialchars($away_manager['name'] ?? 'Chưa có thông tin') ?> avatar" class="manager-avatar">
                    <?php else: ?>
                        <div class="manager-no-image"></div>
                    <?php endif; ?>
                    <div class="manager-name"><?= htmlspecialchars($away_manager['name'] ?? 'Chưa có thông tin') ?></div>
                </div>
            </div>
            
            <div class="players-section">
                <h3><i class="fas fa-tshirt"></i> Thủ môn</h3>
                <?php $goalkeeper = array_filter($away_players, fn($p) => $p['position'] == 'Thủ môn'); 
                foreach ($goalkeeper as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
                
                <h3><i class="fas fa-shield-alt"></i> Hậu vệ</h3>
                <?php $defenders = array_filter($away_players, fn($p) => $p['position'] == 'Hậu vệ'); 
                foreach ($defenders as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
                
                <h3><i class="fas fa-running"></i> Tiền vệ</h3>
                <?php $midfielders = array_filter($away_players, fn($p) => $p['position'] == 'Tiền vệ'); 
                foreach ($midfielders as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
                
                <h3><i class="fas fa-futbol"></i> Tiền đạo</h3>
                <?php $forwards = array_filter($away_players, fn($p) => $p['position'] == 'Tiền đạo'); 
                foreach ($forwards as $player): ?>
                    <div class="player">
                        <?php if ($player['photo_url'] && !empty($player['photo_url'])): ?>
                            <img src="../<?= htmlspecialchars($player['photo_url']) ?>" alt="<?= htmlspecialchars($player['name']) ?> avatar" class="player-avatar">
                        <?php else: ?>
                            <div class="no-image"></div>
                        <?php endif; ?>
                        <div class="player-number"><?= $player['jersey_number'] ?></div>
                        <div class="player-name"><?= $player['name'] ?></div>
                        <div class="player-flag"><i class="fas fa-flag"></i> <?= $player['nationality'] ?? '' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/master.php';
?>