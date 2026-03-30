<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../Controller/EditMatchController.php';

// Lấy match_id từ URL
$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Khởi tạo controller
$controller = new EditMatchController($conn);
$match_data = $controller->getMatchDetails($match_id);

if (!$match_data['success']) {
    header('Location: manageResults.php?error=invalid_match');
    exit;
}

$match = $match_data['data']['match'];
$events = $match_data['data']['events'];
$players = $match_data['data']['players'];

// Kiểm tra home_team_id và away_team_id
if (!isset($match['home_team_id']) || !isset($match['away_team_id'])) {
    die('Lỗi: Thiếu thông tin đội nhà hoặc đội khách.');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết trận đấu</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/match_details.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="match-details-container">
            <a href="<?php echo BASE_URL; ?>admin/manageResults.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>

            <div class="match-info">
                <h3><i class="fas fa-futbol"></i> Chi tiết trận đấu</h3>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center;">
                        <?php if (!empty($match['home_team_logo'])): ?>
                            <img src="../<?php echo htmlspecialchars($match['home_team_logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($match['home_team']); ?>" 
                                 class="team-logo">
                        <?php endif; ?>
                        <span class="team-name"><?php echo htmlspecialchars($match['home_team']); ?></span>
                    </div>
                    <div class="score-display">
                        <?php if ($match['status'] === 'Completed'): ?>
                            <?php echo $match['home_team_score'] . ' - ' . $match['away_team_score']; ?>
                        <?php else: ?>
                            VS
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <?php if (!empty($match['away_team_logo'])): ?>
                            <img src="../<?php echo htmlspecialchars($match['away_team_logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($match['away_team']); ?>" 
                                 class="team-logo">
                        <?php endif; ?>
                        <span class="team-name"><?php echo htmlspecialchars($match['away_team']); ?></span>
                    </div>
                </div>
                <div class="match-meta">
                    <p><i class="fas fa-calendar-alt"></i><strong>Ngày thi đấu:</strong> <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?></p>
                    <p><i class="fas fa-trophy"></i><strong>Mùa giải:</strong> <?php echo htmlspecialchars($match['season_name']); ?></p>
                    <p><i class="fas fa-clock"></i><strong>Trạng thái:</strong> <?php echo $match['status'] === 'Completed' ? 'Đã Kết Thúc' : 'Chưa diễn ra'; ?></p>
                </div>
            </div>

            <div class="match-info">
                <h3><i class="fas fa-list-alt"></i> Thêm/Sửa sự kiện trận đấu</h3>
                <div class="event-form">
                    <h4 id="form-title">Thêm sự kiện mới</h4>
                    <form id="eventForm" method="POST">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                        <input type="hidden" name="event_id" id="eventId" value="0">
                        <div class="form-group">
                            <label>Đội</label>
                            <select name="team_id" id="teamSelect" required onchange="updatePlayerSelect()">
                                <option value="">-- Chọn đội --</option>
                                <option value="<?php echo $match['home_team_id']; ?>" data-is-home="1"><?php echo htmlspecialchars($match['home_team']); ?></option>
                                <option value="<?php echo $match['away_team_id']; ?>" data-is-home="0"><?php echo htmlspecialchars($match['away_team']); ?></option>
                            </select>
                            <input type="hidden" name="is_home" id="isHome">
                        </div>
                        <div class="form-group">
                            <label>Cầu thủ</label>
                            <select name="player_id" id="playerSelect">
                                <option value="">-- Chọn cầu thủ --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Loại sự kiện</label>
                            <select name="event_type" id="eventType" required>
                                <option value="">-- Chọn loại sự kiện --</option>
                                <option value="goal">Bàn thắng</option>
                                <option value="penalty_scored">Penalty ghi bàn</option>
                                <option value="penalty_missed">Hỏng penalty</option>
                                <option value="own_goal">Phản lưới</option>
                                <option value="yellow_card">Thẻ vàng</option>
                                <option value="red_card">Thẻ đỏ</option>
                                <option value="assist">Kiến tạo</option>
                                <option value="save">Cứu thua</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Phút</label>
                            <input type="number" name="minute" id="minute" min="0" max="120" required>
                        </div>
                        <div class="form-group">
                            <label>Ghi chú</label>
                            <textarea name="note" id="note" placeholder="Ghi chú (nếu có)"></textarea>
                        </div>
                        <button type="submit">Lưu sự kiện</button>
                        <button type="button" onclick="resetForm()">Đặt lại</button>
                    </form>
                </div>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <div class="match-info">
                <h3><i class="fas fa-list-alt"></i> Sự kiện trận đấu</h3>
                <div id="events-table-container">
                    <?php if (count($events) > 0): ?>
                        <table class="events-table">
                            <thead>
                                <tr>
                                    <th>Đội</th>
                                    <th>Cầu thủ</th>
                                    <th>Loại sự kiện</th>
                                    <th>Phút</th>
                                    <th>Ghi chú</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="events-table-body">
                                <?php foreach ($events as $event): ?>
                                    <tr data-event-id="<?php echo $event['event_id']; ?>">
                                        <td><?php echo htmlspecialchars($event['team_name']); ?></td>
                                        <td><?php echo htmlspecialchars($event['player_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="event-badge <?php echo htmlspecialchars($event['event_type']); ?>">
                                                <?php
                                                $event_types = [
                                                    'goal' => 'Bàn thắng',
                                                    'penalty_scored' => 'Penalty ghi bàn',
                                                    'penalty_missed' => 'Hỏng penalty',
                                                    'own_goal' => 'Phản lưới',
                                                    'yellow_card' => 'Thẻ vàng',
                                                    'red_card' => 'Thẻ đỏ',
                                                    'assist' => 'Kiến tạo',
                                                    'save' => 'Cứu thua'
                                                ];
                                                echo $event_types[$event['event_type']] ?? $event['event_type'];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($event['minute']); ?>'</td>
                                        <td><?php echo htmlspecialchars($event['note'] ?? ''); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="edit-button" 
                                                        data-event-id="<?php echo $event['event_id']; ?>"
                                                        data-team-id="<?php echo $event['team_id']; ?>"
                                                        data-player-id="<?php echo $event['player_id'] ?? ''; ?>"
                                                        data-event-type="<?php echo $event['event_type']; ?>"
                                                        data-minute="<?php echo $event['minute']; ?>"
                                                        data-is-home="<?php echo $event['is_home']; ?>"
                                                        data-note="<?php echo htmlspecialchars($event['note'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i> Sửa
                                                </button>
                                                <button class="delete-button" data-event-id="<?php echo $event['event_id']; ?>">
                                                    <i class="fas fa-trash"></i> Xóa
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-events">Không có sự kiện nào được ghi nhận cho trận đấu này.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<script>
const controllerUrl = '<?php echo BASE_URL; ?>Controller/EditMatchController.php';
const playersByTeam = <?php echo json_encode($players); ?>;
const matchData = {
    homeTeamId: '<?php echo $match['home_team_id']; ?>',
    awayTeamId: '<?php echo $match['away_team_id']; ?>',
    homeTeamName: '<?php echo htmlspecialchars($match['home_team']); ?>',
    awayTeamName: '<?php echo htmlspecialchars($match['away_team']); ?>'
};

// Hàm hiển thị thông báo
function showMessage(message, type = 'success') {
    const eventsSection = document.querySelector('.match-info:last-child');
    const alert = document.createElement('div');
    alert.className = `${type}-message`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    alert.style.opacity = '1';
    eventsSection.parentNode.insertBefore(alert, eventsSection);
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 3000);
}

// Cập nhật danh sách cầu thủ dựa trên đội
function updatePlayerSelect() {
    const teamSelect = document.getElementById('teamSelect');
    const playerSelect = document.getElementById('playerSelect');
    const isHomeInput = document.getElementById('isHome');
    const teamId = teamSelect.value;
    const isHome = teamSelect.options[teamSelect.selectedIndex]?.dataset.isHome || '';

    isHomeInput.value = isHome;
    playerSelect.innerHTML = '<option value="">-- Chọn cầu thủ --</option>';

    if (teamId && playersByTeam[matchData[teamId === matchData.homeTeamId ? 'homeTeamName' : 'awayTeamName']]) {
        playersByTeam[matchData[teamId === matchData.homeTeamId ? 'homeTeamName' : 'awayTeamName']].forEach(player => {
            const option = document.createElement('option');
            option.value = player.player_id;
            option.textContent = player.name;
            playerSelect.appendChild(option);
        });
    }
}

// Hàm tạo HTML cho một hàng sự kiện
function createEventRow(event) {
    const eventTypes = {
        'goal': 'Bàn thắng',
        'penalty_scored': 'Penalty ghi bàn',
        'penalty_missed': 'Hỏng penalty',
        'own_goal': 'Phản lưới',
        'yellow_card': 'Thẻ vàng',
        'red_card': 'Thẻ đỏ',
        'assist': 'Kiến tạo',
        'save': 'Cứu thua'
    };
    return `
        <tr data-event-id="${event.event_id}">
            <td>${event.team_name}</td>
            <td>${event.player_name || 'N/A'}</td>
            <td><span class="event-badge ${event.event_type}">${eventTypes[event.event_type] || event.event_type}</span></td>
            <td>${event.minute}'</td>
            <td>${event.note || ''}</td>
            <td>
                <div class="action-buttons">
                    <button class="edit-button" 
                            data-event-id="${event.event_id}"
                            data-team-id="${event.team_id}"
                            data-player-id="${event.player_id || ''}"
                            data-event-type="${event.event_type}"
                            data-minute="${event.minute}"
                            data-is-home="${event.is_home}"
                            data-note="${event.note || ''}">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="delete-button" data-event-id="${event.event_id}">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </td>
        </tr>
    `;
}

// Hàm cập nhật bảng sự kiện
function updateEventsTable(events) {
    const tableContainer = document.querySelector('.match-info #events-table-container');
    
    if (events.length > 0) {
        let tableHTML = `
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Đội</th>
                        <th>Cầu thủ</th>
                        <th>Loại sự kiện</th>
                        <th>Phút</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="events-table-body">
                    ${events.map(event => createEventRow(event)).join('')}
                </tbody>
            </table>
        `;
        tableContainer.innerHTML = tableHTML;
    } else {
        tableContainer.innerHTML = '<div class="no-events">Không có sự kiện nào được ghi nhận cho trận đấu này.</div>';
    }
    
    attachDeleteButtonEvents();
    attachEditButtonEvents();
}

// Hàm cập nhật bảng sự kiện sau khi thêm/sửa/xóa
function updateEventsTableAfterAction(matchId) {
    fetch(controllerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_events&match_id=${matchId}`
    })
    .then(response => {
        if (!response.ok) throw new Error(`Lỗi mạng: ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateEventsTable(data.events);
        } else {
            showMessage(data.message || 'Lỗi khi tải danh sách sự kiện', 'error');
        }
    })
    .catch(error => {
        showMessage(`Lỗi khi tải danh sách sự kiện: ${error.message}`, 'error');
    });
}

