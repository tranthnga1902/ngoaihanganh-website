<?php
// Đảm bảo BASE_URL được định nghĩa
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost:3000/');
}


// Kiểm tra trạng thái đăng nhập
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Người dùng';


// Xử lý logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'user/login.php');
    exit;
}
?>




<header>
  <div class="header-container">
    <!-- Link logo các đội bóng  -->
    <?php
      $premierLeagueClubs = [
          ['name' => 'Arsenal', 'url' => 'http://www.arsenal.com', 'badge' => 't3'],
          ['name' => 'Aston Villa', 'url' => 'https://www.avfc.co.uk', 'badge' => 't7'],
          ['name' => 'AFC Bournemouth', 'url' => 'https://www.afcb.co.uk', 'badge' => 't91'],
          ['name' => 'Brentford', 'url' => 'https://www.brentfordfc.com', 'badge' => 't94'],
          ['name' => 'Brighton & Hove Albion', 'url' => 'https://www.brightonandhovealbion.com', 'badge' => 't36'],
          ['name' => 'Chelsea', 'url' => 'https://www.chelseafc.com', 'badge' => 't8'],
          ['name' => 'Crystal Palace', 'url' => 'http://www.cpfc.co.uk', 'badge' => 't31'],
          ['name' => 'Everton', 'url' => 'http://www.evertonfc.com', 'badge' => 't11'],
          ['name' => 'Fulham', 'url' => 'https://www.fulhamfc.com', 'badge' => 't54'],
          ['name' => 'Ipswich Town', 'url' => 'https://www.itfc.co.uk', 'badge' => 't40'],
          ['name' => 'Leicester', 'url' => 'https://www.lcfc.com', 'badge' => 't13'],
          ['name' => 'Liverpool', 'url' => 'http://www.liverpoolfc.com', 'badge' => 't14'],
          ['name' => 'Manchester City', 'url' => 'https://www.mancity.com', 'badge' => 't43'],
          ['name' => 'Manchester United', 'url' => 'http://www.manutd.com', 'badge' => 't1'],
          ['name' => 'Newcastle United', 'url' => 'https://www.newcastleunited.com', 'badge' => 't4'],
          ['name' => 'Nottingham Forest', 'url' => 'https://www.nottinghamforest.co.uk', 'badge' => 't17'],
          ['name' => 'Southampton', 'url' => 'https://www.southamptonfc.com', 'badge' => 't20'],
          ['name' => 'Tottenham Hotspur', 'url' => 'http://www.tottenhamhotspur.com', 'badge' => 't6'],
          ['name' => 'West Ham United', 'url' => 'http://www.whufc.com', 'badge' => 't21'],
          ['name' => 'Wolverhampton Wanderers', 'url' => 'https://www.wolves.co.uk', 'badge' => 't39']
      ];
    ?>
    <div class="club-wrapper">
      <ul class="clubList" role="menu" style="list-style-type: none;">
          <?php foreach ($premierLeagueClubs as $club): ?>
          <li class="clubList__club">
              <a class="clubList__link" target="_blank" href="<?= $club['url'] ?>?utm_source=premier-league-website&utm_campaign=website&utm_medium=link" role="menuitem">
                  <div class="badge badge--large badge-image-container" data-widget="club-badge-image" data-size="50">
                      <img class="badge-image badge-image--50 js-badge-image"
                            src="https://resources.premierleague.com/premierleague/badges/50/<?= $club['badge'] ?>.png"
                            srcset="https://resources.premierleague.com/premierleague/badges/50/<?= $club['badge'] ?>@x2.png 2x">
                     
                  </div>
                 
              </a>
          </li>
          <?php endforeach; ?>
      </ul>
    </div>
  </div>




  <nav id="menu1">
    <div class="menu-nav">
        <!-- Menu chính (trên cùng) -->
        <nav id="menu">
          <!-- Menu tím -->
            <ul class="nav-links">
                <li class="nav-item"><a href="index.php" class="nav-link">Premier League</a></li>
            </ul>
            <!-- <a href="user/login.php" class="login">Login</a> -->
            <?php if ($isLoggedIn): ?>
                    <li class="user-menu">
                        <a href="<?php echo BASE_URL; ?>user/detailUser.php" class="login">
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>user/login.php" class="login">Đăng nhập</a>
                    </li>
                <?php endif; ?>


           
        </nav>
       
        <!-- Logo (giữa, đè lên cả hai menu) -->
        <a href="<?php echo BASE_URL; ?>index.php" class="home-logo">
            <img src="<?php echo BASE_URL; ?>assets/img/pl-main-logo.png" alt="Logo Trang chủ" class="home-logo">
        </a>
       
        <!-- Menu phụ (dưới cùng) -->
        <nav class="secondary-nav">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>index.php">Trang chủ</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/news.php">Tin tức</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/standings.php">Bảng xếp hạng</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/viewTeams.php">Câu lạc bộ</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/viewMatches.php">Lịch thi đấu</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/matchResults.php">Kết quả</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/players.php">Cầu thủ</a></li>
                <li><a href="<?php echo BASE_URL; ?>user/statistics.php">Thống kê</a></li>
                
               
            </ul>
        </nav>
    </div>
</nav>






<script>
function toggleDropdown(event) {
    event.preventDefault();
    const dropdownMenu = document.getElementById('userDropdownMenu');
    dropdownMenu.style.display = dropdownMenu.style.display === 'none' ? 'block' : 'none';
}
</script>
</header>

