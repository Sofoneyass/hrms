<?php
session_start();
require_once 'db_connection.php';

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Password Reset | JIGJIGAHOMES</title>
    <!-- Your existing CSS styles -->
    <style>
       :root {
            --primary: #2a7f62;
            --secondary: #f0c14b;
            --accent: #e2b33a;
            --dark: #1e3c2b;
            --text-light: rgba(255, 255, 255, 0.95);
            --card-bg: rgba(255, 255, 255, 0.1);
            --error: #ef4444;
            --success: #22c55e;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--dark), #1a3224);
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .form-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 2rem;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .logo {
            text-align: center;
            color: var(--secondary);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--secondary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
        }
        
        input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(240,193,75,0.2);
        }
        
        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--secondary);
            color: var(--dark);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            background: var(--accent);
            transform: translateY(-2px);
        }
        
        .message {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
            display: none;
        }
        
        .error {
            background: rgba(239,68,68,0.2);
            color: var(--error);
            display: block;
        }
        
        .success {
            background: rgba(34,197,94,0.2);
            color: var(--success);
            display: block;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        a {
            color: var(--secondary);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">JIGJIGAHOMES</div>
        <h2>Reset Your Password</h2>
        
        <div class="message" id="message">
            <?php if (isset($_GET['error'])): ?>
                <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
        </div>
        
        <form id="reset-request-form" method="POST" action="reset-request.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your email address" required>
            </div>
            
            <button type="submit">Send Reset Link</button>
        </form>
        
        <div class="login-link">
            Remember your password? <a href="login.php">Log in</a>
        </div>
    </div>

    <script>
        document.getElementById('reset-request-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');
            
            // Clear previous messages
            messageDiv.innerHTML = '';
            
            // Disable button and show loading
            const submitBtn = form.querySelector('button');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Sending...';
            
            try {
                const response = await fetch('reset-request.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                const message = document.createElement('div');
                message.textContent = data.message;
                message.className = data.success ? 'success' : 'error';
                messageDiv.appendChild(message);
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    form.reset();
                }
            } catch (error) {
                const message = document.createElement('div');
                message.textContent = 'An error occurred. Please try again.';
                message.className = 'error';
                messageDiv.appendChild(message);
                messageDiv.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Reset Link';
            }
        });
    </script>
</body>
</html>