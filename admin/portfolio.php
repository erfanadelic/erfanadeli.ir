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

// تعریف دسته‌بندی‌های ثابت
$categories = [
    'web' => 'طراحی وب',
    'mobile' => 'اپلیکیشن موبایل',
    'graphic' => 'طراحی گرافیک',
    'other' => 'سایر'
];

// دریافت لیست نمونه‌کارها
$stmt = $pdo->query("SELECT * FROM portfolio ORDER BY id DESC");
$portfolio_items = $stmt->fetchAll();

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $stmt = $pdo->prepare("INSERT INTO portfolio (title, description, category, image, link) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['category'], $_POST['image'], $_POST['link']]);
                $success = 'نمونه‌کار با موفقیت اضافه شد.';
            } elseif ($_POST['action'] == 'edit') {
                $stmt = $pdo->prepare("UPDATE portfolio SET title = ?, description = ?, category = ?, image = ?, link = ? WHERE id = ?");
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['category'], $_POST['image'], $_POST['link'], $_POST['id']]);
                $success = 'نمونه‌کار با موفقیت بروزرسانی شد.';
            } elseif ($_POST['action'] == 'delete') {
                $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'نمونه‌کار با موفقیت حذف شد.';
            }
            
            // ریدایرکت به همان صفحه
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        $error = 'خطا در عملیات: ' . $e->getMessage();
        error_log("Database Error: " . $e->getMessage());
    }
}

// دریافت نمونه‌کار برای ویرایش
$editing_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت نمونه‌کارها - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>مدیریت نمونه‌کارها</h1>
                <button class="primary-btn" onclick="showAddForm()">
                    <i class="fas fa-plus"></i>
                    افزودن نمونه‌کار جدید
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

            <!-- فرم افزودن/ویرایش نمونه‌کار -->
            <div class="admin-form-container" id="portfolioForm" style="display: <?php echo $editing_item ? 'block' : 'none'; ?>">
                <form class="admin-form" method="POST">
                    <input type="hidden" name="action" value="<?php echo $editing_item ? 'edit' : 'add'; ?>">
                    <?php if ($editing_item): ?>
                        <input type="hidden" name="id" value="<?php echo $editing_item['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">عنوان نمونه‌کار:</label>
                        <input type="text" id="title" name="title" value="<?php echo $editing_item ? htmlspecialchars($editing_item['title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">توضیحات:</label>
                        <textarea id="description" name="description" rows="4" required><?php echo $editing_item ? htmlspecialchars($editing_item['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category">دسته‌بندی:</label>
                        <select id="category" name="category" required>
                            <option value="">انتخاب کنید</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($editing_item && $editing_item['category'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">تصویر نمونه‌کار (URL یا مسیر نسبی):</label>
                        <input type="text" id="image" name="image" value="<?php echo $editing_item ? htmlspecialchars($editing_item['image']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="link">لینک نمونه‌کار:</label>
                        <input type="url" id="link" name="link" value="<?php echo $editing_item ? htmlspecialchars($editing_item['link']) : ''; ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-save"></i>
                            <?php echo $editing_item ? 'بروزرسانی' : 'افزودن'; ?>
                        </button>
                        <button type="button" class="secondary-btn" onclick="hideAddForm()">
                            <i class="fas fa-times"></i>
                            انصراف
                        </button>
                    </div>
                </form>
            </div>

            <!-- لیست نمونه‌کارها -->
            <div class="admin-grid">
                <?php foreach ($portfolio_items as $item): ?>
                    <div class="admin-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <span class="portfolio-category"><?php echo htmlspecialchars($categories[$item['category']] ?? $item['category']); ?></span>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($item['image'])): ?>
                                <div class="portfolio-image">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <p class="portfolio-description"><?php echo htmlspecialchars(substr($item['description'], 0, 150)) . (strlen($item['description']) > 150 ? '...' : ''); ?></p>
                            <?php if (!empty($item['link'])): ?>
                                <div class="portfolio-link">
                                    <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-external-link-alt"></i> مشاهده نمونه‌کار
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-actions">
                            <a href="?edit=<?php echo $item['id']; ?>" class="edit-btn">
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="danger-btn" onclick="return confirm('آیا از حذف این نمونه‌کار اطمینان دارید؟')">
                                    <i class="fas fa-trash"></i>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($portfolio_items)): ?>
                    <p>هیچ نمونه‌کاری وجود ندارد.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function showAddForm() {
            document.getElementById('portfolioForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('portfolioForm').style.display = 'none';
        }
    </script>
</body>
</html> 