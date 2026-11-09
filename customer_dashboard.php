<?php
session_start();

require_once "database.php";
require_once "auth.php";

requireRole("Customer");

$uid = (int) currentUserId();
$customerName = currentUserName();

/*
|--------------------------------------------------------------------------
| COUNT CUSTOMER ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE user_id = ?
");

$stmt->execute([$uid]);
$orderCount = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| GET RECENT ORDERS AND RIDER DELIVERY STATUS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.id AS order_id,
        o.total_amount,
        o.delivery_fee,
        o.status AS order_status,
        o.created_at,

        d.status AS delivery_status,
        d.rider_id,

        CONCAT(rider.fname, ' ', rider.lname) AS rider_name

    FROM orders o

    LEFT JOIN delivery d
        ON d.order_id = o.id

    LEFT JOIN users rider
        ON rider.id = d.rider_id

    WHERE o.user_id = ?

    ORDER BY o.id DESC
    LIMIT 5
");

$stmt->execute([$uid]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| HTML ESCAPING
|--------------------------------------------------------------------------
*/

function customerEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| DELIVERY PROGRESS
|--------------------------------------------------------------------------
*/

$deliverySteps = [
    "Assigned",
    "Picked Up",
    "On the Way",
    "Delivered"
];

function deliveryStepIndex($status, $steps): int
{
    $index = array_search($status, $steps, true);

    return ($index === false) ? -1 : $index;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | FoodGo</title>

    <link rel="stylesheet" href="css/style.css">

    <!-- Refresh the dashboard periodically to show new rider updates. -->
    <meta http-equiv="refresh" content="30">

    
</head>

<body>

<header class="header">
    <div class="container nav">

        <a class="brand" href="index.php">
            🍔 Food<span>Go</span>
        </a>

        <button
            class="menu-toggle"
            type="button"
            aria-label="Toggle navigation"
            aria-expanded="false"
        >
            ☰
        </button>

        <nav class="nav-links">
            <a href="restaurants.php">Order Food</a>
            <a href="cart.php">Cart</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </nav>

    </div>
</header>

<main class="section">
    <div class="container">

        <div class="welcome">
            <div>
                <p class="muted">Welcome back</p>

                <h1>
                    <?= customerEscape($customerName) ?> 👋
                </h1>

                <p class="muted">
                    Check your orders and follow your delivery progress.
                </p>
            </div>

            <a class="btn" href="restaurants.php">
                Start Ordering
            </a>
        </div>

        <!-- DASHBOARD STATISTICS -->

        <div class="stats">

            <div>
                <span>Total Orders</span>
                <strong><?= $orderCount ?></strong>
            </div>

            <div>
                <span>Account Role</span>
                <strong>Customer</strong>
            </div>

            <div>
                <span>Tracking</span>
                <strong>Available</strong>
            </div>

        </div>

        <!-- RECENT ORDERS -->

        <h2>Recent Orders</h2>

        <p class="muted">
            Delivery information refreshes automatically every 30 seconds.
        </p>

        <?php if (empty($recent)): ?>

            <div class="empty-orders">
                <h3>No orders yet</h3>

                <p class="muted">
                    Your orders and delivery updates will appear here.
                </p>

                <a class="btn" href="restaurants.php">
                    Browse Restaurants
                </a>
            </div>

        <?php else: ?>

            <?php foreach ($recent as $o): ?>

                <?php
                $deliveryStatus = trim(
                    (string) ($o["delivery_status"] ?? "")
                );

                $orderStatus = trim(
                    (string) ($o["order_status"] ?? "Pending")
                );

                $stepIndex = deliveryStepIndex(
                    $deliveryStatus,
                    $deliverySteps
                );

                $statusClass = "";

                if (strcasecmp($deliveryStatus, "Delivered") === 0) {
                    $statusClass = "delivered";
                } elseif (
                    stripos($orderStatus, "cancel") !== false ||
                    stripos($deliveryStatus, "cancel") !== false
                ) {
                    $statusClass = "cancelled";
                }

                $total = (float) ($o["total_amount"] ?? 0)
                       + (float) ($o["delivery_fee"] ?? 0);
                ?>

                <article class="customer-order-card">

                    <div class="order-top">

                        <div>
                            <span class="muted">
                                Order #<?= (int) $o["order_id"] ?>
                            </span>

                            <p class="order-total">
                                ₱<?= number_format($total, 2) ?>
                            </p>

                            <p>
                                <strong>Order Status:</strong>
                                <?= customerEscape($orderStatus) ?>
                            </p>
                        </div>

                        <span class="status-badge <?= $statusClass ?>">
                            <?= customerEscape(
                                $deliveryStatus !== ""
                                    ? $deliveryStatus
                                    : "Awaiting Rider Assignment"
                            ) ?>
                        </span>

                    </div>

                    <div class="tracking-section">

                        <h4>🚴 Rider Delivery Tracking</h4>

                        <div class="tracking-details">

                            <?php if (!empty($o["rider_id"])): ?>

                                <p>
                                    <strong>Rider:</strong>
                                    <?= customerEscape($o["rider_name"]) ?>
                                </p>

                            <?php else: ?>

                                <p>
                                    <strong>Rider:</strong>
                                    Not assigned yet
                                </p>

                            <?php endif; ?>

                            <p>
                                <strong>Delivery Status:</strong>
                                <?= customerEscape(
                                    $deliveryStatus !== ""
                                        ? $deliveryStatus
                                        : "Waiting for delivery assignment"
                                ) ?>
                            </p>

                        </div>

                        <?php if ($statusClass === "cancelled"): ?>

                            <p class="tracking-note">
                                This order or delivery has been cancelled.
                                Please check the order details for more information.
                            </p>

                        <?php elseif ($stepIndex >= 0): ?>

                            <div class="progress-tracker">

                                <?php foreach ($deliverySteps as $i => $step): ?>

                                    <?php
                                    $stepClass = "";

                                    if ($i < $stepIndex) {
                                        $stepClass = "complete";
                                    } elseif ($i === $stepIndex) {
                                        $stepClass = "current";
                                    }
                                    ?>

                                    <div class="progress-step <?= $stepClass ?>">

                                        <div class="progress-circle">
                                            <?php if ($i < $stepIndex): ?>
                                                ✓
                                            <?php else: ?>
                                                <?= $i + 1 ?>
                                            <?php endif; ?>
                                        </div>

                                        <span>
                                            <?= customerEscape($step) ?>
                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <?php if ($deliveryStatus === "Delivered"): ?>

                                <p class="tracking-note">
                                    ✅ Your order has been delivered. Enjoy your meal!
                                </p>

                            <?php elseif ($deliveryStatus === "On the Way"): ?>

                                <p class="tracking-note">
                                    🛵 Your rider is on the way to your delivery address.
                                </p>

                            <?php elseif ($deliveryStatus === "Picked Up"): ?>

                                <p class="tracking-note">
                                    🍔 Your rider has picked up your order.
                                </p>

                            <?php else: ?>

                                <p class="tracking-note">
                                    Your delivery has been assigned to a rider.
                                </p>

                            <?php endif; ?>

                        <?php else: ?>

                            <p class="tracking-note">
                                Your order is waiting for a rider or delivery
                                status update. Tracking will appear here once
                                the rider is assigned and updates the delivery.
                            </p>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

        <p class="tracking-note">
            Need more details? Visit your
            <a href="orders.php">Orders page</a>.
        </p>

    </div>
</main>

<script src="js/script.js"></script>

</body>
</html>