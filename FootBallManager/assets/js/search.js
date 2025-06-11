document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const suggestionsBox = document.getElementById('suggestions');
   
    // Xử lý khi nhập liệu
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
       
        if (query.length > 1) {
            fetch(`../api/searchTeams.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => showSuggestions(data))
                .catch(error => console.error('Error:', error));
        } else {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
        }
    });
   
    // Hiển thị gợi ý
    function showSuggestions(teams) {
        if (teams.length > 0) {
            let html = '';
            teams.forEach(team => {
                html += `<div class="suggestion-item"
                            onclick="selectSuggestion('${team.name.replace("'", "\\'")}')">
                            <img src="../${team.logo_url}" alt="${team.name}" width="30">
                            ${team.name} (${team.city})
                        </div>`;
            });
            suggestionsBox.innerHTML = html;
            suggestionsBox.style.display = 'block';
        } else {
            suggestionsBox.innerHTML = '<div class="no-suggestion">Không tìm thấy kết quả</div>';
            suggestionsBox.style.display = 'block';
        }
    }
   
    // Chọn gợi ý
    window.selectSuggestion = function(name) {
        searchInput.value = name;
        suggestionsBox.style.display = 'none';
        document.querySelector('form.search-form').submit();
    };
   
    // Ẩn gợi ý khi click ra ngoài
    document.addEventListener('click', function(e) {
        if (e.target.id !== 'search-input') {
            suggestionsBox.style.display = 'none';
        }
    });
});


// cầu thủ tìm kiếm


document.addEventListener('DOMContentLoaded', function () {
    // Tìm kiếm theo tên cầu thủ
    const playerSearchInput = document.getElementById('player-search-input');
    const playerSuggestions = document.getElementById('player-suggestions');
    const teamSelect = document.getElementById('team-select');


    // Kiểm tra phần tử HTML
    if (!playerSearchInput || !playerSuggestions || !teamSelect) {
        console.error('Một hoặc nhiều phần tử HTML không tồn tại.');
        return;
    }


    // Gợi ý tên cầu thủ
    playerSearchInput.addEventListener('input', function () {
        const query = playerSearchInput.value.trim();
        if (query.length >= 3) {
            fetch(`../controller/playerController.php?action=suggestPlayer&query=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Yêu cầu thất bại: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    playerSuggestions.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(player => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = player;
                            div.onclick = () => {
                                playerSearchInput.value = player;
                                playerSuggestions.innerHTML = '';
                                searchByPlayerName();
                            };
                            playerSuggestions.appendChild(div);
                        });
                    }
                })
                .catch(error => console.error('Lỗi khi lấy gợi ý cầu thủ:', error));
        } else {
            playerSuggestions.innerHTML = '';
        }
    });


    // Hàm tìm kiếm theo tên cầu thủ
    window.searchByPlayerName = function () {
        let query = playerSearchInput.value.trim();
        if (query) {
            window.location.href = 'players.php?q=' + encodeURIComponent(query);
        }
    };


    // Hàm tìm kiếm theo CLB (dùng team_id từ select)
    window.searchByTeamId = function () {
        let teamId = teamSelect.value;
        if (teamId && teamId !== '0') {
            window.location.href = 'players.php?team_id=' + encodeURIComponent(teamId);
        }
    };
});
