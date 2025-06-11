<?php
require_once '../includes/config.php';



// Đảm bảo thư mục uploads/news/ tồn tại
$uploadDir = "../uploads/news/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_news':
            $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
            $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
            $category_id = (int)($_POST['category_id'] ?? 0);
            $author = mysqli_real_escape_string($conn, $_POST['author'] ?? '');

            // Kiểm tra các trường bắt buộc
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Thêm tin tức thất bại: Tiêu đề không được để trống.']);
                exit;
            }
            if (empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Thêm tin tức thất bại: Nội dung không được để trống.']);
                exit;
            }
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Thêm tin tức thất bại: Vui lòng chọn danh mục hợp lệ.']);
                exit;
            }
            if (empty($author)) {
                echo json_encode(['success' => false, 'message' => 'Thêm tin tức thất bại: Tác giả không được để trống.']);
                exit;
            }

            $image_url = 'assets/img/default_image.png';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $target_dir = $uploadDir;
                $image_name = uniqid() . "_" . basename($_FILES['image']['name']);
                $target_file = $target_dir . $image_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = "uploads/news/" . $image_name;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Thêm tin tức thất bại: Không thể tải ảnh lên.']);
                    exit;
                }
            }

            $sql = "INSERT INTO news (title, content, publish_date, author, image_url, views, category_id) 
                    VALUES (?, ?, NOW(), ?, ?, 0, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Thêm tin tức thất bại: Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
                exit;
            }
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $content, $author, $image_url, $category_id);
            $success = mysqli_stmt_execute($stmt);
            if ($success) {
                $_SESSION['message'] = 'Thêm tin tức thành công!';
            }
            echo json_encode(['success' => $success, 'message' => $success ? 'Thêm tin tức thành công!' : 'Thêm tin tức thất bại: ' . mysqli_error($conn)]);
            break;

        case 'update_news':
            $news_id = (int)($_POST['news_id'] ?? 0);
            $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
            $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
            $category_id = (int)($_POST['category_id'] ?? 0);
            $author = mysqli_real_escape_string($conn, $_POST['author'] ?? '');

            // Kiểm tra các trường bắt buộc
            if ($news_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: ID tin tức không hợp lệ.']);
                exit;
            }
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: Tiêu đề không được để trống.']);
                exit;
            }
            if (empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: Nội dung không được để trống.']);
                exit;
            }
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: Vui lòng chọn danh mục hợp lệ.']);
                exit;
            }
            if (empty($author)) {
                echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: Tác giả không được để trống.']);
                exit;
            }

            // Lấy image_url hiện tại từ cơ sở dữ liệu
            $sql = "SELECT image_url FROM news WHERE news_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $news_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $current_news = mysqli_fetch_assoc($result);
            $image_url = $current_news['image_url'] ?? 'assets/img/default_image.png';

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $target_dir = $uploadDir;
                $image_name = uniqid() . "_" . basename($_FILES['image']['name']);
                $target_file = $target_dir . $image_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = "uploads/news/" . $image_name;
                    // Xóa ảnh cũ nếu không phải ảnh mặc định
                    if ($current_news['image_url'] && $current_news['image_url'] !== 'assets/img/default_image.png') {
                        @unlink("../" . $current_news['image_url']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: Không thể tải ảnh lên.']);
                    exit;
                }
            }

            $sql = "UPDATE news SET title = ?, content = ?, author = ?, category_id = ?, image_url = ? WHERE news_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Cập nhật tin tức thất bại: Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
                exit;
            }
            mysqli_stmt_bind_param($stmt, "sssisi", $title, $content, $author, $category_id, $image_url, $news_id);
            $success = mysqli_stmt_execute($stmt);
            if ($success) {
                $_SESSION['message'] = 'Cập nhật tin tức thành công!';
            }
            echo json_encode(['success' => $success, 'message' => $success ? 'Cập nhật tin tức thành công!' : 'Cập nhật tin tức thất bại: ' . mysqli_error($conn)]);
            break;

        case 'delete_news':
            $news_id = (int)($_POST['news_id'] ?? 0);

            if ($news_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Xóa tin tức thất bại: ID tin tức không hợp lệ.']);
                exit;
            }

            // Lấy image_url để xóa ảnh nếu cần
            $sql = "SELECT image_url FROM news WHERE news_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $news_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $news = mysqli_fetch_assoc($result);

            $sql = "DELETE FROM news WHERE news_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Xóa tin tức thất bại: Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
                exit;
            }
            mysqli_stmt_bind_param($stmt, "i", $news_id);
            $success = mysqli_stmt_execute($stmt);

            if ($success && $news['image_url'] && $news['image_url'] !== 'assets/img/default_image.png') {
                @unlink("../" . $news['image_url']);
            }
            if ($success) {
                $_SESSION['message'] = 'Xóa tin tức thành công!';
            }
            echo json_encode(['success' => $success, 'message' => $success ? 'Xóa tin tức thành công!' : 'Xóa tin tức thất bại: ' . mysqli_error($conn)]);
            break;

        case 'export_news':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="news_export.xls"');
            $sql = "SELECT title, content, publish_date, author, views, c.category_name 
                    FROM news n 
                    LEFT JOIN categories c ON n.category_id = c.category_id";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                die('Lỗi truy vấn: ' . mysqli_error($conn));
            }
            $output = "Tiêu đề\tNội dung\tNgày đăng\tTác giả\tLượt xem\tDanh mục\n";
            while ($row = mysqli_fetch_assoc($result)) {
                $output .= implode("\t", array_map('htmlspecialchars', $row)) . "\n";
            }
            echo $output;
            exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
}
?>