<aside class="admin-sidebar">
    <ul>
        <li>
            <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span>داشبورد</span>
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
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>خروج</span>
            </a>
        </li>
    </ul>
</aside> 