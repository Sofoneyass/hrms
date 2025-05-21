<?php
include 'db_connection.php';
include 'header.php';

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate UUID format
if (!isset($_GET['id']) || !preg_match('/^[a-f0-9\-]{36}$/i', $_GET['id'])) {
    die("Invalid property ID.");
}
$property_id = $_GET['id'];

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
$stmt->bind_param("s", $property_id); // UUID is string
if (!$stmt->execute()) {
    error_log("Error fetching property: " . $stmt->error);
    die("Database error.");
}
$property_result = $stmt->get_result();

if ($property_result->num_rows === 0) {
    die("Property not found.");
}

$property = $property_result->fetch_assoc();
$stmt->close();

// Fetch property photos
$photos_sql = "SELECT photo_url FROM property_photos WHERE property_id = ?";
$photo_stmt = $conn->prepare($photos_sql);
$photo_stmt->bind_param("s", $property_id);
if (!$photo_stmt->execute()) {
    error_log("Error fetching photos: " . $photo_stmt->error);
}
$photos_result = $photo_stmt->get_result();

$photos = [];
while ($row = $photos_result->fetch_assoc()) {
    $photo_url = preg_replace('#^Uploads/#', '', $row['photo_url']);
    $full_path = __DIR__ . "/Uploads/" . $photo_url;

    if (file_exists($full_path) && !empty($photo_url)) {
        $photos[] = "/Uploads/" . $photo_url;
    } else {
        $photos[] = "/Uploads/default.png";
    }
}
$photo_stmt->close();

// Check if property is favorited (for tenants)
$is_favorited = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'tenant') {
    $stmt = $conn->prepare("SELECT favorite_id FROM favorites WHERE tenant_id = ? AND property_id = ?");
    $stmt->bind_param("is", $_SESSION['user_id'], $property_id);
    if ($stmt->execute()) {
        $is_favorited = $stmt->get_result()->num_rows > 0;
    } else {
        error_log("Error checking favorite status: " . $stmt->error);
    }
    $stmt->close();
}

// Handle messaging
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $message = $_POST['message'];
    $tenant_id = $_SESSION['user_id'];

    $message_sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())";
    $message_stmt = $conn->prepare($message_sql);
    $message_stmt->bind_param("iis", $tenant_id, $property['owner_id'], $message);
    if ($message_stmt->execute()) {
        $message_sent = true;
    } else {
        error_log("Error sending message: " . $message_stmt->error);
    }
    $message_stmt->close();
}

