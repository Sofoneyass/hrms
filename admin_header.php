<?php
// Start session and check authentication
require_once 'auth_session.php';

// Set page title dynamically
$pageTitle = isset($pageTitle) ? $pageTitle : 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - JIGJIGAHOMES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #f0c14b; /* Gold from footer */
            --secondary: #2a7f62; /* Green from footer */
            --dark: #1e3c2b; /* Darker green from footer */
            --light: #f8f9fa; /* Unchanged body background */
            --success: #2a7f62; /* Green for success */
            --warning: #f0c14b; /* Gold for warning */
            --danger: #ff5252; /* Unchanged danger */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--dark), var(--secondary));
            color: #fff;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left: 3px solid var(--primary);
        }
        
        .sidebar-menu i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            color: var(--primary);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 2px solid var(--primary);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0;
            color: #fff;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--dark);
        }
        
        .btn-primary:hover {
            background-color: #e2b33a;
            border-color: #e2b33a;
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: #fff;
        }
        
        .btn-secondary:hover {
            background-color: #226b4f;
            border-color: #226b4f;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
            color: #fff;
        }
        
        .btn-success:hover {
            background-color: #226b4f;
            border-color: #226b4f;
        }
        
        .btn-warning {
            background-color: var(--warning);
            border-color: var(--warning);
            color: var(--dark);
        }
        
        .btn-warning:hover {
            background-color: #e2b33a;
            border-color: #e2b33a;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>