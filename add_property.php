<?php


// Only owners can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit();
}

// Get success/error messages from session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Clear session messages
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Property | JIGJIGAHOMES</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        :root {
            --primary: #2a7f62;
            --secondary: #f0c14b;
            --accent: #e2b33a;
            --dark: #1e3c2b;
            --text-light: #fff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --card-bg: rgba(255, 255, 255, 0.1);
            --card-border: rgba(255, 255, 255, 0.15);
            --glass-blur: blur(10px);
            --shadow-depth: 0 10px 30px rgba(0, 0, 0, 0.2);
            --hover-shadow: 0 5px 15px rgba(240, 193, 75, 0.3);
            --transition-fast: all 0.3s ease;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c2b 0%, #2a7f62 100%);
            color: var(--text-light);
            min-height: 100vh;
        }

        .main-content {
            margin-left: 270px;
            padding: 20px;
        }

        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--card-border);
            padding: 30px;
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

        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 700;
            color: var(--text-light);
            position: relative;
            padding-bottom: 10px;
        }

        .form-container h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 40px;
            height: 2px;
            background: var(--secondary);
        }

        .form-container form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-container input,
        .form-container textarea,
        .form-container select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.9);
            color: var(--dark);
            font-size: 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: var(--transition-fast);
        }

        .form-container input:focus,
        .form-container textarea:focus,
        .form-container select:focus {
            outline: none;
            border-color: var(--secondary);
            background: #fff;
        }

        .form-container textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-container input::placeholder,
        .form-container textarea::placeholder {
            color: rgba(30, 60, 43, 0.7);
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background: var(--secondary);
            color: var(--dark);
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .form-container button:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .message {
            text-align: center;
            font-size: 16px;
            margin: 20px 0;
            padding: 12px;
            border-radius: 6px;
        }

        .success {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 220px;
            }

            .form-container {
                margin: 20px;
                padding: 20px;
            }

            .form-container h2 {
                font-size: 24px;
            }

            .form-container input,
            .form-container textarea,
            .form-container select,
            .form-container button {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .form-container {
                margin: 15px;
                padding: 15px;
            }

            .form-container h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <div class="form-container">
            <h2>Add New Property</h2>
            <?php if (!empty($success)): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="add_property_handler.php" enctype="multipart/form-data">
                <select name="title" required>
                    <option value="">Select Property Title</option>
                    <option value="condo">Condo</option>
                    <option value="apartment">Apartment</option>
                    <option value="villa">Villa</option>
                </select>
                <textarea name="description" placeholder="Property Description"></textarea>
                <input type="text" name="location" placeholder="City/Location" required>
                <input type="text" name="zone" placeholder="Zone" required>
                <input type="text" name="kebele" placeholder="Kebele" required>
                <input type="text" name="address_detail" placeholder="Detailed Address">
                <input type="number" name="bedrooms" placeholder="Number of Bedrooms" min="0" required>
                <input type="number" name="bathrooms" placeholder="Number of Bathrooms" min="0" required>
                <input type="number" step="0.01" name="price_per_month" placeholder="Price Per Month" min="0" required>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                    <option value="rented">Rented</option>
                    <option value="under_maintenance">Under Maintenance</option>
                </select>
                <input type="file" name="property_image" accept="image/jpeg,image/png,image/gif">
                <button type="submit">Add Property</button>
            </form>
        </div>
    </div>
</body>
</html>