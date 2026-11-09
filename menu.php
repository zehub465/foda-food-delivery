
<?php
session_start();

require_once "database.php";

// Escape output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Get restaurant ID
$restaurantId = (int)(
    $_GET['restaurant_id'] ??
    $_POST['restaurant_id'] ??
    0
);

// Get restaurant
$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE id = ?
");

$stmt->execute([$restaurantId]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    header("Location: restaurants.php");
    exit;
}

$error = "";

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['users_id'])) {
        header("Location: login.php");
        exit;
    }

    $foodId = (int)($_POST['food_id'] ?? 0);

    $qty = (int)($_POST['quantity'] ?? 1);

    if ($qty < 1 || $qty > 99) {
        $error = "Quantity must be between 1 and 99.";
    } else {

        // Check food item
        $s = $pdo->prepare("
            SELECT id
            FROM food_items
            WHERE id = ?
            AND restaurant_id = ?
            AND available = 1
        ");

        $s->execute([$foodId, $restaurantId]);

        if ($s->fetch()) {

            try {

                $pdo->beginTransaction();

                // Create or retrieve cart
                $c = $pdo->prepare("
                    INSERT INTO cart (user_id)
                    VALUES (?)
                    ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id)
                ");

                $c->execute([
                    $_SESSION['users_id']
                ]);

                $cartId = (int)$pdo->lastInsertId();

                // Add food to cart
                $c = $pdo->prepare("
                    INSERT INTO cart_items
                    (cart_id, food_item_id, quantity)
                    VALUES (?, ?, ?)

                    ON DUPLICATE KEY UPDATE
                    quantity = quantity + VALUES(quantity)
                ");

                $c->execute([
                    $cartId,
                    $foodId,
                    $qty
                ]);

                $pdo->commit();

                header(
                    "Location: menu.php?restaurant_id=" .
                    $restaurantId . "&added=1"
                );

                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = "Unable to add item to cart. Please try again.";
            }

        } else {

            $error = "This food item is unavailable.";
        }
    }
}

// Get available food items
$s = $pdo->prepare("
    SELECT
        f.*,
        c.name AS category

    FROM food_items f

    LEFT JOIN food_categories c
    ON c.id = f.category_id

    WHERE f.restaurant_id = ?
    AND f.available = 1

    ORDER BY f.id DESC
");

$s->execute([$restaurantId]);

$foods = $s->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= e($restaurant['name']) ?> | FoodGo
    </title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<header class="header">

    <div class="container nav">

        <a class="brand" href="index.php">
            🍔 Food<span>Go</span>
        </a>

        <button class="menu-toggle">☰</button>

        <nav class="nav-links">

            <a href="restaurants.php">Restaurants</a>

            <a href="cart.php">🛒 Cart</a>

            <?php if (!empty($_SESSION['users_id'])): ?>

                <a href="orders.php">Orders</a>

                <a href="logout.php">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<main class="section">

    <div class="container">

        <a href="restaurants.php">
            ← Restaurants
        </a>

        <h1>
            <?= e($restaurant['name']) ?>
        </h1>

        <p class="muted">
            <?= e($restaurant['description']) ?>
        </p>

        <?php if (isset($_GET['added'])): ?>

            <div class="alert success">
                Item successfully added to your cart!
            </div>

        <?php endif; ?>

        <?php if (!empty($error)): ?>

            <div class="alert">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <div class="menu-grid">

            <?php if (empty($foods)): ?>

                <p class="muted">
                    No available food items in this restaurant.
                </p>

            <?php else: ?>

                <?php foreach ($foods as $f): ?>

                    <article class="food-card">

                        <div class="food-image">
                            🍴
                        </div>

                        <div>

                            <span class="category">
                                <?= e($f['category'] ?? 'Food') ?>
                            </span>

                            <h3>
                                <?= e($f['name']) ?>
                            </h3>

                            <p>
                                <?= e($f['description']) ?>
                            </p>

                            <strong>
                                ₱<?= number_format((float)$f['price'], 2) ?>
                            </strong>

                            <form method="POST" class="add-form">

                                <input
                                    type="hidden"
                                    name="restaurant_id"
                                    value="<?= $restaurantId ?>"
                                >

                                <input
                                    type="hidden"
                                    name="food_id"
                                    value="<?= (int)$f['id'] ?>"
                                >

                                <input
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="99"
                                    required
                                >

                                <button type="submit" class="btn">
                                    Add to Cart
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</main>

<script src="js/script.js"></script>

</body>
</html>
