<?php
// بررسی نقش کاربر
$is_admin = isset($_SESSION['admin_role']) && ($_SESSION['admin_role'] === 'admin' || $_SESSION['admin_role'] === 'owner');
?>
<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i>
                    <span>داشبورد</span>
                </a>
            </li>
            <?php if ($is_admin): ?>
                <li>
                    <a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-users"></i>
                        <span>مدیریت کاربران</span>
                    </a>
                </li>
                <li>
                    <a href="skills.php" <?php echo basename($_SERVER['PHP_SELF']) == 'skills.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-laptop-code"></i>
                        <span>مهارت‌ها</span>
                    </a>
                </li>
                <li>
                    <a href="projects.php" <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-project-diagram"></i>
                        <span>پروژه‌ها</span>
                    </a>
                </li>
                <li>
                    <a href="portfolio.php" <?php echo basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-images"></i>
                        <span>نمونه‌کارها</span>
                    </a>
                </li>
                <li>
                    <a href="messages.php" <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-envelope"></i>
                        <span>پیام‌ها</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="files.php" <?php echo basename($_SERVER['PHP_SELF']) == 'files.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-file-upload"></i>
                    <span>مدیریت فایل‌ها</span>
                </a>
            </li>
            <li>
                <a href="edit_profile.php" <?php echo basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-user-edit"></i>
                    <span>ویرایش پروفایل</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>خروج</span>
                </a>
            </li>
        </ul>
    </nav>
</aside> 