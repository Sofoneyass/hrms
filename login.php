<?php
session_start();
require_once 'db_connection.php'; // Your database connection file

// Initialize error message
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Jigjiga Rental</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-dark: #1e3c2b;
      --primary: #2a7f62;
      --accent: #f0c14b;
      --light: #ffffff;
      --text-light: rgba(255, 255, 255, 0.9);
      --text-muted: rgba(255, 255, 255, 0.7);
      --border-radius: 12px;
      --box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
      --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      color: var(--light);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    /* Floating bubbles background */
    .bubble {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      animation: float 15s infinite linear;
    }

    @keyframes float {
      0% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-100px) rotate(180deg); }
      100% { transform: translateY(0) rotate(360deg); }
    }

    /* Login container */
    .login-container {
      width: 100%;
      max-width: 450px;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      overflow: hidden;
      position: relative;
      z-index: 1;
      border: 1px solid rgba(255, 255, 255, 0.15);
      transform: translateY(0);
      transition: var(--transition);
    }

    .login-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    }

    /* Header section */
    .login-header {
      padding: 40px 40px 30px;
      text-align: center;
      position: relative;
    }

    .login-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background: var(--accent);
      border-radius: 3px;
    }

    .logo {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      gap: 15px;
    }

    .logo img {
      height: 50px;
    }

    .logo-text {
      font-size: 24px;
      font-weight: 700;
      color: var(--light);
    }

    .logo-text span {
      color: var(--accent);
    }

    .login-header h1 {
      font-size: 28px;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .login-header p {
      color: var(--text-muted);
      font-size: 15px;
    }

    /* Form styling */
    .login-form {
      padding: 30px 40px 40px;
    }

    .form-group {
      margin-bottom: 25px;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-light);
      font-size: 15px;
    }

    .input-field {
      position: relative;
    }

    .input-field input {
      width: 100%;
      padding: 15px 15px 15px 50px;
      background: rgba(255, 255, 255, 0.15);
      border: 2px solid rgba(255, 255, 255, 0.1);
      border-radius: var(--border-radius);
      color: var(--light);
      font-size: 16px;
      transition: var(--transition);
    }

    .input-field input:focus {
      outline: none;
      background: rgba(255, 255, 255, 0.25);
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(240, 193, 75, 0.2);
    }

    .input-field input::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--accent);
      font-size: 20px;
      transition: var(--transition);
    }

    /* Forgot password link */
    .forgot-password {
      text-align: right;
      margin-top: -15px;
      margin-bottom: 25px;
    }

    .forgot-password a {
      color: var(--text-muted);
      font-size: 14px;
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .forgot-password a:hover {
      color: var(--accent);
    }

    /* Error message styling */
    .error-message {
      background: rgba(239, 35, 60, 0.15);
      color: #ff6b81;
      padding: 12px 15px;
      border-radius: var(--border-radius);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Login button */
    .login-btn {
      width: 100%;
      padding: 16px;
      background: var(--accent);
      color: var(--primary-dark);
      border: none;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(240, 193, 75, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .login-btn:hover {
      background: #e2b33a;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(240, 193, 75, 0.4);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    /* Footer section */
    .login-footer {
      text-align: center;
      padding: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-muted);
      font-size: 14px;
    }

    .login-footer a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .login-footer a:hover {
      color: #e2b33a;
    }

    /* Responsive adjustments */
    @media (max-width: 480px) {
      .login-container {
        border-radius: 0;
        max-width: 100%;
      }
      
      .login-header,
      .login-form {
        padding: 30px 25px;
      }
      
      body {
        padding: 0;
      }
    }
  </style>
</head>
<body>
  <!-- Background bubbles -->
  <div class="bubble" style="width: 150px; height: 150px; top: 10%; left: 5%; animation-delay: 0s;"></div>
  <div class="bubble" style="width: 200px; height: 200px; bottom: 15%; right: 10%; animation-delay: 2s;"></div>
  <div class="bubble" style="width: 100px; height: 100px; top: 60%; left: 20%; animation-delay: 4s;"></div>
  
  <!-- Main login container -->
  <div class="login-container">
    <div class="login-header">
      <div class="logo">
        <img src="img/jigjigacity.jpeg" alt="Jigjiga Homes">
        <div class="logo-text">Jigjiga <span>Homes</span></div>
      </div>
      <h1>Welcome Back</h1>
      <p>Sign in to access your property dashboard</p>
    </div>
    
    <form class="login-form" method="POST" action="login_handler.php">
      <?php if ($error): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>
      
      <div class="form-group">
        <label for="login">Email or Phone</label>
        <div class="input-field">
          <i class="fas fa-user input-icon"></i>
          <input type="text" id="login" name="login" placeholder="your@email.com or +251..." required>
        </div>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-field">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="forgot-password">
          <a href="forgot_password.php">Forgot password?</a>
        </div>
      </div>
      
      <button type="submit" class="login-btn">
        Sign In <i class="fas fa-arrow-right"></i>
      </button>
    </form>
    
    <div class="login-footer">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>

  <script>
    // Micro-interactions for input fields
    document.querySelectorAll('.input-field input').forEach(input => {
      // Focus effect
      input.addEventListener('focus', function() {
        const icon = this.parentNode.querySelector('.input-icon');
        icon.style.transform = 'translateY(-50%) scale(1.2)';
        icon.style.color = '#f0c14b';
      });
      
      // Blur effect
      input.addEventListener('blur', function() {
        const icon = this.parentNode.querySelector('.input-icon');
        icon.style.transform = 'translateY(-50%) scale(1)';
        icon.style.color = '#f0c14b';
      });
    });

    // Button ripple effect
    document.querySelector('.login-btn').addEventListener('click', function(e) {
      // Create ripple element
      const ripple = document.createElement('span');
      ripple.classList.add('ripple-effect');
      
      // Position ripple
      const rect = this.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      
      ripple.style.left = `${x}px`;
      ripple.style.top = `${y}px`;
      
      // Add ripple to button
      this.appendChild(ripple);
      
      // Remove ripple after animation
      setTimeout(() => {
        ripple.remove();
      }, 1000);
    });

    // Add dynamic bubbles
    function createBubbles() {
      const colors = ['rgba(255,255,255,0.03)', 'rgba(240,193,75,0.05)', 'rgba(30,60,43,0.04)'];
      const body = document.body;
      
      for (let i = 0; i < 5; i++) {
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        
        const size = Math.random() * 100 + 50;
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const color = colors[Math.floor(Math.random() * colors.length)];
        const delay = Math.random() * 5;
        const duration = 10 + Math.random() * 20;
        
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${posX}%`;
        bubble.style.top = `${posY}%`;
        bubble.style.background = color;
        bubble.style.animationDelay = `${delay}s`;
        bubble.style.animationDuration = `${duration}s`;
        
        body.appendChild(bubble);
      }
    }

    // Initialize bubbles
    window.addEventListener('load', createBubbles);
  </script>
</body>
</html>