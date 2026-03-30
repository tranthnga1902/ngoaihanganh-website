
// Global variables
let topScorersChart, strongestTeamsChart;
let currentPage = 1;
let totalPages = 1;
let currentSearchTerm = '';
let currentStatsType = 'player';
let currentSeasonId = null;

// Initialize charts
function initCharts() {
    const ctx1 = document.getElementById('topScorersChart').getContext('2d');
    topScorersChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Bàn thắng',
                data: [],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 10,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Top 10 Cầu thủ ghi bàn mùa 2024/2025',
                    color: '#333',
                    font: {
                        size: 18
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Số bàn thắng'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Cầu thủ'
                    }
                }
            }
        }
    });

    const ctx2 = document.getElementById('strongestTeamsChart').getContext('2d');
    strongestTeamsChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4BC0C0', '#FF6384'
                ],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                title: {
                    display: true,
                    text: 'Top 10 Đội mạnh nhất mùa 2024/2025',
                    color: '#333',
                    font: {
                        size: 18
                    }
                }
            }
        }
    });
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('#alert_container').html(alertHtml);
    
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

// Update all statistics
function updateAllStats() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    $('#loading').show();
    
    $.post('', {
        action: 'update_all_stats',
        season_id: seasonId
    }, function(response) {
        $('#loading').hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadStatistics();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        $('#loading').hide();
        showAlert('Lỗi AJAX: ' + textStatus, 'danger');
    });
}

// Update player statistics
function updatePlayerStats() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    $('#loading').show();
    
    $.post('', {
        action: 'update_player_stats',
        season_id: seasonId
    }, function(response) {
        $('#loading').hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadTopScorers();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json');
}

// Update team statistics
function updateTeamStats() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    $('#loading').show();
    
    $.post('', {
        action: 'update_team_stats',
        season_id: seasonId
    }, function(response) {
        $('#loading').hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadStrongestTeams();
        } else {
            showAlert(response.message, 'danger');
        }
    }, 'json');
}

// Load top scorers
function loadTopScorers() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) return;

    $.post('', {
        action: 'get_top_scorers',
        season_id: seasonId,
        limit: 10
    }, function(data) {
        const labels = data.map(item => item.player_name);
        const goals = data.map(item => parseInt(item.total_goals));
        
        topScorersChart.data.labels = labels;
        topScorersChart.data.datasets[0].data = goals;
        topScorersChart.update();
    }, 'json');
}

// Load strongest teams
function loadStrongestTeams() {
    const seasonId = $('#season_select').val() || currentSeasonId;
    if (!seasonId) return;

    $.post('', {
        action: 'get_strongest_teams',
        season_id: seasonId,
        limit: 10
    }, function(data) {
        const labels = data.map(item => item.team_name);
        const points = data.map(item => parseInt(item.points));
        
        strongestTeamsChart.data.labels = labels;
        strongestTeamsChart.data.datasets[0].data = points;
        strongestTeamsChart.update();
    }, 'json');
}

// Search statistics with pagination
function searchStatistics(page = 1) {
    const seasonId = $('#season_select').val() || currentSeasonId;
    const searchTerm = $('#search_input').val();
    const type = $('#stats_type').val();
    
    if (!seasonId) {
        showAlert('Không tìm thấy mùa giải!', 'warning');
        return;
    }

    // Update global variables
    currentPage = page;
    currentSearchTerm = searchTerm;
    currentStatsType = type;
    currentSeasonId = seasonId;

    $('#loading').show();
    
    $.post('', {
        action: 'search_statistics',
        season_id: seasonId,
        search_term: searchTerm,
        type: type,
        page: page
    }, function(response) {
        $('#loading').hide();
        displaySearchResults(response.data, type);
        updatePagination(response.pagination);
    }, 'json');
}

