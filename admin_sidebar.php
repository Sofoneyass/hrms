<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h4>House Rental System</h4>
    </div>
    <div class="sidebar-menu">
        <a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="manage_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="manage_properties.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_properties.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i> Properties
        </a>
        <a href="manage_bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_bookings.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Bookings
        </a>
        <a href="manage_leases.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_leases.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-contract"></i> Leases
        </a>
        <a href="manage_invoices.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_invoices.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i> Invoices
        </a>
        <a href="manage_payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_payments.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> Payments
        </a>
        <a href="manage_maintenance.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_maintenance.php' ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i> Maintenance
        </a>
        <a href="manage_messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Messages
        </a>
        <a href="system_settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'system_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>