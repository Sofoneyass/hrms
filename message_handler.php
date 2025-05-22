<?php
session_start();
require_once 'db_connection.php';

// Check login & role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['tenant', 'owner'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Access denied. Only tenants and owners can access messages.']));
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch messages for a property
    if (!isset($_GET['property_id']) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $_GET['property_id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid property ID']));
    }

    $property_id = $_GET['property_id'];

    // Validate property access
    if ($role === 'tenant') {
        $query = $conn->prepare("
            SELECT p.property_id
            FROM properties p
            LEFT JOIN leases l ON l.property_id = p.property_id AND l.tenant_id = ? AND l.status = 'active'
            LEFT JOIN bookings b ON b.property_id = p.property_id AND b.tenant_id = ? AND b.status = 'confirmed'
            WHERE p.property_id = ? AND (l.lease_id IS NOT NULL OR b.booking_id IS NOT NULL)
        ");
        $query->bind_param("sss", $user_id, $user_id, $property_id);
    } else {
        $query = $conn->prepare("
            SELECT property_id
            FROM properties
            WHERE property_id = ? AND owner_id = ?
        ");
        $query->bind_param("ss", $property_id, $user_id);
    }
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows === 0) {
        $query->close();
        $conn->close();
        http_response_code(403);
        die(json_encode(['error' => 'You do not have access to this property']));
    }
    $query->close();

    // Mark messages as read
    $query = $conn->prepare("UPDATE messages SET status = 'read' WHERE property_id = ? AND receiver_id = ? AND status = 'unread'");
    $query->bind_param("ss", $property_id, $user_id);
    $query->execute();
    $query->close();

    // Fetch messages
    $query = $conn->prepare("
        SELECT m.message_id, m.sender_id, m.receiver_id, m.message, m.status, m.sent_at,
               u.full_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.property_id = ?
        ORDER BY m.sent_at ASC
    ");
    $query->bind_param("s", $property_id);
    $query->execute();
    $result = $query->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $query->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($messages);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send a new message
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }

    $property_id = filter_var($_POST['property_id'] ?? '', FILTER_VALIDATE_REGEXP, [
        'options' => ['regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i']
    ]);
    $receiver_id = filter_var($_POST['receiver_id'] ?? '', FILTER_VALIDATE_REGEXP, [
        'options' => ['regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i']
    ]);
    $message = filter_var(trim($_POST['message'] ?? ''), FILTER_SANITIZE_STRING);

    if (!$property_id || !$message) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid input data']));
    }

    // Validate property and determine receiver
    if ($role === 'tenant') {
        $query = $conn->prepare("
            SELECT p.owner_id
            FROM properties p
            LEFT JOIN leases l ON l.property_id = p.property_id AND l.tenant_id = ? AND l.status = 'active'
            LEFT JOIN bookings b ON b.property_id = p.property_id AND b.tenant_id = ? AND b.status = 'confirmed'
            WHERE p.property_id = ? AND (l.lease_id IS NOT NULL OR b.booking_id IS NOT NULL)
        ");
        $query->bind_param("sss", $user_id, $user_id, $property_id);
    } else {
        $query = $conn->prepare("
            SELECT p.owner_id
            FROM properties p
            WHERE p.property_id = ? AND p.owner_id = ?
        ");
        $query->bind_param("ss", $property_id, $user_id);
    }
    $query->execute();
    $result = $query->get_result();
    $property = $result->fetch_assoc();
    $query->close();

    if (!$property) {
        $conn->close();
        http_response_code(400);
        die(json_encode(['error' => 'Property not found or not accessible']));
    }

    // Determine receiver_id
    if ($role === 'tenant') {
        $receiver_id = $property['owner_id'];
    } else {
        // For owners, find a tenant with an active lease or confirmed booking
        $query = $conn->prepare("
            SELECT l.tenant_id
            FROM leases l
            WHERE l.property_id = ? AND l.status = 'active'
            UNION
            SELECT b.tenant_id
            FROM bookings b
            WHERE b.property_id = ? AND b.status = 'confirmed'
            LIMIT 1
        ");
        $query->bind_param("ss", $property_id, $property_id);
        $query->execute();
        $result = $query->get_result();
        $tenant = $result->fetch_assoc();
        $query->close();
        $receiver_id = $tenant['tenant_id'] ?? null;
        if (!$receiver_id) {
            $conn->close();
            http_response_code(400);
            die(json_encode(['error' => 'No active tenant found for this property']));
        }
    }

    // Validate receiver
    $query = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
    $query->bind_param("s", $receiver_id);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows === 0) {
        $query->close();
        $conn->close();
        http_response_code(400);
        die(json_encode(['error' => 'Invalid receiver']));
    }
    $query->close();

    // Check if this is a reply
    $query = $conn->prepare("SELECT message_id FROM messages WHERE property_id = ? AND sender_id = ? AND receiver_id = ?");
    $query->bind_param("sss", $property_id, $receiver_id, $user_id);
    $query->execute();
    $result = $query->get_result();
    $is_reply = $result->num_rows > 0;
    $query->close();

    // Insert message
    $message_id = sprintf(
        '%s-%s-%s-%s-%s',
        substr(md5(uniqid()), 0, 8),
        substr(md5(uniqid()), 8, 4),
        substr(md5(uniqid()), 12, 4),
        substr(md5(uniqid()), 16, 4),
        substr(md5(uniqid()), 20, 12)
    );
    $status = $is_reply ? 'replied' : 'unread';
    $query = $conn->prepare("
        INSERT INTO messages (message_id, sender_id, receiver_id, property_id, message, status, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $query->bind_param("ssssss", $message_id, $user_id, $receiver_id, $property_id, $message, $status);
    if ($query->execute()) {
        // Mark previous messages as replied
        if ($is_reply) {
            $query = $conn->prepare("UPDATE messages SET status = 'replied' WHERE property_id = ? AND sender_id = ? AND receiver_id = ? AND status IN ('unread', 'read')");
            $query->bind_param("sss", $property_id, $receiver_id, $user_id);
            $query->execute();
        }

        // Insert notification
        $notification_id = sprintf(
            '%s-%s-%s-%s-%s',
            substr(md5(uniqid()), 0, 8),
            substr(md5(uniqid()), 8, 4),
            substr(md5(uniqid()), 12, 4),
            substr(md5(uniqid()), 16, 4),
            substr(md5(uniqid()), 20, 12)
        );
        $notification_message = "New message received for property: " . $property_id;
        $query = $conn->prepare("INSERT INTO notifications (notification_id, user_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $query->bind_param("sss", $notification_id, $receiver_id, $notification_message);
        $query->execute();

        $query->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        $query->close();
        $conn->close();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
}
?>