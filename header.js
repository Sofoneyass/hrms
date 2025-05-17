document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const nav = document.getElementById('nav');
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    let isToggling = false;

    // Apply dark mode inline to prevent flicker
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        if (darkModeToggle) {
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
    }

    // Mobile Menu Toggle with Debounce
    if (mobileMenuBtn && nav) {
        mobileMenuBtn.addEventListener('click', () => {
            if (isToggling) return;
            isToggling = true;

            const isActive = nav.classList.contains('active');
            nav.classList.toggle('active');
            mobileMenuBtn.innerHTML = isActive
                ? '<i class="fas fa-bars"></i>'
                : '<i class="fas fa-times"></i>';
            mobileMenuBtn.setAttribute('aria-expanded', !isActive);

            setTimeout(() => {
                isToggling = false;
            }, 300); // Match CSS transition duration
        });

        // Close mobile menu on navigation
        nav.addEventListener('click', (e) => {
            const target = e.target.closest('.nav-link, .btn-login, .btn-register, .btn-logout');
            if (target && nav.classList.contains('active')) {
                e.preventDefault(); // Delay navigation for animation
                nav.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                mobileMenuBtn.setAttribute('aria-expanded', 'false');

                // Navigate after animation
                setTimeout(() => {
                    if (target.tagName === 'A') {
                        window.location.href = target.href;
                    }
                }, 300);
            }
        });

        // Close mobile menu on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && nav.classList.contains('active')) {
                nav.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Dark Mode Toggle
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            const isDarkMode = document.body.classList.toggle('dark-mode');
            darkModeToggle.innerHTML = isDarkMode
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
            localStorage.setItem('darkMode', isDarkMode);
        });
    }
});