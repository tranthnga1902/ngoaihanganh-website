// Cập nhật ngay khi tải trang và sau đó mỗi giây
document.addEventListener("DOMContentLoaded", function () {
  updateTime();
  setInterval(updateTime, 1000);
});
document.addEventListener("DOMContentLoaded", function () {
  const bellIcon = document.getElementById("bell-icon");
  const notificationDropdown = document.getElementById("notification-dropdown");

  bellIcon.addEventListener("click", function (event) {
    notificationDropdown.classList.toggle("show");
    event.stopPropagation(); // Ngăn sự kiện lan ra ngoài
  });

  // Ẩn dropdown khi nhấn ngoài
  document.addEventListener("click", function (event) {
    if (
      !notificationDropdown.contains(event.target) &&
      event.target !== bellIcon
    ) {
      notificationDropdown.classList.remove("show");
    }
  });
});

// Lấy phần tử nút "Cuộn lên đầu trang"
document.addEventListener("DOMContentLoaded", function () {
  let mybutton = document.getElementById("back-to-top");

  if (!mybutton) {
      console.error("Nút 'back-to-top' không tồn tại!");
      return;
  }

  // Ẩn nút khi ở đầu trang
  mybutton.style.display = "none";

  // Lắng nghe sự kiện cuộn trang
  window.addEventListener("scroll", function () {
      if (window.scrollY > 50) {
          mybutton.style.display = "block"; // Hiện nút khi cuộn xuống
      } else {
          mybutton.style.display = "none"; // Ẩn nút khi ở đầu trang
      }
  });

  // Khi người dùng nhấn vào nút, cuộn lên đầu trang mượt mà
  mybutton.addEventListener("click", function () {
      window.scrollTo({
          top: 0,
          behavior: "smooth"
      });
  });
});

//giữ thanh menu đầu trang
window.addEventListener("scroll", function () {
  var menu1 = document.getElementById("menu1");

  if (window.scrollY > 143) {
      menu1.style.position = "fixed";
      menu1.style.top = "0";
      menu1.style.left = "0";
      menu1.style.width = "100%";
      menu1.style.zIndex = "1000";
      menu1.style.boxShadow = "0 2px 5px rgba(0, 0, 0, 0.3)";
  } else {
      menu1.style.position = "relative";
      menu1.style.boxShadow = "none";
  }
});


// Banner Slider Class
class BannerSlider {
  constructor() {
    this.index = 0;
    this.slides = document.querySelectorAll('.banner-slide');
    this.totalSlides = this.slides.length;
    this.totalRealSlides = this.totalSlides - 1;
    this.wrapper = document.querySelector('.banner-wrapper');
    this.slideInterval = null;
    this.init();
  }

  init() {
    this.showSlide(this.index, false);
    this.startAutoSlide();
    this.setupEventListeners();
  }

  showSlide(i, smooth = true) {
    this.index = i;
    
    if (this.index > this.totalRealSlides) {
      this.index = 0;
    } else if (this.index < 0) {
      this.index = this.totalRealSlides;
    }

    this.wrapper.style.transition = smooth ? 'transform 0.5s ease' : 'none';
    this.wrapper.style.transform = `translateX(-${this.index * 100}%)`;

    if (i === this.totalRealSlides && smooth) {
      setTimeout(() => {
        this.wrapper.style.transition = 'none';
        this.index = 0;
        this.wrapper.style.transform = `translateX(0%)`;
      }, 500);
    }
  }

  nextSlide() {
    this.showSlide(this.index + 1);
  }

  prevSlide() {
    this.showSlide(this.index - 1);
  }

  startAutoSlide() {
    this.slideInterval = setInterval(() => this.nextSlide(), 5000);
  }

  resetTimer() {
    clearInterval(this.slideInterval);
    this.startAutoSlide();
  }

  setupEventListeners() {
    document.querySelector('.prev-btn')?.addEventListener('click', () => {
      this.prevSlide();
      this.resetTimer();
    });

    document.querySelector('.next-btn')?.addEventListener('click', () => {
      this.nextSlide();
      this.resetTimer();
    });

    const bannerContainer = document.querySelector('.banner-container');
    bannerContainer?.addEventListener('mouseenter', () => {
      clearInterval(this.slideInterval);
    });

    bannerContainer?.addEventListener('mouseleave', () => {
      this.startAutoSlide();
    });
  }
}

// Khởi tạo slider khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
  new BannerSlider();
});


