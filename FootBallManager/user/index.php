<?php
// Khởi động session
session_start();

// Bao gồm tệp cấu hình
require_once '../includes/config.php';

// Bắt đầu bộ đệm đầu ra
ob_start();

// Đặt tiêu đề trang
$title = "Thống kê";

ob_start();
?>




<!-- Nội dung chính -->
<main>

</main>

<?php
// Lấy nội dung từ bộ đệm
$content = ob_get_clean();

// Bao gồm tệp mẫu chính
include '../includes/master.php';
?>