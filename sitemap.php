<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once 'db_connection.php';

// Fetch user role if logged in
if (isset($_SESSION['user_id'])) {
    $query = "SELECT role FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($role);
        if ($stmt->fetch()) {
            $_SESSION['user_role'] = $role ?: 'tenant';
        } else {
            $_SESSION['user_role'] = 'tenant';
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
        $_SESSION['user_role'] = 'tenant';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap - Jigjiga Homes</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .sitemap-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        body.dark-mode .sitemap-section {
            background: rgba(30, 60, 43, 0.9);
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }
        .sitemap-section h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 20px;
        }
        body.dark-mode .sitemap-section h1 {
            color: var(--text-light);
        }
        .sitemap-section h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin: 30px 0 15px;
        }
        .sitemap-section ul {
            list-style: none;
            padding: 0;
        }
        .sitemap-section li {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .sitemap-section a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .sitemap-section a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        body.dark-mode .sitemap-section a {
            color: var(--accent);
        }
        body.dark-mode .sitemap-section a:hover {
            color: #e2b33a;
        }
        .sitemap-section p {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin: 5px 0 0;
            opacity: 0.8;
        }
        body.dark-mode .sitemap-section p {
            color: var(--text-light);
        }
        .footer {
            background: var(--primary-dark);
            color: var(--text-light);
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }
        .footer a {
            color: var(--accent);
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Premium Header -->
    <?php
require_once 'header.php';
?>
    <!-- Main Content -->
    <main style="margin-top: 80px;">
        <div class="container">
            <section class="sitemap-section">
                <h1>Sitemap</h1>
                <p>Explore the structure of Jigjiga Homes. Below is a list of key pages to help you navigate our premium house rental platform.</p>

                <h2>Main Pages</h2>
                <ul>
                    <li>
                        <a href="index.php">Home</a>
                        <p>The main landing page for Jigjiga Homes, showcasing premium rental properties and platform features.</p>
                    </li>
                    <li>
                        <a href="properties.php">Properties</a>
                        <p>Browse available rental properties in Jigjiga, with filters for location, price, and amenities.</p>
                    </li>
                    <li>
                        <a href="neighborhoods.php">Neighborhoods</a>
                        <p>Learn about Jigjiga’s neighborhoods to find the perfect location for your rental.</p>
                    </li>
                    <li>
                        <a href="help.php">Help</a>
                        <p>Access FAQs, support resources, and contact information for assistance.</p>
                    </li>
                </ul>

                <h2>Account Management</h2>
                <ul>
                    <li>
                        <a href="login.php">Login</a>
                        <p>Sign in to your Jigjiga Homes account to access personalized features.</p>
                    </li>
                    <li>
                        <a href="register.php">Sign Up</a>
                        <p>Create a new account to start renting or listing properties.</p>
                    </li>
                    <li>
                        <a href="profile.php">Profile</a>
                        <p>Manage your personal information, preferences, and account settings.</p>
                    </li>
                    <li>
                        <a href="reserved_properties.php">My Rentals</a>
                        <p>View and manage your active and past rental bookings.</p>
                    </li>
                    <li>
                        <a href="logout.php">Logout</a>
                        <p>Sign out of your Jigjiga Homes account.</p>
                    </li>
                </ul>

                <h2>Role-Based Dashboards</h2>
                <ul>
                    <li>
                        <a href="admin_dashboard.php">Admin Dashboard</a>
                        <p>Manage platform operations, users, and listings (accessible to admins only).</p>
                    </li>
                    <li>
                        <a href="owner_dashboard.php">Owner Dashboard</a>
                        <p>Manage property listings and booking requests (accessible to property owners only).</p>
                    </li>
                    <li>
                        <a href="tenant_dashboard.php">Tenant Dashboard</a>
                        <p>Track active rentals, past rentals, and pending requests (accessible to tenants only).</p>
                    </li>
                </ul>

                <h2>Legal & Information</h2>
                <ul>
                    <li>
                        <a href="privacy.php">Privacy Policy</a>
                        <p>Understand how we collect, use, and protect your personal information.</p>
                    </li>
                    <li>
                        <a href="terms.php">Terms of Service</a>
                        <p>Review the rules and conditions governing the use of our platform.</p>
                    </li>
                    <li>
                        <a href="sitemap.php">Sitemap</a>
                        <p>View the complete structure of the Jigjiga Homes website (you are here).</p>
                    </li>
                </ul>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <?php
require_once 'footer.php';
?>

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
            
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });

        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }

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