// Handle booking
$booking_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'], $_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $tenant_id = $_SESSION['user_id'];

    $booking_sql = "INSERT INTO bookings (tenant_id, property_id, status, start_date) VALUES (?, ?, 'pending', NOW())";
    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("is", $tenant_id, $property_id);
    if ($booking_stmt->execute()) {
        $booking_success = true;
    } else {
        error_log("Error creating booking: " . $booking_stmt->error);
    }
    $booking_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> | Jigjiga Homes</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2a7f62;
            --primary-dark: #1e3c2b;
            --accent: #f0c14b;
            --text: #333333;
            --text-light: #6b7280;
            --bg: #f9fafb;
            --card-bg: #ffffff;
            --border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header with premium styling */
        .property-header {
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            padding-bottom: 1.5rem;
        }

        .property-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-dark);
            position: relative;
            display: inline-block;
        }

        .property-header h1::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .property-header .title-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .property-header .location {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--text-light);
            margin: 1.5rem 0;
            font-size: 1.1rem;
        }

        .property-header .price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            background: rgba(42, 127, 98, 0.1);
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            vertical-align: middle;
            background: var(--accent);
            color: var(--primary-dark);
        }

        /* Favorite Button */
        .favorite-btn {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .favorite-btn i {
            font-size: 20px;
            color: #666;
        }

        .favorite-btn.favorited i {
            color: #e74c3c;
        }

        .favorite-btn:hover {
            background: var(--accent);
        }

        .favorite-btn:hover i {
            color: var(--primary-dark);
        }

        /* Gallery with premium effects */
        .gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 3rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .gallery-main {
            grid-column: span 2;
            height: 450px;
            position: relative;
            overflow: hidden;
        }

        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-main:hover img {
            transform: scale(1.03);
        }

        .gallery-thumbs {
            display: grid;
            grid-template-rows: repeat(2, 1fr);
            gap: 1rem;
        }

        .gallery-thumb {
            height: calc(225px - 0.5rem);
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .gallery-thumb:hover img {
            transform: scale(1.05);
            opacity: 0.9;
        }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .detail-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.8rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .detail-card h3 {
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .detail-card h3 i {
            color: var(--accent);
            font-size: 1.4rem;
        }

        .detail-card p {
            color: var(--text-light);
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }

        .detail-card .highlight {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Description Section */
        .description {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .description h2 {
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            position: relative;
            padding-bottom: 0.8rem;
        }

        .description h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--accent);
        }

        .description p {
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        /* Owner Card */
        .owner-card {
            background: linear-gradient(135deg, rgba(30, 60, 43, 0.05) 0%, rgba(42, 127, 98, 0.05) 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 3rem;
            border: 1px solid rgba(42, 127, 98, 0.2);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .owner-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
        }

        .owner-info h4 {
            font-size: 1.3rem;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .owner-info p {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .owner-contact {
            display: flex;
            gap: 1rem;
        }

        .owner-contact a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            transition: all 0.3s ease;
        }

        .owner-contact a:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
        }

        /* CTA Buttons */
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            margin: 3rem 0;
            justify-content: center;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 5px 15px rgba(42, 127, 98, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 60, 43, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(42, 127, 98, 0.1);
            transform: translateY(-3px);
        }

        /* Review Section */
        .review-section {
            background: #ffffffdd;
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-top: 3rem;
        }

        .review-section h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--primary);
            border-left: 5px solid var(--primary);
            padding-left: 10px;
        }

        .review-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .review-form select,
        .review-form textarea {
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            resize: vertical;
            width: 100%;
            background-color: #f9f9f9;
            transition: border-color 0.3s ease;
        }

        .review-form select:focus,
        .review-form textarea:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #fff;
        }

        .review-form button.btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .review-form button.btn-primary:hover {
            background-color: var(--primary-dark);
        }

        /* Reviews List */
        .reviews-list h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #444;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .review-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            background-color: #fafafa;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .review-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .review-content {
            flex-grow: 1;
        }

        .review-content strong {
            font-size: 1.1rem;
            color: #333;
        }

        .review-rating {
            color: #f1c40f;
            margin: 4px 0;
            font-size: 1.2rem;
        }

        .review-content p {
            margin: 0.5rem 0;
            color: #555;
            line-height: 1.4;
        }

        .review-content small {
            color: #999;
            font-size: 0.85rem;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .gallery {
                grid-template-columns: 1fr;
            }
            
            .gallery-main {
                grid-column: span 1;
                height: 350px;
            }
            
            .gallery-thumbs {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: auto;
            }
            
            .gallery-thumb {
                height: 170px;
            }
            
            .owner-card {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .property-header h1 {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 600px) {
            .review-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .review-avatar {
                margin-bottom: 0.5rem;
            }
            
            .review-content {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .gallery-thumbs {
                grid-template-columns: 1fr;
            }
            
            .gallery-thumb {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Property Header -->
        <div class="property-header">
            <div class="title-container">
                <h1>
                    <?php echo htmlspecialchars($property['title']); ?>
                    <span class="status-badge"><?= ucfirst($property['status']) ?></span>
                </h1>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'tenant'): ?>
                    <button class="favorite-btn <?= $is_favorited ? 'favorited' : '' ?>" 
                            data-property-id="<?= $property['property_id'] ?>" 
                            title="<?= $is_favorited ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                            aria-label="<?= $is_favorited ? 'Remove '.htmlspecialchars($property['title']).' from favorites' : 'Add '.htmlspecialchars($property['title']).' to favorites' ?>"
                            aria-pressed="<?= $is_favorited ? 'true' : 'false' ?>">
                        <i class="<?= $is_favorited ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="location">
                <i class="fas fa-map-marker-alt"></i>
                <span><?= htmlspecialchars($property['location']) ?></span>
            </div>
            
            <div class="price">BIRR <?= number_format($property['price_per_month'], 2) ?> / month</div>
        </div>

        <!-- Photo Gallery -->
<?php
// Example: fetch photos from database
// $photos = ['Uploads/image1.jpg', 'Uploads/image2.jpg'];
?>
<div class="gallery">
    <div class="gallery-main">
        <img src="<?= (!empty($photos) && !empty($photos[0])) ? htmlspecialchars($photos[0]) : '/Uploads/default.png' ?>" alt="Main property photo" style="max-width: 100%; height: auto;">
    </div>
</div>

        <!-- Key Details -->
        <div class="details-grid">
            <div class="detail-card">
                <h3><i class="fas fa-home"></i> Property Details</h3>
                <p><span class="highlight"><?= $property['bedrooms'] ?></span> Bedrooms</p>
                <p><span class="highlight"><?= $property['bathrooms'] ?></span> Bathrooms</p>
                <p>Zone <span class="highlight"><?= htmlspecialchars($property['zone'] ?? 'N/A') ?></span></p>
                <p>Kebele <span class="highlight"><?= htmlspecialchars($property['kebele'] ?? 'N/A') ?></span></p>
            </div>
            
            <div class="detail-card">
                <h3><i class="fas fa-map-marked-alt"></i> Location</h3>
                <p><?= htmlspecialchars($property['address_detail']) ?></p>
                <p>Status: <span class="highlight"><?= ucfirst($property['status']) ?></span></p>
                <p>Listed: <span class="highlight"><?= date('M j, Y', strtotime($property['created_at'])) ?></span></p>
            </div>
            
            <div class="detail-card">
                <h3><i class="fas fa-calendar-check"></i> Availability</h3>
                <p>Ready for immediate move-in</p>
                <p>Minimum lease: 12 months</p>
                <p>Security deposit: 1 month rent</p>
            </div>
        </div>

        <!-- Full Description -->
        <div class="description">
            <h2>Property Description</h2>
            <p><?= nl2br(htmlspecialchars($property['description'] ?? 'No description available.')) ?></p>
        </div>

        <!-- Owner Information -->
        <div class="owner-card">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($property['owner_name']) ?>&background=2a7f62&color=fff" alt="Owner" class="owner-avatar">
            
            <div class="owner-info">
                <h4><?= htmlspecialchars($property['owner_name']) ?></h4>
                <p>Property Owner</p>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($property['owner_email']) ?></p>
                
                <div class="owner-contact">
                    <a href="#"><i class="fas fa-phone-alt"></i></a>
                    <a href="#"><i class="fas fa-envelope"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="cta-buttons">
            <a href="create_lease.php?property_id=<?= $property['property_id'] ?>" class="btn btn-primary">
                <i class="fas fa-file-signature"></i> Lease Now
            </a>
            <button class="btn btn-outline">
                <i class="fas fa-question-circle"></i> Ask Question
            </button>
        </div>

        <!-- Review Section -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'tenant'): ?>
            <div class="review-section">
                <h2>Leave a Review</h2>
                <form action="submit_review.php" method="POST" class="review-form">
                    <input type="hidden" name="property_id" value="<?= $property['property_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <label for="rating">Rating:</label>
                    <select name="rating" id="rating" required aria-label="Select rating">
                        <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                        <option value="4">⭐⭐⭐⭐ (Good)</option>
                        <option value="3">⭐⭐⭐ (Average)</option>
                        <option value="2">⭐⭐ (Poor)</option>
                        <option value="1">⭐ (Very Poor)</option>
                    </select>

                    <label for="comment">Comment:</label>
                    <textarea name="comment" id="comment" placeholder="Write your review..." required aria-label="Write your review"></textarea>

                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
                <div class="reviews-list">
                    <h2>Tenant Reviews</h2>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT r.*, u.full_name, u.profile_image
                        FROM reviews r
                        JOIN users u ON r.tenant_id = u.user_id
                        WHERE r.property_id = ?
                        ORDER BY r.created_at DESC
                    ");
                    $stmt->bind_param("i", $property_id);
                    if (!$stmt->execute()) {
                        error_log("Error fetching reviews: " . $stmt->error);
                    }
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                        <div class="review-card">
                            <img src="Uploads/<?= htmlspecialchars($row['profile_image'] ?? 'default.jpg') ?>" class="review-avatar" alt="User Avatar">
                            <div class="review-content">
                                <strong><?= htmlspecialchars($row['full_name']) ?></strong>
                                <div class="review-rating"><?= str_repeat("⭐", $row['rating']) ?></div>
                                <p><?= nl2br(htmlspecialchars($row['comment'])) ?></p>
                                <small>Reviewed on <?= date("F j, Y", strtotime($row['created_at'])) ?></small>
                            </div>
                        </div>
                    <?php endwhile;
                    else: ?>
                        <p>No reviews yet. Be the first to review this property!</p>
                    <?php endif; ?>
                    <?php $stmt->close(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Gallery interaction
        document.querySelectorAll('.gallery-thumb').forEach(thumb => {
            thumb.addEventListener('click', function() {
                const mainImg = document.querySelector('.gallery-main img');
                const thumbImg = this.querySelector('img');
                mainImg.src = thumbImg.src;
                
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.style.opacity = '1';
                }, 300);
            });
        });

        // Favorite button interaction
        document.addEventListener('DOMContentLoaded', () => {
            const favoriteButton = document.querySelector('.favorite-btn');
            if (favoriteButton) {
                favoriteButton.addEventListener('click', () => {
                    const propertyId = favoriteButton.dataset.propertyId;
                    const isFavorited = favoriteButton.classList.contains('favorited');
                    const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

                    // If user is not logged in, redirect to login
                    <?php if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant'): ?>
                        alert('Please log in as a tenant to add properties to your favorites.');
                        window.location.href = 'index.php';
                        return;
                    <?php endif; ?>

                    // Send AJAX request to toggle favorite
                    fetch('toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            favoriteButton.classList.toggle('favorited');
                            const icon = favoriteButton.querySelector('i');
                            icon.classList.toggle('far');
                            icon.classList.toggle('fas');
                            favoriteButton.setAttribute('aria-pressed', isFavorited ? 'false' : 'true');
                            favoriteButton.title = isFavorited ? 'Add to Favorites' : 'Remove from Favorites';
                            favoriteButton.setAttribute('aria-label', isFavorited ? `Add ${<?= json_encode($property['title']) ?>} to favorites` : `Remove ${<?= json_encode($property['title']) ?>} from favorites`);
                            showToast(data.message);
                        } else {
                            alert(data.message || 'An error occurred while toggling favorite.');
                        }
                    })
                    .catch(error => {
                        console.error('Error toggling favorite:', error);
                        alert('Failed to connect to the server. Please try again later.');
                    });
                });
            }

            // Toast notification function
            function showToast(message) {
                let toast = document.querySelector('.toast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.className = 'toast';
                    document.body.appendChild(toast);
                }
                toast.textContent = message;
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }

            // Show feedback for messaging and booking
            <?php if ($message_sent): ?>
                showToast('Message sent to owner successfully.');
            <?php endif; ?>
            <?php if ($booking_success): ?>
                showToast('Booking request submitted successfully.');
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>