<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $subject, $message]);
        
        // پیام موفقیت
        header('Location: index.php?message=success#contact');
        exit;
    } catch (PDOException $e) {
        // پیام خطا
        header('Location: index.php?message=error#contact');
        exit;
    }
}

// اگر درخواست POST نبود، برگشت به صفحه اصلی
header('Location: index.php#contact');
exit;
?> 