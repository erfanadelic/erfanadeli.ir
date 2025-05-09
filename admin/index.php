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
    // تعداد کاربران
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $users_count = $stmt->fetchColumn();
    
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
    
    // کاربران جدید
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}

// دریافت اطلاعات کاربر
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();

// محاسبه درصد استفاده از فضای ذخیره‌سازی
$used_space = $user['used_space'];
$upload_limit = $user['upload_limit'];
$usage_percent = ($upload_limit > 0) ? ($used_space / $upload_limit) * 100 : 0;

// تابع تبدیل حجم فایل
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
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
    <style>
        .storage-chart-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .storage-chart {
            flex-shrink: 0;
            width: 180px;
            height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .circular-chart {
            display: block;
            margin: 0 auto;
            width: 160px;
            height: 160px;
        }

        .circle-bg {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 3;
        }

        .circle {
            fill: none;
            stroke: var(--primary-color);
            stroke-width: 3;
            stroke-linecap: round;
            animation: progress 1s ease-out forwards;
        }

        .percentage {
            fill: var(--dark-color);
            font-size: 0.5em;
            text-anchor: middle;
            dominant-baseline: middle;
            font-weight: 600;
        }

        .storage-usage {
            font-size: 1rem;
            color: #666;
            text-align: center;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .storage-details {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .storage-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            width: 100%;
            margin-top: 1rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .storage-warning {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            padding: 0.75rem;
            border-radius: 4px;
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            justify-content: center;
            width: 100%;
            max-width: 300px;
        }

        .storage-warning i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .storage-header {
                flex-direction: column;
                gap: 1rem;
            }

            .storage-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .storage-chart {
                width: 150px;
                height: 150px;
            }

            .circular-chart {
                width: 130px;
                height: 130px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>داشبورد</h1>
            </div>

            <!-- آمار -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo isset($users_count) ? $users_count : 0; ?></div>
                    <div class="stat-label">کاربران</div>
                </div>
                
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

            <!-- نمایش وضعیت فضای ذخیره‌سازی -->
            <div class="storage-info">
                <div class="storage-header">
                    <h3>
                        <i class="fas fa-hdd"></i>
                        وضعیت فضای ذخیره‌سازی
                    </h3>
                    <div class="storage-stats">
                        <div class="stat-item">
                            <span class="stat-label">فضای کل:</span>
                            <span class="stat-value"><?php echo formatFileSize($upload_limit); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">فضای استفاده شده:</span>
                            <span class="stat-value"><?php echo formatFileSize($used_space); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">فضای خالی:</span>
                            <span class="stat-value"><?php echo formatFileSize($upload_limit - $used_space); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="storage-chart-container">
                    <div class="storage-chart">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg"
                                d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                            <path class="circle"
                                stroke-dasharray="<?php echo $usage_percent; ?>, 100"
                                d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                            <text x="18" y="18" class="percentage" style="font-size: 0.35em;"><?php echo round($usage_percent, 1); ?>%</text>
                        </svg>
                        <div class="storage-usage">
                            <?php echo formatFileSize($used_space); ?> از <?php echo formatFileSize($upload_limit); ?>
                        </div>
                    </div>
                    <div class="storage-details">
                        <?php if ($usage_percent > 90): ?>
                            <div class="storage-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                هشدار: فضای ذخیره‌سازی شما رو به اتمام است!
                            </div>
                        <?php elseif ($usage_percent > 75): ?>
                            <div class="storage-warning" style="color: #f39c12;">
                                <i class="fas fa-exclamation-circle"></i>
                                توجه: فضای ذخیره‌سازی شما در حال پر شدن است.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
            <!-- پیام‌های اخیر -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>پیام‌های اخیر</h3>
                        <a href="messages.php" class="view-all">مشاهده همه</a>
            </div>
                    <div class="card-content">
                        <?php if (empty($recent_messages)): ?>
                            <p class="no-data">هیچ پیامی وجود ندارد</p>
                        <?php else: ?>
                            <div class="messages-list">
                    <?php foreach ($recent_messages as $message): ?>
                                    <div class="message-item">
                                        <div class="message-header">
                                            <span class="sender-name"><?php echo htmlspecialchars($message['name']); ?></span>
                                            <span class="message-date"><?php echo date('Y/m/d H:i', strtotime($message['created_at'])); ?></span>
                                        </div>
                                        <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                                </div>
                            </div>

                <!-- کاربران جدید -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>کاربران جدید</h3>
                        <a href="users.php" class="view-all">مشاهده همه</a>
                    </div>
                    <div class="card-content">
                        <?php if (empty($recent_users)): ?>
                            <p class="no-data">هیچ کاربری وجود ندارد</p>
                        <?php else: ?>
                            <div class="users-list">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="user-item">
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                        <div class="user-meta">
                                            <span class="user-date"><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></span>
                                            <span class="user-status <?php echo $user['status']; ?>"><?php echo $user['status'] == 'active' ? 'فعال' : 'غیرفعال'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                            </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 