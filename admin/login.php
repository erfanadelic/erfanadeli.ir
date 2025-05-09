                                                <?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $error = '';

    try {
        // اول فقط کاربر را پیدا می‌کنیم
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'کاربری با این نام کاربری یافت نشد';
        } 
        // بررسی وضعیت کاربر
        elseif ($user['status'] !== 'active') {
            $error = 'حساب کاربری شما غیرفعال است';
        }
        // بررسی رمز عبور
        elseif (!password_verify($password, $user['password'])) {
            $error = 'رمز عبور اشتباه است';
        }
        // اگر همه چیز درست بود
        else {
            // بروزرسانی آخرین ورود
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_fullname'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            
            header('Location: index.php');
            exit;
        }
    } catch (PDOException $e) {
        // خطای دیتابیس
        $error = 'خطا در ارتباط با پایگاه داده';
        error_log("Database Error in login.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h1>ورود به پنل مدیریت</h1>
            </div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST">
                <div class="form-group">
                    <label for="username">نام کاربری:</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">رمز عبور:</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>

                <button type="submit" class="btn primary-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    ورود به پنل
                </button>
            </form>
        </div>
    </div>
</body>
</html> 