// Hàm xóa sự kiện
function deleteEvent(eventId) {
    if (!eventId || isNaN(eventId) || eventId <= 0) {
        showMessage('ID sự kiện không hợp lệ', 'error');
        console.error('Lỗi: eventId không hợp lệ', eventId);
        return;
    }

    const matchId = <?php echo isset($match_id) ? json_encode($match_id) : 'null'; ?>;
    if (!matchId || isNaN(matchId) || matchId <= 0) {
        showMessage('ID trận đấu không hợp lệ', 'error');
        console.error('Lỗi: matchId không hợp lệ', matchId);
        return;
    }

    if (!confirm('Bạn có chắc muốn xóa sự kiện này?')) return;

    fetch(controllerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&event_id=${encodeURIComponent(eventId)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Lỗi mạng: ${response.status} ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Phản hồi không phải JSON:', text);
                throw new Error('Phản hồi không phải JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Phản hồi từ server:', data);
        if (data.success) {
            showMessage(data.message, 'success');
            updateEventsTableAfterAction(matchId);
            updateScoreDisplay();
        } else {
            showMessage(data.message || 'Không thể xóa sự kiện', 'error');
        }
    })
    .catch(error => {
        console.error('Lỗi xóa sự kiện:', error);
        showMessage(`Lỗi khi xóa sự kiện: ${error.message}`, 'error');
    });
}

