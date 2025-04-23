// انیمیشن‌های GSAP با تایم‌لاین
const tl = gsap.timeline();

tl.from('.logo', {
    duration: 1,
    y: -50,
    opacity: 0,
    ease: 'power3.out'
})
.from('nav ul li', {
    duration: 0.8,
    y: -50,
    opacity: 0,
    stagger: 0.1,
    ease: 'power3.out'
}, '-=0.5')
.from('.hero h1', {
    duration: 1,
    y: 50,
    opacity: 0,
    ease: 'power3.out'
}, '-=0.3')
.from('.hero p', {
    duration: 1,
    y: 30,
    opacity: 0,
    ease: 'power3.out'
}, '-=0.7')
.from('.hero-buttons', {
    duration: 1,
    y: 30,
    opacity: 0,
    ease: 'power3.out'
}, '-=0.7');

// انیمیشن اسکرول برای عناصر با افکت‌های متنوع
const animateOnScroll = () => {
    const elements = document.querySelectorAll('.animate-on-scroll');
    
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementBottom = element.getBoundingClientRect().bottom;
        
        if (elementTop < window.innerHeight - 100 && elementBottom > 0) {
            if (!element.classList.contains('animate')) {
                element.classList.add('animate');
                
                // افکت‌های متنوع برای المان‌های مختلف
                if (element.classList.contains('skill-card')) {
                    gsap.from(element, {
                        duration: 0.8,
                        y: 50,
                        opacity: 0,
                        rotation: 5,
                        ease: 'power3.out'
                    });
                } else if (element.classList.contains('project-card')) {
                    gsap.from(element, {
                        duration: 0.8,
                        scale: 0.8,
                        opacity: 0,
                        ease: 'back.out(1.7)'
                    });
                }
            }
        }
    });
};

window.addEventListener('scroll', animateOnScroll);
animateOnScroll();

// بهبود منوی موبایل با انیمیشن
const menuBtn = document.querySelector('.menu-btn');
const navMenu = document.querySelector('nav ul');
let isMenuOpen = false;

menuBtn.addEventListener('click', () => {
    if (!isMenuOpen) {
        navMenu.classList.add('active');
        gsap.from('nav ul li', {
            duration: 0.5,
            x: 50,
            opacity: 0,
            stagger: 0.1,
            ease: 'power3.out'
        });
        menuBtn.innerHTML = '<i class="fas fa-times"></i>';
    } else {
        navMenu.classList.remove('active');
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    }
    isMenuOpen = !isMenuOpen;
});

// اسکرول نرم بهبود یافته
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const headerOffset = 100;
            const elementPosition = target.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
            
            // بستن منو در موبایل
            if (isMenuOpen) {
                navMenu.classList.remove('active');
                menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                isMenuOpen = false;
            }
        }
    });
});

// تغییر استایل هدر هنگام اسکرول با انیمیشن
const header = document.querySelector('header');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll <= 0) {
        gsap.to(header, {
            duration: 0.3,
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
        });
        return;
    }
    
    if (currentScroll > lastScroll) {
        gsap.to(header, {
            duration: 0.3,
            backgroundColor: 'rgba(255, 255, 255, 1)',
            boxShadow: '0 2px 20px rgba(0,0,0,0.1)'
        });
    }
    
    lastScroll = currentScroll;
});

// انیمیشن نوارهای پیشرفت مهارت‌ها با GSAP
const animateSkillBars = () => {
    gsap.from('.progress', {
        width: 0,
        duration: 1.5,
        ease: 'power3.out',
        stagger: 0.2
    });
};

// مشاهده نوارهای مهارت با Intersection Observer
const skillsSection = document.querySelector('.skills');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateSkillBars();
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

observer.observe(skillsSection);

// انیمیشن شمارنده‌ها با GSAP
const animateCounter = (counter) => {
    const target = parseInt(counter.textContent);
    gsap.from(counter, {
        textContent: 0,
        duration: 2,
        ease: 'power2.out',
        snap: { textContent: 1 },
        onUpdate: () => {
            counter.textContent = Math.floor(counter.textContent) + '+';
        }
    });
};

// مشاهده شمارنده‌ها با Intersection Observer
const experienceSection = document.querySelector('.experience');
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            document.querySelectorAll('.exp-item .number').forEach(counter => {
                animateCounter(counter);
            });
            counterObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

counterObserver.observe(experienceSection);

// اضافه کردن افکت موج به دکمه‌ها
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('mouseenter', function(e) {
        const x = e.clientX - e.target.offsetLeft;
        const y = e.clientY - e.target.offsetTop;
        
        const ripple = document.createElement('span');
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
}); 