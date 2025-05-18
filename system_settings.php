<?php
$pageTitle = "System Settings";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get system settings
$settingsQuery = "SELECT * FROM system_settings LIMIT 1";
$settings = $conn->query($settingsQuery)->fetch_assoc();

// Handle general settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['chapa_api_key'])) {
    $siteName = $conn->real_escape_string($_POST['site_name']);
    $currency = $conn->real_escape_string($_POST['currency']);
    $timezone = $conn->real_escape_string($_POST['timezone']);
    $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    if ($settings) {
        // Update existing settings
        $updateQuery = "UPDATE system_settings SET 
                        site_name = '$siteName',
                        currency = '$currency',
                        timezone = '$timezone',
                        maintenance_mode = $maintenanceMode,
                        updated_at = NOW()";
        $conn->query($updateQuery);
    } else {
        // Insert new settings
        $insertQuery = "INSERT INTO system_settings 
                        (site_name, currency, timezone, maintenance_mode)
                        VALUES ('$siteName', '$currency', '$timezone', $maintenanceMode)";
        $conn->query($insertQuery);
    }
    
    // Refresh settings
    $settings = $conn->query($settingsQuery)->fetch_assoc();
    $successMessage = "Settings updated successfully!";
}

// Get payment settings messages from session
$paymentSuccessMessage = $_SESSION['payment_success_message'] ?? '';
$paymentErrorMessage = $_SESSION['payment_error_message'] ?? '';
unset($_SESSION['payment_success_message'], $_SESSION['payment_error_message']);
?>

<div class="main-content">
    <div class="header">
        <h1>System Settings</h1>
    </div>

    <?php if (isset($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($paymentSuccessMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($paymentSuccessMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($paymentErrorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($paymentErrorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">General Settings</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'JIGJIGAHOMES'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="currency" class="form-label">Currency</label>
                        <select class="form-select" id="currency" name="currency" required>
                            <option value="ETB" <?php echo ($settings['currency'] ?? 'ETB') === 'ETB' ? 'selected' : ''; ?>>ETB - Ethiopian Birr</option>
                            <option value="USD" <?php echo ($settings['currency'] ?? 'ETB') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo ($settings['currency'] ?? 'ETB') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select" id="timezone" name="timezone" required>
                            <option value="Africa/Addis_Ababa" <?php echo ($settings['timezone'] ?? 'Africa/Addis_Ababa') === 'Africa/Addis_Ababa' ? 'selected' : ''; ?>>Africa/Addis_Ababa</option>
                            <option value="UTC" <?php echo ($settings['timezone'] ?? 'Africa/Addis_Ababa') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">System Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Payment Gateway Settings</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="save_payment_settings.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="chapa_api_key" class="form-label">Chapa API Key</label>
                        <input type="password" class="form-control" id="chapa_api_key" name="chapa_api_key" 
                               value="<?php echo htmlspecialchars($settings['chapa_api_key'] ?? ''); ?>" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label for="paypal_client_id" class="form-label">PayPal Client ID</label>
                        <input type="password" class="form-control" id="paypal_client_id" name="paypal_client_id" 
                               value="<?php echo htmlspecialchars($settings['paypal_client_id'] ?? ''); ?>" maxlength="255">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">System Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">PHP Version</label>
                        <input type="text" class="form-control" value="<?php echo phpversion(); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MySQL Version</label>
                        <input type="text" class="form-control" value="<?php echo $conn->server_info; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Server Software</label>
                        <input type="text" class="form-control" value="<?php echo $_SERVER['SERVER_SOFTWARE']; ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Last Updated</label>
                        <input type="text" class="form-control" value="<?php echo $settings['updated_at'] ?? 'Never'; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Max Filesize</label>
                        <input type="text" class="form-control" value="<?php echo ini_get('upload_max_filesize'); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Memory Limit</label>
                        <input type="text" class="form-control" value="<?php echo ini_get('memory_limit'); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>