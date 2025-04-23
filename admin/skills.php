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

// دریافت لیست مهارت‌ها
$stmt = $pdo->query("SELECT * FROM skills ORDER BY category, percentage DESC");
$skills = $stmt->fetchAll();

// تعریف دسته‌بندی‌های ثابت
$categories = [
    'frontend' => 'فرانت‌اند',
    'backend' => 'بک‌اند',
    'database' => 'پایگاه داده',
    'other' => 'سایر'
];

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $stmt = $pdo->prepare("INSERT INTO skills (name, category, percentage) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['category'], $_POST['percentage']]);
                $success = 'مهارت با موفقیت اضافه شد.';
            } elseif ($_POST['action'] == 'edit') {
                $stmt = $pdo->prepare("UPDATE skills SET name = ?, category = ?, percentage = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $_POST['category'], $_POST['percentage'], $_POST['id']]);
                $success = 'مهارت با موفقیت بروزرسانی شد.';
            } elseif ($_POST['action'] == 'delete') {
                $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'مهارت با موفقیت حذف شد.';
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

// دریافت مهارت برای ویرایش
$editing_skill = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing_skill = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت مهارت‌ها - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>مدیریت مهارت‌ها</h1>
                <button class="primary-btn" onclick="showAddForm()">
                    <i class="fas fa-plus"></i>
                    افزودن مهارت جدید
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

            <!-- فرم افزودن/ویرایش مهارت -->
            <div class="admin-form-container" id="skillForm" style="display: <?php echo $editing_skill ? 'block' : 'none'; ?>">
                <form class="admin-form" method="POST">
                    <input type="hidden" name="action" value="<?php echo $editing_skill ? 'edit' : 'add'; ?>">
                    <?php if ($editing_skill): ?>
                        <input type="hidden" name="id" value="<?php echo $editing_skill['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name">نام مهارت:</label>
                        <input type="text" id="name" name="name" value="<?php echo $editing_skill ? $editing_skill['name'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category">دسته‌بندی:</label>
                        <select id="category" name="category" required>
                            <option value="">انتخاب کنید</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($editing_skill && $editing_skill['category'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="percentage">درصد تسلط:</label>
                        <input type="number" id="percentage" name="percentage" min="0" max="100" value="<?php echo $editing_skill ? $editing_skill['percentage'] : ''; ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-save"></i>
                            <?php echo $editing_skill ? 'بروزرسانی' : 'افزودن'; ?>
                        </button>
                        <button type="button" class="secondary-btn" onclick="hideAddForm()">
                            <i class="fas fa-times"></i>
                            انصراف
                        </button>
                    </div>
                </form>
            </div>

            <!-- لیست مهارت‌ها -->
            <div class="admin-grid">
                <?php foreach ($categories as $key => $value): ?>
                    <?php 
                    $category_skills = array_filter($skills, function($skill) use ($key) {
                        return $skill['category'] == $key;
                    });
                    if (!empty($category_skills)): 
                    ?>
                    <div class="skills-category">
                        <h3><?php echo $value; ?></h3>
                        <div class="skills-list">
                            <?php foreach ($category_skills as $skill): ?>
                                <div class="admin-card">
                                    <div class="card-header">
                                        <h3><?php echo htmlspecialchars($skill['name']); ?></h3>
                                    </div>
                                    <div class="card-content">
                                        <div class="skill-progress">
                                            <div class="progress-bar">
                                                <div class="progress" style="width: <?php echo $skill['percentage']; ?>%"></div>
                                            </div>
                                            <span class="percentage"><?php echo $skill['percentage']; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="card-actions">
                                        <a href="?edit=<?php echo $skill['id']; ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i>
                                            ویرایش
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $skill['id']; ?>">
                                            <button type="submit" class="danger-btn" onclick="return confirm('آیا از حذف این مهارت اطمینان دارید؟')">
                                                <i class="fas fa-trash"></i>
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function showAddForm() {
            document.getElementById('skillForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('skillForm').style.display = 'none';
        }
    </script>
</body>
</html> 