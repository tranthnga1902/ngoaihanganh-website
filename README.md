# Ngoại Hạng Anh - Football Manager Website

Website quản lý và theo dõi giải bóng đá Ngoại Hạng Anh (Premier League).

## Công Nghệ Sử Dụng

| Công nghệ | Mô tả |
|-----------|-------|
| **PHP** | Ngôn ngữ lập trình backend chính |
| **MySQL** | Hệ quản trị cơ sở dữ liệu |
| **HTML/CSS** | Giao diện người dùng |
| **JavaScript/jQuery** | Xử lý tương tác phía client |
| **Laragon** | Môi trường phát triển local (Apache, MySQL, PHP) |

## Cấu Trúc Dự Án

```
├── admin/              # Trang quản trị (dashboard, quản lý dữ liệu)
├── api/                 # API endpoints
├── assets/              # Tài nguyên tĩnh
│   ├── css/            # Stylesheet
│   ├── js/             # JavaScript
│   └── img/            # Hình ảnh
├── Controller/          # Xử lý logic (MVC pattern)
├── DB/                  # File SQL database
├── includes/            # Các file include chung (config, header, footer)
├── user/                # Trang người dùng
│   └── thongke/        # Thống kê
├── uploads/             # File upload
├── index.php            # Trang chủ
└── .gitignore           # Git ignore rules
```

## Tính Năng Chính

### Người dùng
- Xem lịch thi đấu
- Xem bảng xếp hạng
- Xem thông tin cầu thủ, đội bóng
- Xem tin tức
- Thống kê

### Quản trị
- Dashboard quản lý
- Quản lý cầu thủ
- Quản lý đội bóng
- Quản lý trận đấu
- Quản lý tin tức
- Quản lý người dùng

## Cài Đặt

### Yêu cầu
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Laragon (khuyến nghị)

### Các bước cài đặt

1. **Clone repository**
```bash
git clone https://github.com/tranthnga1902/ngoaihanganh-website.git
```

2. **Cấu hình database**
   - Tạo database mới trong MySQL
   - Import file SQL từ thư mục `DB/`

3. **Cấu hình kết nối**
   - Mở file `includes/config.php`
   - Cập nhật thông tin database:
```php
$host = "localhost";
$dbname = "football6";
$username = "root";
$password = "";
```

4. **Chạy project**
   - Nếu dùng Laragon: bật Laragon và truy cập `localhost`
   - Hoặc cấu hình Apache/Nginx trỏ đến thư mục project

## Database

Import database từ file trong thư mục `DB/`:
- `football.sql`
- `football3 (5).sql`
- `sua.sql`

## Phát Triển

MVC Pattern:
- **Model**: Kết nối database trong `includes/config.php`
- **View**: Các file `.php` trong `admin/`, `user/`
- **Controller**: Các file trong `Controller/`

## License

MIT License
