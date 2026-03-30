<?php
class NewsDetailController {
    private $conn;


    public function __construct($db) {
        $this->conn = $db;
    }


    public function getNewsById($id) {
        $stmt = $this->conn->prepare("
            SELECT n.*, c.category_name
            FROM news n
            JOIN categories c ON n.category_id = c.category_id
            WHERE n.news_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    public function incrementViews($id) {
        $stmt = $this->conn->prepare("
            UPDATE news SET views = views + 1
            WHERE news_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }


    public function getRelatedNews($category_id, $exclude_id, $limit = 4) {
        $stmt = $this->conn->prepare("
            SELECT news_id, title, image_url, publish_date, views
            FROM news
            WHERE category_id = ? AND news_id != ?
            ORDER BY publish_date DESC
            LIMIT ?
        ");
        $stmt->bind_param("iii", $category_id, $exclude_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }


    public function getLatestNews($limit = 3) {
        $stmt = $this->conn->prepare("
            SELECT news_id, title, image_url
            FROM news
            ORDER BY publish_date DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

