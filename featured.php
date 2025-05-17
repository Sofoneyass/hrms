<?php

require_once 'db_connection.php';

// Initialize CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// Fetch all properties with their first photo
$sql = "SELECT p.*, 
        (SELECT photo_url FROM property_photos WHERE property_id = p.property_id LIMIT 1) AS photo 
        FROM properties p 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);

// Initialize favorited properties array
$favorited_properties = [];

if (isset($user_id)) {
    // Ensure this user is a tenant before checking favorites
    $role_check = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $role_check->bind_param("i", $user_id);
    $role_check->execute();
    $role_result = $role_check->get_result();
    $user_role = $role_result->fetch_assoc()['role'];
    $role_check->close();

    if ($user_role === 'tenant') {
        $stmt = $conn->prepare("SELECT property_id FROM favorites WHERE tenant_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $fav_result = $stmt->get_result();
        while ($row = $fav_result->fetch_assoc()) {
            $favorited_properties[] = $row['property_id'];
        }
        $stmt->close();
    }
}
?>

<section class="featured-properties">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured <span>Properties</span></h2>
            <p class="section-subtitle">Premium homes available in Jigjiga</p>
        </div>

        <div class="properties-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="property-card">
                        <div class="property-image-container">
                            <img src="<?= htmlspecialchars($row['photo'] ?? 'Uploads/default.png') ?>" 
                                 alt="<?= htmlspecialchars($row['title']) ?>" 
                                 class="property-image">
                            <span class="property-badge <?= htmlspecialchars($row['status']) ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                            <?php if ($user_id): ?>
                                <button class="favorite-btn <?= in_array($row['property_id'], $favorited_properties) ? 'favorited' : '' ?>" 
                                        data-property-id="<?= $row['property_id'] ?>" 
                                        title="<?= in_array($row['property_id'], $favorited_properties) ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                                    <i class="<?= in_array($row['property_id'], $favorited_properties) ? 'fas' : 'far' ?> fa-heart"></i>
                                </button>
                            <?php endif; ?>
                            <div class="property-overlay">
                                <a href="property_detail.php?id=<?= $row['property_id'] ?>" class="quick-view-btn">
                                    <i class="fas fa-expand"></i> Quick View
                                </a>
                            </div>
                        </div>
                        
                        <div class="property-content">
                            <div class="property-meta">
                                <span class="property-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($row['location']) ?> — <?= htmlspecialchars($row['address_detail']) ?>
                                </span>
                                <span class="property-size">
                                    <i class="fas fa-ruler-combined"></i>
                                    <?= intval($row['square_meters'] ?? 0) ?> sqm
                                </span>
                            </div>
                            
                            <h3 class="property-title"><?= htmlspecialchars($row['title']) ?></h3>
                            
                            <div class="property-features">
                                <span><i class="fas fa-bed"></i> <?= intval($row['bedrooms']) ?> Beds</span>
                                <span><i class="fas fa-bath"></i> <?= intval($row['bathrooms']) ?> Baths</span>
                                <span><i class="fas fa-car"></i> <?= intval($row['parking_spaces'] ?? 0) ?> Parking</span>
                            </div>
                            
                            <div class="property-footer">
                                <p class="property-price">BIRR <?= number_format($row['price_per_month'], 2) ?><span>/month</span></p>
                                <div class="property-actions">
                                    <a href="property_detail.php?id=<?=$row['property_id'] ?>" class="details-btn">
                                        Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <a href="reserve_property.php?id=<?= $row['property_id'] ?>" class="reserve-btn">
                                        Reserve Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-properties">
                    <i class="fas fa-home"></i>
                    <h3>No Properties Available</h3>
                    <p>Check back soon for new listings in Jigjiga</p>
                    <a href="#" class="browse-btn">Browse Other Areas</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="view-all-container">
            <a href="properties.php" class="view-all-btn">
                View All Properties <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<style>
    /* Featured Properties Section */
    .featured-properties {
        padding: 80px 0;
        background-color: #f8faf9;
        position: relative;
    }

    body.dark-mode .featured-properties {
        background-color: #121212;
    }

    .featured-properties .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: #1e3c2b;
        margin-bottom: 10px;
    }

    body.dark-mode .section-title {
        color: #f8f9fa;
    }

    .section-title span {
        color: #2a7f62;
    }

    body.dark-mode .section-title span {
        color: #f0c14b;
    }

    .section-subtitle {
        color: #666;
        font-size: 1.1rem;
    }

    body.dark-mode .section-subtitle {
        color: #aaa;
    }

    /* Properties Grid */
    .properties-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    /* Property Card */
    .property-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
    }

    body.dark-mode .property-card {
        background: #1e3c2b;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .property-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(42, 127, 98, 0.15);
    }

    body.dark-mode .property-card:hover {
        box-shadow: 0 15px 40px rgba(240, 193, 75, 0.1);
    }

    /* Property Image */
    .property-image-container {
        position: relative;
        height: 220px;
        overflow: hidden;
    }

    .property-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .property-card:hover .property-image {
        transform: scale(1.05);
    }

    .property-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        z-index: 2;
    }

    .property-badge.active {
        background: #2ecc71;
    }

    .property-badge.inactive {
        background: #e74c3c;
    }

    .property-badge.pending {
        background: #f39c12;
    }

    /* Favorite Button */
    .favorite-btn {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2;
        transition: all 0.3s ease;
    }

    body.dark-mode .favorite-btn {
        background: rgba(30, 60, 43, 0.9);
    }

    .favorite-btn i {
        font-size: 18px;
        color: #666;
    }

    .favorite-btn.favorited i {
        color: #e74c3c;
    }

    .favorite-btn:hover {
        background: #f0c14b;
    }

    .favorite-btn:hover i {
        color: #1e3c2b;
    }

    body.dark-mode .favorite-btn:hover i {
        color: #f8f9fa;
    }

    .property-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(30, 60, 43, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1;
    }

    .property-card:hover .property-overlay {
        opacity: 1;
    }

    .quick-view-btn {
        color: white;
        background: rgba(240, 193, 75, 0.9);
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .quick-view-btn:hover {
        background: #f0c14b;
    }

    /* Property Content */
    .property-content {
        padding: 20px;
    }

    .property-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 0.85rem;
        color: #666;
    }

    body.dark-mode .property-meta {
        color: #aaa;
    }

    .property-meta i {
        margin-right: 5px;
        color: #2a7f62;
    }

    body.dark-mode .property-meta i {
        color: #f0c14b;
    }

    .property-title {
        font-size: 1.3rem;
        margin-bottom: 15px;
        color: #333;
    }

    body.dark-mode .property-title {
        color: #f8f9fa;
    }

    .property-features {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: #666;
    }

    body.dark-mode .property-features {
        color: #aaa;
    }

    .property-features i {
        margin-right: 5px;
        color: #2a7f62;
    }

    body.dark-mode .property-features i {
        color: #f0c14b;
    }

    /* Property Footer */
    .property-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    body.dark-mode .property-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .property-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2a7f62;
        margin: 0;
    }

    body.dark-mode .property-price {
        color: #f0c14b;
    }

    .property-price span {
        font-size: 0.9rem;
        font-weight: 400;
        color: #666;
    }

    body.dark-mode .property-price span {
        color: #aaa;
    }

    .property-actions {
        display: flex;
        gap: 10px;
    }

    .details-btn {
        color: #2a7f62;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }

    body.dark-mode .details-btn {
        color: #f0c14b;
    }

    .details-btn:hover {
        color: #1e3c2b;
    }

    body.dark-mode .details-btn:hover {
        color: #f8f9fa;
    }

    .reserve-btn {
        background: #2a7f62;
        color: white;
        padding: 8px 15px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    body.dark-mode .reserve-btn {
        background: #f0c14b;
        color: #1e3c2b;
    }

    .reserve-btn:hover {
        background: #1e3c2b;
    }

    body.dark-mode .reserve-btn:hover {
        background: #e2b33a;
    }

    /* No Properties */
    .no-properties {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: rgba(42, 127, 98, 0.05);
        border-radius: 12px;
        margin: 20px 0;
    }

    body.dark-mode .no-properties {
        background: rgba(240, 193, 75, 0.05);
    }

    .no-properties i {
        font-size: 3rem;
        color: #2a7f62;
        margin-bottom: 20px;
    }

    body.dark-mode .no-properties i {
        color: #f0c14b;
    }

    .no-properties h3 {
        font-size: 1.5rem;
        margin-bottom: 10px;
        color: #333;
    }

    body.dark-mode .no-properties h3 {
        color: #f8f9fa;
    }

    .no-properties p {
        color: #666;
        margin-bottom: 20px;
    }

    body.dark-mode .no-properties p {
        color: #aaa;
    }

    .browse-btn {
        background: #2a7f62;
        color: white;
        padding: 10px 25px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    body.dark-mode .browse-btn {
        background: #f0c14b;
        color: #1e3c2b;
    }

    .browse-btn:hover {
        background: #1e3c2b;
    }

    body.dark-mode .browse-btn:hover {
        background: #e2b33a;
    }

    /* View All Button */
    .view-all-container {
        text-align: center;
        margin-top: 30px;
    }

    .view-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #2a7f62;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    body.dark-mode .view-all-btn {
        color: #f0c14b;
    }

    .view-all-btn:hover {
        color: #1e3c2b;
        transform: translateX(5px);
    }

    body.dark-mode .view-all-btn:hover {
        color: #f8f9fa;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .properties-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .featured-properties {
            padding: 60px 0;
        }
        
        .section-title {
            font-size: 2rem;
        }
    }

    @media (max-width: 480px) {
        .properties-grid {
            grid-template-columns: 1fr;
        }
        
        .property-features {
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .property-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        
        favoriteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const propertyId = button.dataset.propertyId;
                const isFavorited = button.classList.contains('favorited');
                const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

                // If user is not logged in, redirect to login
                <?php if (!$user_id): ?>
                    alert('Please log in to add properties to your favorites.');
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
                        button.classList.toggle('favorited');
                        const icon = button.querySelector('i');
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                        button.title = isFavorited ? 'Add to Favorites' : 'Remove from Favorites';
                    } else {
                        alert(data.message || 'An error occurred.');
                    }
                })
                .catch(error => {
                    console.error('Error toggling favorite:', error);
                    alert('An error occurred while processing your request.');
                });
            });
        });
    });
</script>