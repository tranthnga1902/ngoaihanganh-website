<?php
require_once(__DIR__ . '/../includes/config.php');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Lịch Thi Đấu</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <style>
    .container {
        padding: 20px;
        margin-left: 250px;
        padding-top: 80px;
        background-color: #f5f7fa;
    }

    h2 {
        color: #2c3e50;
        margin-bottom: 25px;
        font-size: 28px;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }

    .form-container {
        background-color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .form-container h3 {
        margin-top: 0;
        color: #2c3e50;
        font-size: 20px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .form-container form {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .form-group label {
        font-size: 14px;
        color: #34495e;
        font-weight: 500;
    }

    .form-container select, 
    .form-container input {
        padding: 12px 15px;
        border: 1px solid #dfe6e9;
        border-radius: 6px;
        font-size: 14px;
        width: 220px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }

    .form-container select:focus, 
    .form-container input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        background-color: white;
    }

    .form-container button {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        height: 42px;
    }

    .form-container button:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        box-shadow: 0 1px 10px rgba(0, 0, 0, 0.05);
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ecf0f1;
    }

    th {
        background-color: #3498db;
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    tr:hover {
        background-color: #f1f9ff;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .action-buttons a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 12px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        min-width: 60px;
    }

    .action-buttons a:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-postpone {
        background-color: #f39c12;
        color: white;
    }

    .btn-postpone:hover {
        background-color: #e67e22;
    }

    .btn-cancel {
        background-color: #e74c3c;
        color: white;
    }

    .btn-cancel:hover {
        background-color: #c0392b;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-scheduled {
        background-color: #d5f5e3;
        color: #27ae60;
    }

    .status-postponed {
        background-color: #fdebd0;
        color: #f39c12;
    }

    .error {
        color: #e74c3c;
        background-color: #fde8e8;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #e74c3c;
        font-size: 14px;
    }

    .success {
        color: #27ae60;
        background-color: #e8f8f0;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #27ae60;
        font-size: 14px;
    }

    @media (max-width: 992px) {
        .container {
            margin-left: 0;
            padding-top: 20px;
        }
        
        .form-container form {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        
        .form-container select, 
        .form-container input {
            width: 100%;
        }
        
        table {
            display: block;
            overflow-x: auto;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 8px;
        }
        
        .action-buttons a {
            width: 100%;
            text-align: center;
        }
    }
</style>
</head>
<body>
    <?php include(__DIR__ . '/../includes/sidebar.php'); ?>
    <div class="container">
        <h2>Quản lý Lịch Thi Đấu - Mùa Giải 2024/2025</h2>

        <!-- Form to create a new match -->
        <div class="form-container">
            <h3>Thêm Trận Đấu Mới</h3>
            <?php if (isset($_GET['message'])): ?>
                <p class="<?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </p>
            <?php endif; ?>
            <form action="<?php echo BASE_URL; ?>Controller/manageMatchesController.php" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="home_team_id">Đội Nhà</label>
                    <select name="home_team_id" id="home_team_id" required>
                        <option value="">Chọn Đội Nhà</option>
                        <?php
                        $sql = "SELECT team_id, name FROM Teams";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['team_id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="away_team_id">Đội Khách</label>
                    <select name="away_team_id" id="away_team_id" required>
                        <option value="">Chọn Đội Khách</option>
                        <?php
                        $sql = "SELECT team_id, name FROM Teams";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['team_id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stadium_id">Sân Vận Động</label>
                    <select name="stadium_id" id="stadium_id" required>
                        <option value="">Chọn Sân Vận Động</option>
                        <?php
                        $sql = "SELECT stadium_id, name FROM Stadiums";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['stadium_id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="match_date">Thời Gian</label>
                    <input type="datetime-local" name="match_date" id="match_date" required>
                </div>
                
                <div class="form-group">
                    <label for="round">Vòng Đấu</label>
                    <input type="text" name="round" id="round" placeholder="Ví dụ: Round 1" required>
                </div>
                
                <button type="submit">Thêm Trận Đấu</button>
            </form>
        </div>

        <!-- Matches table -->
        <table>
            <thead>
                <tr>
                    <th>Đội Nhà</th>
                    <th>Đội Khách</th>
                    <th>Thời Gian</th>
                    <th>Sân</th>
                    <th>Vòng</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT m.match_id, m.match_date, m.round, m.status,
                        h.name AS home_team, a.name AS away_team, st.name AS stadium_name
                        FROM Matches m
                        JOIN Teams h ON m.home_team_id = h.team_id
                        JOIN Teams a ON m.away_team_id = a.team_id
                        JOIN Stadiums st ON m.stadium_id = st.stadium_id
                        WHERE m.season_id = 1 AND m.status != 'Completed'";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['home_team']}</td>";
                    echo "<td>{$row['away_team']}</td>";
                    echo "<td>" . date('d/m/Y H:i', strtotime($row['match_date'])) . "</td>";
                    echo "<td>{$row['stadium_name']}</td>";
                    echo "<td>{$row['round']}</td>";
                    echo "<td><span class='status-badge " . ($row['status'] == 'Scheduled' ? 'status-scheduled' : 'status-postponed') . "'>" . 
                         ($row['status'] == 'Scheduled' ? 'Chưa diễn ra' : 'Hoãn') . "</span></td>";
                    echo "<td class='action-buttons'>";
                    echo "<a href='#' class='btn-postpone' onclick='postponeMatch({$row['match_id']})'>Hoãn</a>";
                    echo "<a href='#' class='btn-cancel' onclick='cancelMatch({$row['match_id']})'>Hủy</a>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function postponeMatch(matchId) {
            const newTime = prompt("Nhập thời gian mới (YYYY-MM-DD HH:MM):");
            if (newTime) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo BASE_URL; ?>Controller/manageMatchesController.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="postpone">
                    <input type="hidden" name="match_id" value="${matchId}">
                    <input type="hidden" name="new_date" value="${newTime}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function cancelMatch(matchId) {
            if (confirm("Bạn có chắc muốn hủy trận đấu này?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo BASE_URL; ?>Controller/manageMatchesController.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="match_id" value="${matchId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>