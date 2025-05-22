<?php
require_once 'db_connection.php';
require_once 'header.php';
require_once 'featured.php';
require_once 'footer.php';


// Fetch all properties with their first photo (if any)
$sql = "SELECT p.*, 
        (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo 
        FROM properties p ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Properties</title>
    
</head>
<body>


</body>
</html>
