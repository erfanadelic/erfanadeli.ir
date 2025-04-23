<header class="admin-header">
    <a href="index.php" class="admin-logo">
        <i class="fas fa-laptop-code"></i>
        <span>پنل مدیریت وبسایت</span>
    </a>
    
    <div class="admin-user">
        <div class="user-info">
            <div class="user-name"><?php echo isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'ادمین'; ?></div>
            <div class="user-role">مدیر سایت</div>
        </div>
        <div class="user-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
</header> 