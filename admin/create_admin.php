<?php
require_once '../config/database.php';

try {
    // رمز عبور جدید: admin123
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // حذف کاربر admin قبلی
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    // ایجاد کاربر admin جدید
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'admin',
        'admin@example.com',
        $password,
        'مدیر سایت',
        'owner',
        'active'
    ]);
    
    echo "کاربر admin با موفقیت ایجاد شد. حالا می‌توانید با نام کاربری admin و رمز عبور admin123 وارد شوید.";
    
} catch (PDOException $e) {
    echo "خطا در ایجاد کاربر: " . $e->getMessage();
}
?> 