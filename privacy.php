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
    <title>Privacy Policy - Jigjiga Homes</title>
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
       
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .privacy-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        body.dark-mode .privacy-section {
            background: rgba(30, 60, 43, 0.9);
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }
        .privacy-section h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 20px;
        }
        body.dark-mode .privacy-section h1 {
            color: var(--text-light);
        }
        .privacy-section h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin: 30px 0 15px;
        }
        .privacy-section p, .privacy-section ul {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .privacy-section ul {
            padding-left: 20px;
        }
        .privacy-section li {
            margin-bottom: 10px;
        }
        .privacy-section a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }
        .privacy-section a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        body.dark-mode .privacy-section a {
            color: var(--accent);
        }
        body.dark-mode .privacy-section a:hover {
            color: #e2b33a;
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
            <section class="privacy-section">
                <h1>Privacy Policy</h1>
                <p>Last Updated: May 4, 2025</p>
                <p>At Jigjiga Homes, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, share, and safeguard your personal information when you use our house rental management platform. By accessing or using our services, you agree to the practices described in this policy.</p>

                <h2>1. Information We Collect</h2>
                <p>We collect information to provide and improve our rental services. The types of information we may collect include:</p>
                <ul>
                    <li><strong>Personal Information:</strong> Name, email address, phone number, mailing address, and payment details provided when you register, book a rental, or contact us.</li>
                    <li><strong>Account Information:</strong> Username, password (encrypted), and user role (admin, owner, or tenant) for accessing your account.</li>
                    <li><strong>Property Information:</strong> Details about properties listed by owners, such as descriptions, photos, and availability.</li>
                    <li><strong>Usage Data:</strong> Information about how you interact with our platform, including IP address, browser type, pages visited, and timestamps.</li>
                    <li><strong>Cookies and Tracking:</strong> We use cookies to enhance your experience, such as remembering your preferences (e.g., dark mode) and analyzing site usage.</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <p>We use your information to operate and improve our services, including:</p>
                <ul>
                    <li>Processing rental bookings and payments.</li>
                    <li>Verifying user identities and managing accounts.</li>
                    <li>Communicating with you about bookings, account updates, or customer support.</li>
                    <li>Personalizing your experience, such as recommending properties based on your preferences.</li>
                    <li>Analyzing usage trends to enhance platform functionality and security.</li>
                    <li>Complying with legal obligations and resolving disputes.</li>
                </ul>

                <h2>3. How We Share Your Information</h2>
                <p>We do not sell your personal information. We may share your information in the following cases:</p>
                <ul>
                    <li><strong>With Property Owners or Tenants:</strong> To facilitate rental agreements (e.g., sharing tenant contact details with owners for booking coordination).</li>
                    <li><strong>Service Providers:</strong> With trusted third parties who assist us in operating our platform, such as payment processors or hosting providers, under strict confidentiality agreements.</li>
                    <li><strong>Legal Requirements:</strong> When required by law, such as responding to subpoenas or government requests.</li>
                    <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred to the new entity.</li>
                </ul>

                <h2>4. Data Security</h2>
                <p>We implement industry-standard security measures to protect your information, including:</p>
                <ul>
                    <li>Encryption of sensitive data (e.g., passwords and payment details).</li>
                    <li>Secure Socket Layer (SSL) technology for data transmission.</li>
                    <li>Regular security audits and access controls to prevent unauthorized access.</li>
                </ul>
                <p>While we strive to protect your data, no system is completely secure. Please use strong passwords and avoid sharing your account details.</p>

                <h2>5. Your Rights and Choices</h2>
                <p>You have the following rights regarding your personal information:</p>
                <ul>
                    <li><strong>Access:</strong> Request a copy of the personal data we hold about you.</li>
                    <li><strong>Correction:</strong> Update or correct inaccurate information via your account settings.</li>
                    <li><strong>Deletion:</strong> Request deletion of your account and associated data, subject to legal retention requirements.</li>
                    <li><strong>Opt-Out:</strong> Unsubscribe from marketing emails or disable cookies through your browser settings.</li>
                </ul>
                <p>To exercise these rights, contact us at <a href="mailto:privacy@jigjigahomes.com">privacy@jigjigahomes.com</a>.</p>

                <h2>6. Cookies and Tracking</h2>
                <p>We use cookies and similar technologies to:</p>
                <ul>
                    <li>Authenticate users and maintain session security.</li>
                    <li>Store preferences, such as dark mode settings.</li>
                    <li>Analyze site performance and user behavior.</li>
                </ul>
                <p>You can manage cookie preferences through your browser settings, but disabling cookies may affect platform functionality.</p>

                <h2>7. Third-Party Links</h2>
                <p>Our platform may contain links to third-party websites (e.g., payment processors). We are not responsible for their privacy practices. Please review their policies before providing personal information.</p>

                <h2>8. Children's Privacy</h2>
                <p>Jigjiga Homes is not intended for users under 18. We do not knowingly collect personal information from children. If you believe we have collected such data, please contact us to have it removed.</p>

                <h2>9. International Data Transfers</h2>
                <p>Your information may be stored and processed in countries outside your jurisdiction, where data protection laws may differ. We ensure appropriate safeguards are in place for such transfers.</p>

                <h2>10. Changes to This Privacy Policy</h2>
                <p>We may update this Privacy Policy to reflect changes in our practices or legal requirements. We will notify you of significant changes via email or a notice on our platform. The "Last Updated" date at the top indicates the latest revision.</p>

                <h2>11. Contact Us</h2>
                <p>If you have questions or concerns about this Privacy Policy, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> <a href="mailto:privacy@jigjigahomes.com">privacy@jigjigahomes.com</a></li>
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