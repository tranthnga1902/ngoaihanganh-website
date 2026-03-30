<?php
require_once '../includes/config.php';
require_once '../controller/teamController.php';


// Lấy nội dung từ bộ đệm
// $content = ob_get_clean();
$title = "Danh sách câu lạc bộ";


// $teams = getAllTeams($conn); tìm kiếm theo tên
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search_query) {
    $teams = searchTeamsByName($conn, $search_query);
} else {
    $teams = getAllTeams($conn);
}
ob_start()
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách câu lạc bộ</title>
   
    <link rel="stylesheet" href="../assets/css/components/card.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>
<body>
   


    <div class="container">
        <h2>Danh sách câu lạc bộ</h2>
      <!-- Form tìm kiếm -->
    <form method="GET" action="" class="search-form">
        <div class="search-bar">
            <input type="text" name="q" id="search-input"
                placeholder="Tìm kiếm theo tên CLB..."
                value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> <!-- icon kính lúp -->
            </button>
        </div>
        <div id="suggestions" class="suggestions"></div>
    </form>
        <div class="team-list">
            <?php if (empty($teams)): ?>
                <p class="no-results">Không tìm thấy câu lạc bộ nào phù hợp với "<?php echo htmlspecialchars($search_query); ?>"</p>
            <?php else: ?>
                <?php foreach ($teams as $team): ?>
                    <div class="team-card">
                        <a href="viewTeamDetail.php?team_id=<?php echo $team['team_id']; ?>">
                            <img src="../<?php echo htmlspecialchars($team['logo_url']  ?? ''); ?>" alt="<?php echo htmlspecialchars($team['name']); ?>" class="team-logo">
                            <h3><?php echo htmlspecialchars($team['name']); ?></h3>
                            <p>Sân nhà: <?php echo htmlspecialchars($team['stadium_name']  ?? ''); ?></p>
                            <p>Thành phố: <?php echo htmlspecialchars($team['city']  ?? ''); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>




    <script src="../assets/js/search.js"></script>
</body>
</html>


<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();


// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>

