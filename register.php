<?php
session_start();
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
error_log('register.php - Session ID: ' . session_id() . ', CSRF Token: ' . $csrf_token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register | JIGJIGAHOMES</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        :root {
            --primary: #2a7f62;
            --secondary: #f0c14b;
            --accent: #e2b33a;
            --dark: #1e3c2b;
            --darker: #1e3c2b;
            --text-light: rgba(255, 255, 255, 0.95);
            --text-muted: rgba(255, 255, 255, 0.7);
            --card-bg: rgba(255, 255, 255, 0.1);
            --card-border: rgba(255, 255, 255, 0.15);
            --glass-blur: blur(10px);
            --shadow-depth: 0 10px 30px rgba(0, 0, 0, 0.2);
            --hover-shadow: 0 5px 15px rgba(240, 193, 75, 0.3);
            --transition-fast: all 0.3s ease;
            --error: #ef4444;
            --success: #22c55e;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--darker), var(--dark));
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .form-container {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--card-border);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-depth);
            transition: var(--transition-fast);
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--secondary);
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-light);
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--secondary);
        }
        
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
        }
        
        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition-fast);
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 2px rgba(240, 193, 75, 0.2);
        }
        
        input::placeholder {
            color: var(--text-muted);
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border: 1px dashed var(--card-border);
            background: rgba(255, 255, 255, 0.05);
            text-align: center;
            cursor: pointer;
            transition: var(--transition-fast);
        }
        
        .file-input-label:hover {
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .file-name {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: none;
        }
        
        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--secondary);
            color: var(--dark);
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition-fast);
            margin-top: 1rem;
        }
        
        button:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }
        
        button:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .message {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            display: none;
        }
        
        .error {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error);
            display: block;
        }
        
        .success {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
            display: block;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .login-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-fast);
        }
        
        .login-link a:hover {
            color: var(--accent);
            text-decoration: underline;
        }
        
        .password-strength {
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            background: var(--error);
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .form-container {
                padding: 1.25rem;
            }
            
            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">JIGJIGAHOMES</div>
        <h2>Create Your Account</h2>
        <div class="message" id="message"></div>
        
        <form id="register-form" enctype="multipart/form-data" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group password-container">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8">
                <span class="toggle-password material-icons-round" onclick="togglePassword()">visibility_off</span>
                <div class="password-strength">
                    <div class="strength-meter" id="strength-meter"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+251912345678" required>
            </div>
            
            <div class="form-group">
                <label for="role">I am a:</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="owner">Property Owner</option>
                    <option value="tenant">Tenant</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Profile Picture (Optional)</label>
                <div class="file-input-wrapper">
                    <div class="file-input-label" id="file-label">
                        <span class="material-icons-round" style="vertical-align: middle;">upload</span>
                        <span>Choose an image</span>
                    </div>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    <div class="file-name" id="file-name"></div>
                </div>
            </div>
            
            <button type="submit" id="submit-btn">
                <span id="btn-text">Create Account</span>
                <span id="btn-spinner" style="display:none;">Processing...</span>
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script>
        // DOM Elements
        const form = document.getElementById('register-form');
        const messageDiv = document.getElementById('message');
        const submitBtn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.getElementById('strength-meter');
        const fileInput = document.getElementById('profile_image');
        const fileName = document.getElementById('file-name');
        const fileLabel = document.getElementById('file-label');
        
        // Password visibility toggle
        function togglePassword() {
            const icon = document.querySelector('.toggle-password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }
        
        // Password strength indicator
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updateStrengthMeter(strength);
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Character variety
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            return Math.min(strength, 5);
        }
        
        function updateStrengthMeter(strength) {
            const colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#22c55e'];
            const width = (strength / 5) * 100;
            
            strengthMeter.style.width = width + '%';
            strengthMeter.style.backgroundColor = colors[strength - 1] || colors[0];
        }
        
        // File input display
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                fileName.style.display = 'block';
                fileLabel.innerHTML = '<span class="material-icons-round" style="vertical-align: middle;">check_circle</span> <span>File selected</span>';
                fileLabel.style.borderColor = 'var(--secondary)';
            } else {
                fileName.style.display = 'none';
                fileLabel.innerHTML = '<span class="material-icons-round" style="vertical-align: middle;">upload</span> <span>Choose an image</span>';
                fileLabel.style.borderColor = 'var(--card-border)';
            }
        });
        
        // Form validation
        function validateForm() {
            let isValid = true;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const phone = document.getElementById('phone').value;
            const role = document.getElementById('role').value;
            const file = fileInput.files[0];
            
            // Reset previous errors
            document.querySelectorAll('input, select').forEach(el => {
                el.style.borderColor = '';
            });
            messageDiv.style.display = 'none';
            
            // Email validation
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                document.getElementById('email').style.borderColor = 'var(--error)';
                showMessage('Please enter a valid email address', 'error');
                isValid = false;
            }
            
            // Password validation
            if (password.length < 8) {
                document.getElementById('password').style.borderColor = 'var(--error)';
                showMessage('Password must be at least 8 characters', 'error');
                isValid = false;
            }
            
            // Phone validation (Ethiopian format)
            if (!/^\+251[1-9]\d{8}$/.test(phone)) {
                document.getElementById('phone').style.borderColor = 'var(--error)';
                showMessage('Please enter a valid Ethiopian phone number (+251...)', 'error');
                isValid = false;
            }
            
            // Role validation
            if (!role) {
                document.getElementById('role').style.borderColor = 'var(--error)';
                showMessage('Please select your role', 'error');
                isValid = false;
            }
            
            // File validation (if selected)
            if (file) {
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!validTypes.includes(file.type)) {
                    showMessage('Please upload a valid image (JPEG, PNG, GIF)', 'error');
                    isValid = false;
                }
                
                if (file.size > maxSize) {
                    showMessage('Image size should be less than 5MB', 'error');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        // Show message
        function showMessage(text, type) {
            messageDiv.textContent = text;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';
            
            // Scroll to message
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
        
        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm()) return;
            
            // Disable submit button
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';
            
            try {
                const formData = new FormData(form);
                
                const response = await fetch('register_handler.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = data.redirect || 'login.php';
                    }, 2000);
                } else {
                    showMessage(data.message, 'error');
                    
                    // Highlight problematic fields
                    if (data.errors) {
                        data.errors.forEach(error => {
                            const field = document.querySelector(`[name="${error.field}"]`);
                            if (field) {
                                field.style.borderColor = 'var(--error)';
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>