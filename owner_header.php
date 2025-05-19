<div id="header">
    <h1>Owner Dashboard</h1>
    <div>
        Welcome, <?php echo htmlspecialchars($owner_info['full_name'] ?? 'Owner'); ?>!
        <a href="logout.php" class="ml-4 text-red-500 hover:underline text-sm">Logout</a>
    </div>
</div>