<?php 
include 'db_connection.php';
include 'auth_session.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid property ID.");
}

$property_id = intval($_GET['id']);

// Fetch property details
$property_sql = "
SELECT 
    p.*, 
    u.full_name AS owner_name, 
    u.email AS owner_email, 
    u.user_id AS owner_id
FROM properties p
LEFT JOIN users u ON p.owner_id = u.user_id
WHERE p.property_id = ?
";
$stmt = $conn->prepare($property_sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property_result = $stmt->get_result();

if ($property_result->num_rows === 0) {
    die("Property not found.");
}

$property = $property_result->fetch_assoc();

// Fetch property photos (with full/relative path stored in DB)
$photos_sql = "SELECT photo_url FROM property_photos WHERE property_id = ?";
$photo_stmt = $conn->prepare($photos_sql);
$photo_stmt->bind_param("i", $property_id);
$photo_stmt->execute();
$photos_result = $photo_stmt->get_result();

$photos = [];
while ($row = $photos_result->fetch_assoc()) {
    $photo_path = $row['photo_url'];
    $photos[] = file_exists($photo_path) ? $photo_path : 'uploads/default.png';
    
}


// Handle messaging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $conn->real_escape_string($_POST['message']);
    $tenant_id = $_SESSION['user_id'];

    $message_sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())";
    $message_stmt = $conn->prepare($message_sql);
    $message_stmt->bind_param("iis", $tenant_id, $property['owner_id'], $message);
    $message_stmt->execute();
    $message_sent = $message_stmt->affected_rows > 0;
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $tenant_id = $_SESSION['user_id'];
    $booking_sql = "INSERT INTO bookings (tenant_id, property_id, status, start_date) VALUES (?, ?, 'pending', NOW())";
    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("ii", $tenant_id, $property_id);
    $booking_stmt->execute();
    $booking_success = $booking_stmt->affected_rows > 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($property['title']); ?> - Property Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .property-details {
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }

        .carousel {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .carousel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .carousel img.active {
            display: block;
        }

        .carousel-buttons {
            position: absolute;
            width: 100%;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 10px;
        }

        .carousel-buttons button {
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }

        .property-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .property-info h2 {
            margin-top: 0;
        }

        .owner-info {
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2b7cff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }

        .message-form textarea, .booking-btn {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .message-form textarea {
            height: 100px;
        }

        .message-form button, .booking-btn {
            background-color: #2b7cff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .message-form button:hover, .booking-btn:hover {
            background-color: #1a5bb8;
        }
    </style>
</head>
<body>

<div class="property-details">
    <h1><?php echo htmlspecialchars($property['title']); ?></h1>

    <!-- Carousel -->
    <div class="carousel">
        <?php if (!empty($photos)): ?>
            <?php foreach ($photos as $index => $photo): ?>
                <img src="<?= htmlspecialchars($photo) ?>" class="<?= $index === 0 ? 'active' : '' ?>" alt="Property Photo">
            <?php endforeach; ?>
        <?php else: ?>
            <img src="uploads/default.png" class="active" alt="Default Property Photo">
        <?php endif; ?>

        <div class="carousel-buttons">
            <button onclick="changeSlide(-1)">❮</button>
            <button onclick="changeSlide(1)">❯</button>
        </div>
    </div>

    <!-- Info -->
    <div class="property-info">
        <h2>Description</h2>
        <p><?= nl2br(htmlspecialchars($property['description'])) ?></p>

        <p><strong>Location:</strong> <?= htmlspecialchars($property['location']) ?></p>
        <p><strong>zone:</strong> <?= $property['zone'] ?></p>
        <p><strong>kebele:</strong> <?= $property['kebele'] ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($property['address_detail']) ?></p>
        <p><strong>Bedrooms:</strong> <?= $property['bedrooms'] ?></p>
        <p><strong>Bathrooms:</strong> <?= $property['bathrooms'] ?></p>
        <p><strong>Rent:</strong> BIRR <?= number_format($property['price_per_month'], 2) ?> / month</p>
        <p><strong>Status:</strong> <?= ucfirst($property['status']) ?></p>
        <a href="create_lease.php?property_id=<?= $property['property_id'] ?>">Lease This Property</a>

        <div class="owner-info">
            <p><strong>Listed By:</strong> <?= htmlspecialchars($property['owner_name']) ?></p>
        </div>

        <!-- Messaging -->
        <?php if ($_SESSION['role'] === 'tenant'): ?>
            <h3>Send a Message to the Owner</h3>
            <form method="POST" class="message-form">
                <textarea name="message" placeholder="Type your message here..." required></textarea>
                <button type="submit">Send Message</button>
            </form>
            <?php if (isset($message_sent) && $message_sent): ?>
                <p>Your message has been sent!</p>
            <?php endif; ?>

            <!-- Booking -->
            <h3>Book this Property</h3>
            <form method="POST">
                <button type="submit" name="book" class="booking-btn">Book Property</button>
            </form>
            <?php if (isset($booking_success) && $booking_success): ?>
                <p>Your booking has been confirmed!</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll(".carousel img");

    function changeSlide(direction) {
        slides[currentSlide].classList.remove("active");
        currentSlide = (currentSlide + direction + slides.length) % slides.length;
        slides[currentSlide].classList.add("active");
    }
</script>

</body>
</html>
