<footer class="admin-footer">
    <div class="container">
        <p>© <?php echo date('Y'); ?> - تمامی حقوق محفوظ است.</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // اضافه کردن کلاس active به لینک صفحه فعلی
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.admin-nav a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
});
</script> 