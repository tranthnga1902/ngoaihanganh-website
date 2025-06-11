<?php
session_start();

require_once '../includes/config.php';
require_once '../controller/AplayerController.php';

// // Kiểm tra quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../user/login.php");
//     exit;
// }

// Lấy danh sách đội bóng để hiển thị trong form
$teams_sql = "SELECT * FROM Teams";
$teams_result = mysqli_query($conn, $teams_sql);
$teams = mysqli_fetch_all($teams_result, MYSQLI_ASSOC);

// Cài đặt phân trang
$players_per_page = 20; // Số cầu thủ hiển thị trên mỗi trang
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Lấy số trang hiện tại từ tham số URL 'page', mặc định là 1
if ($current_page < 1) $current_page = 1; // Đảm bảo số trang không nhỏ hơn 1
$offset = ($current_page - 1) * $players_per_page; // Tính vị trí bắt đầu của bản ghi (offset) dựa trên trang hiện tại

// Lấy danh sách cầu thủ với bộ lọc
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$position = isset($_GET['position']) ? $_GET['position'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
//$players = getPlayers($conn, $team_id, $position, $search);

// Đếm tổng số cầu thủ để tính số trang
$count_sql = "SELECT COUNT(*) as total FROM Players"; // Truy vấn cơ bản để đếm tổng số cầu thủ
if ($team_id) {
    $count_sql .= " WHERE team_id = $team_id"; // Thêm điều kiện lọc theo đội bóng
}
if ($position) {
    $count_sql .= $team_id ? " AND position = '$position'" : " WHERE position = '$position'"; // Thêm điều kiện lọc theo vị trí
}
if ($search) {
    $count_sql .= ($team_id || $position) ? " AND name LIKE '%$search%'" : " WHERE name LIKE '%$search%'"; // Thêm điều kiện tìm kiếm theo tên
}
$count_result = mysqli_query($conn, $count_sql); // Thực thi truy vấn đếm
$total_players = mysqli_fetch_assoc($count_result)['total']; // Lấy tổng số cầu thủ
$total_pages = ceil($total_players / $players_per_page); // Tính tổng số trang (làm tròn lên)

// Lấy danh sách cầu thủ với bộ lọc và phân trang
$players = getPlayers($conn, $team_id, $position, $search, $players_per_page, $offset); // Gọi hàm getPlayers với tham số phân trang

//ob_start()
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý cầu thủ</title>
    <link rel="stylesheet" href="../assets/css/managerPlayer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
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

        <!-- Tìm kiếm cầu thủ -->
        <div class="card search-section">
            <h2>Tìm kiếm cầu thủ</h2>
            <form class="search-form">
                <div class="form-group">
                    <label for="playerSearch">Tên cầu thủ:</label>
                    <div class="autocomplete">
                        <input type="text" id="playerSearch" name="search" placeholder="Nhập tên cầu thủ..." value="<?php echo htmlspecialchars($search ?? ''); ?>" onkeyup="searchPlayers()">
                    </div>
                </div>
            </form>
        </div>

        <!-- Bộ lọc -->
        <div class="card filter-section">
            <h2>Lọc cầu thủ</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="filter_team">Đội bóng:</label>
                    <select name="team_id" id="filter_team">
                        <option value="">Tất cả</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>" <?php echo $team_id == $team['team_id'] ? 'selected' : ''; ?>>
                                <?php echo $team['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_position">Vị trí:</label>
                    <select name="position" id="filter_position">
                        <option value="">Tất cả</option>
                        <option value="Tiền đạo" <?php echo $position == 'Tiền đạo' ? 'selected' : ''; ?>>Tiền đạo</option>
                        <option value="Tiền vệ" <?php echo $position == 'Tiền vệ' ? 'selected' : ''; ?>>Tiền vệ</option>
                        <option value="Hậu vệ" <?php echo $position == 'Hậu vệ' ? 'selected' : ''; ?>>Hậu vệ</option>
                        <option value="Thủ môn" <?php echo $position == 'Thủ môn' ? 'selected' : ''; ?>>Thủ môn</option>
                    </select>
                </div>
                <button type="submit" class="btn">Lọc</button>
            </form>
        </div>

        <!-- Bảng danh sách cầu thủ -->
        <div class="card">
            <h2>Danh sách cầu thủ</h2>
            <table class="player-table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Tên</th>
                        <th>Số áo</th>
                        <th>Vị trí</th>
                        <th>Đội</th>
                        <th>Thống kê</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7">
                            <button class="btn add-btn" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Thêm cầu thủ
                            </button>
                        </td>
                    </tr>
                    <?php foreach ($players as $player): ?>
                        <tr>
                            <td>
                                <img src="../<?php echo htmlspecialchars($player['photo_url'] ?? BASE_URL . 'assets/img/default_avatar.png'); ?>" alt="" class="player-image">
                            </td>
                            <td><?php echo htmlspecialchars($player['name']); ?></td>
                            <td><?php echo htmlspecialchars($player['jersey_number']); ?></td>
                            <td><?php echo htmlspecialchars($player['position']); ?></td>
                            <td><?php echo htmlspecialchars($player['team_name'] ?? 'Chưa có đội'); ?></td>
                            <td>
                                <?php $stats = getPlayerStats($conn, $player['player_id']); ?>
                                <div class="stats">
                                    <span><i class="fas fa-futbol"></i> Bàn: <?php echo $stats['goals'] ?? 0; ?></span>
                                    <span><i class="fas fa-exclamation-triangle"></i> Thẻ vàng: <?php echo $stats['yellow_cards'] ?? 0; ?></span>
                                    <span><i class="fas fa-exclamation-circle"></i> Thẻ đỏ: <?php echo $stats['red_cards'] ?? 0; ?></span>
                                </div>
                            </td>
                            <td>
                                
                               
                                <button class="btn edit-btn" onclick="openEditModal(<?php echo $player['player_id']; ?>, '<?php echo htmlspecialchars(addslashes($player['name'])); ?>', <?php echo $player['jersey_number']; ?>, '<?php echo $player['position']; ?>', '<?php echo $player['birth_date']; ?>', '<?php echo htmlspecialchars(addslashes($player['nationality'])); ?>', <?php echo $player['height']; ?>, <?php echo $player['weight']; ?>, <?php echo $player['team_id']; ?>, '<?php echo htmlspecialchars($player['photo_url'] ?? ''); ?>')">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn delete-btn" onclick="confirmDelete(<?php echo $player['player_id']; ?>)">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Phân trang -->
            
            <?php if ($total_pages > 1): ?> <!-- Chỉ hiển thị phân trang nếu có nhiều hơn 1 trang -->
                <div class="pagination">
                    <?php
                    // Tạo mảng chứa các tham số bộ lọc để giữ nguyên khi chuyển trang
                    $query_params = [];
                    if ($team_id) $query_params['team_id'] = $team_id; // Thêm team_id nếu có
                    if ($position) $query_params['position'] = $position; // Thêm position nếu có
                    if ($search) $query_params['search'] = $search; // Thêm search nếu có

                    // Liên kết "Trước"
                    if ($current_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $current_page - 1])); ?>">« Trước</a> <!-- Liên kết đến trang trước -->
                    <?php endif; ?>

                    <?php
                    // Số trang hiển thị xung quanh trang hiện tại (2 trang trước và 2 trang sau, tổng 5 trang)
                    $range = 2; // Số trang hiển thị trước và sau trang hiện tại
                    $show_pages = 5; // Tổng số trang muốn hiển thị (2 trước + trang hiện tại + 2 sau = 5)

                    // Tính toán trang bắt đầu và kết thúc
                    $start_page = max(1, $current_page - $range); // Trang bắt đầu, đảm bảo không nhỏ hơn 1
                    $end_page = min($total_pages, $current_page + $range); // Trang kết thúc, không vượt quá tổng số trang

                    // Nếu số trang hiển thị nhỏ hơn $show_pages, điều chỉnh $start_page
                    if ($end_page - $start_page + 1 < $show_pages) {
                        $start_page = max(1, $end_page - $show_pages + 1); // Đảm bảo đủ số trang hiển thị
                    }

                    // Hiển thị dấu "..." trước nếu trang bắt đầu không phải là 1
                    if ($start_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => 1])); ?>">1</a> <!-- Luôn hiển thị trang 1 -->
                        <?php if ($start_page > 2): ?>
                            <span>...</span> <!-- Hiển thị dấu ba chấm nếu có khoảng cách giữa trang 1 và $start_page -->
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php
                    // Hiển thị các số trang trong khoảng $start_page đến $end_page
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $active_class = $i === $current_page ? 'active' : ''; // Đánh dấu trang hiện tại
                    ?>
                        <a class="<?php echo $active_class; ?>" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $i])); ?>"><?php echo $i; ?></a> <!-- Liên kết đến trang cụ thể -->
                    <?php endfor; ?>

                    <?php
                    // Hiển thị dấu "..." sau nếu trang kết thúc không phải là trang cuối
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span> <!-- Hiển thị dấu ba chấm nếu có khoảng cách giữa $end_page và trang cuối -->
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a> <!-- Luôn hiển thị trang cuối -->
                    <?php endif; ?>

                    <?php
                    // Liên kết "Tiếp"
                    if ($current_page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $current_page + 1])); ?>">Tiếp »</a> <!-- Liên kết đến trang tiếp theo -->
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        

        <!-- Modal thêm cầu thủ -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">×</span>
                <h2>Thêm cầu thủ mới</h2>
                <form action="../controller/AplayerController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_player">
                    <div class="form-group">
                        <label for="name">Tên cầu thủ:</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="jersey_number">Số áo:</label>
                        <input type="number" name="jersey_number" id="jersey_number" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Vị trí:</label>
                        <select name="position" id="position" required>
                            <option value="Tiền đạo">Tiền đạo</option>
                            <option value="Tiền vệ">Tiền vệ</option>
                            <option value="Hậu vệ">Hậu vệ</option>
                            <option value="Thủ môn">Thủ môn</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="birth_date">Ngày sinh:</label>
                        <input type="date" name="birth_date" id="birth_date" required>
                    </div>
                    <div class="form-group">
                        <label for="nationality">Quốc tịch:</label>
                        <input type="text" name="nationality" id="nationality" required>
                    </div>
                    <div class="form-group">
                        <label for="height">Chiều cao (cm):</label>
                        <input type="number" name="height" id="height" required>
                    </div>
                    <div class="form-group">
                        <label for="weight">Cân nặng (kg):</label>
                        <input type="number" name="weight" id="weight" required>
                    </div>
                    <div class="form-group">
                        <label for="team_id">Đội bóng:</label>
                        <select name="team_id" id="team_id" required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo $team['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="image">Hình ảnh:</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn">Thêm cầu thủ</button>
                </form>
            </div>
        </div>

        <!-- Modal sửa cầu thủ -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">×</span>
                <h2>Sửa thông tin cầu thủ</h2>
                <form action="../controller/AplayerController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_player">
                    <input type="hidden" name="player_id" id="edit_player_id">
                    <div class="form-group">
                        <label for="edit_name">Tên cầu thủ:</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_jersey_number">Số áo:</label>
                        <input type="number" name="jersey_number" id="edit_jersey_number" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_position">Vị trí:</label>
                        <select name="position" id="edit_position" required>
                            <option value="Tiền đạo">Tiền đạo</option>
                            <option value="Tiền vệ">Tiền vệ</option>
                            <option value="Hậu vệ">Hậu vệ</option>
                            <option value="Thủ môn">Thủ môn</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_birth_date">Ngày sinh:</label>
                        <input type="date" name="birth_date" id="edit_birth_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nationality">Quốc tịch:</label>
                        <input type="text" name="nationality" id="edit_nationality" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_height">Chiều cao (cm):</label>
                        <input type="number" name="height" id="edit_height" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_weight">Cân nặng (kg):</label>
                        <input type="number" name="weight" id="edit_weight" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_team_id">Đội bóng:</label>
                        <select name="team_id" id="edit_team_id" required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo $team['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_image">Hình ảnh (Chỉ chọn nếu muốn thay đổi):</label>
                        <input type="file" name="image" id="edit_image" accept="image/*">
                        <input type="hidden" name="current_image" id="edit_current_image">
                    </div>
                    <button type="submit" class="btn">Cập nhật</button>
                </form>
            </div>
        </div>

        <!-- Modal chuyển nhượng -->
        <div id="transferModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeTransferModal()">×</span>
                <h2>Chuyển nhượng cầu thủ</h2>
                <form action="../controller/AplayerController.php" method="POST">
                    <input type="hidden" name="action" value="transfer_player">
                    <input type="hidden" name="player_id" id="transfer_player_id">
                    <div class="form-group">
                        <label for="to_team_id">Đội mới:</label>
                        <select name="to_team_id" id="to_team_id" required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo $team['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="transfer_date">Ngày chuyển nhượng:</label>
                        <input type="date" name="transfer_date" id="transfer_date" required>
                    </div>
                    <button type="submit" class="btn">Xác nhận</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal thêm cầu thủ
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Modal chuyển nhượng
        function openTransferModal(playerId) {
            document.getElementById('transfer_player_id').value = playerId;
            document.getElementById('transferModal').style.display = 'block';
        }

        function closeTransferModal() {
            document.getElementById('transferModal').style.display = 'none';
        }

        // Modal sửa cầu thủ
        function openEditModal(playerId, name, jerseyNumber, position, birthDate, nationality, height, weight, teamId, photoUrl) {
            document.getElementById('edit_player_id').value = playerId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_jersey_number').value = jerseyNumber;
            document.getElementById('edit_position').value = position;
            document.getElementById('edit_birth_date').value = birthDate;
            document.getElementById('edit_nationality').value = nationality;
            document.getElementById('edit_height').value = height;
            document.getElementById('edit_weight').value = weight;
            document.getElementById('edit_team_id').value = teamId;
            document.getElementById('edit_current_image').value = photoUrl;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // // Xác nhận xóa cầu thủ
        // function confirmDelete(playerId) {
        //     if (confirm('Bạn có chắc chắn muốn xóa cầu thủ này không?')) {
        //         const form = document.createElement('form');
        //         form.method = 'POST';
        //         form.action = '../controller/AplayerController.php';
        //         const actionInput = document.createElement('input');
        //         actionInput.type = 'hidden';
        //         actionInput.name = 'action';
        //         actionInput.value = 'delete_player';
        //         const idInput = document.createElement('input');
        //         idInput.type = 'hidden';
        //         idInput.name = 'player_id';
        //         idInput.value = playerId;
        //         form.appendChild(actionInput);
        //         form.appendChild(idInput);
        //         document.body.appendChild(form);
        //         form.submit();
        //     }
        // }

                // Thay thế đoạn code cũ bằng đoạn này
document.addEventListener('DOMContentLoaded', function() {
    // Modal xác nhận xóa
    let currentPlayerIdToDelete = null;

    function confirmDelete(playerId) {
        currentPlayerIdToDelete = playerId;
        document.getElementById('confirmDeleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('confirmDeleteModal').style.display = 'none';
    }

    // Gán sự kiện sau khi DOM tải xong
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (currentPlayerIdToDelete) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../controller/AplayerController.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_player';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'player_id';
            idInput.value = currentPlayerIdToDelete;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
        closeDeleteModal();
    });

    // Gán hàm confirmDelete toàn cục để sử dụng trong HTML
    window.confirmDelete = confirmDelete;
    window.closeDeleteModal = closeDeleteModal;
});
                

        // Tìm kiếm và gợi ý cầu thủ
        function searchPlayers() {
            let input = document.getElementById('playerSearch');
            let suggestions = document.createElement('div');
            suggestions.setAttribute('class', 'autocomplete-items');
            input.parentNode.appendChild(suggestions);

            if (input.value.length >= 3) {
                let xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        suggestions.innerHTML = '';
                        let response = JSON.parse(this.responseText);
                        response.suggestions.forEach(player => {
                            let div = document.createElement('div');
                            div.innerHTML = player.name;
                            div.addEventListener('click', function() {
                                input.value = player.name;
                                suggestions.remove();
                                // Thực hiện lọc hoặc tải lại trang
                                window.location.href = `?search=${encodeURIComponent(player.name)}`;
                            });
                            suggestions.appendChild(div);
                        });
                    }
                };
                xhr.open('GET', '../controller/searchPlayers.php?term=' + encodeURIComponent(input.value), true);
                xhr.send();
            } else {
                suggestions.remove();
            }
        }

        // Đóng modal khi nhấp ra ngoài
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target == document.getElementById('transferModal')) {
                closeTransferModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            let suggestions = document.querySelector('.autocomplete-items');
            if (suggestions && event.target != document.getElementById('playerSearch')) {
                suggestions.remove();
            }

            if (event.target == document.getElementById('confirmDeleteModal')) {
                closeDeleteModal();
            }
        }
    </script>

            <!-- Modal xác nhận xóa -->
    <div id="confirmDeleteModal" class="confirm-delete-modal">
        <p>Bạn có chắc muốn xóa cầu thủ này không?</p>
        <button class="confirm-btn" id="confirmDeleteBtn">Xóa</button>
        <button class="cancel-btn" onclick="closeDeleteModal()">Hủy</button>
    </div>

</body>
</html>
