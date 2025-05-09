SET SQL_SAFE_UPDATES = 0;

-- جدول مهارت‌ها
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    percentage INT NOT NULL,
    category ENUM('frontend', 'backend', 'database', 'other') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول پروژه‌ها
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    link VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول نمونه‌کارها
CREATE TABLE IF NOT EXISTS portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول پیام‌ها
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول کاربران
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('owner', 'admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    upload_limit BIGINT DEFAULT 5368709120, -- 5GB in bytes
    used_space BIGINT DEFAULT 0
);

-- جدول فایل‌ها
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    category VARCHAR(255),
    description TEXT,
    uploader VARCHAR(255),
    download_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول اشتراک‌گذاری فایل‌ها
CREATE TABLE IF NOT EXISTS `file_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `shared_by` varchar(255) NOT NULL,
  `shared_with` varchar(255) NOT NULL,
  `shared_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `shared_with` (`shared_with`),
  CONSTRAINT `file_shares_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد کاربر ادمین پیش‌فرض (رمز عبور: admin123)
-- نام کاربری: admin
-- رمز عبور: admin123
INSERT INTO users (username, email, password, full_name, role, status) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مالک سایت', 'owner', 'active');

-- ایجاد ایندکس‌های اضافی برای بهبود کارایی
ALTER TABLE users ADD INDEX idx_role (role);
ALTER TABLE users ADD INDEX idx_status (status);
ALTER TABLE users ADD INDEX idx_created_at (created_at);

ALTER TABLE files ADD INDEX idx_category (category);
ALTER TABLE files ADD INDEX idx_uploader (uploader);
ALTER TABLE files ADD INDEX idx_created_at (created_at);
ALTER TABLE files ADD INDEX idx_file_type (file_type);

-- ایجاد دایرکتوری آپلود
-- این دستور را باید به صورت دستی اجرا کنید:
-- mkdir -p ../uploads && chmod 755 ../uploads

-- توضیحات:
-- 1. جدول users برای مدیریت کاربران سیستم
-- 2. جدول files برای مدیریت فایل‌های آپلود شده
-- 3. یک کاربر ادمین پیش‌فرض با نام کاربری admin و رمز عبور admin123
-- 4. کالیشن utf8mb4_persian_ci برای پشتیبانی کامل از زبان فارسی
-- 5. فیلدهای created_at و updated_at برای ثبت زمان ایجاد و بروزرسانی رکوردها
-- 6. ایندکس‌های مناسب برای جستجوی سریع‌تر
-- 7. محدودیت‌های یکتایی برای نام کاربری و ایمیل

-- ایجاد ایندکس‌های اضافی برای بهبود کارایی

-- آپدیت کاربر ادمین موجود به نقش مالک
UPDATE users SET role = 'owner' WHERE id IN (SELECT id FROM (SELECT id FROM users WHERE role = 'super_admin' OR role = 'admin' LIMIT 1) AS temp);

-- بروزرسانی کاربران موجود
UPDATE users SET upload_limit = 5368709120 WHERE id IN (SELECT id FROM (SELECT id FROM users WHERE upload_limit IS NULL) AS temp);
UPDATE users SET used_space = 0 WHERE id IN (SELECT id FROM (SELECT id FROM users WHERE used_space IS NULL) AS temp);

-- در انتها، فعال کردن مجدد حالت Safe Update
SET SQL_SAFE_UPDATES = 1;
