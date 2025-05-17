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
    <title>Terms of Service - Jigjiga Homes</title>
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
        .terms-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        body.dark-mode .terms-section {
            background: rgba(30, 60, 43, 0.9);
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }
        .terms-section h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 20px;
        }
        body.dark-mode .terms-section h1 {
            color: var(--text-light);
        }
        .terms-section h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin: 30px 0 15px;
        }
        .terms-section p, .terms-section ul {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .terms-section ul {
            padding-left: 20px;
        }
        .terms-section li {
            margin-bottom: 10px;
        }
        .terms-section a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }
        .terms-section a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        body.dark-mode .terms-section a {
            color: var(--accent);
        }
        body.dark-mode .terms-section a:hover {
            color: #e2b33a;
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
                        <li><a href="reserved_properties.php" class="nav-link">My Rentals</a></li>
                        <li><a href="profile.php" class="nav-link">Profile</a></li>
                        <?php if (isset($_SESSION['user_role'])): ?>
                            <li>
                                <a href="<?php
                                    if ($_SESSION['user_role'] === 'admin') {
                                        echo 'admin_dashboard.php';
                                    } elseif ($_SESSION['user_role'] === 'owner') {
                                        echo 'owner_dashboard.php';
                                    } else {
                                        echo 'tenant_dashboard.php';
                                    }
                                ?>" class="nav-link">Dashboard</a>
                            </li>
                        <?php endif; ?>
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

    <!-- Main Content -->
    <main style="margin-top: 80px;">
        <div class="container">
            <section class="terms-section">
                <h1>Terms of Service</h1>
                <p>Last Updated: May 4, 2025</p>
                <p>Welcome to Jigjiga Homes. These Terms of Service ("Terms") govern your use of our house rental management platform, including our website and services. By accessing or using Jigjiga Homes, you agree to be bound by these Terms. If you do not agree, please do not use our services.</p>

                <h2>1. Acceptance of Terms</h2>
                <p>By creating an account, listing a property, booking a rental, or otherwise using our platform, you agree to these Terms, our <a href="privacy.php">Privacy Policy</a>, and any additional policies or guidelines posted on our platform. These Terms apply to all users, including tenants, property owners, and administrators.</p>

                <h2>2. User Accounts</h2>
                <p>To access certain features, you must create an account. You agree to:</p>
                <ul>
                    <li>Provide accurate, complete, and current information during registration.</li>
                    <li>Maintain the security of your account by not sharing your password.</li>
                    <li>Notify us immediately of any unauthorized access to your account.</li>
                    <li>Accept responsibility for all activities under your account.</li>
                </ul>
                <p>We reserve the right to suspend or terminate accounts that violate these Terms.</p>

                <h2>3. User Roles and Responsibilities</h2>
                <p>Our platform supports three user roles, each with specific responsibilities:</p>
                <ul>
                    <li><strong>Tenants:</strong> Responsible for paying rent on time, maintaining rented properties, and complying with rental agreements.</li>
                    <li><strong>Property Owners:</strong> Responsible for providing accurate property listings, maintaining properties in good condition, and responding promptly to tenant inquiries.</li>
                    <li><strong>Administrators:</strong> Responsible for overseeing platform operations, resolving disputes, and ensuring compliance with these Terms.</li>
                </ul>

                <h2>4. Property Listings</h2>
                <p>Property owners agree to:</p>
                <ul>
                    <li>Provide truthful and accurate information about their properties, including descriptions, photos, and availability.</li>
                    <li>Comply with local laws and regulations regarding property rentals.</li>
                    <li>Not list properties that are unsafe, uninhabitable, or misrepresented.</li>
                </ul>
                <p>Jigjiga Homes reserves the right to remove listings that violate these Terms or are deemed inappropriate.</p>

                <h2>5. Booking and Payments</h2>
                <p>Bookings are subject to the following:</p>
                <ul>
                    <li>Tenants must submit payment through our platform’s secure payment system.</li>
                    <li>Payments are processed by third-party payment providers, subject to their terms.</li>
                    <li>Cancellation policies are set by property owners and displayed at booking. Refunds are subject to these policies.</li>
                    <li>Jigjiga Homes may charge service fees, which will be disclosed at booking.</li>
                </ul>

                <h2>6. Prohibited Activities</h2>
                <p>You agree not to:</p>
                <ul>
                    <li>Use the platform for illegal purposes or to violate local, national, or international laws.</li>
                    <li>Post false, misleading, or offensive content.</li>
                    <li>Attempt to hack, disrupt, or bypass our security measures.</li>
                    <li>Harass, threaten, or discriminate against other users.</li>
                    <li>Use automated tools (e.g., bots) to scrape or manipulate the platform.</li>
                </ul>
                <p>Violations may result in account suspension, termination, or legal action.</p>

                <h2>7. Dispute Resolution</h2>
                <p>Disputes between users (e.g., tenants and owners) should first be resolved directly. If unresolved, you may contact our support team at <a href="mailto:support@jigjigahomes.com">support@jigjigahomes.com</a>. We will mediate disputes in good faith but are not liable for user disputes.</p>
                <p>Any legal disputes with Jigjiga Homes will be governed by the laws of Somalia and resolved in the courts of Jigjiga, unless otherwise required by law.</p>

                <h2>8. Intellectual Property</h2>
                <p>All content on the platform, including logos, designs, and text, is owned by Jigjiga Homes or licensed to us. You may not copy, distribute, or modify our content without written permission. User-generated content (e.g., property listings) remains your property, but you grant us a non-exclusive, worldwide license to use it for platform operations.</p>

                <h2>9. Limitation of Liability</h2>
                <p>Jigjiga Homes provides a platform to connect tenants and owners but is not a party to rental agreements. We are not liable for:</p>
                <ul>
                    <li>Damages or losses arising from rental agreements or property conditions.</li>
                    <li>Inaccurate or misleading information provided by users.</li>
                    <li>Platform interruptions or data breaches beyond our control.</li>
                </ul>
                <p>Our liability is limited to the amount paid for our services, to the extent permitted by law.</p>

                <h2>10. Termination</h2>
                <p>We may terminate or suspend your access to the platform at our discretion, with or without notice, for violations of these Terms or other reasons. You may terminate your account at any time by contacting us or using account settings.</p>

                <h2>11. Changes to These Terms</h2>
                <p>We may update these Terms to reflect changes in our services or legal requirements. We will notify you of significant changes via email or a notice on our platform. Continued use after changes constitutes acceptance of the updated Terms.</p>

                <h2>12. Contact Us</h2>
                <p>If you have questions or concerns about these Terms, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> <a href="mailto:support@jigjigahomes.com">support@jigjigahomes.com</a></li>
                    <li><strong>Phone:</strong> +252-123-456-789</li>
                    <li><strong>Address:</strong> Jigjiga Homes, 123 Main Street, Jigjiga, Somalia</li>
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