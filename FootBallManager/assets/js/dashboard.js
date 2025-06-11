document.addEventListener('DOMContentLoaded', () => {
    // Xử lý tabs
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });

    // Xử lý biểu đồ
    const chartButtons = document.querySelectorAll('.chart-btn');
    const charts = document.querySelectorAll('.chart');
    chartButtons.forEach(button => {
        button.addEventListener('click', () => {
            chartButtons.forEach(btn => btn.classList.remove('active'));
            charts.forEach(chart => chart.style.display = 'none');
            button.classList.add('active');
            document.getElementById(button.dataset.chart + 'Chart').style.display = 'block';
        });
    });

    // Biểu đồ hiệu suất tấn công
    const attackCtx = document.getElementById('attackChart').getContext('2d');
    new Chart(attackCtx, {
    type: 'line',
    data: {
        labels: ['0-15', '15-30', '30-45', '45-60', '60-75', '75-90+'],
        datasets: [
            { label: 'Tổng bàn thắng', data: attackPerformance.total_goals, borderColor: '#FF5733', backgroundColor: 'rgba(255, 87, 51, 0.2)', fill: true },
            { label: 'Tỷ lệ penalty (%)', data: attackPerformance.penalty_percent, borderColor: '#3357FF', backgroundColor: 'rgba(51, 87, 255, 0.2)', fill: true }
        ],
        options: {
            scales: { 
                y: { 
                    beginAtZero: true, 
                    title: { 
                        display: true, 
                        text: 'Số liệu', 
                        color: '#FFFFFF',
                        font: { 
                            style: 'normal',
                            weight: 'bold',
                            size: 16
                        },
                        padding: 20
                    },
                    ticks: {
                        color: '#FFFFFF',
                        font: { 
                            style: 'normal',
                            weight: 'bold'
                        },
                        padding: 10
                    }
                }, 
                x: { 
                    title: { 
                        display: true, 
                        text: 'Phút', 
                        color: '#FFFFFF',
                        font: { 
                            style: 'normal',
                            weight: 'bold',
                            size: 16
                        },
                        padding: 20
                    },
                    ticks: {
                        color: '#FFFFFF',
                        font: { 
                            style: 'normal',
                            weight: 'bold'
                        }
                    }
                } 
            },
            plugins: { 
                legend: { 
                    labels: { 
                        color: '#FFFFFF', // Đảm bảo màu trắng cho "Tổng bàn thắng" và "Tỷ lệ penalty (%)"
                        font: { 
                            style: 'normal',
                            weight: 'bold',
                            size: 14 // Tăng kích thước chữ để nổi bật
                        }
                    } 
                } 
            },
            layout: {
                padding: {
                    left: 40, // Đảm bảo đủ không gian cho tiêu đề trục y
                    bottom: 40, // Đảm bảo đủ không gian cho tiêu đề trục x
                    right: 20,
                    top: 20
                }
            }
        }
    }
});
    
    // Biểu đồ hiệu quả chuyền bóng
    const passCtx = document.getElementById('passChart').getContext('2d');
    new Chart(passCtx, {
        type: 'scatter',
        data: { datasets: [{ label: 'Hiệu quả chuyền bóng', data: passEfficiency, backgroundColor: '#4CAF50' }] },
        options: {
            scales: { x: { title: { display: true, text: 'Tổng số đường chuyền', color: '#e0e0e0' } }, y: { title: { display: true, text: 'Tỷ lệ chính xác (%)', color: '#e0e0e0' } } },
            plugins: {
                tooltip: { callbacks: { label: function(context) { return context.raw.name + ': (' + context.raw.x + ', ' + context.raw.y + '%)'; } } },
                legend: { labels: { color: '#e0e0e0' } }
            }
        }
    });
});
