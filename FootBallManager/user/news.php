<?php
// Khởi động session
session_start();

require_once '../includes/config.php';
require_once '../Controller/newsController.php';


// Khởi tạo controller
$newsController = new NewsController($conn);

// Xử lý filter và search
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_keyword)) {
    $news_items = $newsController->searchNews($search_keyword);
} else {
    $news_items = $newsController->getNewsByCategory($category_id);
}

// Lấy danh sách categories để hiển thị filter
$categories = $newsController->getAllCategories();


// Bắt đầu bộ đệm đầu ra
ob_start();

// Bao gồm tệp mẫu chính
//include '../includes/master.php';

// Đặt tiêu đề trang
$title = "Tin tức";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../assets/css/components/news.css">
    <!-- <base href="http://localhost:3000/"> Thêm dòng này -->
</head>
<body>
<div class="container">
    <div class="news-header">
        <h1>Tin tức bóng đá</h1>
        <form method="GET" action="" class="search-bar">
            <input type="text" name="search" placeholder="Tìm kiếm" class="search-input" value="<?php echo htmlspecialchars($search_keyword); ?>">
            <button type="submit" class="search-button">🔍</button>
        </form>
    </div>
    <div class="filter-section">
        <span class="filter-label">Lọc theo chủ đề</span>
        <form method="GET" action="" class="filter-form">
            <select name="category" class="category-filter" onchange="this.form.submit()">
                <option value="0">Tất cả</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_id == $category['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
                $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                ?>
                <a href="<?= htmlspecialchars($current_path) ?>" class="reset-filter">Bỏ lọc</a>
        </form>
    </div>
    <div class="news-list">
        <?php if (count($news_items) > 0): ?>
            <?php foreach ($news_items as $item): ?>
                <a href="<?php echo BASE_URL; ?>user/viewNewsDetail.php?id=<?php echo htmlspecialchars($item['news_id']); ?>" class="news-item">
                    <div class="news-card">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="news-image">
                        <?php endif; ?>
                        <div class="news-content">
                            <span class="news-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            <h3 class="news-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="news-summary">
                                <?php 
                                // Chuyển nội dung thành mảng các từ
                                $words = str_word_count($item['content'], 1, 'àáãạảăắằẳẵặâấầẩẫậèéẹẻẽêềếểễệđìíĩỉịòóõọỏôốồổỗộơớờởỡợùúũụủưứừửữựỳỵỷỹýÀÁÃẠẢĂẮẰẲẴẶÂẤẦẨẪẬÈÉẸẺẼÊỀẾỂỄỆĐÌÍĨỈỊÒÓÕỌỎÔỐỒỔỖỘƠỚỜỞỠỢÙÚŨỤỦƯỨỪỬỮỰỲỴỶỸÝ');
                                // Lấy tối đa 50 từ
                                $short_content = implode(' ', array_slice($words, 0, 50));
                                // Thêm dấu "..." nếu nội dung bị cắt
                                if (count($words) > 50) {
                                    $short_content .= '...';
                                }
                                echo htmlspecialchars($short_content);
                                ?>
                            </p>
                            <div class="news-meta">
                                <span class="news-date"><?php echo date('d/m/Y', strtotime($item['publish_date'])); ?></span>
                                <span class="news-views">👁️ <?php echo $item['views']; ?> lượt xem</span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <p>Không tìm thấy tin tức phù hợp.</p>
            </div>
        <?php endif; ?>
    </div>
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