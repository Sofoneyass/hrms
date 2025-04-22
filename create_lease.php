<?php
session_start();
include 'db_connection.php';

// Check login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    die("Access denied. Only tenants can create leases.");
}

// Get user ID from session
$tenant_id = $_SESSION['user_id'];

// Get property_id from URL
if (!isset($_GET['property_id'])) {
    die("Property ID is required.");
}
$property_id = intval($_GET['property_id']);

// Fetch property rent (optional: auto-fill rent)
$query = $conn->prepare("SELECT price_per_month FROM properties WHERE property_id = ?");
$query->bind_param("i", $property_id);
$query->execute();
$result = $query->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    die("Property not found.");
}
?>

<h2>Create Lease</h2>
<form method="POST" action="create_lease_handler.php">
    <input type="hidden" name="tenant_id" value="<?= $tenant_id ?>">
    <input type="hidden" name="property_id" value="<?= $property_id ?>">

    <label>Start Date:</label>
    <input type="date" name="start_date" required><br>

    <label>End Date:</label>
    <input type="date" name="end_date" required><br>

    <label>Monthly Rent:</label>
    <input type="number" step="0.01" name="monthly_rent" value="<?= $property['price_per_month'] ?>" required><br>

    <input type="submit" value="Create Lease">
</form>
