<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$owner_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Get property ID from query string or POST
$property_id = $_SERVER["REQUEST_METHOD"] == "POST" ? ($_POST['property_id'] ?? '') : ($_GET['property_id'] ?? '');

// Validate property_id format (alphanumeric, hyphens allowed, e.g., UUID)
if (empty($property_id) || !preg_match('/^[a-zA-Z0-9-]+$/', $property_id)) {
    $_SESSION['error_message'] = "Invalid property ID format.";
    header("Location: my_properties.php");
    exit;
}

// Fetch property details to verify ownership
$query = "SELECT title FROM properties WHERE property_id = ? AND owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $property_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    $_SESSION['error_message'] = "Property not found or you do not have permission to delete it.";
    header("Location: my_properties.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token.";
    } elseif (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
        $error_message = "Deletion not confirmed.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update related tables to set property_id to NULL where applicable
            $update_tables = [
                'leases' => 'UPDATE leases SET property_id = NULL WHERE property_id = ?',
                'maintenance_requests' => 'UPDATE maintenance_requests SET property_id = NULL WHERE property_id = ?',
                'invoices' => 'UPDATE invoices SET lease_id = NULL WHERE lease_id IN (SELECT lease_id FROM leases WHERE property_id = ?)'
            ];

            foreach ($update_tables as $table => $query) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $property_id);
                $stmt->execute();
                $stmt->close();
            }

            // Delete the property (cascades to amenities, bookings, favorites, messages, reviews, property_photos)
            $delete_query = "DELETE FROM properties WHERE property_id = ? AND owner_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("ss", $property_id, $owner_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['success_message'] = "Property '{$property['title']}' deleted successfully!";
            header("Location: my_properties.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error deleting property: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Property - JIGJIGAHOMES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a44 0%, #2a4066 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar h2 {
            color: #FFD700;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #FFD700;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 5px;
            min-width: 150px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #FFD700;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 16px;
        }

        input:focus, textarea:focus, select:focus {
            outline: 2px solid #FFD700;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffffff;
            font-weight: normal;
        }

        .submit-btn {
            background: #FFD700;
            color: #1a2a44;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #e6c200;
        }

        .cancel-btn {
            background: #FF6347;
            color: #ffffff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }

        .cancel-btn:hover {
            background: #e5533d;
        }

        .error {
            color: #FF6347;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .success {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .confirmation-text {
            color: #ffffff;
            margin-bottom: 20px;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .header {
                position: relative;
            }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #FFD700;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <a href="owner_dashboard.php">Dashboard</a>
        <a href="my_properties.php" class="active">my Properties</a>
        <a href="manage_leases.php">Manage Leases</a>
        <a href="view_payments.php">View Payments</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="header">
                       <span class="menu-toggle d-md-none">&#9776;</span>
            <h1>Delete Property</h1>
            <div class="profile-dropdown">
                <button class="profile-btn">Owner ▼</button>
                <div class="dropdown-content">
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>

        <div class="form-container">
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <p class="confirmation-text">
                Are you sure you want to delete the property: <strong><?php echo htmlspecialchars($property['title']); ?></strong>?
                This action cannot be undone.
            </p>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property_id); ?>">
                <input type="hidden" name="confirm_delete" value="yes">
                <button type="submit" class="submit-btn">Yes, Delete</button>
                <a href="my_properties.php" class="cancel-btn">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        // Optional sidebar toggle script for mobile
        document.querySelector('.menu-toggle')?.addEventListener('click', () => {
            document.querySelector('.sidebar')?.classList.toggle('active');
        });
    </script>
</body>
</html>
