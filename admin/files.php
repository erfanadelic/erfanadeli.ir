<?php
session_start();
require_once '../config/database.php';

// بررسی احراز هویت
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// دریافت اطلاعات کاربر و محدودیت آپلود
$stmt = $pdo->prepare("SELECT username, full_name, upload_limit, used_space FROM users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();
$username = isset($user['username']) ? $user['username'] : '';
$fullname = isset($user['full_name']) ? $user['full_name'] : '';
$uploader_name = $username;

$success = '';
$error = '';

// ایجاد دایرکتوری آپلود اگر وجود نداشته باشد
$upload_dir = '../uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
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

// محاسبه فضای ذخیره‌سازی
$stmt = $pdo->prepare("SELECT SUM(file_size) as total_size FROM files WHERE uploader = ? OR uploader = ?");
$stmt->execute([$username, $fullname]);
$user_space = $stmt->fetch();
$used_space = $user_space['total_size'] ?? 0;

// محاسبه حجم فایل‌های به اشتراک گذاشته شده
$stmt = $pdo->prepare("
    SELECT SUM(f.file_size) as shared_size 
    FROM files f 
    JOIN file_shares fs ON f.id = fs.file_id 
    WHERE fs.shared_with = ?
");
$stmt->execute([$uploader_name]);
$shared_space = $stmt->fetch();
$total_shared = $shared_space['shared_size'] ?? 0;

$upload_limit = $user['upload_limit'];
$used_space_gb = round($used_space / (1024 * 1024 * 1024), 2);
$upload_limit_gb = round($upload_limit / (1024 * 1024 * 1024), 2);
$usage_percent = ($upload_limit > 0) ? ($used_space / $upload_limit) * 100 : 0;

// پردازش آپلود فایل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        $file = $_FILES['file'];
        $title = $_POST['title'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        
        // بررسی خطاهای آپلود
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'حجم فایل از حد مجاز سرور بیشتر است.',
                UPLOAD_ERR_FORM_SIZE => 'حجم فایل از حد مجاز فرم بیشتر است.',
                UPLOAD_ERR_PARTIAL => 'فایل به طور کامل آپلود نشده است.',
                UPLOAD_ERR_NO_FILE => 'هیچ فایلی انتخاب نشده است.',
                UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت سرور وجود ندارد.',
                UPLOAD_ERR_CANT_WRITE => 'خطا در نوشتن فایل روی سرور.',
                UPLOAD_ERR_EXTENSION => 'آپلود فایل متوقف شد.'
            ];
            throw new Exception('خطا در آپلود فایل: ' . ($error_messages[$file['error']] ?? 'خطای ناشناخته'));
        }
        
        // بررسی محدودیت فضای آپلود
        if ($user['used_space'] + $file['size'] > $user['upload_limit']) {
            throw new Exception('فضای ذخیره‌سازی شما پر شده است. لطفاً ابتدا برخی فایل‌ها را حذف کنید.');
        }
        
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $original_filename = $file['name'];
        $new_filename = uniqid() . '.' . $file_type;
        $upload_path = $upload_dir . '/' . $new_filename;
        
        // بررسی و آپلود فایل
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // ذخیره اطلاعات فایل در دیتابیس
            $stmt = $pdo->prepare("INSERT INTO files (title, filename, original_name, file_type, file_size, category, description, uploader) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $new_filename,
                $original_filename,
                $file_type,
                $file['size'],
                $category,
                $description,
                $uploader_name
            ]);
            
            // بروزرسانی فضای استفاده شده کاربر
            $stmt = $pdo->prepare("UPDATE users SET used_space = used_space + ? WHERE id = ?");
            $stmt->execute([$file['size'], $_SESSION['admin_id']]);
            
            $success = 'فایل با موفقیت آپلود شد.';
        } else {
            throw new Exception('خطا در آپلود فایل.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// حذف فایل
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    try {
        $file_id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT filename, file_size FROM files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();
        if ($file) {
            $file_path = $upload_dir . '/' . $file['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            // حذف همه اشتراک‌های این فایل
            $stmt = $pdo->prepare("DELETE FROM file_shares WHERE file_id = ?");
            $stmt->execute([$file_id]);
            // کاهش فضای استفاده شده
            $stmt = $pdo->prepare("UPDATE users SET used_space = used_space - ? WHERE id = ?");
            $stmt->execute([$file['file_size'], $_SESSION['admin_id']]);
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$file_id]);
            $_SESSION['success'] = "فایل با موفقیت حذف شد.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'خطا در حذف فایل: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// پردازش دانلود فایل
if (isset($_GET['download'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$_GET['download']]);
        $file = $stmt->fetch();
        
        if ($file) {
            $file_path = $upload_dir . '/' . $file['filename'];
            if (file_exists($file_path)) {
                // تنظیم هدرهای دانلود
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
                header('Content-Length: ' . filesize($file_path));
                header('Cache-Control: no-cache');
                header('Pragma: no-cache');
                
                // ارسال فایل
                readfile($file_path);
                exit;
            }
        }
        throw new Exception('فایل مورد نظر یافت نشد.');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// پردازش اشتراک‌گذاری فایل
if (isset($_POST['action']) && $_POST['action'] == 'share') {
    try {
        $file_id = $_POST['file_id'];
        $shared_with = $_POST['shared_with'];
        
        // بررسی وجود کاربر
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$shared_with]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('کاربر مورد نظر یافت نشد.');
        }
        
        // بررسی تکراری نبودن اشتراک‌گذاری
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_shares WHERE file_id = ? AND shared_with = ?");
        $stmt->execute([$file_id, $shared_with]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('این فایل قبلاً با این کاربر به اشتراک گذاشته شده است.');
        }
        
        // ذخیره اطلاعات اشتراک‌گذاری
        $stmt = $pdo->prepare("INSERT INTO file_shares (file_id, shared_by, shared_with, shared_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$file_id, $uploader_name, $shared_with]);
        
        $success = 'فایل با موفقیت به اشتراک گذاشته شد.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// حذف همه اشتراک‌های یک فایل
if (isset($_POST['action']) && $_POST['action'] == 'delete_shares') {
    try {
        $file_id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM file_shares WHERE file_id = ?");
        $stmt->execute([$file_id]);
        $_SESSION['success'] = "تمام اشتراک‌های این فایل حذف شد.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'خطا در حذف اشتراک‌ها: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// نمایش پیام‌های نوتیفیکیشن
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// دریافت لیست فایل‌ها - فایل‌های کاربر فعلی
$stmt = $pdo->prepare("
    SELECT f.* 
    FROM files f 
    WHERE f.uploader = ? OR f.uploader = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$username, $fullname]);
$files = $stmt->fetchAll();

// لاگ برای دیباگ
error_log("Checking files for user: " . $uploader_name);
error_log("Number of files found: " . count($files));

// دیباگ برای بررسی جدول file_shares
$debug_stmt = $pdo->prepare("SELECT * FROM file_shares WHERE shared_with = ?");
$debug_stmt->execute([$uploader_name]);
$debug_shares = $debug_stmt->fetchAll();
error_log("Debug - All shares for user: " . print_r($debug_shares, true));

// دریافت فایل‌های به اشتراک گذاشته شده با کاربر فعلی
$shared_stmt = $pdo->prepare("
    SELECT f.*, fs.shared_by, fs.shared_at
    FROM files f 
    INNER JOIN file_shares fs ON f.id = fs.file_id 
    WHERE fs.shared_with = ?
    ORDER BY fs.shared_at DESC
");
$shared_stmt->execute([$uploader_name]);
$shared_files = $shared_stmt->fetchAll();

// لاگ برای دیباگ
error_log("Checking shared files for user: " . $uploader_name);
error_log("Number of shared files found: " . count($shared_files));
error_log("Debug - Shared files details: " . print_r($shared_files, true));

// دریافت دسته‌بندی‌های موجود
$stmt = $pdo->prepare("SELECT DISTINCT category FROM files WHERE category IS NOT NULL");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// دریافت آیدی فایل‌هایی که کاربر فعلی با دیگران به اشتراک گذاشته است
$shared_by_me_stmt = $pdo->prepare("SELECT file_id FROM file_shares WHERE shared_by = ?");
$shared_by_me_stmt->execute([$username]);
$shared_by_me_files = $shared_by_me_stmt->fetchAll(PDO::FETCH_COLUMN);

// دریافت آیدی همه فایل‌هایی که به اشتراک گذاشته شده‌اند
$shared_files_all_stmt = $pdo->query("SELECT file_id FROM file_shares");
$all_shared_file_ids = $shared_files_all_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت فایل‌ها - پنل ادمین</title>
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
        
        .file-info {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        
        .file-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .file-title i {
            color: var(--primary-color);
        }
        
        .file-original-name {
            font-size: 0.85rem;
            color: #666;
            margin-right: 1.5rem;
        }
        
        .file-link {
            color: inherit;
            text-decoration: none;
        }
        
        .file-link:hover {
            color: var(--primary-color);
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
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.9rem;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .download-btn {
            color: #27ae60;
            background-color: rgba(46, 204, 113, 0.1);
        }

        .download-btn:hover {
            background-color: rgba(46, 204, 113, 0.2);
        }

        .delete-btn {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .delete-btn:hover {
            background-color: rgba(231, 76, 60, 0.2);
        }

        @media (max-width: 768px) {
            .admin-table th,
            .admin-table td {
                padding: 0.75rem;
            }
            
            .file-original-name {
                display: none;
            }

            .action-text {
                display: none;
            }

            .action-btn {
                width: 32px;
                height: 32px;
                padding: 0;
                justify-content: center;
            }
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .header-actions {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .storage-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .storage-title {
            margin: 0 0 1.5rem 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem;
            font-size: 1.1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .storage-title i {
            color: var(--primary-color);
        }

        .storage-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .storage-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            width: 100%;
            max-width: 800px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-width: 150px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .storage-chart-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
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
            .storage-stats {
                flex-direction: column;
                gap: 1.5rem;
            }

            .stat-item {
                min-width: auto;
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

        .upload-progress-container {
            margin-top: 10px;
        }
        .upload-progress-bar {
            width: 100%;
            height: 8px;
            background-color: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        .upload-progress {
            height: 100%;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        .upload-percent {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .file-input {
            padding: 15px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: #f8f9fa;
        }

        .file-input:hover {
            border-color: var(--primary-color);
            background: #f0f0f0;
        }

        .file-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
        }

        .selected-file {
            margin-top: 10px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #666;
            display: none;
        }

        .selected-file:not(:empty) {
            display: block;
            border: 1px solid #ddd;
        }

        .file-input::file-selector-button {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            margin-left: 15px;
            transition: all 0.2s;
        }

        .file-input::file-selector-button:hover {
            background: var(--primary-color-dark);
        }

        .upload-progress-container {
            margin-top: 15px;
            display: none;
        }

        .upload-progress-bar {
            width: 100%;
            height: 8px;
            background-color: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .upload-progress {
            height: 100%;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        .upload-percent {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
            outline: none;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .file-input::file-selector-button {
                padding: 6px 12px;
                margin-left: 10px;
            }
        }

        .share-btn {
            color: #3498db;
            background-color: rgba(52, 152, 219, 0.1);
        }

        .share-btn:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--dark-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0;
        }

        .close-btn:hover {
            color: var(--dark-color);
        }

        .share-form {
            margin-top: 1rem;
        }

        .share-form .form-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .share-form .form-group label {
            min-width: 90px;
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0;
            font-size: 1rem;
        }
        .share-form .form-group input[type="text"] {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .share-form .form-group input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
            outline: none;
        }
        @media (max-width: 600px) {
            .share-form .form-group {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            .share-form .form-group label {
                min-width: unset;
                font-size: 0.95rem;
            }
        }

        /* --- استایل زیباتر برای ورودی نام کاربری در مودال اشتراک‌گذاری --- */
        .share-user-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        .share-user-row label {
            min-width: 90px;
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0;
        }
        .share-user-row input[type="text"] {
            flex: 1;
            width: 100%;
            min-width: 140px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .share-user-row input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
            outline: none;
        }

        /* دکمه‌های مودال اشتراک‌گذاری در یک ردیف */
        .share-modal-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        .share-modal-actions form,
        .share-modal-actions button {
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>مدیریت فایل‌ها</h1>
                <button class="primary-btn" onclick="showUploadForm()">
                    <i class="fas fa-upload"></i>
                    آپلود فایل جدید
                </button>
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

            <!-- فرم آپلود فایل -->
            <div class="admin-form-container" id="fileForm" style="display: none;">
                <form class="admin-form" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">عنوان فایل:</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="category">دسته‌بندی:</label>
                        <input type="text" id="category" name="category" list="categories" required>
                        <datalist id="categories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="description">توضیحات:</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="file">انتخاب فایل:</label>
                        <input type="file" id="file" name="file" required>
                        <div class="selected-file"></div>
                        <div class="upload-progress-container" style="display: none;">
                            <div class="upload-progress-bar">
                                <div class="upload-progress" style="width: 0%"></div>
                            </div>
                            <div class="upload-percent">0%</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-upload"></i>
                            آپلود فایل
                        </button>
                        <button type="button" class="secondary-btn" onclick="hideUploadForm()">
                            <i class="fas fa-times"></i>
                            انصراف
                        </button>
                    </div>
                </form>
            </div>

            <!-- جدول فایل‌ها -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>فایل</th>
                            <th>نوع</th>
                            <th>حجم</th>
                            <th>دسته‌بندی</th>
                            <th>آپلودکننده</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">هیچ فایلی وجود ندارد</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td>
                                        <div class="file-info">
                                            <div class="file-title">
                                                <i class="fas fa-file"></i>
                                                <a href="?download=<?php echo $file['id']; ?>" class="file-link">
                                                    <?php echo htmlspecialchars($file['title']); ?>
                                                </a>
                                                <?php if (in_array($file['id'], $all_shared_file_ids)): ?>
                                                    <span class="badge badge-info" title="این فایل با کسی به اشتراک گذاشته شده است">
                                                        <i class="fas fa-share-alt"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="file-original-name">
                                                <?php echo htmlspecialchars($file['original_name']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo strtoupper($file['file_type']); ?></span></td>
                                    <td><span class="text-muted"><?php echo formatFileSize($file['file_size']); ?></span></td>
                                    <td><span class="badge badge-secondary"><?php echo htmlspecialchars($file['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($file['uploader']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="?download=<?php echo $file['id']; ?>" class="action-btn download-btn" title="دانلود">
                                                <i class="fas fa-download"></i>
                                                <span class="action-text">دانلود</span>
                                            </a>
                                            <button type="button" class="action-btn share-btn" onclick="showShareForm(<?php echo $file['id']; ?>)" title="اشتراک‌گذاری">
                                                <i class="fas fa-share-alt"></i>
                                                <span class="action-text">اشتراک‌گذاری</span>
                                            </button>
                                            <?php if ($file['uploader'] === $username || $file['uploader'] === $fullname): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $file['id']; ?>">
                                                    <button type="submit" class="action-btn delete-btn" onclick="return confirm('آیا از حذف این فایل اطمینان دارید؟')" title="حذف">
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

            <?php if (!empty($shared_files)): ?>
            <div class="table-container">
                <h3>فایل‌های به اشتراک گذاشته شده با من</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>فایل</th>
                            <th>نوع</th>
                            <th>حجم</th>
                            <th>دسته‌بندی</th>
                            <th>آپلودکننده</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shared_files as $file): ?>
                            <tr>
                                <td>
                                    <div class="file-info">
                                        <div class="file-title">
                                            <i class="fas fa-file"></i>
                                            <a href="?download=<?php echo $file['id']; ?>" class="file-link">
                                                <?php echo htmlspecialchars($file['title']); ?>
                                            </a>
                                            <span class="badge badge-info" title="این فایل با شما به اشتراک گذاشته شده است">
                                                <i class="fas fa-share-alt"></i>
                                            </span>
                                        </div>
                                        <div class="file-original-name">
                                            <?php echo htmlspecialchars($file['original_name']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-info"><?php echo strtoupper($file['file_type']); ?></span></td>
                                <td><span class="text-muted"><?php echo formatFileSize($file['file_size']); ?></span></td>
                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($file['category']); ?></span></td>
                                <td><?php echo htmlspecialchars($file['uploader']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="?download=<?php echo $file['id']; ?>" class="action-btn download-btn" title="دانلود">
                                            <i class="fas fa-download"></i>
                                            <span class="action-text">دانلود</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- نمایش وضعیت فضای ذخیره‌سازی -->
            <div class="storage-info">
                <h3 class="storage-title">
                    <i class="fas fa-hdd"></i>
                    وضعیت فضای ذخیره‌سازی
                </h3>
                
                <div class="storage-content">
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
                        <div class="stat-item">
                            <span class="stat-label">حجم اشتراک‌گذاری:</span>
                            <span class="stat-value"><?php echo formatFileSize($total_shared); ?></span>
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
                                <text x="18" y="18" class="percentage" style="font-size: 0.5em;"><?php echo round($usage_percent, 1); ?>%</text>
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
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>
    <script>
        function showUploadForm() {
            document.getElementById('fileForm').style.display = 'block';
        }

        function hideUploadForm() {
            document.getElementById('fileForm').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.admin-form');
            const fileInput = document.getElementById('file');
            const selectedFileDiv = document.querySelector('.selected-file');
            const progressContainer = document.querySelector('.upload-progress-container');
            const progressBar = document.querySelector('.upload-progress');
            const progressText = document.querySelector('.upload-percent');
            
            // اضافه کردن کلاس‌های استایل به input فایل
            fileInput.classList.add('file-input');
            
            // نمایش نام فایل انتخاب شده
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const fileSize = file.size;
                    const remainingSpace = <?php echo $upload_limit - $used_space; ?>;
                    
                    // بررسی محدودیت فضای آپلود
                    if (fileSize > remainingSpace) {
                        alert('حجم فایل انتخابی بیشتر از فضای خالی شما است!');
                        this.value = '';
                        selectedFileDiv.textContent = '';
                        return;
                    }
                    
                    const fileName = file.name;
                    const fileSizeFormatted = formatFileSize(fileSize);
                    selectedFileDiv.textContent = `${fileName} (${fileSizeFormatted})`;
                } else {
                    selectedFileDiv.textContent = '';
                }
            });

            // مدیریت آپلود فایل و نمایش پیشرفت
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = ((e.loaded / e.total) * 100).toFixed(2);
                        progressContainer.style.display = 'block';
                        progressBar.style.width = percentComplete + '%';
                        progressText.textContent = percentComplete + '%';
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        // آپلود موفق
                        window.location.reload();
                    } else {
                        // خطا در آپلود
                        alert('خطا در آپلود فایل. لطفاً دوباره تلاش کنید.');
                        progressContainer.style.display = 'none';
                    }
                });
                
                xhr.addEventListener('error', function() {
                    alert('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
                    progressContainer.style.display = 'none';
                });
                
                xhr.open('POST', window.location.href, true);
                xhr.send(formData);
            });
        });

        function showShareForm(fileId) {
            document.getElementById('shareFileId').value = fileId;
            document.getElementById('deleteSharesFileId').value = fileId;
            document.getElementById('shareModal').style.display = 'block';
            document.getElementById('shared_with_hidden').value = '';
        }

        function hideShareForm() {
            document.getElementById('shareModal').style.display = 'none';
        }

        // بستن مودال با کلیک خارج از آن
        window.onclick = function(event) {
            const modal = document.getElementById('shareModal');
            if (event.target == modal) {
                hideShareForm();
            }
        }

        document.getElementById('shared_with').addEventListener('input', function() {
            document.getElementById('shared_with_hidden').value = this.value;
        });
    </script>
</body>
</html>

<!-- مودال اشتراک‌گذاری -->
<div class="modal" id="shareModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>اشتراک‌گذاری فایل</h3>
            <button type="button" class="close-btn" onclick="hideShareForm()">&times;</button>
        </div>
        <form method="POST" class="share-form" style="margin-bottom: 0;">
            <input type="hidden" name="action" value="share">
            <input type="hidden" name="file_id" id="shareFileId">
            <div class="form-group share-user-row">
                <label for="shared_with">نام کاربری:</label>
                <input type="text" id="shared_with" name="shared_with" required>
            </div>
        </form>
        <div class="share-modal-actions">
            <button type="button" class="secondary-btn" onclick="hideShareForm()">
                <i class="fas fa-times"></i>
                انصراف
            </button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete_shares">
                <input type="hidden" name="id" id="deleteSharesFileId">
                <button type="submit" class="action-btn share-btn" style="background: #f8d7da; color: #c0392b;" onclick="return confirm('آیا از حذف تمام اشتراک‌های این فایل اطمینان دارید؟')">
                    <i class="fas fa-user-slash"></i>
                    حذف تمام اشتراک‌ها
                </button>
            </form>
            <form method="POST" class="share-form" style="display: inline;">
                <input type="hidden" name="action" value="share">
                <input type="hidden" name="file_id" id="shareFileId">
                <input type="hidden" name="shared_with" id="shared_with_hidden">
                <button type="submit" class="primary-btn">
                    <i class="fas fa-share-alt"></i>
                    اشتراک‌گذاری
                </button>
            </form>
        </div>
    </div>
</div> 