<?php
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login.php');
//     exit();
// }


if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}


// Giả lập thông tin người dùng
$user_info = [
    'username' => 'AdminUser',
    'avatar' => BASE_URL . 'assets/img/default_avatar.png'
];


?>


<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


<header class="admin-header">
        <div class="header-left">
            <h1>Admin Panel</h1>
        </div>
        <div class="header-right">
            
            <div class="user-account">
                <img src="<?php echo $user_info['avatar']; ?>" alt="Avatar" class="avatar">
                <span><?php echo htmlspecialchars($user_info['username']); ?></span>
                <button class="dropdown-toggle"><i class="fas fa-chevron-down"></i></button>
                <div class="dropdown-menu">
                    <!-- <a href="<?php echo BASE_URL; ?>controller/authController.php?action=logout">Logout</a> -->
                </div>
            </div>
        </div>
    </header>


<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo BASE_URL; ?>assets/img/bong2.png" alt="Logo" class="logo">
        <h2>Football Manager</h2>
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-arrow-left"></i>
        </button>
    </div>


    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/dashboard.png" alt="Dashboard Icon">
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageTeams.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageTeams.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/teams.png" alt="Teams Icon">
                    <span>Quản lý câu lạc bộ</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/managePlayers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'managePlayers.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/players.png" alt="Players Icon">
                    <span>Quản lý cầu thủ</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageCoaches.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageMatches.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/matches.png" alt="Matches Icon">
                    <span>Quản lý huấn luyện viên</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageMatches.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageMatches.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/matches.png" alt="Matches Icon">
                    <span>Quản lý lịch thi đấu</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageResults.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageResults.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/results.png" alt="Results Icon">
                    <span>Quản lý kết quả</span>
                </a>
            </li>
           
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageStat.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageVideos.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/videos.png" alt="Videos Icon">
                    <span>Quản lý thống kê</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageNews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageNews.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/news.png" alt="News Icon">
                    <span>Quản lý tin tức</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/manageUsers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manageUsers.php' ? 'active' : ''; ?>">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/users.png" alt="Users Icon">
                    <span>Quản lý người dùng</span>
                </a>
            </li>
            <!-- Thêm nút Logout ở cuối -->
            <li class="logout-item">
                <a href="<?php echo BASE_URL; ?>?action=logout">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/logout.png" alt="Logout Icon">
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>


<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const header = document.querySelector('.admin-header');
    const footer = document.querySelector('.admin-footer');
    const toggleBtn = document.querySelector('.toggle-btn i');


    sidebar.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
        mainContent.style.marginLeft = '60px';
        mainContent.style.width = 'calc(100% - 60px)';
        header.style.left = '60px';
        header.style.width = 'calc(100% - 60px)';
        footer.style.marginLeft = '60px';
        footer.style.width = 'calc(100% - 60px)';
        toggleBtn.classList.remove('fa-arrow-left');
        toggleBtn.classList.add('fa-arrow-right');
    } else {
        mainContent.style.marginLeft = '250px';
        mainContent.style.width = 'calc(100% - 250px)';
        header.style.left = '250px';
        header.style.width = 'calc(100% - 250px)';
        footer.style.marginLeft = '250px';
        footer.style.width = 'calc(100% - 250px)';
        toggleBtn.classList.remove('fa-arrow-right');
        toggleBtn.classList.add('fa-arrow-left');
    }
}


// Gọi lại toggleSidebar() khi resize hoặc zoom trình duyệt
window.addEventListener('resize', toggleSidebar);
   
    document.querySelector('.dropdown-toggle').addEventListener('click', function() {
        const dropdownMenu = document.querySelector('.dropdown-menu');
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });
   
</script>





