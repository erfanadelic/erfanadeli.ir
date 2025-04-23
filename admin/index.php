<?php
session_start();
require_once '../config/database.php';

// بررسی احراز هویت
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// دریافت آمار
try {
    // تعداد مهارت‌ها
    $stmt = $pdo->query("SELECT COUNT(*) FROM skills");
    $skills_count = $stmt->fetchColumn();
    
    // تعداد پروژه‌ها
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $projects_count = $stmt->fetchColumn();
    
    // تعداد نمونه‌کارها
    $stmt = $pdo->query("SELECT COUNT(*) FROM portfolio");
    $portfolio_count = $stmt->fetchColumn();
    
    // تعداد پیام‌ها
    $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
    $messages_count = $stmt->fetchColumn();
    
    // پیام‌های جدید
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5");
    $recent_messages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>داشبورد</h1>
                <a href="messages.php" class="primary-btn">
                    <i class="fas fa-envelope"></i>
                    مشاهده پیام‌ها
                </a>
            </div>

            <!-- آمار -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div class="stat-value"><?php echo isset($skills_count) ? $skills_count : 0; ?></div>
                    <div class="stat-label">مهارت‌ها</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-value"><?php echo isset($projects_count) ? $projects_count : 0; ?></div>
                    <div class="stat-label">پروژه‌ها</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-value"><?php echo isset($portfolio_count) ? $portfolio_count : 0; ?></div>
                    <div class="stat-label">نمونه‌کارها</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-value"><?php echo isset($messages_count) ? $messages_count : 0; ?></div>
                    <div class="stat-label">پیام‌ها</div>
                </div>
            </div>

            <!-- پیام‌های اخیر -->
            <div class="admin-header">
                <h2>پیام‌های اخیر</h2>
                <a href="messages.php" class="secondary-btn">
                    <i class="fas fa-external-link-alt"></i>
                    مشاهده همه
                </a>
            </div>
            
            <div class="admin-grid">
                <?php if (isset($recent_messages) && !empty($recent_messages)): ?>
                    <?php foreach ($recent_messages as $message): ?>
                        <div class="admin-card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($message['name']); ?></h3>
                                <span><?php echo date('Y/m/d', strtotime($message['created_at'])); ?></span>
                            </div>
                            <div class="card-content">
                                <p><strong>ایمیل:</strong> <?php echo htmlspecialchars($message['email']); ?></p>
                                <p><strong>موضوع:</strong> <?php echo htmlspecialchars($message['subject']); ?></p>
                                <div class="message-text">
                                    <?php echo htmlspecialchars(substr($message['message'], 0, 150)) . (strlen($message['message']) > 150 ? '...' : ''); ?>
                                </div>
                            </div>
                            <div class="card-actions">
                                <a href="messages.php?view=<?php echo $message['id']; ?>" class="primary-btn">
                                    <i class="fas fa-eye"></i>
                                    مشاهده
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>هیچ پیامی وجود ندارد.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 