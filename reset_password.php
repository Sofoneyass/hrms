<?php
session_start();
require_once 'db_connection.php';

// Verify token
if (!isset($_GET['token'])) {
    header('Location: forgot-password.php?error=Invalid reset link');
    exit;
}

$token = $_GET['token'];

// Check if token is valid
$stmt = $conn->prepare("
    SELECT user_id 
    FROM users 
    WHERE password_reset_token = ? 
    AND password_reset_expires > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: forgot-password.php?error=Invalid or expired reset link');
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['user_id'];

// Generate new CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Set New Password | JIGJIGAHOMES</title>
    <!-- Your existing CSS styles -->
</head>
<body>
    <div class="form-container">
        <div class="logo">JIGJIGAHOMES</div>
        <h2>Create New Password</h2>
        
        <div class="message" id="message"></div>
        
        <form id="reset-password-form" method="POST" action="reset-handler.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="New password (min 8 characters)" required minlength="8">
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required minlength="8">
            </div>
            
            <button type="submit">Update Password</button>
        </form>
    </div>

    <script>
        document.getElementById('reset-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');
            
            // Clear previous messages
            messageDiv.innerHTML = '';
            
            // Validate passwords match
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                const message = document.createElement('div');
                message.textContent = 'Passwords do not match';
                message.className = 'error';
                messageDiv.appendChild(message);
                messageDiv.style.display = 'block';
                return;
            }
            
            // Disable button and show loading
            const submitBtn = form.querySelector('button');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Updating...';
            
            try {
                const response = await fetch('reset-handler.php', {
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
                    setTimeout(() => {
                        window.location.href = 'login.php?success=Password updated successfully';
                    }, 2000);
                }
            } catch (error) {
                const message = document.createElement('div');
                message.textContent = 'An error occurred. Please try again.';
                message.className = 'error';
                messageDiv.appendChild(message);
                messageDiv.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Update Password';
            }
        });
    </script>
</body>
</html>