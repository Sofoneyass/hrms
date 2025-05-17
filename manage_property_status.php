<?php
include 'auth_session.php';
include 'db_connection.php';

header('Content-Type: application/json');

// Ensure admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$property_id = $input['property_id'] ?? 0;
$action = $input['action'] ?? ''; // 'approve' or 'reject'
$booking_id = $input['booking_id'] ?? null;

if (!$property_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$conn->begin_transaction();
try {
    // Update property status
    if ($action === 'approve') {
        $new_status = 'available'; // For pending properties
        if ($booking_id) {
            $new_status = 'rented'; // For reserved properties with approved booking
        }
        $stmt = $conn->prepare('UPDATE properties SET status = ? WHERE property_id = ?');
        $stmt->bind_param('si', $new_status, $property_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'reject') {
        $new_status = 'pending'; // Rejected properties remain pending or revert
        $stmt = $conn->prepare('UPDATE properties SET status = ? WHERE property_id = ?');
        $stmt->bind_param('si', $new_status, $property_id);
        $stmt->execute();
        $stmt->close();
    }

    // Update booking status if applicable
    if ($booking_id) {
        $booking_status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE booking_id = ?');
        $stmt->bind_param('si', $booking_status, $booking_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['message' => ucfirst($action) . 'd successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>