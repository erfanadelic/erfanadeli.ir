<?php
require_once 'config/database.php';

// دریافت مهارت‌ها
$skills = $pdo->query("SELECT * FROM skills ORDER BY category, percentage DESC")->fetchAll();

// دریافت پروژه‌ها
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();

// دریافت نمونه‌کارها
$portfolio_items = $pdo->query("SELECT * FROM portfolio ORDER BY created_at DESC")->fetchAll();

// دسته‌بندی مهارت‌ها
$categorized_skills = [];
foreach ($skills as $skill) {
    $categorized_skills[$skill['category']][] = $skill;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرفان عادلی</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-code"></i>
                <span class="name">عرفان عادلی</span>
            </div>
            <ul>
                <li><a href="#home" class="nav-link"><i class="fas fa-home"></i> خانه</a></li>
                <li><a href="#about" class="nav-link"><i class="fas fa-user"></i> درباره من</a></li>
                <li><a href="#skills" class="nav-link"><i class="fas fa-code"></i> مهارت‌ها</a></li>
                <li><a href="#projects" class="nav-link"><i class="fas fa-project-diagram"></i> پروژه‌ها</a></li>
                <li><a href="#portfolio" class="nav-link"><i class="fas fa-briefcase"></i> نمونه‌کارها</a></li>
                <li><a href="#contact" class="nav-link"><i class="fas fa-envelope"></i> تماس با من</a></li>
            </ul>
            <div class="menu-btn">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

    <main>
        <section id="home" class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <div class="text-wrapper">
                            <span class="hero-greeting">سلام 👋</span>
                            <h1 class="animate-text">عرفان عادلی هستم</h1>
                            <p class="animate-text">توسعه‌دهنده خلاق وب و طراح رابط کاربری، با تخصص در ساخت تجربه‌های دیجیتال جذاب و کاربرپسند</p>
                        </div>
                    </div>
                    <div class="hero-profile">
                        <img src="profile.jpg" alt="تصویر پروفایل" class="hero-profile-image">
                    </div>
                </div>
            </div>
            <div class="wave"></div>
        </section>

        <section id="about" class="about">
            <div class="container">
                <h2 class="section-title"><i class="fas fa-user-circle"></i> درباره من</h2>
                <div class="about-content">
                    <div class="about-image">
                        <div class="profile-frame">
                            <img src="profile-avatar.jpg" alt="تصویر پروفایل" class="profile-image">
                        </div>
                        <div class="profile-decoration"></div>
                    </div>
                    <div class="about-text">
                        <p class="animate-text">من یک توسعه‌دهنده وب با بیش از 5 سال تجربه در زمینه طراحی و توسعه وب‌سایت‌های مدرن هستم. تخصص اصلی من در توسعه فرانت‌اند و بک‌اند است.</p>
                        <div class="experience">
                            <div class="exp-item">
                                <span class="number">5+</span>
                                <span class="label">سال تجربه</span>
                            </div>
                            <div class="exp-item">
                                <span class="number">50+</span>
                                <span class="label">پروژه موفق</span>
                            </div>
                            <div class="exp-item">
                                <span class="number">30+</span>
                                <span class="label">مشتری راضی</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="skills" id="skills">
            <h2 class="section-title"><i class="fas fa-laptop-code"></i> مهارت‌های من</h2>
            <div class="skills-container">
                <?php foreach ($categorized_skills as $category => $category_skills): ?>
                <div class="skills-category">
                    <h3><?php echo htmlspecialchars($category); ?></h3>
                    <div class="skills-list">
                        <?php foreach ($category_skills as $skill): ?>
                        <div class="skill">
                            <div class="skill-name">
                                <?php echo htmlspecialchars($skill['name']); ?>
                                <span class="skill-percentage"><?php echo $skill['percentage']; ?>%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-per" style="width: <?php echo $skill['percentage']; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="projects" id="projects">
            <h2 class="section-title"><i class="fas fa-tasks"></i> پروژه‌های من</h2>
            <div class="projects-container">
                <?php foreach ($projects as $project): ?>
                <div class="project-card">
                    <div class="project-img">
                        <img src="<?php echo htmlspecialchars($project['image'] ?: 'images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" onerror="this.src='images/default.jpg'">
                    </div>
                    <div class="project-content">
                        <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                        <p><?php echo htmlspecialchars($project['description']); ?></p>
                        <?php if ($project['link']): ?>
                        <a href="<?php echo htmlspecialchars($project['link']); ?>" class="btn" target="_blank">
                            <i class="fas fa-external-link-alt"></i> مشاهده پروژه
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="portfolio" id="portfolio">
            <h2 class="section-title"><i class="fas fa-images"></i> نمونه‌کارها</h2>
            <div class="portfolio-container">
                <?php
                $stmt = $pdo->query("SELECT * FROM portfolio ORDER BY created_at DESC");
                while($item = $stmt->fetch()) {
                ?>
                    <div class="portfolio-item">
                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>">
                    <h3 class="item-title"><?php echo $item['title']; ?></h3>
                    <div class="overlay">
                        <p><?php echo $item['description']; ?></p>
                        <?php if($item['link']) { ?>
                        <a href="<?php echo $item['link']; ?>" class="btn" target="_blank">مشاهده پروژه</a>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </section>

        <section id="contact" class="contact">
            <div class="container">
                <h2 class="section-title"><i class="fas fa-paper-plane"></i> تماس با من</h2>
                <div class="contact-content">
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope-open-text"></i>
                            <h3>ایمیل</h3>
                            <p><a href="mailto:me@erfanadeli.ir" title="ارسال ایمیل">me@erfanadeli.ir</a></p>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-telegram-plane"></i>
                            <h3>تلگرام</h3>
                            <p><a href="https://t.me/erfanadelic" target="_blank" title="چت در تلگرام">@erfanadelic</a></p>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-discord"></i>
                            <h3>دیسکورد</h3>
                            <p><a href="https://discord.com/users/erfanadeli" target="_blank" title="چت در دیسکورد">erfanadeli</a></p>
                        </div>
                    </div>
                    <form action="contact.php" method="POST" class="contact-form">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> نام شما</label>
                            <input type="text" id="name" name="name" placeholder="نام خود را وارد کنید" required>
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> ایمیل شما</label>
                            <input type="email" id="email" name="email" placeholder="ایمیل خود را وارد کنید" required>
                        </div>
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-heading"></i> موضوع</label>
                            <input type="text" id="subject" name="subject" placeholder="موضوع پیام را وارد کنید" required>
                        </div>
                        <div class="form-group">
                            <label for="message"><i class="fas fa-comment-alt"></i> پیام شما</label>
                            <textarea id="message" name="message" placeholder="پیام خود را بنویسید" required></textarea>
                        </div>
                        <button type="submit" class="btn primary-btn">
                            <i class="fas fa-paper-plane"></i>
                            ارسال پیام
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
                </div>
                <p>&copy; <?php echo date('Y'); ?> تمامی حقوق محفوظ است.</p>
            </div>
        </div>
    </footer>
</body>
</html> 