<?php
// Khởi động session
session_start();
// Bao gồm tệp cấu hình
include '../includes/config.php';
// Bắt đầu bộ đệm đầu ra
ob_start();
// Đặt tiêu đề trang
$title = "Chi tiết cầu thủ";

?>


<?php


// Lấy player_id từ URL
$player_id = isset($_GET['player_id']) ? (int)$_GET['player_id'] : 0;
$player = null;


if ($player_id > 0) {
    // Truy vấn thông tin cầu thủ
    $sql = "SELECT p.*, t.name as team_name
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.team_id
            WHERE p.player_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $player = $result->fetch_assoc();
    $stmt->close();
}


// $conn->close();


$team_name = $player['team_name'] ?? 'Unknown';
$nationality = $player['nationality'] ?? 'Unknown';


// Gán màu theo tên CLB Premier League
$background_color = match ($team_name) {
    'Manchester United' => 'rgb(164, 0, 30)', // Đỏ
    'Liverpool FC' => '#C8102E',       // Đỏ sẫm
    'Chelsea FC' => '#034694',         // Xanh đậm
    'Arsenal FC' => '#EF0107',         // Đỏ tươi
    'Manchester City' => '#6CABDD',    // Xanh da trời
    'Tottenham Hotspur' => '#132257', // Xanh đậm
    'Newcastle United' => 'rgb(84, 83, 83)',   // Đen xám
    'Aston Villa' => 'rgb(141, 39, 88)',    
    'Brighton & Hove Albion FC' => '#0057B8', // Xanh biển
    'Brentford' => '#E30613',          // Đỏ
    'Fulham' => 'rgb(190, 190, 190)',             // Đen
    'Crystal Palace' => '#1B458F',     // Xanh đỏ
    'Everton' => '#003399',            // Xanh lam
    'West Ham United' => '#7A263A',    // Rượu vang
    'Wolverhampton Wanderers' => '#FDB913', // Vàng
    'AFC Bournemouth' => '#DA292C',        // Đỏ
    'Leicester City' => '#003090',     // Xanh hoàng gia
    'Southampton' => 'rgb(128, 18, 36)',        // Đỏ
    'Nottingham Forest' => 'rgb(192, 45, 79)',  // Đỏ
    'Ipswich Town' => '#0057B8',       // Xanh lam
    default => '#f0f0f0',              // Xám nhạt nếu không khớp
};


?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết cầu thủ</title>
    <link rel="stylesheet" href="../assets/css/player-detail.css">
    <style>
        body {
            background-color: <?php echo $background_color; ?>;
        }
        .header-background {
            background-color: <?php echo $background_color; ?>;
        }
        
    </style>
</head>
<body>
    <div class="player-detail-container">
        <div class="header-background"></div>
        <?php if ($player): ?>
            <h2>Chi tiết cầu thủ: <?php echo htmlspecialchars($player['name']  ?? ''); ?></h2>
            <div class="jersey-number"><?php echo htmlspecialchars($player['jersey_number'] ?? ''); ?></div>
            <div class="player-info">
            <img src="../<?php echo htmlspecialchars($player['photo_url'] ?: 'assets/img/default_avatar.png'); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>">
            <div>
                <table>
                    <tr><td><strong>Tên:</strong></td><td><?php echo htmlspecialchars($player['name']  ?? ''); ?></td></tr>
                    <tr><td><strong>Vị trí:</strong></td><td><?php echo htmlspecialchars($player['position']  ?? ''); ?></td></tr>
                    <tr>
                        <td><strong>Quốc tịch:</strong></td>
                        <td><img src="../<?php echo htmlspecialchars($player['nationality_flag_url'] ?: 'assets/img/default_avatar.png'); ?>" alt="" class="flag"></td>
                    </tr>
                    <tr><td><strong>CLB:</strong></td><td><?php echo htmlspecialchars($player['team_name'] ?? 'Không có CLB'); ?></td></tr>
                </table>
            </div>
        </div>


        <div class="player-stats">
             <table>
                <tr>
                    <td><strong>Ngày sinh:</strong> <?php echo htmlspecialchars($player['birth_date'] ?? 'N/A'); ?></td>
                    <td><strong>Chiều cao:</strong> <?php echo htmlspecialchars($player['height'] ?? 'N/A'); ?> cm</td>
                    <td><strong>Cân nặng:</strong> <?php echo htmlspecialchars($player['weight'] ?? 'N/A'); ?> kg</td>
                    <td><strong>Số áo:</strong> <?php echo htmlspecialchars($player['jersey_number'] ?? 'N/A'); ?></td>
                </tr>
  </table>
        </div>
        <?php else: ?>
            <h2>Không tìm thấy cầu thủ</h2>
            <p>Không có thông tin về cầu thủ này.</p>
        <?php endif; ?>
        <a href="players.php" class="back-link">Quay lại danh sách cầu thủ</a>
    </div>
</body>
</html>




<!-- Nội dung chính -->
<main>


</main>


<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();


// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>

