<?php
require_once 'db_connection.php';

$conn->query("
    UPDATE bookings SET status = 'expired'
    WHERE status = 'pending' AND created_at < NOW() - INTERVAL 3 DAY
");

$conn->query("
    UPDATE properties SET status = 'available'
    WHERE property_id IN (
        SELECT property_id FROM bookings
        WHERE status = 'expired'
    )
");