// Display search results
function displaySearchResults(data, type) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="8" class="text-center text-muted">Không tìm thấy dữ liệu</td></tr>';
    } else {
        data.forEach((item, index) => {
            const rowNumber = (currentPage - 1) * 20 + index + 1;
            
            if (type === 'player') {
                html += `
                    <tr>
                        <td>${rowNumber}</td>
                        <td><strong>${item.player_name}</strong></td>
                        <td>${item.team_name}</td>
                        <td>${item.matches_played || 0}</td>
                        <td><span class="badge bg-success">${item.total_goals || 0}</span></td>
                        <td><span class="badge bg-info">${item.assists || 0}</span></td>
                        <td><span class="badge bg-warning">${item.yellow_cards || 0}</span></td>
                        <td><span class="badge bg-danger">${item.red_cards || 0}</span></td>
                    </tr>
                `;
            } else {
                html += `
                    <tr>
                        <td>${rowNumber}</td>
                        <td><strong>${item.team_name}</strong></td>
                        <td>-</td>
                        <td>${item.matches_played || 0}</td>
                        <td><span class="badge bg-success">${item.wins || 0}</span></td>
                        <td><span class="badge bg-info">${item.draws || 0}</span></td>
                        <td><span class="badge bg-warning">${item.losses || 0}</span></td>
                        <td><span class="badge bg-primary">${item.points || 0}</span></td>
                    </tr>
                `;
            }
        });
    }
    
    $('#stats_table_body').html(html);
    
    // Update table header
    if (type === 'team') {
        $('#table_header').html(`
            <th>STT</th>
            <th>Tên đội</th>
            <th>-</th>
            <th>Số trận</th>
            <th>Thắng</th>
            <th>Hòa</th>
            <th>Thua</th>
            <th>Điểm</th>
        `);
    } else {
        $('#table_header').html(`
            <th>STT</th>
            <th>Tên</th>
            <th>Đội</th>
            <th>Số trận</th>
            <th>Bàn thắng</th>
            <th>Kiến tạo</th>
            <th>Thẻ vàng</th>
            <th>Thẻ đỏ</th>
        `);
    }
}

// Update pagination controls
function updatePagination(pagination) {
    const { total_pages, current_page, total_items } = pagination;
    totalPages = total_pages;
    
    let html = '';
    const maxVisiblePages = 5; // Number of visible page links
    
    if (total_pages <= 1) {
        $('#pagination_container').hide();
        return;
    }
    
    $('#pagination_container').show();
    
    // Previous button
    html += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${current_page - 1})" aria-label="Previous">
            <span aria-hidden="true">«</span>
        </a>
    </li>`;
    
    // Calculate start and end page numbers
    let startPage = Math.max(1, current_page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(total_pages, startPage + maxVisiblePages - 1);
    
    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page and ellipsis if needed
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
        </li>`;
    }
    
    // Last page and ellipsis if needed
    if (endPage < total_pages) {
        if (endPage < total_pages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${total_pages})">${total_pages}</a></li>`;
    }
    
    // Next button
    html += `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${current_page + 1})" aria-label="Next">
            <span aria-hidden="true">»</span>
        </a>
    </li>`;
    
    $('#pagination').html(html);
    
    // Show items count
    const startItem = (current_page - 1) * 20 + 1;
    const endItem = Math.min(current_page * 20, total_items);
    $('#pagination_container').append(`
        <div class="ms-3 d-flex align-items-center">
            <span class="text-muted">Hiển thị ${startItem}-${endItem} của ${total_items}</span>
        </div>
    `);
}

// Change page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    searchStatistics(page);
}

// Load all statistics
function loadStatistics() {
    loadTopScorers();
    loadStrongestTeams();
    searchStatistics();
}

// Initialize when page loads
$(document).ready(function() {
    initCharts();
    
    // Lấy season_id mặc định từ season_select và tải dữ liệu ngay lập tức
    const defaultSeasonId = $('#season_select').val();
    if (defaultSeasonId) {
        currentSeasonId = defaultSeasonId;
        loadStatistics();
    } else {
        showAlert('Không tìm thấy mùa giải 2024/2025!', 'warning');
        $('#stats_table_body').html('<tr><td colspan="8" class="text-center text-muted">Không tìm thấy dữ liệu cho mùa giải 2024/2025</td></tr>');
        $('#pagination_container').hide();
    }

    // Event listeners
    $('#season_select').change(function() {
        const seasonId = $(this).val();
        if (seasonId) {
            currentSeasonId = seasonId;
            loadStatistics();
        } else {
            showAlert('Vui lòng chọn một mùa giải!', 'warning');
            $('#stats_table_body').html('<tr><td colspan="8" class="text-center text-muted">Chọn mùa giải để xem thống kê</td></tr>');
            topScorersChart.data.labels = [];
            topScorersChart.data.datasets[0].data = [];
            topScorersChart.update();
            strongestTeamsChart.data.labels = [];
            strongestTeamsChart.data.datasets[0].data = [];
            strongestTeamsChart.update();
            $('#pagination_container').hide();
        }
    });

    $('#stats_type').change(function() {
        if (currentSeasonId) {
            searchStatistics();
        }
    });

    $('#search_input').on('keyup', function() {
        if (currentSeasonId) {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => searchStatistics(1), 500);
        }
    });
});
