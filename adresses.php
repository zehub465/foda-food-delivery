<?php
require_once "auth.php";
require_once "database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the role-checking function provided by your auth.php.
requireRole("Customer");

$uid = (int) ($_SESSION["users_id"] ?? 0);

if ($uid <= 0) {
    header("Location: login.php");
    exit;
}

$error = "";
$label = "";
$address = "";
$isDefault = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $label = trim($_POST["label"] ?? "");
    $address = trim($_POST["full_address"] ?? "");
    $isDefault = !empty($_POST["is_default"]);

    if ($label === "" || $address === "") {
        $error = "Enter both a label and full address.";

    } else {
        try {
            $pdo->beginTransaction();

            if ($isDefault) {
                // Remove the previous default address.
                $stmt = $pdo->prepare(
                    "UPDATE addresses
                     SET is_default = 0
                     WHERE user_id = ?"
                );
                $stmt->execute([$uid]);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO addresses
                    (user_id, label, full_address, is_default)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $uid,
                $label,
                $address,
                $isDefault ? 1 : 0
            ]);

            $pdo->commit();

            header("Location: checkout.php");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Address save error: " . $e->getMessage());
            $error = "Unable to save the address. Please check the details and try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Add Delivery Address | FoodGo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<div class="auth-wrap">
    <section class="auth-card">
        <a class="brand center" href="index.php">
            🍔 Food<span>Go</span>
        </a>

        <h1>Add delivery address</h1>
        <p class="muted">Enter where you want your order delivered.</p>

        <?php if ($error !== ""): ?>
            <div class="alert error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="addresses.php" class="form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <label>
                Label
                <input
                    type="text"
                    name="label"
                    required
                    maxlength="100"
                    placeholder="Home, Work..."
                    value="<?= e($label) ?>"
                >
            </label>

            <label>
                Full address
                <textarea
                    name="full_address"
                    required
                    rows="4"
                ><?= e($address) ?></textarea>
            </label>

            <label class="check">
                <input
                    type="checkbox"
                    name="is_default"
                    value="1"
                    <?= $isDefault ? "checked" : "" ?>
                >
                Set as default
            </label>

            <button type="submit" class="btn full">
                Save address
            </button>
        </form>

        <p class="center muted">
            <a href="checkout.php">Back to checkout</a>
        </p>
    </section>
</div>

<script src="js/script.js"></script>
</body>
</html>