// Gắn sự kiện cho nút chỉnh sửa
function attachEditButtonEvents() {
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('eventId').value = this.dataset.eventId;
            document.getElementById('teamSelect').value = this.dataset.teamId;
            document.getElementById('isHome').value = this.dataset.isHome;
            document.getElementById('eventType').value = this.dataset.eventType;
            document.getElementById('minute').value = this.dataset.minute;
            document.getElementById('note').value = this.dataset.note;
            document.getElementById('form-title').textContent = 'Sửa sự kiện';

            updatePlayerSelect();
            setTimeout(() => {
                document.getElementById('playerSelect').value = this.dataset.playerId;
            }, 100);
            window.scrollTo({ top: document.querySelector('.event-form').offsetTop, behavior: 'smooth' });
        });
    });
}

// Gắn sự kiện cho nút xóa
function attachDeleteButtonEvents() {
    document.querySelectorAll('.delete-button').forEach(button => {
        button.removeEventListener('click', handleDeleteClick);
        button.addEventListener('click', handleDeleteClick);
    });
}

function handleDeleteClick() {
    const eventId = this.dataset.eventId;
    if (eventId && !isNaN(eventId)) {
        deleteEvent(parseInt(eventId));
    } else {
        showMessage('ID sự kiện không hợp lệ', 'error');
        console.error('Lỗi: Nút xóa thiếu hoặc có data-event-id không hợp lệ', this.dataset.eventId);
    }
}

