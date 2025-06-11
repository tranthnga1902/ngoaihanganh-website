<?php
require_once(dirname(__DIR__) . '/includes/config.php');


class NewsController {
    private $conn;


    public function __construct($conn) {
        $this->conn = $conn;
    }


    // Lấy tất cả danh mục tin tức
    public function getAllCategories() {
        $sql = "SELECT * FROM Categories ORDER BY category_name ASC";
        $result = $this->conn->query($sql);
       
        $categories = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }


    // Lấy tin tức theo danh mục (hoặc tất cả nếu category_id = 0)
    public function getNewsByCategory($category_id = 0) {
        $sql = "SELECT n.*, c.category_name
                FROM News n
                JOIN Categories c ON n.category_id = c.category_id";
       
        if ($category_id > 0) {
            $sql .= " WHERE n.category_id = ?";
        }
       
        $sql .= " ORDER BY n.publish_date DESC";
       
        $stmt = $this->conn->prepare($sql);
       
        if ($category_id > 0) {
            $stmt->bind_param("i", $category_id);
        }
       
        $stmt->execute();
        $result = $stmt->get_result();
       
        $news_items = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $news_items[] = $row;
            }
        }
        $stmt->close();
       
        return $news_items;
    }


    // Tìm kiếm tin tức theo từ khóa
    public function searchNews($keyword) {
        $sql = "SELECT n.*, c.category_name
                FROM News n
                JOIN Categories c ON n.category_id = c.category_id
                WHERE n.title LIKE ? OR n.content LIKE ?
                ORDER BY n.publish_date DESC";
       
        $stmt = $this->conn->prepare($sql);
        $search_term = "%$keyword%";
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
       
        $news_items = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $news_items[] = $row;
            }
        }
        $stmt->close();
       
        return $news_items;
    }


    // Lấy tin tức theo ID
    public function getNewsById($id) {
        $sql = "SELECT n.*, c.category_name
                FROM News n
                JOIN Categories c ON n.category_id = c.category_id
                WHERE n.news_id = ?";
       
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $news_item = $result->fetch_assoc();
        $stmt->close();
       
        return $news_item;
    }


    // Lấy danh sách ảnh từ thư mục uploads/news/news_id
    public function getNewsImages($news_id) {
        $uploadDir = '/uploads/news/'; // Thay đổi thành đường dẫn web (từ root)
        $newsImageDir = $_SERVER['DOCUMENT_ROOT'] . $uploadDir . $news_id . '/'; // Đường dẫn vật lý
   
        if (file_exists($newsImageDir)) {
            $files = scandir($newsImageDir);
            $images = array_filter($files, function($file) {
                return !in_array($file, ['.', '..']);
            });
   
            // Trả về đường dẫn web (URL)
            return array_map(function($image) use ($uploadDir, $news_id) {
                return $uploadDir . $news_id . '/' . $image; // Ví dụ: '/uploads/news/123/messi.png'
                //return '/' . ltrim($uploadDir, '/') . $news_id . '/' . $image; // Thêm '/' ở đầu
            }, $images);
        }
        return [];
    }


    // Tăng lượt xem tin tức
    public function incrementViews($id) {
        $sql = "UPDATE News SET views = views + 1 WHERE news_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}


?>

