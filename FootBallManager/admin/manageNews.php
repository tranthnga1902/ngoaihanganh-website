<?php
require_once '../includes/config.php';

// Xử lý tìm kiếm và lọc
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Lấy danh sách tin tức
$sql = "SELECT n.*, c.category_name 
        FROM news n 
        LEFT JOIN categories c ON n.category_id = c.category_id 
        WHERE 1=1";
if ($search) $sql .= " AND n.title LIKE '%$search%'";
if ($category_id > 0) $sql .= " AND n.category_id = $category_id";
$sql .= " LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
$news_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Đếm tổng số bản ghi
$sql = "SELECT COUNT(*) as total FROM news WHERE 1=1";
if ($search) $sql .= " AND title LIKE '%$search%'";
if ($category_id > 0) $sql .= " AND category_id = $category_id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_news = $row['total'];
$total_pages = ceil($total_news / $limit);

// Lấy danh mục tin tức
$sql = "SELECT category_id, category_name FROM categories";
$result = mysqli_query($conn, $sql);
$categories = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Thống kê lượt xem theo danh mục
$sql = "SELECT c.category_name, COALESCE(SUM(n.views), 0) AS total_views 
        FROM categories c 
        LEFT JOIN news n ON c.category_id = n.category_id 
        GROUP BY c.category_id, c.category_name";
$result = mysqli_query($conn, $sql);
$views_by_category = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Chuẩn bị dữ liệu cho biểu đồ
$categories_data = array_column($views_by_category, 'category_name');
$views_data = array_column($views_by_category, 'total_views');

// Tạo mảng màu sắc cho mỗi danh mục
$colors = ['#FF6F61', '#6B5B95', '#88B04B', '#F7CAC9', '#92A8D1', '#955251', '#B565A7', '#009B77'];
$backgroundColors = [];
foreach ($categories_data as $index => $category) {
    $backgroundColors[] = $colors[$index % count($colors)]; // Lặp lại màu nếu danh mục nhiều hơn số màu
}

$chart_config = [
    'type' => 'bar',
    'data' => [
        'labels' => $categories_data,
        'datasets' => [[
            'label' => 'Lượt xem',
            'data' => $views_data,
            'backgroundColor' => $backgroundColors,
            'borderColor' => $backgroundColors,
            'borderWidth' => 1,
            'hoverBackgroundColor' => array_map(function($color) {
                return adjustBrightness($color, -20); // Làm tối màu khi hover
            }, $backgroundColors)
        ]]
    ],
    'options' => [
        'indexAxis' => 'y',
        'responsive' => true,
        'maintainAspectRatio' => false,
        'scales' => [
            'x' => [
                'title' => ['display' => true, 'text' => 'Số lượt xem', 'color' => '#FFFFFF'],
                'ticks' => ['color' => '#FFFFFF'],
                'grid' => ['color' => 'rgba(176, 190, 197, 0.2)'] // Làm mờ lưới
            ],
            'y' => [
                'title' => ['display' => true, 'text' => 'Danh mục', 'color' => '#FFFFFF'],
                'ticks' => ['color' => '#FFFFFF'],
                'grid' => ['color' => 'rgba(176, 190, 197, 0.2)']
            ]
        ],
        'plugins' => ['legend' => ['labels' => ['color' => '#FFFFFF']]]
    ]
];

