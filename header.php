<?php
require_once 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jigjiga Homes | Premium House Rentals</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== Consistent Theme with Footer ===== */
        :root {
            --primary: #2a7f62;
            --primary-dark: #1e3c2b;
            --accent: #f0c14b;
            --text-dark: #333;
            --text-light: #f8f9fa;
            --bg-light: #ffffff;
            --bg-dark: #121212;
            --transition: all 0.3s ease;
        }

        /* ===== Base Styles ===== */
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            transition: var(--transition);
        }

        body.dark-mode {
            background: var(--bg-dark);
            color: var(--text-light);
        }

        /* ===== Premium Header ===== */
        .header {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        body.dark-mode .header {
            background: rgba(30, 60, 43, 0.95);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-img {
            height: 40px;
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 22px;
            color: var(--primary-dark);
            line-height: 1;
        }

        body.dark-mode .logo-text {
            color: var(--text-light);
        }

        .logo-tagline {
            font-size: 10px;
            color: var(--primary);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 3px;
        }

        /* Navigation */
        .nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-list {
            display: flex;
            gap: 20px;
            list-style: none;
        }

        .nav-link {
            position: relative;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: var(--transition);
            padding: 5px 0;
        }

        body.dark-mode .nav-link {
            color: var(--text-light);
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: var(--transition);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Buttons */
        .btn {
            padding: 10px 22px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
        }

        .btn-login {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-login:hover {
            background: var(--primary);
            color: white;
        }

        .btn-register {
            background: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-register:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-logout {
            background: var(--accent);
            color: var(--primary-dark);
            border: 2px solid var(--accent);
            font-weight: 600;
        }

        .btn-logout:hover {
            background: #e2b33a;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
            z-index: 1001;
        }

        body.dark-mode .mobile-menu-btn {
            color: var(--text-light);
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-dark);
            transition: var(--transition);
            margin-left: 15px;
        }

        body.dark-mode .dark-mode-toggle {
            color: var(--accent);
        }

        /* ===== Responsive Design ===== */
        @media (max-width: 992px) {
            .header-container {
                padding: 0 25px;
            }
            
            .nav-list {
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .nav {
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: var(--bg-light);
                flex-direction: column;
                align-items: flex-start;
                padding: 100px 30px 30px;
                gap: 25px;
                transition: var(--transition);
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }
            
            body.dark-mode .nav {
                background: var(--primary-dark);
            }
            
            .nav.active {
                right: 0;
            }
            
            .nav-list {
                flex-direction: column;
                width: 100%;
            }
            
            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 20px;
            }
            
            .logo-tagline {
                font-size: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Premium Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="img/jigjigacity.jpeg" alt="Jigjiga Homes" class="logo-img">
                <div>
                    <div class="logo-text">Jigjiga Homes</div>
                    <div class="logo-tagline">Premium Somali Rentals</div>
                </div>
            </a>
            
            <button class="mobile-menu-btn" id="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="nav" id="nav">
                <ul class="nav-list">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="index.php" class="nav-link">Home</a></li>
                        <li><a href="properties.php" class="nav-link">Properties</a></li>
                        <li><a href="neighborhoods.php" class="nav-link">Neighborhoods</a></li>
                        <li><a href="help.php" class="nav-link">Help</a></li>
                        <li><a href="login.php" class="btn btn-login">Login</a></li>
                        <li><a href="register.php" class="btn btn-register">Sign Up</a></li>
                    <?php else: ?>
                        <li><a href="index.php" class="nav-link">Home</a></li>
                        <li><a href="properties.php" class="nav-link">Properties</a></li>
                        <li><a href="reserved_houses.php" class="nav-link">My Rentals</a></li>
                        <li><a href="profile.php" class="nav-link">Profile</a></li>
                        <li><a href="logout.php" class="btn btn-logout">Logout</a></li>
                    <?php endif; ?>
                    <li>
                        <button class="dark-mode-toggle" id="dark-mode-toggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content (Add margin-top to avoid header overlap) -->
    <main style="margin-top: 80px;">
        <!-- Your page content here -->
    </main>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const nav = document.getElementById('nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
            mobileMenuBtn.innerHTML = nav.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            darkModeToggle.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
            
            // Save preference
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        });
    </script>
</body>
</html>