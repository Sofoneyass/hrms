<?php
require_once 'db_connection.php';

$location = $_GET['location'] ?? '';
$title = $_GET['title'] ?? '';

$sql = "SELECT * FROM properties WHERE 1=1";
if (!empty($location)) {
    $safe_location = mysqli_real_escape_string($conn, $location);
    $sql .= " AND location LIKE '%$safe_location%'";
}
if (!empty($title)) {
    $safe_title = mysqli_real_escape_string($conn, $title);
    $sql .= " AND title = '$safe_title'";
}
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search Results | House Rental</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f0f2f5;
      color: #333;
    }

    header {
      padding: 1rem 2rem;
      background: #1e293b;
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .search-header {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .search-header input, .search-header select {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      border: none;
    }

    .search-header button {
      background: #2563eb;
      color: white;
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    h2 {
      padding: 2rem;
      margin: 0;
      background: linear-gradient(to right, #2563eb, #1e40af);
      color: white;
    }

    .properties-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      padding: 2rem;
    }

    .property-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }

    .property-card:hover {
      transform: translateY(-5px);
    }

    .property-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .property-card h3 {
      margin: 1rem;
      font-size: 1.2rem;
      color: #1e3a8a;
    }

    .property-card p {
      margin: 0 1rem 0.5rem;
    }

    .property-card a {
      display: block;
      margin: 1rem;
      text-align: center;
      padding: 0.5rem;
      background: #1e3a8a;
      color: white;
      border-radius: 10px;
      text-decoration: none;
    }

    .no-results {
      padding: 2rem;
      text-align: center;
      color: #888;
    }

    .back-button {
      display: inline-block;
      margin: 1rem 2rem;
      padding: 0.5rem 1rem;
      background: #334155;
      color: white;
      border-radius: 8px;
      text-decoration: none;
    }
  </style>
</head>
<body>

<header>
  <div><i class="fas fa-home"></i> House Rental</div>
  <form action="search_results.php" method="GET" class="search-header">
    <input type="text" name="location" placeholder="Location" value="<?= htmlspecialchars($location) ?>">
    <select name="title">
      <option value="">Any Type</option>
      <option value="apartment" <?= $title == 'apartment' ? 'selected' : '' ?>>Apartment</option>
      <option value="villa" <?= $title == 'villa' ? 'selected' : '' ?>>Villa</option>
      <option value="house" <?= $title == 'house' ? 'selected' : '' ?>>Condo</option>
    </select>
    <button type="submit"><i class="fas fa-search"></i></button>
  </form>
</header>

<h2>Search Results</h2>

<?php if (mysqli_num_rows($result) > 0): ?>
  <div class="properties-grid">
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="property-card">
        <img src="<?= htmlspecialchars($row['main_image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($row['title']) ?>">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['location']) ?></p>
        <p><strong>Price:</strong> BIRR <?= number_format($row['price_per_month'], 2) ?></p>
        <a href="property_detail.php?id=<?= $row['property_id'] ?>">View Details</a>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="no-results">
    <p><i class="fas fa-exclamation-circle"></i> No properties found matching your search.</p>
    <a class="back-button" href="index.php">← Back to Homepage</a>
  </div>
<?php endif; ?>

<?php include 'footer.php';  ?>

</body>
</html>