// Hàm PHP hỗ trợ làm tối màu
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tin tức - Quản lý Bóng đá</title>
    <link rel="stylesheet" href="../assets/css/manageNews.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <h1>Quản lý Tin tức</h1>

        <!-- Tìm kiếm, lọc và biểu đồ -->
        <div class="content-wrapper">
            <div class="filter-container">
                <form method="GET" action="">
                    <div class="filter-group">
                        <div class="form-group">
                            <label for="search">Tìm kiếm:</label>
                            <input type="text" name="search" placeholder="Tìm theo tiêu đề..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="category">Danh mục:</label>
                            <select name="category">
                                <option value="0">Tất cả danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-filter">Lọc</button>
                    </div>
                </form>
            </div>

            <div class="chart-container">
                <h2>Thống kê lượt xem theo danh mục</h2>
                <div><canvas id="viewsChartCanvas"></canvas></div>
            </div>
        </div>

        <!-- Nút hành động và bảng -->
        <div class="table-container">
            <div class="action-buttons">
                <button class="btn btn-small" onclick="openAddNewsModal()">Thêm Tin tức</button>
                <!-- <button class="btn btn-small" onclick="exportToExcel()">Xuất Excel</button> -->
            </div>

            <table class="news-table">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Nội dung</th>
                        <th>Ngày đăng</th>
                        <th>Tác giả</th>
                        <th>Ảnh</th>
                        <th>Lượt xem</th>
                        <th>Danh mục</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($news_list)): ?>
                        <tr><td colspan="8">Không tìm thấy tin tức nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($news_list as $index => $news): ?>
                            <tr class="<?php echo $index % 2 == 0 ? 'row-even' : 'row-odd'; ?>">
                                <td><?php echo htmlspecialchars($news['title']); ?></td>
                                <td class="content-cell"><?php echo nl2br(htmlspecialchars(substr($news['content'], 0, 50))) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($news['publish_date']); ?></td>
                                <td><?php echo htmlspecialchars($news['author'] ?: 'N/A'); ?></td>
                                <td><img src="../<?php echo htmlspecialchars($news['image_url'] ?: 'assets/img/default_image.png'); ?>" alt="Ảnh" class="table-image"></td>
                                <td><?php echo $news['views']; ?></td>
                                <td><?php echo htmlspecialchars($news['category_name'] ?: 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-small btn-edit" onclick="openEditNewsModal(<?php echo htmlspecialchars(json_encode($news)); ?>)">Sửa</button>
                                    <button class="btn btn-small btn-danger" onclick="openDeleteConfirmModal(<?php echo $news['news_id']; ?>)">Xóa</button>
                                    <a href="newsDetail.php?news_id=<?php echo $news['news_id']; ?>" class="btn btn-small btn-view">Xem chi tiết</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_id; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal Thêm/Sửa Tin tức -->
        <div id="newsModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <h2 id="modalTitle">Thêm Tin tức</h2>
                <form id="newsForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="news_id" id="newsId">
                    <div class="form-group">
                        <label for="title">Tiêu đề:</label>
                        <input type="text" name="title" id="title" required>
                    </div>
                    <div class="form-group">
                        <label for="content">Nội dung:</label>
                        <textarea name="content" id="content" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Danh mục:</label>
                        <select name="category_id" id="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="author">Tác giả:</label>
                        <input type="text" name="author" id="author">
                    </div>
                    <div class="form-group">
                        <label for="image">Ảnh minh họa:</label>
                        <input type="file" name="image" id="image" accept="image/*">
                        <img id="imagePreview" src="../assets/img/default_image.png" alt="Ảnh minh họa" class="preview-photo">
                    </div>
                    <button type="submit" class="btn btn-submit">Lưu</button>
                </form>
            </div>
        </div>

        <!-- Modal Xác nhận Xóa -->
        <div id="deleteConfirmModal" class="modal">
            <div class="modal-content delete-confirm-modal">
                <h2>Xác nhận Xóa</h2>
                <p>Bạn có chắc muốn xóa tin tức này không?</p>
                <div class="modal-actions">
                    <button class="btn btn-danger" id="confirmDeleteButton">Xác nhận</button>
                    <button class="btn btn-cancel" onclick="closeDeleteConfirmModal()">Hủy</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const chartData = <?php echo json_encode($chart_config); ?>;
        new Chart(document.getElementById('viewsChartCanvas').getContext('2d'), chartData);

        function openAddNewsModal() {
            document.getElementById('modalTitle').textContent = 'Thêm Tin tức';
            document.getElementById('formAction').value = 'add_news';
            document.getElementById('newsId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('content').value = '';
            document.getElementById('category_id').value = '';
            document.getElementById('author').value = '';
            document.getElementById('imagePreview').src = '../assets/img/default_image.png';
            document.getElementById('newsModal').style.display = 'block';
        }

        function openEditNewsModal(news) {
            document.getElementById('modalTitle').textContent = 'Sửa Tin tức';
            document.getElementById('formAction').value = 'update_news';
            document.getElementById('newsId').value = news.news_id;
            document.getElementById('title').value = news.title;
            document.getElementById('content').value = news.content;
            document.getElementById('category_id').value = news.category_id;
            document.getElementById('author').value = news.author;
            document.getElementById('imagePreview').src = news.image_url ? `../${news.image_url}` : '../assets/img/default_image.png';
            document.getElementById('newsModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('newsModal').style.display = 'none';
        }

        function openDeleteConfirmModal(newsId) {
            document.getElementById('deleteConfirmModal').style.display = 'block';
            document.getElementById('confirmDeleteButton').onclick = function() {
                confirmDelete(newsId);
            };
        }

        function closeDeleteConfirmModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }

        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        function showCustomAlert(message, type = 'success') {
            const alertBox = document.createElement('div');
            alertBox.className = `custom-alert ${type}`;
            alertBox.innerHTML = `
                <div class="alert-content">
                    <p>${message}</p>
                    <button onclick="this.parentElement.parentElement.remove()">Đóng</button>
                </div>
            `;
            document.body.appendChild(alertBox);
            setTimeout(() => alertBox.remove(), 5000);
        }

        document.getElementById('newsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../controller/AnewsController.php', { 
                method: 'POST', 
                body: formData 
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showCustomAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showCustomAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCustomAlert('Đã xảy ra lỗi: ' + error.message, 'error');
            });
        });

        function confirmDelete(newsId) {
            fetch('../controller/AnewsController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete_news&news_id=' + newsId
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                closeDeleteConfirmModal();
                if (data.success) {
                    showCustomAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showCustomAlert(data.message, 'error');
                }
            })
            .catch(error => {
                closeDeleteConfirmModal();
                console.error('Error:', error);
                showCustomAlert('Đã xảy ra lỗi: ' + error.message, 'error');
            });
        }

        function exportToExcel() {
            window.location.href = '../controller/AnewsController.php?action=export_news';
        }
    </script>
</body>
</html>