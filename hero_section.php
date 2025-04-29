<!-- Hero Section -->
 <?php
require_once 'db_connection.php';  

// Get total properties
$propertyCountQuery = "SELECT COUNT(*) AS total_properties FROM properties";
$propertyCountResult = mysqli_query($conn, $propertyCountQuery);
$propertyCount = mysqli_fetch_assoc($propertyCountResult)['total_properties'] ?? 0;

// Get total tenants
$tenantCountQuery = "SELECT COUNT(*) AS total_tenants FROM users WHERE role = 'tenant'";
$tenantCountResult = mysqli_query($conn, $tenantCountQuery);
$tenantCount = mysqli_fetch_assoc($tenantCountResult)['total_tenants'] ?? 0;

// Get total owners
$ownerCountQuery = "SELECT COUNT(*) AS total_owners FROM users WHERE role = 'owner'";
$ownerCountResult = mysqli_query($conn, $ownerCountQuery);
$ownerCount = mysqli_fetch_assoc($ownerCountResult)['total_owners'] ?? 0;
?>
<section class="hero">
    <!-- Background Image with Overlay -->
    <div class="hero-background">
        <div class="hero-overlay"></div>
        <img src="img/jigjigacity.jpeg" alt="Jigjiga City View" class="hero-image">
    </div>

    <!-- Hero Content -->
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">Find Your Perfect Home in <span>Jigjiga</span></h1>
            <p class="hero-subtitle">Premium rentals in the heart of the Somali Region</p>
            
            <!-- Search Bar -->
            <div class="hero-search">
                <form action="/properties" method="GET">
                    <div class="search-input-group">
                        <input 
                            type="text" 
                            name="location" 
                            placeholder="Search by neighborhood (e.g., Karaamarda, Aw-Dale)" 
                            class="search-input"
                        >
                        <select name="type" class="search-select">
                            <option value="">Any Type</option>
                            <option value="apartment">Apartment</option>
                            <option value="villa">Villa</option>
                            <option value="house">House</option>
                        </select>
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="hero-stats">
    <div class="stat-item">
        <span class="stat-number"><?= $propertyCount ?></span>
        <span class="stat-label">Properties</span>
    </div>
    <div class="stat-item">
        <span class="stat-number"><?= $tenantCount ?></span>
        <span class="stat-label">Tenants</span>
    </div>
    <div class="stat-item">
        <span class="stat-number"><?= $ownerCount ?></span>
        <span class="stat-label">Owners</span>
    </div>
</div>
</section>

<!-- CSS Styling -->
<style>
    /* ===== Hero Section ===== */
    .hero {
        position: relative;
        height: 90vh;
        min-height: 600px;
        overflow: hidden;
        margin-top: 80px; /* Adjust based on header height */
    }

    .hero-background {
        position: absolute;
        width: 100%;
        height: 100%;
    }

    .hero-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(0.8);
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(30, 60, 43, 0.8) 0%, rgba(42, 127, 98, 0.6) 100%);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 0 20px;
        color: white;
    }

    .hero-text {
        max-width: 800px;
        margin-bottom: 40px;
    }

    .hero-title {
        font-family: 'Playfair Display', serif;
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 20px;
        line-height: 1.2;
    }

    .hero-title span {
        color: #f0c14b;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    /* Search Bar */
    .hero-search {
        width: 100%;
        max-width: 800px;
    }

    .search-input-group {
        display: flex;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .search-input {
        flex: 1;
        padding: 15px 20px;
        border: none;
        font-size: 1rem;
        outline: none;
    }

    .search-select {
        padding: 15px;
        border: none;
        border-left: 1px solid #eee;
        font-size: 1rem;
        outline: none;
        background: white;
    }

    .search-button {
        background: #f0c14b;
        color: #1e3c2b;
        border: none;
        padding: 0 25px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-button:hover {
        background: #e2b33a;
    }

    /* Stats Bar */
    .hero-stats {
        display: flex;
        gap: 40px;
        margin-top: 50px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 20px 40px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        display: block;
        font-size: 2.5rem;
        font-weight: 700;
        color: #f0c14b;
    }

    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
        }

        .search-input-group {
            flex-direction: column;
        }

        .search-input,
        .search-select,
        .search-button {
            width: 100%;
            border-radius: 0;
            border-left: none;
            border-bottom: 1px solid #eee;
        }

        .search-button {
            padding: 15px;
        }

        .hero-stats {
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }
    }
</style>