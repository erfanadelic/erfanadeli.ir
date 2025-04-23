<?php
session_start();
require_once '../config/database.php';

// بررسی احراز هویت
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// دریافت لیست پیام‌ها
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

// دریافت جزئیات پیام
$viewing_message = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$_GET['view']]);
    $viewing_message = $stmt->fetch();
    
    // بروزرسانی وضعیت خوانده شدن
    if ($viewing_message && !$viewing_message['is_read']) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$_GET['view']]);
    }
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'delete') {
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'پیام با موفقیت حذف شد.';
                
                // اگر پیام در حال مشاهده حذف شد، برگشت به لیست
                if (isset($_GET['view']) && $_GET['view'] == $_POST['id']) {
                    header('Location: messages.php');
                    exit;
                }
            } elseif ($_POST['action'] == 'mark_read') {
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'پیام به عنوان خوانده شده علامت‌گذاری شد.';
            } elseif ($_POST['action'] == 'mark_unread') {
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 0 WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'پیام به عنوان خوانده نشده علامت‌گذاری شد.';
            }
            
            if (!isset($_GET['view'])) {
                // ریدایرکت به همان صفحه فقط در صورتی که در حالت نمایش پیام خاصی نباشیم
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    } catch (PDOException $e) {
        $error = 'خطا در عملیات: ' . $e->getMessage();
        error_log("Database Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت پیام‌ها - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>مدیریت پیام‌ها</h1>
                <?php if ($viewing_message): ?>
                    <a href="messages.php" class="secondary-btn">
                        <i class="fas fa-arrow-right"></i>
                        بازگشت به لیست پیام‌ها
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($viewing_message): ?>
                <!-- نمایش جزئیات پیام -->
                <div class="admin-form message-details">
                    <div class="message-header">
                        <h2><?php echo htmlspecialchars($viewing_message['subject']); ?></h2>
                        <span class="message-date"><?php echo date('Y/m/d H:i', strtotime($viewing_message['created_at'])); ?></span>
                    </div>
                    
                    <div class="message-info">
                        <div class="info-row">
                            <strong>فرستنده:</strong>
                            <span><?php echo htmlspecialchars($viewing_message['name']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <strong>ایمیل:</strong>
                            <span>
                                <a href="mailto:<?php echo htmlspecialchars($viewing_message['email']); ?>">
                                    <?php echo htmlspecialchars($viewing_message['email']); ?>
                                </a>
                            </span>
                        </div>
                        
                        <?php if (isset($viewing_message['phone']) && !empty($viewing_message['phone'])): ?>
                        <div class="info-row">
                            <strong>تلفن:</strong>
                            <span><?php echo htmlspecialchars($viewing_message['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="message-content">
                        <p><?php echo nl2br(htmlspecialchars($viewing_message['message'])); ?></p>
                    </div>
                    
                    <div class="message-actions">
                        <a href="mailto:<?php echo htmlspecialchars($viewing_message['email']); ?>" class="primary-btn">
                            <i class="fas fa-reply"></i>
                            پاسخ
                        </a>
                        
                        <?php if ($viewing_message['is_read']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="mark_unread">
                                <input type="hidden" name="id" value="<?php echo $viewing_message['id']; ?>">
                                <button type="submit" class="secondary-btn">
                                    <i class="fas fa-envelope"></i>
                                    علامت‌گذاری به عنوان خوانده نشده
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="id" value="<?php echo $viewing_message['id']; ?>">
                                <button type="submit" class="secondary-btn">
                                    <i class="fas fa-envelope-open"></i>
                                    علامت‌گذاری به عنوان خوانده شده
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $viewing_message['id']; ?>">
                            <button type="submit" class="danger-btn" onclick="return confirm('آیا از حذف این پیام اطمینان دارید؟')">
                                <i class="fas fa-trash"></i>
                                حذف پیام
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- لیست پیام‌ها -->
                <div class="admin-grid">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="admin-card <?php echo $message['is_read'] ? '' : 'unread-message'; ?>">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($message['subject']); ?></h3>
                                    <span><?php echo date('Y/m/d', strtotime($message['created_at'])); ?></span>
                                </div>
                                <div class="card-content">
                                    <div class="message-sender">
                                        <strong>فرستنده:</strong> <?php echo htmlspecialchars($message['name']); ?>
                                    </div>
                                    <div class="message-email">
                                        <strong>ایمیل:</strong> <?php echo htmlspecialchars($message['email']); ?>
                                    </div>
                                    <div class="message-preview">
                                        <?php echo htmlspecialchars(substr($message['message'], 0, 150)) . (strlen($message['message']) > 150 ? '...' : ''); ?>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <a href="?view=<?php echo $message['id']; ?>" class="primary-btn">
                                        <i class="fas fa-eye"></i>
                                        مشاهده
                                    </a>
                                    
                                    <?php if ($message['is_read']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_unread">
                                            <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="secondary-btn">
                                                <i class="fas fa-envelope"></i>
                                                نخوانده
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="secondary-btn">
                                                <i class="fas fa-envelope-open"></i>
                                                خوانده شده
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" class="danger-btn" onclick="return confirm('آیا از حذف این پیام اطمینان دارید؟')">
                                            <i class="fas fa-trash"></i>
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>هیچ پیامی وجود ندارد.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 