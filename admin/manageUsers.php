<?php
session_start();

require_once '../includes/config.php';
require_once '../controller/userController.php';

// Kiểm tra quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../user/login.php");
//     exit;
// }

// Lấy danh sách người dùng với bộ lọc
$role = isset($_GET['role']) ? trim($_GET['role']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$users = getUsers($conn, $role, $search);

ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng</title>
    <link rel="stylesheet" href="../assets/css/manageUsers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS cho bảng và giao diện */
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

        .user-table th,
        .user-table td {
            position: relative;
            overflow: hidden;
        }

        .user-table th:hover .resizer,
        .user-table td:hover .resizer {
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

        <!-- Tìm kiếm người dùng -->
        <div class="card search-section">
            <h2>Tìm kiếm Người dùng</h2>
            <form class="search-form">
                <div class="form-group">
                    <label for="userSearch">Tên người dùng:</label>
                    <div class="autocomplete">
                        <input type="text" id="userSearch" name="search" placeholder="Nhập tên người dùng..." value="<?php echo htmlspecialchars($search ?? ''); ?>" onkeyup="searchUsers()">
                    </div>
                </div>
            </form>
        </div>

        <!-- Bộ lọc -->
        <div class="card filter-section">
            <h2>Lọc Người dùng</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="filter_role">Quyền:</label>
                    <select name="role" id="filter_role">
                        <option value="">Tất cả</option>
                        <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo $role == 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <button type="submit" class="btn">Lọc</button>
            </form>
        </div>

        <!-- Bảng danh sách người dùng -->
        <div class="card">
            <h2>Danh sách Người dùng</h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th data-column="stt">STT<span class="resizer"></span></th>
                        <th data-column="username">Tên người dùng<span class="resizer"></span></th>
                        <th data-column="email">Email<span class="resizer"></span></th>
                        <th data-column="phone">Số điện thoại<span class="resizer"></span></th>
                        <th data-column="role">Quyền<span class="resizer"></span></th>
                        <th data-column="team">Câu lạc bộ yêu thích<span class="resizer"></span></th>
                        <th data-column="actions">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7">
                            <button class="btn add-btn" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Thêm người dùng
                            </button>
                        </td>
                    </tr>
                    <?php $stt = 1; foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $stt++; ?></td>
                            <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['role'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['favorite_team_names'] ?? 'Chưa chọn'); ?></td>
                            <td>
                                <?php if ($user['is_locked'] == 0): ?>
                                    <button class="btn lock-btn" onclick="openLockModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'] ?? '')); ?>')">
                                        <i class="fas fa-lock"></i> Khóa
                                    </button>
                                <?php else: ?>
                                    <button class="btn" style="background-color: #27ae60; color: white;" onclick="openUnlockModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'] ?? '')); ?>')">
                                        <i class="fas fa-unlock"></i> Mở khóa
                                    </button>
                                    <span class="locked"> (Đã khóa: <?php echo htmlspecialchars($user['lock_reason'] ?? 'Không có lý do'); ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal thêm người dùng -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">×</span>
                <h2>Thêm Người dùng mới</h2>
                <form action="../controller/userController.php" method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-group">
                        <label for="username">Tên người dùng:</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Số điện thoại:</label>
                        <input type="text" name="phone_number" id="phone_number">
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu:</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Quyền:</label>
                        <select name="role" id="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Thêm người dùng</button>
                </form>
            </div>
        </div>

        <!-- Modal khóa người dùng -->
        <div id="lockModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeLockModal()">×</span>
                <h2>Khóa người dùng</h2>
                <form action="../controller/userController.php" method="POST">
                    <input type="hidden" name="action" value="lock_user">
                    <input type="hidden" name="user_id" id="lock_user_id">
                    <div class="form-group">
                        <label for="lock_reason">Lý do khóa:</label>
                        <textarea name="lock_reason" id="lock_reason" required></textarea>
                    </div>
                    <button type="submit" class="btn">Khóa</button>
                </form>
            </div>
        </div>

        <!-- Modal mở khóa người dùng -->
        <div id="unlockModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeUnlockModal()">×</span>
                <h2>Mở khóa người dùng</h2>
                <form action="../controller/userController.php" method="POST">
                    <input type="hidden" name="action" value="unlock_user">
                    <input type="hidden" name="user_id" id="unlock_user_id">
                    <p>Bạn có chắc muốn mở khóa tài khoản <strong id="unlock_username"></strong>?</p>
                    <button type="submit" class="btn" style="background-color: #27ae60; color: white;">Mở khóa</button>
                </form>
            </div>
        </div>

    </div>

    <script>
        // Modal thêm người dùng
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Modal khóa người dùng
        function openLockModal(userId, username) {
            document.getElementById('lock_user_id').value = userId;
            document.getElementById('lockModal').style.display = 'block';
        }

        function closeLockModal() {
            document.getElementById('lockModal').style.display = 'none';
        }

        // Modal mở khóa người dùng
        function openUnlockModal(userId, username) {
            document.getElementById('unlock_user_id').value = userId;
            document.getElementById('unlock_username').textContent = username;
            document.getElementById('unlockModal').style.display = 'block';
        }

        function closeUnlockModal() {
            document.getElementById('unlockModal').style.display = 'none';
        }

        // Tìm kiếm và gợi ý người dùng
        function searchUsers() {
            let input = document.getElementById('userSearch');
            let suggestions = document.createElement('div');
            suggestions.setAttribute('class', 'autocomplete-items');
            input.parentNode.appendChild(suggestions);

            if (input.value.length >= 3) {
                let xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        suggestions.innerHTML = '';
                        let response = JSON.parse(this.responseText);
                        response.suggestions.forEach(user => {
                            let div = document.createElement('div');
                            div.innerHTML = user.username;
                            div.addEventListener('click', function() {
                                input.value = user.username;
                                suggestions.remove();
                                window.location.href = `?search=${encodeURIComponent(user.username)}`;
                            });
                            suggestions.appendChild(div);
                        });
                    }
                };
                xhr.open('GET', '../controller/userController.php?term=' + encodeURIComponent(input.value), true);
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
            if (event.target == document.getElementById('lockModal')) {
                closeLockModal();
            }
            if (event.target == document.getElementById('unlockModal')) {
                closeUnlockModal();
            }
            let suggestions = document.querySelector('.autocomplete-items');
            if (suggestions && event.target != document.getElementById('userSearch')) {
                suggestions.remove();
            }
        };

        // Tính năng kéo thả để điều chỉnh kích thước cột
        let resizers = document.querySelectorAll('.resizer');
        let columns = document.querySelectorAll('.user-table th');
        let currentResizer = null;
        let currentColumn = null;
        let nextColumn = null;

        resizers.forEach(resizer => {
            resizer.addEventListener('mousedown', function(e) {
                currentResizer = e.target;
                currentColumn = currentResizer.parentElement;

                // Tìm cột tiếp theo (bỏ qua cột cuối cùng)
                nextColumn = currentColumn.nextElementSibling;
                if (!nextColumn || nextColumn === columns[columns.length - 1]) {
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
                if (newWidth > 50 && newWidth < maxWidth) { // Giới hạn tối thiểu 50px, tối đa 600px
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
    </script>
</body>
</html>