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

// دریافت لیست پروژه‌ها
$stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
$projects = $stmt->fetchAll();

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $stmt = $pdo->prepare("INSERT INTO projects (title, description, technologies, link, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['technologies'], $_POST['link'], $_POST['image']]);
                $success = 'پروژه با موفقیت اضافه شد.';
            } elseif ($_POST['action'] == 'edit') {
                $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, technologies = ?, link = ?, image = ? WHERE id = ?");
                $stmt->execute([$_POST['title'], $_POST['description'], $_POST['technologies'], $_POST['link'], $_POST['image'], $_POST['id']]);
                $success = 'پروژه با موفقیت بروزرسانی شد.';
            } elseif ($_POST['action'] == 'delete') {
                $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = 'پروژه با موفقیت حذف شد.';
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

// دریافت پروژه برای ویرایش
$editing_project = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing_project = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت پروژه‌ها - پنل ادمین</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>مدیریت پروژه‌ها</h1>
                <button class="primary-btn" onclick="showAddForm()">
                    <i class="fas fa-plus"></i>
                    افزودن پروژه جدید
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

            <!-- فرم افزودن/ویرایش پروژه -->
            <div class="admin-form-container" id="projectForm" style="display: <?php echo $editing_project ? 'block' : 'none'; ?>">
                <form class="admin-form" method="POST">
                    <input type="hidden" name="action" value="<?php echo $editing_project ? 'edit' : 'add'; ?>">
                    <?php if ($editing_project): ?>
                        <input type="hidden" name="id" value="<?php echo $editing_project['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">عنوان پروژه:</label>
                        <input type="text" id="title" name="title" value="<?php echo $editing_project ? htmlspecialchars($editing_project['title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">توضیحات:</label>
                        <textarea id="description" name="description" rows="4" required><?php echo $editing_project ? htmlspecialchars($editing_project['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="technologies">تکنولوژی‌ها (با کاما جدا کنید):</label>
                        <input type="text" id="technologies" name="technologies" value="<?php echo $editing_project ? htmlspecialchars($editing_project['technologies']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="link">لینک پروژه:</label>
                        <input type="url" id="link" name="link" value="<?php echo $editing_project ? htmlspecialchars($editing_project['link']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="image">تصویر پروژه (URL یا مسیر نسبی):</label>
                        <input type="text" id="image" name="image" value="<?php echo $editing_project ? htmlspecialchars($editing_project['image']) : ''; ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-save"></i>
                            <?php echo $editing_project ? 'بروزرسانی' : 'افزودن'; ?>
                        </button>
                        <button type="button" class="secondary-btn" onclick="hideAddForm()">
                            <i class="fas fa-times"></i>
                            انصراف
                        </button>
                    </div>
                </form>
            </div>

            <!-- لیست پروژه‌ها -->
            <div class="admin-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="admin-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($project['image'])): ?>
                                <div class="project-image">
                                    <img src="<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <p class="project-description"><?php echo htmlspecialchars(substr($project['description'], 0, 150)) . (strlen($project['description']) > 150 ? '...' : ''); ?></p>
                            <div class="project-tech">
                                <strong>تکنولوژی‌ها:</strong>
                                <div class="tech-tags">
                                    <?php
                                    $techs = explode(',', $project['technologies']);
                                    foreach ($techs as $tech) {
                                        echo '<span class="tech-tag">' . trim(htmlspecialchars($tech)) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if (!empty($project['link'])): ?>
                                <div class="project-link">
                                    <a href="<?php echo htmlspecialchars($project['link']); ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-external-link-alt"></i> مشاهده پروژه
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-actions">
                            <a href="?edit=<?php echo $project['id']; ?>" class="edit-btn">
                                <i class="fas fa-edit"></i>
                                ویرایش
                            </a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                                <button type="submit" class="danger-btn" onclick="return confirm('آیا از حذف این پروژه اطمینان دارید؟')">
                                    <i class="fas fa-trash"></i>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($projects)): ?>
                    <p>هیچ پروژه‌ای وجود ندارد.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function showAddForm() {
            document.getElementById('projectForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('projectForm').style.display = 'none';
        }
    </script>
</body>
</html> 