<?php
$pageTitle = "Edit User";
require_once 'db_connection.php';
require_once 'admin_header.php';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$userId = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("s", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manage_users.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Validation
    if (empty($fullName)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if ($phone && !preg_match("/^\+?[1-9]\d{1,14}$/", $phone)) {
        $errors[] = "Invalid phone number.";
    }
    if (!in_array($role, ['admin', 'owner', 'tenant'])) {
        $errors[] = "Invalid role.";
    }
    if (!in_array($status, ['active', 'inactive', 'pending'])) {
        $errors[] = "Invalid status.";
    }

    // Check for email uniqueness (excluding current user)
    $emailStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $emailStmt->bind_param("ss", $email, $userId);
    $emailStmt->execute();
    if ($emailStmt->get_result()->num_rows > 0) {
        $errors[] = "Email is already in use.";
    }
    $emailStmt->close();

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET 
                                full_name = ?, 
                                email = ?, 
                                phone = ?, 
                                role = ?, 
                                status = ?, 
                                updated_at = NOW() 
                                WHERE user_id = ?");
        $phone = $phone ?: null; // Allow null phone
        $stmt->bind_param("ssssss", $fullName, $email, $phone, $role, $status, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User updated successfully!";
            header("Location: manage_users.php");
            exit;
        } else {
            $errors[] = "Error updating user: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="main-content">
    <div class="header">
        <h1>Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h1>
        <a href="manage_users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit User Details</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="owner" <?php echo $user['role'] === 'owner' ? 'selected' : ''; ?>>Owner</option>
                            <option value="tenant" <?php echo $user['role'] === 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>