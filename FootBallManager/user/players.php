<?php
require_once '../includes/config.php';
require_once '../controller/playerController.php';


// Khởi động session
session_start();


// Đặt tiêu đề trang
$title = "Cầu thủ";


// Lấy danh sách CLB cho thanh chọn
$teams = getAllTeams($conn);
$players = getAllPlayers($conn);
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;


if ($search_query) {
    $players = searchPlayersByName($conn, $search_query);
} elseif ($search_team_id > 0) {
    $players = searchPlayersByTeamId($conn, $search_team_id);
}




ob_start();
?>




<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách cầu thủ</title>
    <link rel="stylesheet" href="../assets/css/player-table.css"> <!-- Sửa đường dẫn CSS -->
</head>
<body>
    <div class="player-table-container">
        <h2>Danh sách cầu thủ</h2>
        <div class="search-bar">
            <input type="text" id="player-search-input" placeholder="Tìm kiếm cầu thủ..." value="<?php echo htmlspecialchars($search_query); ?>">
            <div id="player-suggestions" class="suggestions"></div>
            <button type="button" onclick="searchByPlayerName()">Tìm theo tên</button>
        </div>
        <div class="search-bar">
            <select id="team-select" name="team_id">
                <option value="0">Chọn CLB...</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo $team['team_id']; ?>" <?php echo $search_team_id == $team['team_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($team['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="searchByTeamId()">Tìm theo CLB</button>
        </div>
        <table class="player-table">
            <thead>
                <tr>
                    <th>Ảnh cầu thủ</th>
                    <th>Tên cầu thủ</th>
                    <th>Vị trí</th>
                    <th>Quốc tịch</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($players)): ?>
                    <tr>
                        <td colspan="4">Không tìm thấy cầu thủ nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($players as $player): ?>
                        <tr>
                            <td class="player-column">
                                <img src="../<?php echo htmlspecialchars($player['photo_url'] ?: 'assets/img/default_avatar.png'); ?>" alt="" class="player-image">
                            </td>
                            <td>
                                <a href="viewplayerdetail.php?player_id=<?php echo $player['player_id']; ?>">
                                    <?php echo htmlspecialchars($player['name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($player['position']); ?></td>
                            <td class="nationality-column">
                                <?php
                                $nationality = htmlspecialchars($player['nationality'] ?? 'Unknown');
                               
                                ?>
                                <img src="../<?php echo htmlspecialchars($player['nationality_flag_url'] ?: 'assets/img/default_avatar.png'); ?>" class="flag">
                                <span><?php echo $nationality; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>


    <script src="../assets/js/search.js"></script>
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

