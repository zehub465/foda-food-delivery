<?php
session_start();
require_once "database.php";
require_once "auth.php";
requireLogin();

$uid=currentUserId();$message="";
$s=$pdo->prepare(
    "SELECT u.fname,
            u.lname,
            u.email,
            u.phone,
            u.address 
    FROM users u 
    WHERE u.id=?");

$s->execute([$uid]);
$user=$s->fetch();
$s=$pdo->prepare(
    "SELECT ci.quantity,
            f.id food_id,
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
    WHERE c.user_id=?");

$s->execute([$uid]);
$items=$s->fetchAll();
$subtotal=0;
$restaurantId=0;
$restaurantName='';

foreach ($items as $i){
    $subtotal+=$i['price']*$i['quantity'];
    $restaurantId=(int)$i['restaurant_id'];
    $restaurantName=$i['restaurant'];
        }
$fee=$items?50:0;
$total=$subtotal+$fee;

if ($_SERVER['REQUEST_METHOD']==='POST' && $items){
    $address=trim($_POST['address']??'');
    $payment=$_POST['payment_method']??'Cash on Delivery';

    if($address===''){
        $message="Delivery address is required.";
        }

    elseif(count(array_unique(array_column($items,'restaurant_id')))>1)
        {
            $message="Please order from one restaurant at a time.";
        }
    else{
        try{$pdo->beginTransaction();
            $o=$pdo->prepare(
                "INSERT INTO orders(user_id,
                        restaurant_id,
                        total_amount,
                        delivery_fee,
                        delivery_address,
                        payment_method) 
                VALUES(?,?,?,?,?,?)");

            $o->execute(
                [$uid,
                $restaurantId,
                $subtotal,
                $fee,
                $address,
                $payment]);
                $orderId=(int)$pdo->lastInsertId();

            $oi=$pdo->prepare(
                "INSERT INTO order_items
                    (order_id,
                    food_item_id,
                    food_name,
                    price,
                    quantity,
                    subtotal) 
                VALUES(?,?,?,?,?,?)");

            foreach(
                $items as $i)
                $oi->execute([$orderId,$i['food_id'],
                    $i['name'],
                    $i['price'],
                    $i['quantity'],
                    $i['price']*$i['quantity']]);

            $d=$pdo->prepare(
                "INSERT INTO delivery(order_id) 
                VALUES(?)");

            $d->execute([$orderId]);
            $c=$pdo->prepare(
                "DELETE ci 
                FROM cart_items ci 
                JOIN cart c 
                ON c.id=ci.cart_id 
                WHERE c.user_id=?");
                
                $c->execute([$uid]);
            $pdo->commit();header(
                    "Location: orders.php?placed=1");
                    exit;
        }catch(Throwable $e) {
            if($pdo->inTransaction())$pdo->rollBack();
            $message="Could not place the order. Please try again.";
            }
    }
}
?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">

        <title>Checkout | FoodGo</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
<header class="header">
    <div class="container nav">
        <a class="brand" href="index.php">🍔 Food<span>Go</span></a>

        <nav class="nav-links">
            <a href="cart.php">Cart</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="section">
    <div class="container">

        <h1>Checkout</h1>

        <?php if($message):?>
            <div class="alert error">
                <?=htmlspecialchars($message)?>
            </div>
            <?php endif;?>
            <?php if(!$items):?>
                
                <div class="empty">
                    <h2>Your cart is empty.</h2>
                    <a class="btn" href="restaurants.php">Browse Food</a>
                </div>

                <?php else:?>
                    <div class="checkout-grid">

                        <form method="post" class="panel">
                            <h2>Delivery details</h2>

                            <label>Delivery address
                                <textarea name="address" required>
                                    <?=htmlspecialchars($user['address']??'')?>
                                </textarea>
                            </label>
                            
                            <label>Payment method
                                <select name="payment_method">
                                    <option>Cash on Delivery</option>
                                    <option>GCash</option>
                                </select>
                            </label>

                            <button class="btn full">Place Order · ₱<?=number_format($total,2)?>
                        </button>
                    </form>

                    <aside class="summary">
                        <h2>Order summary</h2>
                        <p>
                            <strong><?=htmlspecialchars($restaurantName)?></strong>
                        </p>
                        
                        <?php foreach($items as $i):?>
                            <div>
                                <span>
                                    <?=$i['quantity']?> × <?=htmlspecialchars($i['name'])?>
                                </span>
                                <strong>₱<?=number_format($i['price']*$i['quantity'],2)?></strong>
                            </div>

                            <?php endforeach;?>
                            <hr>
                            <div>
                                <span>Subtotal</span>
                                <strong>₱<?=number_format($subtotal,2)?></strong>
                            </div>

                            <div>
                                <span>Delivery</span>
                                <strong>₱<?=number_format($fee,2)?></strong>
                            </div>

                            <div>
                                <span>Total</span>
                                <strong>₱<?=number_format($total,2)?></strong>
                            </div>

                        </aside>
                    </div>

                    <?php endif;?>

                </div>
            </main>
        </body>
        </html>
