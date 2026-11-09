<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';
requireRole('Admin');
?>
<header class="header admin-header">
    <div class="container nav">
        <a class="brand" href="../index.php">🍔 Food<span>Go</span></a>
        <button class="menu-toggle" type="button" aria-label="Open menu">☰</button>
        <nav class="nav-links">
            <a href="../admin_dashboard.php">Dashboard</a>
            <a href="../admin_restaurants.php">Restaurants</a>
            <a href="../admin_food.php">Food Items</a>
            <a href="../admin_orders.php">Orders</a>
            <a href="../admin_users.php">Users</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
</header>