// Đặt lại form
function resetForm() {
    document.getElementById('eventForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('eventId').value = '';
    document.getElementById('form-title').textContent = 'Thêm sự kiện mới';
    document.getElementById('playerSelect').innerHTML = '<option value="">-- Chọn cầu thủ --</option>';
    document.getElementById('isHome').value = '';
    document.getElementById('note').value = '';
}

// Hàm cập nhật tỉ số hiển thị
function updateScoreDisplay() {
    fetch(controllerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_match_details&match_id=<?php echo $match_id; ?>`
    })
    .then(response => {
        if (!response.ok) throw new Error(`Lỗi mạng: ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success && data.data.match) {
            const scoreDisplay = document.querySelector('.score-display');
            if (data.data.match.status === 'Completed') {
                scoreDisplay.textContent = `${data.data.match.home_team_score} - ${data.data.match.away_team_score}`;
            } else {
                scoreDisplay.textContent = 'VS';
            }
        }
    })
    .catch(error => {
        console.error('Lỗi cập nhật tỉ số:', error);
    });
}

// Xử lý gửi form thêm/sửa sự kiện
function handleAddEvent(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    // Đảm bảo is_home được cập nhật đúng khi chọn đội
    const teamSelect = document.getElementById('teamSelect');
    const isHome = teamSelect.options[teamSelect.selectedIndex]?.dataset.isHome || '0';
    formData.set('is_home', isHome);

    const action = formData.get('action');
    const data = {
        action: action,
        match_id: formData.get('match_id'),
        team_id: formData.get('team_id'),
        player_id: formData.get('player_id') || null,
        event_type: formData.get('event_type'),
        minute: formData.get('minute'),
        is_home: isHome,
        note: formData.get('note') || ''
    };
    if (action === 'edit') {
        data.event_id = formData.get('event_id');
    }

    if (!data.match_id || !data.team_id || !data.event_type || !data.minute) {
        showMessage('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
        return;
    }
    if (data.minute < 0 || data.minute > 120) {
        showMessage('Phút phải từ 0 đến 120', 'error');
        return;
    }

    fetch(controllerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data).toString()
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Lỗi mạng: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            resetForm();
            updateEventsTableAfterAction(data.match_id || formData.get('match_id'));
            updateScoreDisplay();
        } else {
            showMessage(data.message || 'Lỗi khi lưu sự kiện', 'error');
        }
    })
    .catch(error => {
        showMessage(`Lỗi khi lưu sự kiện: ${error.message}`, 'error');
    });
}

// Xử lý sự kiện khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('eventForm').addEventListener('submit', handleAddEvent);
    attachEditButtonEvents();
    attachDeleteButtonEvents();

    const successMessage = document.querySelector('.success-message');
    const errorMessage = document.querySelector('.error-message');
    if (successMessage || errorMessage) {
        setTimeout(() => {
            if (successMessage) {
                successMessage.style.transition = 'opacity 0.5s';
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 500);
            }
            if (errorMessage) {
                errorMessage.style.transition = 'opacity 0.5s';
                errorMessage.style.opacity = '0';
                setTimeout(() => errorMessage.remove(), 500);
            }
        }, 3000);
    }

    updateEventsTableAfterAction(<?php echo $match_id; ?>);
});
</script>
</body>
</html>