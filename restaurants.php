<?php
session_start(); 
require_once "database.php";

$restaurants = $pdo->query(
    "SELECT * 
    FROM restaurants 
    ORDER BY status='Open' 
    DESC,name")->fetchAll();
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">

<title>Restaurants | FoodGo</title>
<link rel="stylesheet" href="css/style.css">
</head>

<body>
<header class="header">
    <div class="container nav">

        <a class="brand" href="index.php">🍔 Food<span>Go</span></a>
        <button class="menu-toggle">☰</button>
        <nav class="nav-links">
            <a href="restaurants.php">Restaurants</a>
            <a href="cart.php">Cart</a>
            
            <?php if(!empty($_SESSION['users_id'])):?>

                <a href="orders.php">Orders</a>
                <a href="logout.php">Logout</a>
                <?php else:?>
                    <a href="login.php">Login</a>
                    <?php endif;?>

                </nav>
            </div>
        </header>

<main class="section">
    <div class="container">
        <div class="section-title">
            <div>
                <h1>Restaurants</h1>
                <p class="muted">Choose where you want to order.</p>
            </div>
        </div>

<div class="cards">
    <?php foreach($restaurants as $r):?>
        <article class="card">
            <div class="food-image">🍽️</div>
            <div class="card-body">
                <span class="<?=$r['status']==='Open'?'status':'closed'?>"><?=$r['status']?></span>
                <h3><?=htmlspecialchars($r['name'])?></h3>
                <p><?=htmlspecialchars($r['description'])?></p>
                <p class="muted">📍 <?=htmlspecialchars($r['address'])?></p>
                <a class="btn full" href="menu.php?restaurant_id=<?=$r['id']?>">View Menu</a>
            </div>
        </article>
        
        <?php endforeach;?>
    </div>

</div>
</main>
<script src="js/script.js">

</script>
</body>
</html>
