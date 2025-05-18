<?php
session_start();
require_once 'db_connection.php';
require_once 'auth_session.php'; // Ensure user is authenticated

// Initialize message variables
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $chapaApiKey = trim($_POST['chapa_api_key'] ?? '');
    $paypalClientId = trim($_POST['paypal_client_id'] ?? '');

    // Validate inputs (ensure they don't exceed 255 characters)
    if (strlen($chapaApiKey) > 255 || strlen($paypalClientId) > 255) {
        $errorMessage = "API Key or Client ID exceeds maximum length of 255 characters.";
    } else {
        try {
            // Check if a settings row exists
            $settingsQuery = "SELECT id FROM system_settings LIMIT 1";
            $settingsResult = $conn->query($settingsQuery);
            $settings = $settingsResult->fetch_assoc();

            if ($settings) {
                // Update existing settings
                $stmt = $conn->prepare("UPDATE system_settings SET 
                                        chapa_api_key = ?, 
                                        paypal_client_id = ?, 
                                        updated_at = NOW() 
                                        WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sss", $chapaApiKey, $paypalClientId, $settings['id']);
            } else {
                // Insert new settings (unlikely, as schema includes default row)
                $stmt = $conn->prepare("INSERT INTO system_settings 
                                        (id, chapa_api_key, paypal_client_id, created_at, updated_at)
                                        VALUES (UUID(), ?, ?, NOW(), NOW())");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $chapaApiKey, $paypalClientId);
            }

            if ($stmt->execute()) {
                $successMessage = "Payment settings updated successfully!";
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $errorMessage = "Error updating payment settings: " . $e->getMessage();
        }
    }

    // Store messages in session for display on redirect
    $_SESSION['payment_success_message'] = $successMessage;
    $_SESSION['payment_error_message'] = $errorMessage;

    // Redirect back to system_settings.php
    header("Location: system_settings.php");
    exit;
} else {
    // Invalid request method
    header("Location: system_settings.php");
    exit;
}

$conn->close();
?>