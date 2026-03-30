<?php
session_start();
require_once '../includes/config.php';
require_once '../controller/AteamController.php';

// Kiểm tra quyền admin (bỏ comment nếu cần)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../user/login.php");
//     exit;
// }

// Lấy danh sách mùa giải
$seasons_sql = "SELECT season_id, name FROM seasons ORDER BY name DESC";
$seasons_result = mysqli_query($conn, $seasons_sql);
$seasons = mysqli_fetch_all($seasons_result, MYSQLI_ASSOC);

// Lấy danh sách tất cả đội bóng
$teams_sql = "SELECT team_id, name FROM teams ORDER BY name ASC";
$teams_result = mysqli_query($conn, $teams_sql);
$all_teams = mysqli_fetch_all($teams_result, MYSQLI_ASSOC);

// Lấy danh sách sân vận động
$stadiums_sql = "SELECT stadium_id, name FROM stadiums ORDER BY name ASC";
$stadiums_result = mysqli_query($conn, $stadiums_sql);
$stadiums = mysqli_fetch_all($stadiums_result, MYSQLI_ASSOC);

$season_id = isset($_GET['season_id']) ? (int)$_GET['season_id'] : null;
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$teams = getTeams($conn, $season_id, $team_id);

$stt = 1;

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đội bóng</title>
    <link rel="stylesheet" href="../assets/css/managerTeams.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Lọc đội bóng -->
        <div class="card filter-section">
            <h2>Lọc đội bóng</h2>
            <div class="filter-form">
                <div class="form-group">
                    <label for="filter_team">Đội bóng:</label>
                    <select name="team_id" id="filter_team" onchange="applyFilter()">
                        <option value="">Tất cả</option>
                        <?php foreach ($all_teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>" <?php echo $team_id == $team['team_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_season">Mùa giải:</label>
                    <select name="season_id" id="filter_season" onchange="applyFilter()">
                        <option value="">Tất cả</option>
                        <?php foreach ($seasons as $s): ?>
                            <option value="<?php echo $s['season_id']; ?>" <?php echo $season_id == $s['season_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bảng danh sách đội bóng -->
        <div class="card">
            <h2>Danh sách đội bóng</h2>
            <table class="team-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Logo</th>
                        <th>Tên đội</th>
                        <th>Sân nhà</th>
                        <th>Năm thành lập</th>
                        <th>Huấn luyện viên</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="teamTableBody">
                    <tr>
                        <td colspan="7">
                            <button class="btn add-btn" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Thêm đội bóng
                            </button>
                        </td>
                    </tr>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td><?php echo $stt++; ?></td>
                            <td>
                                <img src="../<?php echo htmlspecialchars($team['logo_url'] ?? BASE_URL . 'assets/img/default_logo.png'); ?>" alt="" class="team-image">
                            </td>
                            <td><?php echo htmlspecialchars($team['name']); ?></td>
                            <td><?php echo htmlspecialchars($team['stadium_name'] ?? 'Chưa có'); ?></td>
                            <td><?php echo htmlspecialchars($team['founded_year'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($team['coach_name'] ?? 'Chưa có'); ?></td>
                            <td>
                                <button class="btn edit-btn" onclick="openEditModal(<?php echo $team['team_id']; ?>, '<?php echo htmlspecialchars(addslashes($team['name'])); ?>', <?php echo $team['stadium_id'] ?? 'null'; ?>, '<?php echo htmlspecialchars(addslashes($team['stadium_name'] ?? '')); ?>', <?php echo $team['founded_year'] ?? 'null'; ?>, '<?php echo htmlspecialchars(addslashes($team['coach_name'] ?? '')); ?>', '<?php echo htmlspecialchars($team['logo_url'] ?? ''); ?>')">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn edit-stadium-btn" onclick="openEditStadiumModal(<?php echo $team['stadium_id'] ?? 'null'; ?>, '<?php echo htmlspecialchars(addslashes($team['stadium_name'] ?? '')); ?>')">
                                    <i class="fas fa-stadium"></i> Sửa sân
                                </button>
                                <button class="btn delete-btn" onclick="openConfirmDeleteModal(<?php echo $team['team_id']; ?>)">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal thêm đội bóng -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('addModal')">×</span>
                <h2>Thêm đội bóng mới</h2>
                <form id="addTeamForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_team">
                    <div class="modal-body">
                        <div class="modal-image">
                            <img id="add_logo_preview" src="../<?php echo BASE_URL; ?>assets/img/default_logo.png" alt="Logo CLB">
                        </div>
                        <div class="modal-form">
                            <div class="form-group">
                                <label for="team_name">Tên đội bóng:</label>
                                <input type="text" name="name" id="team_name" required>
                            </div>
                            <div class="form-group stadium-group">
                                <label for="stadium_id">Sân nhà:</label>
                                <div class="stadium-select-wrapper">
                                    <select name="stadium_id" id="add_stadium_id" required>
                                        <option value="">Chọn sân vận động</option>
                                        <?php foreach ($stadiums as $stadium): ?>
                                            <option value="<?php echo $stadium['stadium_id']; ?>">
                                                <?php echo htmlspecialchars($stadium['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="add-stadium-btn" onclick="openCreateStadiumModal('add')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="founded_year">Năm thành lập:</label>
                                <input type="number" name="founded_year" id="founded_year" min="1000" max="2025" required>
                            </div>
                            <div class="form-group">
                                <label for="coach_name">Huấn luyện viên:</label>
                                <input type="text" name="coach_name" id="coach_name">
                            </div>
                            <div class="form-group">
                                <label for="logo">Logo:</label>
                                <input type="file" name="logo" id="logo" accept="image/*" onchange="previewImage('add')">
                            </div>
                            <button type="submit" class="btn">Thêm đội bóng</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal sửa đội bóng -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('editModal')">×</span>
                <h2>Sửa thông tin đội bóng</h2>
                <form id="editTeamForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_team">
                    <input type="hidden" name="team_id" id="edit_team_id">
                    <div class="modal-body">
                        <div class="modal-image">
                            <img id="edit_logo_preview" src="../<?php echo BASE_URL; ?>assets/img/default_logo.png" alt="Logo CLB">
                        </div>
                        <div class="modal-form">
                            <div class="form-group">
                                <label for="edit_team_name">Tên đội bóng:</label>
                                <input type="text" name="name" id="edit_team_name">
                            </div>
                            <div class="form-group stadium-group">
                                <label for="edit_stadium_id">Sân nhà:</label>
                                <div class="stadium-select-wrapper">
                                    <select name="stadium_id" id="edit_stadium_id">
                                        <option value="">Chọn sân vận động</option>
                                        <?php foreach ($stadiums as $stadium): ?>
                                            <option value="<?php echo $stadium['stadium_id']; ?>">
                                                <?php echo htmlspecialchars($stadium['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="add-stadium-btn" onclick="openCreateStadiumModal('edit')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="edit_founded_year">Năm thành lập:</label>
                                <input type="number" name="founded_year" id="edit_founded_year" min="1000" max="2025">
                            </div>
                            <div class="form-group">
                                <label for="edit_coach_name">Huấn luyện viên:</label>
                                <input type="text" name="coach_name" id="edit_coach_name">
                            </div>
                            <div class="form-group">
                                <label for="edit_logo">Logo (Chỉ chọn nếu muốn thay đổi):</label>
                                <input type="file" name="logo" id="edit_logo" accept="image/*" onchange="previewImage('edit')">
                                <input type="hidden" name="current_logo" id="edit_current_logo">
                            </div>
                            <button type="submit" class="btn">Cập nhật</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal tạo sân vận động -->
        <div id="createStadiumModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('createStadiumModal')">×</span>
                <h2>Tạo sân vận động mới</h2>
                <div class="modal-body">
                    <div class="modal-image">
                        <img id="create_stadium_photo_preview" src="../<?php echo BASE_URL; ?>assets/img/default_stadium.jpg" alt="Ảnh sân vận động">
                    </div>
                    <div class="modal-form">
                        <div class="form-group">
                            <label for="create_stadium_name">Tên sân:</label>
                            <input type="text" id="create_stadium_name" required>
                        </div>
                        <div class="form-group">
                            <label for="create_capacity">Sức chứa:</label>
                            <input type="number" id="create_capacity" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="create_address">Địa chỉ:</label>
                            <input type="text" id="create_address" required>
                        </div>
                        <div class="form-group">
                            <label for="create_city">Thành phố:</label>
                            <input type="text" id="create_city" required>
                        </div>
                        <div class="form-group">
                            <label for="create_built_year">Năm xây dựng:</label>
                            <input type="number" id="create_built_year" min="1800" max="2025" required>
                        </div>
                        <div class="form-group">
                            <label for="create_photo">Ảnh sân:</label>
                            <input type="file" id="create_photo" accept="image/*" onchange="previewStadiumImage('create')">
                        </div>
                        <button type="button" class="btn" onclick="createStadium()">Tạo sân vận động</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal sửa sân vận động -->
        <div id="editStadiumModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('editStadiumModal')">×</span>
                <h2>Sửa thông tin sân vận động</h2>
                <div class="modal-body">
                    <div class="modal-image">
                        <img id="edit_stadium_photo_preview" src="../<?php echo BASE_URL; ?>assets/img/default_stadium.png" alt="Ảnh sân vận động">
                    </div>
                    <div class="modal-form">
                        <input type="hidden" id="edit_stadium_id">
                        <div class="form-group">
                            <label for="edit_stadium_name">Tên sân:</label>
                            <input type="text" id="edit_stadium_name">
                        </div>
                        <div class="form-group">
                            <label for="edit_capacity">Sức chứa:</label>
                            <input type="number" id="edit_capacity" min="0">
                        </div>
                        <div class="form-group">
                            <label for="edit_address">Địa chỉ:</label>
                            <input type="text" id="edit_address">
                        </div>
                        <div class="form-group">
                            <label for="edit_city">Thành phố:</label>
                            <input type="text" id="edit_city">
                        </div>
                        <div class="form-group">
                            <label for="edit_built_year">Năm xây dựng:</label>
                            <input type="number" id="edit_built_year" min="1800" max="2025">
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_photo">Ảnh sân (Chỉ chọn nếu muốn thay đổi):</label>
                            <input type="file" id="edit_photo" accept="image/*" onchange="previewStadiumImage('edit')">
                            <input type="hidden" id="edit_current_photo">
                        </div>
                        <button type="button" class="btn" onclick="updateStadium()">Cập nhật sân vận động</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal thông báo -->
        <div id="notificationModal" class="notification-modal">
            <div class="notification-content" id="notificationContent">
                <h3 id="notificationTitle"></h3>
                <p id="notificationMessage"></p>
                <button class="btn" onclick="closeNotificationModal()">Đóng</button>
            </div>
        </div>

        <!-- Modal xác nhận xóa -->
        <div id="confirmDeleteModal" class="confirm-modal">
            <div class="confirm-content">
                <h3>Xác nhận xóa</h3>
                <p>Bạn có chắc chắn muốn xóa đội bóng này không?</p>
                <div class="confirm-buttons">
                    <button class="btn cancel" onclick="closeConfirmDeleteModal()">Hủy</button>
                    <button class="btn confirm" id="confirmDeleteBtn">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTeamIdToDelete = null;
        let currentMode = null;

        // Đóng modal chung
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'createStadiumModal' || modalId === 'editStadiumModal') {
                document.getElementById(currentMode + 'Modal').style.display = 'block';
            }
        }

        // Modal thêm đội bóng
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.getElementById('add_logo_preview').src = '../<?php echo BASE_URL; ?>assets/img/default_logo.png';
            document.getElementById('team_name').value = '';
            document.getElementById('add_stadium_id').value = '';
            document.getElementById('founded_year').value = '';
            document.getElementById('coach_name').value = '';
            document.getElementById('logo').value = '';
        }

        // Modal sửa đội bóng
        function openEditModal(teamId, name, stadiumId, stadiumName, foundedYear, coachName, logoUrl) {
            document.getElementById('edit_team_id').value = teamId;
            document.getElementById('edit_team_name').value = name;
            document.getElementById('edit_founded_year').value = foundedYear || '';
            document.getElementById('edit_coach_name').value = coachName || '';
            document.getElementById('edit_current_logo').value = logoUrl || '';
            document.getElementById('edit_logo_preview').src = logoUrl ? '../' + logoUrl : '../<?php echo BASE_URL; ?>assets/img/default_logo.png';

            const stadiumSelect = document.getElementById('edit_stadium_id');
            stadiumSelect.value = stadiumId || '';
            document.getElementById('editModal').style.display = 'block';
        }

        // Modal tạo sân vận động
        function openCreateStadiumModal(mode) {
            document.getElementById(mode + 'Modal').style.display = 'none';
            document.getElementById('create_stadium_name').value = '';
            document.getElementById('create_capacity').value = '';
            document.getElementById('create_address').value = '';
            document.getElementById('create_city').value = '';
            document.getElementById('create_built_year').value = '';
            document.getElementById('create_stadium_photo_preview').src = '../<?php echo BASE_URL; ?>assets/img/default_stadium.jpg';
            document.getElementById('create_photo').value = '';
            document.getElementById('createStadiumModal').style.display = 'block';
            currentMode = mode;
        }

        // Modal sửa sân vận động
        function openEditStadiumModal(stadiumId, stadiumName) {
            if (!stadiumId) {
                showNotification('error', 'Đội bóng này chưa có sân vận động để sửa!');
                return;
            }
            fetch('../controller/AstadiumController.php?action=get_stadium&stadium_id=' + encodeURIComponent(stadiumId))
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const stadium = data.stadium;
                        document.getElementById('edit_stadium_id').value = stadium.stadium_id;
                        document.getElementById('edit_stadium_name').value = stadium.name;
                        document.getElementById('edit_capacity').value = stadium.capacity;
                        document.getElementById('edit_address').value = stadium.address;
                        document.getElementById('edit_city').value = stadium.city;
                        document.getElementById('edit_built_year').value = stadium.built_year;
                        document.getElementById('edit_current_photo').value = stadium.photo_url || '';
                        document.getElementById('edit_stadium_photo_preview').src = stadium.photo_url ? '../' + stadium.photo_url : '../<?php echo BASE_URL; ?>assets/img/default_stadium.jpg';
                        document.getElementById('editStadiumModal').style.display = 'block';
                    } else {
                        showNotification('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching stadium:', error);
                    showNotification('error', 'Có lỗi xảy ra khi lấy thông tin sân vận động!');
                });
        }

        // Xem trước ảnh CLB
        function previewImage(mode) {
            const input = document.getElementById(mode + '_logo');
            const preview = document.getElementById(mode + '_logo_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Xem trước ảnh sân vận động
        function previewStadiumImage(mode) {
            const input = document.getElementById(mode + '_photo');
            const preview = document.getElementById(mode + '_stadium_photo_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Tạo sân vận động qua AJAX
        function createStadium() {
            const formData = new FormData();
            formData.append('action', 'add_stadium');
            formData.append('name', document.getElementById('create_stadium_name').value);
            formData.append('capacity', document.getElementById('create_capacity').value);
            formData.append('address', document.getElementById('create_address').value);
            formData.append('city', document.getElementById('create_city').value);
            formData.append('built_year', document.getElementById('create_built_year').value);
            if (document.getElementById('create_photo').files.length > 0) {
                formData.append('photo', document.getElementById('create_photo').files[0]);
            }

            fetch('../controller/AstadiumController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('success', data.message);
                    closeModal('createStadiumModal');
                    updateStadiumDropdown(currentMode, data.stadium_id);
                    const select = document.getElementById(currentMode + '_stadium_id');
                    select.value = data.stadium_id;
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error creating stadium:', error);
                showNotification('error', 'Có lỗi xảy ra khi tạo sân vận động!');
            });
        }

        // Cập nhật sân vận động qua AJAX
        function updateStadium() {
            const formData = new FormData();
            formData.append('action', 'update_stadium');
            formData.append('stadium_id', document.getElementById('edit_stadium_id').value);
            formData.append('name', document.getElementById('edit_stadium_name').value);
            formData.append('capacity', document.getElementById('edit_capacity').value);
            formData.append('address', document.getElementById('edit_address').value);
            formData.append('city', document.getElementById('edit_city').value);
            formData.append('built_year', document.getElementById('edit_built_year').value);
            formData.append('current_photo', document.getElementById('edit_current_photo').value);
            if (document.getElementById('edit_photo').files.length > 0) {
                formData.append('photo', document.getElementById('edit_photo').files[0]);
            }

            fetch('../controller/AstadiumController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('success', data.message);
                    closeModal('editStadiumModal');
                    updateTeamTable(); // Thêm dòng này để cập nhật bảng
                    updateStadiumDropdown(currentMode, data.stadium_id);
                    const select = document.getElementById(currentMode + '_stadium_id');
                    if (select) select.value = data.stadium_id;
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error updating stadium:', error);
                showNotification('error', 'Có lỗi xảy ra khi cập nhật sân vận động!');
            });
        }

        // Cập nhật dropdown sân vận động
        function updateStadiumDropdown(mode, selectedStadiumId = null) {
            fetch('../controller/AteamController.php?action=get_stadiums')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(mode + '_stadium_id');
                if (select) {
                    const currentValue = select.value;
                    select.innerHTML = '<option value="">Chọn sân vận động</option>';
                    data.forEach(stadium => {
                        const option = document.createElement('option');
                        option.value = stadium.stadium_id;
                        option.textContent = stadium.name;
                        select.appendChild(option);
                        if (selectedStadiumId && stadium.stadium_id == selectedStadiumId) {
                            option.selected = true;
                        } else if (currentValue && stadium.stadium_id == currentValue) {
                            option.selected = true;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching stadiums:', error);
                showNotification('error', 'Có lỗi xảy ra khi cập nhật danh sách sân vận động!');
            });
        }

        // Cập nhật bảng danh sách đội bóng qua AJAX
        function updateTeamTable() {
            const teamId = document.getElementById('filter_team').value;
            const seasonId = document.getElementById('filter_season').value;
            let url = '../controller/AteamController.php?action=get_teams';
            const params = new URLSearchParams();
            if (teamId) params.append('team_id', teamId);
            if (seasonId) params.append('season_id', seasonId);
            if (params.toString()) url += '?' + params.toString();

            fetch(url)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('teamTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <button class="btn add-btn" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Thêm đội bóng
                            </button>
                        </td>
                    </tr>
                `;
                let stt = 1;
                data.forEach(team => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${stt++}</td>
                        <td>
                            <img src="../${team.logo_url || '<?php echo BASE_URL; ?>assets/img/default_logo.png'}" alt="" class="team-image">
                        </td>
                        <td>${team.name}</td>
                        <td>${team.stadium_name || 'Chưa có'}</td>
                        <td>${team.founded_year || 'N/A'}</td>
                        <td>${team.coach_name || 'Chưa có'}</td>
                        <td>
                            <button class="btn edit-btn" onclick="openEditModal(${team.team_id}, '${team.name.replace(/'/g, "\\'")}', ${team.stadium_id || 'null'}, '${(team.stadium_name || '').replace(/'/g, "\\'")}', ${team.founded_year || 'null'}, '${(team.coach_name || '').replace(/'/g, "\\'")}', '${(team.logo_url || '').replace(/'/g, "\\'")}')">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn edit-stadium-btn" onclick="openEditStadiumModal(${team.stadium_id || 'null'}, '${(team.stadium_name || '').replace(/'/g, "\\'")}')">
                                <i class="fas fa-stadium"></i> Sửa sân
                            </button>
                            <button class="btn delete-btn" onclick="openConfirmDeleteModal(${team.team_id})">
                                <i class="fas fa-trash-alt"></i> Xóa
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error fetching teams:', error);
                showNotification('error', 'Có lỗi xảy ra khi cập nhật bảng danh sách đội bóng!');
            });
        }

        // Modal thông báo
        function showNotification(type, message) {
            const modal = document.getElementById('notificationModal');
            const content = document.getElementById('notificationContent');
            const title = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');

            content.className = 'notification-content ' + type;
            title.textContent = type === 'success' ? 'Thành công' : type === 'error' ? 'Lỗi' : 'Thông báo';
            messageEl.textContent = message;
            modal.style.display = 'flex';
        }

        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        // Modal xác nhận xóa
        function openConfirmDeleteModal(teamId) {
            fetch('../controller/AteamController.php?action=check_team_delete&team_id=' + encodeURIComponent(teamId))
                .then(response => response.text())
                .then(data => {
                    if (data === 'constrained') {
                        showNotification('error', 'Không thể xóa đội bóng vì có ràng buộc với lịch thi đấu!');
                    } else {
                        currentTeamIdToDelete = teamId;
                        document.getElementById('confirmDeleteModal').style.display = 'flex';
                    }
                })
                .catch(error => {
                    console.error('Error checking delete:', error);
                    showNotification('error', 'Có lỗi xảy ra khi kiểm tra ràng buộc!');
                });
        }

        function closeConfirmDeleteModal() {
            document.getElementById('confirmDeleteModal').style.display = 'none';
            currentTeamIdToDelete = null;
        }

        function deleteTeam() {
            if (!currentTeamIdToDelete) return;

            const formData = new FormData();
            formData.append('action', 'delete_team');
            formData.append('team_id', currentTeamIdToDelete);

            fetch('../controller/AteamController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeConfirmDeleteModal();
                if (data.status === 'success') {
                    showNotification('success', data.message);
                    updateTeamTable();
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting team:', error);
                closeConfirmDeleteModal();
                showNotification('error', 'Có lỗi xảy ra khi xóa đội bóng!');
            });
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', deleteTeam);

        // Xử lý submit form thêm đội bóng qua AJAX
        document.getElementById('addTeamForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../controller/AteamController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('success', data.message);
                    closeModal('addModal');
                    updateTeamTable();
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error adding team:', error);
                showNotification('error', 'Có lỗi xảy ra khi thêm đội bóng!');
            });
        });

        // Xử lý submit form sửa đội bóng qua AJAX
        document.getElementById('editTeamForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../controller/AteamController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('success', data.message);
                    closeModal('editModal');
                    updateTeamTable();
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error editing team:', error);
                showNotification('error', 'Có lỗi xảy ra khi cập nhật đội bóng!');
            });
        });

        // Hàm áp dụng bộ lọc tự động khi thay đổi dropdown
        function applyFilter() {
            const teamId = document.getElementById('filter_team').value;
            const seasonId = document.getElementById('filter_season').value;
            let url = '?';
            if (teamId) url += 'team_id=' + encodeURIComponent(teamId);
            if (seasonId) url += (url === '?' ? '' : '&') + 'season_id=' + encodeURIComponent(seasonId);
            window.location.href = url || '?';
        }

        // Đóng modal khi nhấp ra ngoài
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                if (event.target.id === 'createStadiumModal' || event.target.id === 'editStadiumModal') {
                    document.getElementById(currentMode + 'Modal').style.display = 'block';
                }
            }
        }

        // Hiển thị thông báo từ session (nếu có)
        <?php if (isset($_SESSION['message'])): ?>
            showNotification('success', '<?php echo $_SESSION['message']; ?>');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            showNotification('error', '<?php echo $_SESSION['error']; ?>');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
    <?php ob_end_flush(); ?>
</body>
</html>