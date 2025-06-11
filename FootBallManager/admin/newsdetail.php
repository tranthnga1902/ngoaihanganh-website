<?php
require_once '../includes/config.php';


if (!isset($_GET['news_id']) || !is_numeric($_GET['news_id'])) {
    die("ID tin tức không hợp lệ.");
}

$news_id = (int)$_GET['news_id'];

// Lấy thông tin tin tức
function getNewsDetail($conn, $news_id) {
    $sql = "SELECT n.*, c.category_name 
            FROM news n 
            LEFT JOIN categories c ON n.category_id = c.category_id 
            WHERE n.news_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $news_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Lấy danh sách bình luận
// function getComments($conn, $news_id) {
//     $sql = "SELECT c.*, u.username 
//             FROM comments c 
//             LEFT JOIN users u ON c.user_id = u.user_id 
//             WHERE c.news_id = ? 
//             ORDER BY c.comment_date DESC";
//     $stmt = mysqli_prepare($conn, $sql);
//     mysqli_stmt_bind_param($stmt, "i", $news_id);
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);
//     return mysqli_fetch_all($result, MYSQLI_ASSOC);
// }

$news = getNewsDetail($conn, $news_id);
//$comments = getComments($conn, $news_id);

if (!$news) {
    die("Tin tức không tồn tại.");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Tin tức - Quản lý Bóng đá</title>
    <link rel="stylesheet" href="../assets/css/newsDetail.css">
</head>
<body>
    >
    <div class="container">
        <div class="main-content">
            <h1><?php echo htmlspecialchars($news['title']); ?></h1>
            <div class="news-detail">
                <p><strong>Nội dung:</strong> <?php echo nl2br(htmlspecialchars($news['content'])); ?></p>
                <?php if ($news['image_url']): ?>
                    <img src="../<?php echo htmlspecialchars($news['image_url']); ?>" alt="Ảnh tin tức" class="detail-image">
                <?php endif; ?>
                <p><strong>Ngày đăng:</strong> <?php echo htmlspecialchars($news['publish_date']); ?></p>
                <p><strong>Tác giả:</strong> <?php echo htmlspecialchars($news['author'] ?: 'N/A'); ?></p>
                <p><strong>Lượt xem:</strong> <?php echo $news['views']; ?></p>
                <p><strong>Danh mục:</strong> <?php echo htmlspecialchars($news['category_name'] ?: 'N/A'); ?></p>
            </div>

            
            <a href="manageNews.php" class="btn btn-back">Quay lại</a>
        </div>
    </div>
</body>
</html>