document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-btn').forEach(button => {
        button.addEventListener('click', function() {
            const newsId = this.getAttribute('data-news-id');
            
            // Chỉ gửi yêu cầu khi mở nội dung
            if (this.textContent === 'Xem thêm') {
                updateViewCount(newsId);
            }
        });
    });
});

function updateViewCount(newsId) {
    console.log('Attempting to update view for news ID:', newsId); // Debug log
    
    fetch('../api/updateView.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `news_id=${newsId}`
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug log
        return response.json();
    })
    .then(data => {
        console.log('API response:', data); // Debug log
        if (data.success) {
            const viewElement = document.querySelector(`.views-count[data-news-id="${newsId}"]`);
            if (viewElement) {
                const currentViews = parseInt(viewElement.textContent);
                viewElement.textContent = currentViews + 1;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}