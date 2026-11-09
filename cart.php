<?php
session_start();
require_once "database.php";

if(empty($_SESSION['users_id'])){
    header("Location: login.php");
    exit;
    }

$uid=(int)$_SESSION['users_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $item=(int)($_POST['item_id']??0);
    $s=$pdo->prepare(
        "SELECT c.id cart_id 
        FROM cart c 
        WHERE c.user_id=?");
        $s->execute([$uid]);
        $cart=$s->fetch();

    if($cart){
        if($action==='remove'){
            $q=$pdo->prepare(
                "DELETE FROM cart_items 
                WHERE id=? 
                AND cart_id=?");
                $q->execute([$item,$cart['cart_id']]);
                }
        if($action==='update'){
            $q=$pdo->prepare(
                "UPDATE cart_items 
                SET quantity=? 
                WHERE id=? 
                AND cart_id=?");
            $q->execute([max(1,(int)$_POST['quantity']),
            $item,$cart['cart_id']]);}
    }
    header("Location: cart.php");
    exit;
}

$s=$pdo->prepare(
    "SELECT ci.id,
            ci.quantity,
            f.name,
            f.price,
            f.restaurant_id,
            r.name restaurant 
    FROM cart c 
    JOIN cart_items ci 
    ON ci.cart_id=c.id 
    JOIN food_items f 
    ON f.id=ci.food_item_id 
    JOIN restaurants r 
    ON r.id=f.restaurant_id 
    WHERE c.user_id=? 
    ORDER BY ci.id DESC");

$s->execute([$uid]);
$items=$s->fetchAll();
$subtotal=0;
$restaurantId=0;

foreach($items as $i){
        $subtotal+=$i['price']*$i['quantity'];
        $restaurantId=(int)$i['restaurant_id'];}
        $fee=$items?50:0;
        $total=$subtotal+$fee;
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Cart | FoodGo</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>

<header class="header">
    <div class="container nav">
        <a class="brand" href="index.php">🍔 Food<span>Go</span></a>
        <button class="menu-toggle">☰</button>
        <nav class="nav-links">
            <a href="restaurants.php">Restaurants</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="section">
    <div class="container">
        <h1>Your Cart</h1>
        
        <?php if(!$items):?>
            
            <div class="empty">
                <div>🛒</div>
                <h2>Your cart is empty</h2>
                <a class="btn" href="restaurants.php">Browse Restaurants</a>
            </div>
            
            <?php else:?>
                
                <div class="cart-layout"><div>
                    <?php foreach($items as $i):?>

                        <div class="cart-item">
                            <div class="mini-food">🍴</div>
                            <div class="grow">
                                <h3><?=htmlspecialchars($i['name'])?></h3>
                                <p class="muted">
                                    <?=htmlspecialchars($i['restaurant'])?> · ₱<?=number_format($i['price'],2)?>
                                </p>
                                <form method="post" class="inline">
                                    <input type="hidden" 
                                            name="item_id" 
                                            value="<?=$i['id']?>">
                                            
                                            <input type="hidden" 
                                                    name="action" 
                                                    value="update">
                                                    
                                                    <input type="number" 
                                                            name="quantity" 
                                                            min="1" 
                                                            value="<?=$i['quantity']?>">
                                                            
                                                            <button class="btn small">Update</button>
                                </form>
                            </div>
                            <div>
                                <strong>₱<?=number_format($i['price']*$i['quantity'],2)?></strong>
                                <form method="post">
                                    
                                <input type="hidden" 
                                        name="item_id" 
                                        value="<?=$i['id']?>">
                                        
                                        <input type="hidden" 
                                                name="action" 
                                                value="remove">
                                                
                                                <button class="link danger">Remove</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach;?>
                    </div>
<aside class="summary">
    <h2>Summary</h2>
    <div>
        <span>Subtotal</span>
        <strong>₱<?=number_format($subtotal,2)?></strong>
    </div>
    <div>
        <span>Delivery</span>
        <strong>₱<?=number_format($fee,2)?></strong>
    </div>
    <hr>
    <div>
        <span>Total</span>
        <strong>₱<?=number_format($total,2)?></strong>
    </div>
    <a class="btn full" href="checkout.php">Checkout</a>
</aside>
</div>

<?php endif;?>

</div>
</main>
<script src="js/script.js"></script>
</body>
</html>
