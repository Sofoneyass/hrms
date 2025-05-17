<?php
include 'db_connection.php';

if (!isset($_GET['action'], $_GET['id'])) {
    die("Invalid request.");
}

$action = $_GET['action'];
$id = intval($_GET['id']);
$status = ($action === 'approve') ? 'approved' : 'rejected';

$sql = "UPDATE properties SET status = ? WHERE property_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $status, $id);
$stmt->execute();

header("Location: manage_properties.php");
exit;
