<?php
session_start();

require_once '../includes/config.php';
require_once '../controller/coachController.php';

// Kiểm tra quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../user/login.php");
//     exit;
// }

// Lấy danh sách đội bóng để hiển thị trong form
$teams_sql = "SELECT * FROM teams";
$teams_result = mysqli_query($conn, $teams_sql);
$teams = mysqli_fetch_all($teams_result, MYSQLI_ASSOC);

// Lấy danh sách HLV với bộ lọc
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$managers = getManagers($conn, $team_id, $search);

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Huấn luyện viên</title>
    <link rel="stylesheet" href="../assets/css/manageCoaches.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS bổ sung cho resizable columns */
        .resizer {
            position: absolute;
            right: 0;
            top: 0;
            width: 5px;
            height: 100%;
            cursor: col-resize;
            background-color: transparent;
            z-index: 1;
        }

        .resizer:hover {
            background-color: var(--accent-teal);
        }

        .manager-table th,
        .manager-table td {
            position: relative;
            overflow: hidden;
        }

        .manager-table th:hover .resizer,
        .manager-table td:hover .resizer {
            background-color: rgba(0, 188, 212, 0.5);
        }
    </style>
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

        <!-- Tìm kiếm HLV -->
        <div class="card search-section">
            <h2>Tìm kiếm Huấn luyện viên</h2>
            <form class="search-form">
                <div class="form-group">
                    <label for="managerSearch">Tên HLV:</label>
                    <div class="autocomplete">
                        <input type="text" id="managerSearch" name="search" placeholder="Nhập tên HLV..." value="<?php echo htmlspecialchars($search ?? ''); ?>" onkeyup="searchManagers()">
                    </div>
                </div>
            </form>
        </div>

        <!-- Bộ lọc -->
        <div class="card filter-section">
            <h2>Lọc Huấn luyện viên</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="filter_team">Đội bóng:</label>
                    <select name="team_id" id="filter_team">
                        <option value="">Tất cả</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>" <?php echo $team_id == $team['team_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Lọc</button>
            </form>
        </div>

        <!-- Bảng danh sách HLV -->
        <div class="card">
            <h2>Danh sách Huấn luyện viên</h2>
            <table class="manager-table">
                <thead>
                    <tr>
                        <th data-column="photo">Ảnh</th>
                        <th data-column="name">Tên<span class="resizer"></span></th>
                        <th data-column="nationality">Quốc tịch<span class="resizer"></span></th>
                        <th data-column="birth_date">Ngày sinh<span class="resizer"></span></th>
                        <th data-column="start_date">Ngày bắt đầu<span class="resizer"></span></th>
                        <th data-column="team">Đội<span class="resizer"></span></th>
                        <th data-column="information">Thông tin<span class="resizer"></span></th>
                        <th data-column="actions">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8">
                            <button class="btn add-btn" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Thêm HLV
                            </button>
                            <button class="btn export-btn" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> Xuất Excel
                            </button>
                        </td>
                    </tr>
                    <?php foreach ($managers as $manager): ?>
                        <tr>
                            <td>
                                <img src="../<?php echo htmlspecialchars($manager['photo_url'] ?? BASE_URL . 'assets/img/default_avatar.png'); ?>" alt="" class="manager-image">
                            </td>
                            <td><?php echo htmlspecialchars($manager['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($manager['nationality'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($manager['birth_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($manager['start_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($manager['team_name'] ?? 'Chưa có đội'); ?></td>
                            <td><?php echo htmlspecialchars($manager['information'] ?? 'Chưa có thông tin'); ?></td>
                            <td>
                                <button class="btn edit-btn" onclick="openEditModal(<?php echo $manager['manager_id']; ?>, '<?php echo htmlspecialchars(addslashes($manager['name'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($manager['nationality'] ?? '')); ?>', '<?php echo htmlspecialchars($manager['birth_date'] ?? ''); ?>', '<?php echo htmlspecialchars($manager['start_date'] ?? ''); ?>', <?php echo $manager['team_id'] ?? 0; ?>, '<?php echo htmlspecialchars($manager['photo_url'] ?? ''); ?>', '<?php echo htmlspecialchars(addslashes($manager['information'] ?? '')); ?>')">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn delete-btn" onclick="confirmDelete(<?php echo $manager['manager_id']; ?>)">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal thêm HLV -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">×</span>
                <h2>Thêm HLV mới</h2>
                <form action="../controller/coachController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_manager">
                    <div class="form-group">
                        <label for="name">Tên HLV:</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="nationality">Quốc tịch:</label>
                        <input type="text" name="nationality" id="nationality" required>
                    </div>
                    <div class="form-group">
                        <label for="birth_date">Ngày sinh:</label>
                        <input type="date" name="birth_date" id="birth_date" required>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Ngày bắt đầu:</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="team_id">Đội bóng:</label>
                        <select name="team_id" id="team_id">
                            <option value="0">Chưa có đội</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="image">Hình ảnh:</label>
                        <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(this, 'add_photo_preview')">
                        <img id="add_photo_preview" src="#" alt="Xem trước ảnh" class="photo-preview" style="display: none;">
                    </div>
                    <div class="form-group">
                        <label for="information">Thông tin:</label>
                        <textarea name="information" id="information"></textarea>
                    </div>
                    <button type="submit" class="btn">Thêm HLV</button>
                </form>
            </div>
        </div>

        <!-- Modal sửa HLV -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">×</span>
                <h2>Sửa thông tin HLV</h2>
                <form action="../controller/coachController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_manager">
                    <input type="hidden" name="manager_id" id="edit_manager_id">
                    <div class="form-group">
                        <label for="edit_name">Tên HLV:</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nationality">Quốc tịch:</label>
                        <input type="text" name="nationality" id="edit_nationality" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_birth_date">Ngày sinh:</label>
                        <input type="date" name="birth_date" id="edit_birth_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_start_date">Ngày bắt đầu:</label>
                        <input type="date" name="start_date" id="edit_start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_team_id">Đội bóng:</label>
                        <select name="team_id" id="edit_team_id">
                            <option value="0">Chưa có đội</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_image">Hình ảnh (Chỉ chọn nếu muốn thay đổi):</label>
                        <input type="file" name="image" id="edit_image" accept="image/*" onchange="previewImage(this, 'edit_photo_preview')">
                        <input type="hidden" name="current_image" id="edit_current_image">
                        <img id="edit_photo_preview" src="#" alt="Xem trước ảnh" class="photo-preview" style="display: none;">
                    </div>
                    <div class="form-group">
                        <label for="edit_information">Thông tin:</label>
                        <textarea name="information" id="edit_information"></textarea>
                    </div>
                    <button type="submit" class="btn">Cập nhật</button>
                </form>
            </div>
        </div>

        <!-- Modal xác nhận xóa -->
        <div id="confirmDeleteModal" class="confirm-delete-modal">
            <p>Bạn có chắc muốn xóa HLV này không?</p>
            <button class="confirm-btn" id="confirmDeleteBtn">Xóa</button>
            <button class="cancel-btn" onclick="closeDeleteModal()">Hủy</button>
        </div>
    </div>

    <script>
        // Modal thêm HLV
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Modal sửa HLV
        function openEditModal(managerId, name, nationality, birthDate, startDate, teamId, photoUrl, information) {
        // Đảm bảo các giá trị không phải là undefined hoặc null
        name = name || '';
        nationality = nationality || '';
        birthDate = birthDate || '';
        startDate = startDate || '';
        teamId = teamId || 0;
        photoUrl = photoUrl || '';
        information = information || '';

        document.getElementById('edit_manager_id').value = managerId;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_nationality').value = nationality;
        document.getElementById('edit_birth_date').value = birthDate;
        document.getElementById('edit_start_date').value = startDate;
        document.getElementById('edit_team_id').value = teamId;
        document.getElementById('edit_current_image').value = photoUrl;
        document.getElementById('edit_information').value = information;

        if (photoUrl) {
            document.getElementById('edit_photo_preview').src = '../' + photoUrl;
            document.getElementById('edit_photo_preview').style.display = 'block';
        } else {
            document.getElementById('edit_photo_preview').style.display = 'none';
        }

        document.getElementById('editModal').style.display = 'block';
        }

        // Modal xác nhận xóa
        let currentManagerIdToDelete = null;

        function confirmDelete(managerId) {
            currentManagerIdToDelete = managerId;
            document.getElementById('confirmDeleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('confirmDeleteModal').style.display = 'none';
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (currentManagerIdToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../controller/coachController.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_manager';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'manager_id';
                idInput.value = currentManagerIdToDelete;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
            closeDeleteModal();
        });

        // Tìm kiếm và gợi ý HLV
        function searchManagers() {
            let input = document.getElementById('managerSearch');
            let suggestions = document.createElement('div');
            suggestions.setAttribute('class', 'autocomplete-items');
            input.parentNode.appendChild(suggestions);

            if (input.value.length >= 3) {
                let xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        suggestions.innerHTML = '';
                        let response = JSON.parse(this.responseText);
                        response.suggestions.forEach(manager => {
                            let div = document.createElement('div');
                            div.innerHTML = manager.name;
                            div.addEventListener('click', function() {
                                input.value = manager.name;
                                suggestions.remove();
                                window.location.href = `?search=${encodeURIComponent(manager.name)}`;
                            });
                            suggestions.appendChild(div);
                        });
                    }
                };
                xhr.open('GET', '../controller/searchManagers.php?term=' + encodeURIComponent(input.value), true);
                xhr.send();
            } else {
                suggestions.remove();
            }
        }

        // Xuất Excel
        function exportToExcel() {
            window.location.href = '../controller/coachController.php?action=export_excel';
        }

        // Xem trước ảnh
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        // Đóng modal khi nhấp ra ngoài
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('confirmDeleteModal')) {
                closeDeleteModal();
            }
            let suggestions = document.querySelector('.autocomplete-items');
            if (suggestions && event.target != document.getElementById('managerSearch')) {
                suggestions.remove();
            }
        };

        // Tính năng kéo thả để điều chỉnh kích thước cột
        let resizers = document.querySelectorAll('.resizer');
        let columns = document.querySelectorAll('.manager-table th');
        let currentResizer = null;
        let currentColumn = null;
        let nextColumn = null;

        resizers.forEach(resizer => {
            resizer.addEventListener('mousedown', function(e) {
                currentResizer = e.target;
                currentColumn = currentResizer.parentElement;

                // Tìm cột tiếp theo (bỏ qua cột cuối cùng)
                nextColumn = currentColumn.nextElementSibling;
                if (!nextColumn || nextColumn === columns[columns.length - 0]) {
                    return; // Không cho phép kéo thả nếu không có cột tiếp theo hoặc là cột cuối
                }

                document.addEventListener('mousemove', resize);
                document.addEventListener('mouseup', stopResize);
                e.preventDefault();
            });
        });

        function resize(e) {
            if (currentResizer && currentColumn && nextColumn) {
                const newWidth = e.pageX - currentColumn.getBoundingClientRect().left;
                const maxWidth = 600; // Giới hạn tối đa chiều rộng cột
                if (newWidth > 50 && newWidth < maxWidth) { // Giới hạn tối thiểu 50px, tối đa 300px
                    const widthChange = newWidth - currentColumn.getBoundingClientRect().width;
                    const nextWidth = nextColumn.getBoundingClientRect().width - widthChange;
                    if (nextWidth > 50) { // Đảm bảo cột tiếp theo không nhỏ hơn 50px
                        currentColumn.style.width = `${newWidth}px`;
                        nextColumn.style.width = `${nextWidth}px`;
                    }
                }
            }
        }

        function stopResize() {
            currentResizer = null;
            currentColumn = null;
            nextColumn = null;
            document.removeEventListener('mousemove', resize);
            document.removeEventListener('mouseup', stopResize);
        }

        function exportToExcel() {
    window.location.href = '../controller/coachController.php?action=export_excel';
        }

    </script>
    
</body>
</html>