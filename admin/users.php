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

// دریافت لیست کاربران
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// تعریف نقش‌های کاربری
$roles = [
    'owner' => 'مالک سایت',
    'admin' => 'مدیر سایت',
    'user' => 'کاربر عادی'
];

// تعریف وضعیت‌های کاربر
$statuses = [
    'active' => 'فعال',
    'inactive' => 'غیرفعال'
];

// دریافت اطلاعات کاربر فعلی
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$current_user = $stmt->fetch();

// بررسی دسترسی ادمین یا مالک
$is_admin = $current_user['role'] === 'admin' || $current_user['role'] === 'owner';

// اگر کاربر ادمین یا مالک نیست، به داشبورد برگردان
if (!$is_admin) {
    header('Location: index.php');
    exit;
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                // بررسی تکراری نبودن نام کاربری و ایمیل
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$_POST['username'], $_POST['email']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('نام کاربری یا ایمیل تکراری است.');
                }

                // رمزنگاری پسورد
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // تبدیل گیگابایت به بایت
                $upload_limit = floatval($_POST['upload_limit']) * 1024 * 1024 * 1024;
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, status, upload_limit) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['username'],
                    $_POST['email'],
                    $hashed_password,
                    $_POST['full_name'],
                    $_POST['role'],
                    $_POST['status'],
                    $upload_limit
                ]);
                $success = 'کاربر جدید با موفقیت اضافه شد.';
            } elseif ($_POST['action'] == 'edit') {
                $update_fields = [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'full_name' => $_POST['full_name'],
                    'role' => $_POST['role'],
                    'status' => $_POST['status'],
                    'upload_limit' => floatval($_POST['upload_limit']) * 1024 * 1024 * 1024
                ];
                
                // اگر پسورد جدید وارد شده باشد
                if (!empty($_POST['password'])) {
                    $update_fields['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                // ساخت کوئری آپدیت
                $sql_parts = [];
                $params = [];
                foreach ($update_fields as $key => $value) {
                    $sql_parts[] = "$key = ?";
                    $params[] = $value;
                }
                $params[] = $_POST['id']; // برای WHERE
                
                $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?");
                $stmt->execute($params);
                $success = 'اطلاعات کاربر با موفقیت بروزرسانی شد.';
            } elseif ($_POST['action'] == 'delete') {
                // بررسی نقش کاربر برای حذف
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $user_role = $stmt->fetchColumn();
                
                if ($user_role == 'owner') {
                    throw new Exception('امکان حذف مالک سایت وجود ندارد.');
                }
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'کاربر با موفقیت حذف شد.';
            }
            
            // ریدایرکت به همان صفحه
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        $error = 'خطا در عملیات: ' . $e->getMessage();
        error_log("Database Error: " . $e->getMessage());
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// دریافت کاربر برای ویرایش
$editing_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            background: white;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            padding: 2rem;
        }

        .admin-main {
            flex: 1;
            padding: 0;
            width: 100%;
            max-width: 100%;
        }

        .table-container {
            overflow-x: auto;
            margin: 2rem 0;
            padding: 0;
            border-radius: 8px;
            width: 100%;
            background: white;
        }

        .admin-table {
            width: 100%;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            background: white;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
            white-space: nowrap;
            padding: 1rem;
            text-align: right;
        }

        .admin-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .admin-table th:first-child,
        .admin-table td:first-child {
            padding-right: 1.5rem;
        }

        .admin-table th:last-child,
        .admin-table td:last-child {
            padding-left: 1.5rem;
        }
        
        .admin-table tr:hover {
            background-color: #f8f9fa;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        
        .user-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .user-title i {
            color: var(--primary-color);
        }
        
        .user-username {
            font-size: 0.85rem;
            color: #666;
            margin-right: 1.5rem;
        }
        
        .user-email {
            font-size: 0.85rem;
            color: #666;
            margin-right: 1.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            min-width: 60px;
        }
        
        .badge-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        
        .badge-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }
        
        .badge-secondary {
            background-color: rgba(149, 165, 166, 0.1);
            color: #7f8c8d;
        }
        
        .text-muted {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-start;
        }
        
        .edit-btn,
        .delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .edit-btn {
            color: #3498db;
            background-color: rgba(52, 152, 219, 0.1);
        }

        .delete-btn {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .edit-btn:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }

        .delete-btn:hover {
            background-color: rgba(231, 76, 60, 0.2);
        }

        @media (max-width: 768px) {
            .admin-table th,
            .admin-table td {
                padding: 0.75rem;
            }
            
            .user-email {
                display: none;
            }

            .action-text {
                display: none;
            }

            .edit-btn,
            .delete-btn {
                width: 32px;
                height: 32px;
                padding: 0;
                justify-content: center;
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
                <h1>مدیریت کاربران</h1>
                <button class="primary-btn" onclick="showAddForm()">
                    <i class="fas fa-plus"></i>
                    افزودن کاربر جدید
                </button>
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

            <!-- فرم افزودن/ویرایش کاربر -->
            <div class="admin-form-container" id="userForm" style="display: <?php echo $editing_user ? 'block' : 'none'; ?>">
                <form class="admin-form" method="POST">
                    <input type="hidden" name="action" value="<?php echo $editing_user ? 'edit' : 'add'; ?>">
                    <?php if ($editing_user): ?>
                        <input type="hidden" name="id" value="<?php echo $editing_user['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="username">نام کاربری:</label>
                        <input type="text" id="username" name="username" value="<?php echo $editing_user ? htmlspecialchars($editing_user['username']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">ایمیل:</label>
                        <input type="email" id="email" name="email" value="<?php echo $editing_user ? htmlspecialchars($editing_user['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">رمز عبور<?php echo $editing_user ? ' (در صورت تغییر)' : ''; ?>:</label>
                        <input type="password" id="password" name="password" <?php echo $editing_user ? '' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label for="full_name">نام کامل:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $editing_user ? htmlspecialchars($editing_user['full_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="role">نقش کاربری:</label>
                        <select id="role" name="role" required>
                            <?php foreach ($roles as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($editing_user && $editing_user['role'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">وضعیت:</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($editing_user && $editing_user['status'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="upload_limit">محدودیت آپلود (گیگابایت):</label>
                        <input type="number" id="upload_limit" name="upload_limit" step="0.1" min="0" value="<?php echo $editing_user ? round($editing_user['upload_limit'] / (1024 * 1024 * 1024), 2) : 5; ?>" required>
                        <small class="text-muted">مقدار پیش‌فرض: 5 گیگابایت</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-save"></i>
                            <?php echo $editing_user ? 'بروزرسانی' : 'افزودن'; ?>
                        </button>
                        <button type="button" class="secondary-btn" onclick="hideAddForm()">
                            <i class="fas fa-times"></i>
                            انصراف
                        </button>
                    </div>
                </form>
            </div>

            <!-- جدول کاربران -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>کاربر</th>
                            <th>نقش</th>
                            <th>وضعیت</th>
                            <th>تاریخ عضویت</th>
                            <th>آخرین ورود</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">هیچ کاربری وجود ندارد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-title">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($u['full_name']); ?>
                                            </div>
                                            <div class="user-username">
                                                <?php echo htmlspecialchars($u['username']); ?>
                                            </div>
                                            <div class="user-email">
                                                <?php echo htmlspecialchars($u['email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo $roles[$u['role']]; ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $u['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $statuses[$u['status']]; ?>
                                        </span>
                                    </td>
                                    <td><span class="text-muted"><?php echo date('Y/m/d', strtotime($u['created_at'])); ?></span></td>
                                    <td><span class="text-muted"><?php echo $u['last_login'] ? date('Y/m/d H:i', strtotime($u['last_login'])) : '-'; ?></span></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="?edit=<?php echo $u['id']; ?>" class="edit-btn" title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                                <span class="action-text">ویرایش</span>
                                            </a>
                                            <?php if ($u['role'] != 'owner' && $u['id'] != $_SESSION['admin_id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="delete-btn" onclick="return confirm('آیا از حذف این کاربر اطمینان دارید؟')" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="action-text">حذف</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function showAddForm() {
            document.getElementById('userForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('userForm').style.display = 'none';
        }
    </script>
</body>
</html> 