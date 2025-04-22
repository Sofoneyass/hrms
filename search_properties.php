<?php
include 'auth_session.php';
include 'db_connection.php'; // your database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Properties</title>
    <link rel="stylesheet" href="search_properties.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <h2>Search Filters</h2>
        <form id="searchForm">
            <label>Location</label>
            <input type="text" name="location">

            <label>Min Price</label>
            <input type="number" name="min_price">

            <label>Max Price</label>
            <input type="number" name="max_price">

            <label>Bedrooms</label>
            <select name="bedrooms">
                <option value="">Any</option>
                <option value="1">1+</option>
                <option value="2">2+</option>
                <option value="3">3+</option>
                <option value="4">4+</option>
            </select>

            <label>Status</label>
            <select name="status">
                <option value="">Any</option>
                <option value="available">Available</option>
                <option value="reserved">Reserved</option>
            </select>

            <button type="submit">Search</button>
        </form>
    </aside>

    <main class="main-content">
        <h1>Available Properties</h1>
        <div id="results" class="property-grid"></div>
    </main>
</div>

<script>
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('fetch_properties.php', {
        method: 'POST',
        body: formData
    }).then(res => res.text()).then(data => {
        document.getElementById('results').innerHTML = data;
    });
});
</script>
</body>
</html>