//kết quả dưới banner
function initializeCarousel() {
            const carousel = document.querySelector('.carousel');
            if (!carousel) return;

            const track = carousel.querySelector('.carousel-track');
            const prevButton = carousel.querySelector('.carousel-button.prev');
            const backButton = carousel.querySelector('.carousel-button.back');
            const nextButton = carousel.querySelector('.carousel-button.next');
            const cards = track.querySelectorAll('.match-card');

            if (cards.length === 0) {
                prevButton.style.display = 'none';
                backButton.style.display = 'none';
                nextButton.style.display = 'none';
                return;
            }

            const cardWidth = cards[0].offsetWidth + 20; // 20px for margin
            const slideStep = 4; // Di chuyển 4 thẻ mỗi lần
            let currentIndex = 0;
            const totalCards = cards.length;
            const maxIndex = Math.ceil(totalCards / slideStep) - 1;

            function updateCarousel() {
                const offset = -currentIndex * cardWidth * slideStep;
                track.style.transform = `translateX(${offset}px)`;
                prevButton.disabled = currentIndex === 0;
                backButton.disabled = currentIndex === 0;
                nextButton.disabled = currentIndex >= maxIndex;
            }

            prevButton.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });

            backButton.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });

            nextButton.addEventListener('click', () => {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateCarousel();
                }
            });

            window.addEventListener('resize', updateCarousel);
            updateCarousel();
        }

        document.addEventListener('DOMContentLoaded', initializeCarousel);

//video chuyển 
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('[data-carousel]');
    const items = document.querySelectorAll('[data-item]');
    const buttons = document.querySelectorAll('.carousel-button');
    
    if (!carousel || items.length === 0) return;
    
    const itemWidth = items[0].offsetWidth + parseInt(getComputedStyle(items[0]).marginRight);
    let currentPosition = 0;
    const maxPosition = carousel.scrollWidth - carousel.clientWidth;
    
    function updateButtons() {
        document.querySelector('.prev').disabled = currentPosition <= 0;
        document.querySelector('.next').disabled = currentPosition >= maxPosition;
    }
    
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const direction = this.dataset.direction;
            
            if (direction === 'prev') {
                currentPosition = Math.max(0, currentPosition - itemWidth * 3);
            } else {
                currentPosition = Math.min(maxPosition, currentPosition + itemWidth * 3);
            }
            
            carousel.scrollTo({
                left: currentPosition,
                behavior: 'smooth'
            });
            
            setTimeout(updateButtons, 300);
        });
    });
    
    // Initialize button states
    updateButtons();
    
    // Update on resize
    window.addEventListener('resize', function() {
        currentPosition = carousel.scrollLeft;
        updateButtons();
    });
});

function showCustomAlert(message, type = 'success') {
    const alertBox = document.createElement('div');
    alertBox.className = `custom-alert ${type}`;
    alertBox.innerHTML = `
        <div class="alert-content">
            <p>${message}</p>
            <button onclick="this.parentElement.parentElement.remove()">Đóng</button>
        </div>
    `;
    document.body.appendChild(alertBox);
    setTimeout(() => alertBox.remove(), 5000);
}


function openDeleteConfirmModal(newsId) {
    document.getElementById('deleteConfirmModal').style.display = 'block';
    document.getElementById('confirmDeleteButton').onclick = function() {
        confirmDelete(newsId);
    };
}


function closeDeleteConfirmModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
}


function confirmDelete(newsId) {
    fetch('../controller/AnewsController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete_news&news_id=' + newsId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        closeDeleteConfirmModal();
        if (data.success) {
            showCustomAlert(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showCustomAlert(data.message, 'error');
        }
    })
    .catch(error => {
        closeDeleteConfirmModal();
        console.error('Error:', error);
        showCustomAlert('Đã xảy ra lỗi: ' + error.message, 'error');
    });
}


function openAddNewsModal() {
    document.getElementById('modalTitle').textContent = 'Thêm Tin tức';
    document.getElementById('formAction').value = 'add_news';
    document.getElementById('newsId').value = '';
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('author').value = '';
    document.getElementById('imagePreview').src = '../assets/img/default_image.png';
    document.getElementById('newsModal').style.display = 'block';
}


function openEditNewsModal(news) {
    document.getElementById('modalTitle').textContent = 'Sửa Tin tức';
    document.getElementById('formAction').value = 'update_news';
    document.getElementById('newsId').value = news.news_id;
    document.getElementById('title').value = news.title;
    document.getElementById('content').value = news.content;
    document.getElementById('category_id').value = news.category_id;
    document.getElementById('author').value = news.author;
    document.getElementById('imagePreview').src = news.image_url ? `../${news.image_url}` : '../assets/img/default_image.png';
    document.getElementById('newsModal').style.display = 'block';
}


function closeModal() {
    document.getElementById('newsModal').style.display = 'none';
}


document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});



