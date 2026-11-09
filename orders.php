<?php
session_start();
require_once "database.php";

if(empty($_SESSION['users_id'])){
    header("Location: login.php");
    exit;
    }

$uid=(int)$_SESSION['users_id'];

$s=$pdo->prepare(
    "SELECT o.*,r.name restaurant 
    FROM orders o 
    JOIN restaurants r 
    ON r.id=o.restaurant_id 
    WHERE o.user_id=? 
    ORDER BY o.id DESC");

$s->execute([$uid]);
$orders=$s->fetchAll();
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>My Orders | FoodGo</title>
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
            <a href="customer_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="section">
    <div class="container">
        <h1>My Orders</h1>

        <?php if(isset($_GET['placed'])):?>
            <div class="alert success">Order placed successfully!</div>
            <?php endif;?>
            <?php if(!$orders):?>
                <div class="empty">
                    <h2>No orders yet.</h2>
                    <a class="btn" href="restaurants.php">Order Food</a>

                </div>
                <?php else:?>

                    <div class="order-list"><?php foreach($orders as $o):?>
                        <article class="order-card"><div>
                            <span class="muted">Order #<?=$o['id']?></span>
                            <h3><?=htmlspecialchars($o['restaurant'])?></h3>
                            <p><?=htmlspecialchars($o['created_at'])?></p>
                        </div>
                        <div class="order-right">
                            <span class="badge"><?=htmlspecialchars($o['status'])?></span>
                            <strong>
                                ₱<?=number_format($o['total_amount']+$o['delivery_fee'],2)?>
                            </strong>
                            <small><?=htmlspecialchars($o['payment_method'])?></small>
                        </div>
                    </article>

                    <?php endforeach;?>

                </div>
                <?php endif;?>
            </div>
        </main>
        <script src="js/script.js"></script>
    </body>
    </html>
