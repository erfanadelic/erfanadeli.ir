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

// دریافت اطلاعات کاربر
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'خطا در دریافت اطلاعات کاربر';
    error_log("Database Error in edit_profile.php: " . $e->getMessage());
}

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

// پردازش فرم ویرایش
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $update_fields = [
            'email' => $_POST['email'],
            'full_name' => $_POST['full_name']
        ];
        
        // اگر رمز عبور جدید وارد شده باشد
        if (!empty($_POST['new_password'])) {
            // بررسی رمز عبور فعلی
            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception('رمز عبور فعلی اشتباه است');
            }
            
            // بررسی تطابق رمز عبور جدید با تکرار آن
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception('رمز عبور جدید با تکرار آن مطابقت ندارد');
            }
            
            $update_fields['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }
        
        // ساخت کوئری آپدیت
        $sql_parts = [];
        $params = [];
        foreach ($update_fields as $key => $value) {
            $sql_parts[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $_SESSION['admin_id']; // برای WHERE
        
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?");
        $stmt->execute($params);
        
        // بروزرسانی اطلاعات نشست
        $_SESSION['admin_fullname'] = $_POST['full_name'];
        
        $success = 'اطلاعات شما با موفقیت بروزرسانی شد';
        
        // دریافت مجدد اطلاعات کاربر
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = 'خطا در بروزرسانی اطلاعات';
        error_log("Database Error in edit_profile.php: " . $e->getMessage());
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش پروفایل - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            padding: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .profile-header i {
            font-size: 2rem;
            color: var(--primary-color);
        }

        .profile-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark-color);
        }

        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .form-section-title {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section-title i {
            color: var(--primary-color);
        }

        .disabled-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
            color: #666;
        }

        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                padding: 1rem;
            }
        }

        .storage-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            border: 1px solid #e9ecef;
        }

        .storage-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .storage-stats {
            display: flex;
            gap: 2rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .storage-chart-container {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .storage-chart {
            flex-shrink: 0;
            width: 200px;
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .circular-chart {
            display: block;
            margin: 0 auto;
            max-width: 100%;
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
            font-size: 0.45em;
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

        @keyframes progress {
            0% {
                stroke-dasharray: 0 100;
            }
        }

        .storage-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .storage-text {
            font-size: 1.1rem;
            color: #495057;
            margin-bottom: 1rem;
            text-align: center;
        }

        .storage-text span {
            color: var(--primary-color);
            font-weight: 600;
        }

        .storage-warning {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
                width: 100%;
                justify-content: space-between;
                gap: 1rem;
            }

            .storage-chart-container {
                flex-direction: column;
                text-align: center;
            }

            .storage-chart {
                margin-bottom: 1rem;
            }

            .stat-item {
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="profile-container">
                <div class="profile-header">
                    <i class="fas fa-user-edit"></i>
                    <h1>ویرایش پروفایل</h1>
                </div>

                <?php if ($success): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="admin-form">
                    <!-- اطلاعات اصلی -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            اطلاعات اصلی
                        </h2>
                        
                        <div class="form-group">
                            <label for="username">نام کاربری:</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="disabled-field" disabled>
                            <small class="text-muted">نام کاربری قابل تغییر نیست</small>
                        </div>

                        <div class="form-group">
                            <label for="email">ایمیل:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="full_name">نام کامل:</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>محدودیت فضای آپلود:</label>
                            <?php
                                $used_space = $user['used_space'];
                                $upload_limit = $user['upload_limit'];
                                $usage_percent = ($upload_limit > 0) ? ($used_space / $upload_limit) * 100 : 0;
                            ?>
                            <div class="storage-info">
                                <div class="storage-header">
                                    <div class="storage-stats">
                                        <div class="stat-item">
                                            <span class="stat-label">فضای کل:</span>
                                            <span class="stat-value"><?php echo formatFileSize($user['upload_limit']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">فضای استفاده شده:</span>
                                            <span class="stat-value"><?php echo formatFileSize($user['used_space']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">فضای خالی:</span>
                                            <span class="stat-value"><?php echo formatFileSize($user['upload_limit'] - $user['used_space']); ?></span>
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
                                            <?php echo formatFileSize($user['used_space']); ?> از <?php echo formatFileSize($user['upload_limit']); ?>
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
                        </div>
                    </div>

                    <!-- تغییر رمز عبور -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-key"></i>
                            تغییر رمز عبور
                        </h2>
                        
                        <div class="form-group">
                            <label for="current_password">رمز عبور فعلی:</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">رمز عبور جدید:</label>
                            <input type="password" id="new_password" name="new_password">
                            <small class="password-requirements">رمز عبور باید حداقل 8 کاراکتر باشد</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">تکرار رمز عبور جدید:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-save"></i>
                            ذخیره تغییرات
                        </button>
                        <a href="index.php" class="secondary-btn">
                            <i class="fas fa-times"></i>
                            انصراف
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // فرم تغییر رمز عبور
        const form = document.querySelector('form');
        const currentPassword = document.getElementById('current_password');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        form.addEventListener('submit', function(e) {
            // اگر هر کدام از فیلدهای رمز عبور پر شده باشد
            if (currentPassword.value || newPassword.value || confirmPassword.value) {
                // بررسی پر بودن همه فیلدها
                if (!currentPassword.value || !newPassword.value || !confirmPassword.value) {
                    e.preventDefault();
                    alert('لطفاً همه فیلدهای مربوط به تغییر رمز عبور را پر کنید');
                    return;
                }

                // بررسی طول رمز عبور جدید
                if (newPassword.value.length < 8) {
                    e.preventDefault();
                    alert('رمز عبور جدید باید حداقل 8 کاراکتر باشد');
                    return;
                }

                // بررسی تطابق رمز عبور جدید با تکرار آن
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('رمز عبور جدید با تکرار آن مطابقت ندارد');
                    return;
                }
            }
        });
    });
    </script>
</body>
</html> 