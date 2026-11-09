<?php
session_start();
require_once "database.php";
$restaurants = $pdo->query(
    "SELECT * 
    FROM restaurants 
    WHERE status='Open' 
    ORDER BY id DESC LIMIT 6")->fetchAll();
?>

<!doctype html>
<html lang="en">
    <head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>FoodGo | Food Delivery</title>
<link rel="stylesheet" href="css/style.css">
</head>

<body>
<header class="header">
    <div class="container nav">

        <a href="index.php" class="brand">
            Food<span>Go</span>
        </a>

        <button
            type="button"
            class="menu-toggle"
            aria-label="Open navigation menu"
            aria-expanded="false"
        >
            ☰
        </button>

        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            <a href="cart.php">Cart</a>
            <a href="login.php">Login</a>
        </nav>

    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-grid">
            <div>
    <span class="pill">Fast • Fresh • Local</span>
    <h1>Your favorite food, delivered.</h1>
    <p>Discover great restaurants, 
        build your cart, and get delicious meals delivered to your door.</p>
    <a class="btn" href="restaurants.php">Order Now</a>
    </div>
    <div class="hero-food">🍔<span>🍕</span><b>🥤</b>
</div>
</div>
</section>

    <section class="section">
        <div class="container">
    <div class="section-title">
        <div>
            <h2>Popular restaurants</h2>
            <p class="muted">Start with one of our available restaurants.</p>
        </div>
        <a href="restaurants.php">View all →</a>
    </div>
    <div class="cards">
    <?php foreach ($restaurants as $r): ?>

        <article class="card">
    <div class="food-image">🍽️</div>
    <div class="card-body">
        <span class="status">● Open</span>
    <h3><?=htmlspecialchars($r['name'])?></h3>
    
    <p><?=htmlspecialchars($r['description'])?></p>

    <a class="btn full" href="menu.php?restaurant_id=<?=$r['id']?>">View Menu</a>
</div>
</article>

<?php endforeach; ?>

</div>
</div>
</section>
</main>
<script src="js/script.js">

</script>
</body>
</html>
