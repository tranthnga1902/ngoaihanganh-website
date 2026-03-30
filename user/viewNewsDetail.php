<?php
require_once '../Controller/NewsDetailController.php';
require_once '../includes/config.php';
$title = "Chi tiết tin tức";
$controller = new NewsDetailController($conn);
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$news = $controller->getNewsById($id);

if (!$news) {
    header("Location: news.php");
    exit();
}

$controller->incrementViews($id);
$relatedNews = $controller->getRelatedNews($news['category_id'], $id);
$latestNews = $controller->getLatestNews();

$title = htmlspecialchars($news['title']);
ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | Premier League</title>
    <!-- <base href="http://localhost:3000/"> Thêm dòng này -->
    <link rel="stylesheet" href="../assets/css/components/news-detail.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap">
    <style>
        /* Thêm CSS mới kết hợp với file hiện có */
        .news-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
        }
        
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .news-title {
            color: #2c1e5e;
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
            font-weight: 700;
        }
        
        .news-image-detail {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .news-content-detail {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
        }
        
        .news-content-detail p {
            margin-bottom: 1.5rem;
        }
        
        .related-news-title {
            font-size: 1.5rem;
            color: #2c1e5e;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .related-news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .related-news-item {
            display: block;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s;
        }
        
        .related-news-item:hover {
            transform: translateY(-5px);
        }
        
        .related-news-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .related-news-item h3 {
            font-size: 1rem;
            margin: 8px 0 5px;
            color: #2c1e5e;
        }
        
        .sidebar-title {
            font-size: 1.2rem;
            color: #2c1e5e;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .latest-news-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .latest-news-item img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .latest-news-item h4 {
            font-size: 0.9rem;
            margin: 0;
            color: #2c1e5e;
        }
        
        @media (max-width: 768px) {
            .news-detail-container {
                grid-template-columns: 1fr;
            }
            
            .news-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="news-detail-container">
        <div class="main-content">
            <article>
                <h1 class="news-title"><?php echo htmlspecialchars($news['title']  ?? ''); ?></h1>
                
                <div class="news-meta">
                    <span class="news-category"><?php echo htmlspecialchars($news['category_name']  ?? ''); ?></span>
                    <span class="news-date"><?php echo date('d/m/Y H:i', strtotime($news['publish_date'])); ?></span>
                    <span class="news-views">👁️ <?php echo number_format($news['views']); ?> views</span>
                    <span class="news-author">By <?php echo htmlspecialchars($news['author']); ?></span>
                </div>
                
                <?php if (!empty($news['image_url'])): ?>
                    <img src="../<?php echo htmlspecialchars($news['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                         class="news-image-detail">
                <?php endif; ?>
                
                <div class="news-content-detail">
                    <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                </div>

                <br>
                
                <!-- <h2 class="related-news-title">Tin liên quan</h2> -->
                <!-- <div class="related-news-grid">
                    <?php foreach ($relatedNews as $item): ?>
                    <a href="viewNewsDetail.php?id=<?php echo $item['news_id']; ?>" class="related-news-item">
                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <span class="news-date"><?php echo date('d/m/Y', strtotime($item['publish_date'])); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div> -->

                <div class="related-news-grid">
                    <?php foreach ($relatedNews as $item): ?>
                    <a href="<?php echo BASE_URL; ?>/user/viewNewsDetail.php?id=<?php echo $item['news_id']; ?>" class="related-news-item">
                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <span class="news-date"><?php echo date('d/m/Y', strtotime($item['publish_date'])); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

            </article>
        </div>

        <aside class="sidebar">
            <h3 class="sidebar-title">Tin mới nhất</h3>
            <?php foreach ($latestNews as $item): ?>
            <a href="<?php echo BASE_URL; ?>/user/viewNewsDetail.php?id=<?php echo $item['news_id']; ?>" class="latest-news-item">
                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
            </a>
            <?php endforeach; ?>
        </aside>
        
        <!-- <aside class="sidebar">
            <h3 class="sidebar-title">Tin mới nhất</h3>
            <?php foreach ($latestNews as $item): ?>
            <a href="viewNewsDetail.php?id=<?php echo $item['news_id']; ?>" class="latest-news-item">
                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
            </a>
            <?php endforeach; ?>
        </aside> -->


        
        
        <!-- Danh sách bình luận -->
        <div class="comments-list">
            <?php if (empty($comments)): ?>
                <p class="no-comments">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-author"><?php echo htmlspecialchars($comment['name']); ?></span>
                            <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    </div>
</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/master.php';
?>