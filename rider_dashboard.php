<?php
session_start();

require_once "database.php";
require_once "auth.php";

requireRole("Rider");

$uid = (int) currentUserId();

$message = "";
$messageType = "success";

/*
|--------------------------------------------------------------------------
| AVAILABLE DELIVERY STATUSES
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    "Assigned",
    "Picked Up",
    "On the Way",
    "Delivered"
];

/*
|--------------------------------------------------------------------------
| UPDATE DELIVERY STATUS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $deliveryId = (int) ($_POST["delivery_id"] ?? 0);
    $newStatus = trim($_POST["status"] ?? "");

    if ($deliveryId <= 0) {

        $message = "Invalid delivery selected.";
        $messageType = "error";

    } elseif (!in_array($newStatus, $allowedStatuses, true)) {

        $message = "Please select a valid delivery status.";
        $messageType = "error";

    } else {

        try {

            /*
             * Claim the delivery if it is unassigned.
             * A rider cannot update another rider's delivery.
             */
            $update = $pdo->prepare("
                UPDATE delivery
                SET rider_id = ?,
                    status = ?
                WHERE id = ?
                  AND (rider_id = ? OR rider_id IS NULL)
            ");

            $update->execute([
                $uid,
                $newStatus,
                $deliveryId,
                $uid
            ]);

            if ($update->rowCount() > 0) {

                $message = "Delivery status updated successfully.";

            } else {

                // Determine whether the delivery exists and belongs to this rider.
                $check = $pdo->prepare("
                    SELECT id, rider_id
                    FROM delivery
                    WHERE id = ?
                    LIMIT 1
                ");

                $check->execute([$deliveryId]);
                $delivery = $check->fetch(PDO::FETCH_ASSOC);

                if (!$delivery) {
                    $message = "Delivery not found.";
                } elseif (
                    $delivery["rider_id"] !== null &&
                    (int) $delivery["rider_id"] !== $uid
                ) {
                    $message = "This delivery is assigned to another rider.";
                } else {
                    $message = "No changes were made to the delivery status.";
                }

                $messageType = "error";
            }

        } catch (PDOException $e) {

            error_log("Rider delivery update error: " . $e->getMessage());

            $message = "Unable to update the delivery status. Please try again.";
            $messageType = "error";
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH THIS RIDER'S DELIVERIES AND AVAILABLE DELIVERIES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        d.id AS delivery_id,
        d.order_id,
        d.rider_id,
        d.status AS delivery_status,
        o.delivery_address,
        o.status AS order_status,
        r.name AS restaurant,
        CONCAT(u.fname, ' ', u.lname) AS customer
    FROM delivery d
    INNER JOIN orders o
        ON o.id = d.order_id
    INNER JOIN restaurants r
        ON r.id = o.restaurant_id
    INNER JOIN users u
        ON u.id = o.user_id
    WHERE d.rider_id = ?
       OR d.rider_id IS NULL
    ORDER BY d.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$uid]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| HTML ESCAPE HELPER
|--------------------------------------------------------------------------
*/

function riderEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rider Dashboard | FoodGo</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .rider-message {
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 8px;
            background: #e8f7ed;
            color: #176534;
        }

        .rider-message.error {
            background: #fdeaea;
            color: #a12626;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .rider-delivery-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        .rider-delivery-card h3 {
            margin: 10px 0;
        }

        .delivery-info {
            margin: 8px 0;
            overflow-wrap: anywhere;
        }

        .status-form {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #eee;
        }

        .status-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .status-form select {
            width: 100%;
            padding: 11px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            background: #fff;
        }

        .status-form button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 7px;
            background: #f97316;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .status-form button:hover {
            background: #ea580c;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #fff1df;
            color: #9a4a00;
            font-size: 13px;
            font-weight: 600;
        }

        .empty-deliveries {
            padding: 30px;
            text-align: center;
            background: #f9fafb;
            border-radius: 10px;
            margin-top: 20px;
        }

        .claim-note {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }
    </style>
</head>

<body>

<header class="header">
    <div class="container nav">
        <a class="brand" href="index.php">
            🍔 Food<span>Go</span>
        </a>

        <nav class="nav-links">
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="section">
    <div class="container">

        <h1>Rider Dashboard</h1>
        <p class="muted">View deliveries and update their status.</p>

        <?php if ($message !== ""): ?>
            <div class="rider-message <?= $messageType === "error" ? "error" : "" ?>">
                <?= riderEscape($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($deliveries)): ?>

            <div class="empty-deliveries">
                <h3>No deliveries available</h3>
                <p class="muted">
                    Assigned deliveries will appear here.
                </p>
            </div>

        <?php else: ?>

            <div class="delivery-grid">

                <?php foreach ($deliveries as $d): ?>

                    <article class="rider-delivery-card">

                        <span class="muted">
                            Order #<?= (int) $d["order_id"] ?>
                        </span>

                        <h3>
                            <?= riderEscape($d["restaurant"]) ?>
                        </h3>

                        <p class="delivery-info">
                            <strong>Customer:</strong>
                            <?= riderEscape($d["customer"]) ?>
                        </p>

                        <p class="delivery-info">
                            <strong>Delivery Address:</strong><br>
                            📍 <?= riderEscape($d["delivery_address"]) ?>
                        </p>

                        <p class="delivery-info">
                            <strong>Current Status:</strong><br>

                            <span class="status-badge">
                                <?= riderEscape($d["delivery_status"]) ?>
                            </span>
                        </p>

                        <?php if ($d["rider_id"] === null): ?>
                            <p class="claim-note">
                                This delivery is unassigned. Updating its status
                                will assign it to you.
                            </p>
                        <?php endif; ?>

                        <form method="POST" class="status-form">

                            <input
                                type="hidden"
                                name="delivery_id"
                                value="<?= (int) $d["delivery_id"] ?>"
                            >

                            <label for="status-<?= (int) $d["delivery_id"] ?>">
                                Update Delivery Status
                            </label>

                            <select
                                name="status"
                                id="status-<?= (int) $d["delivery_id"] ?>"
                                required
                            >
                                <?php foreach ($allowedStatuses as $status): ?>
                                    <option
                                        value="<?= riderEscape($status) ?>"
                                        <?= $d["delivery_status"] === $status ? "selected" : "" ?>
                                    >
                                        <?= riderEscape($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit">
                                Update Status
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</main>

</body>
</html>