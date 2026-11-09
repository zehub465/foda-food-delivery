
<?php


session_start();
require_once "database.php";

if (!empty($_SESSION['users_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

$fname = "";
$lname = "";
$email = "";
$phone = "";

$house_number = "";
$street = "";
$barangay = "";
$city = "";
$province = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Personal information
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    // Address information
    $house_number = trim($_POST['house_number'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');

    // Password information
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Combine address fields into one database value
    $addressParts = [
        $house_number,
        $street,
        $barangay,
        $city,
        $province
    ];

    $address = implode(', ', array_filter(
        $addressParts,
        fn($part) => $part !== ''
    ));

    // Validation
    if (
        $fname === '' ||
        $lname === '' ||
        $email === '' ||
        $phone === '' ||
        $house_number === '' ||
        $street === '' ||
        $barangay === '' ||
        $city === '' ||
        $province === '' ||
        $password === '' ||
        $confirm === ''
    ) {
        $error = "Please complete all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";

    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";

    } else {

        try {

            // Check existing email
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            $check->execute([$email]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {

                $error = "That email is already registered.";

            } else {

                // Secure password hashing
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert customer account
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (fname, lname, email, password, phone, address, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'Customer')"
                );

                $stmt->execute([
                    $fname,
                    $lname,
                    $email,
                    $hashedPassword,
                    $phone,
                    $address
                ]);

                $success = "Account created successfully. You can now log in.";

                // Clear form values
                $fname = "";
                $lname = "";
                $email = "";
                $phone = "";

                $house_number = "";
                $street = "";
                $barangay = "";
                $city = "";
                $province = "";
            }

        } catch (PDOException $e) {

            error_log(
                "FoodGo registration error: " . $e->getMessage()
            );

            if ($e->getCode() === '23000') {
                $error = "Email already exists or a database constraint was violated.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Register | FoodGo</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body class="auth-page">

<div class="auth-card">

    <a class="brand center" href="index.php">
        🍔 Food<span>Go</span>
    </a>

    <h1>Create account</h1>

    <p class="muted">
        Order your favorite food with ease.
    </p>

    <?php if ($error !== ''): ?>
        <div class="alert error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert success">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <!-- FIRST NAME -->
        <label for="fname">First name</label>

        <input
            type="text"
            id="fname"
            name="fname"
            required
            maxlength="100"
            placeholder="First name"
            autocomplete="given-name"
            value="<?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- LAST NAME -->
        <label for="lname">Last name</label>

        <input
            type="text"
            id="lname"
            name="lname"
            required
            maxlength="50"
            placeholder="Last name"
            autocomplete="family-name"
            value="<?= htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- EMAIL -->
        <label for="email">Email</label>

        <input
            type="email"
            id="email"
            name="email"
            required
            maxlength="150"
            placeholder="Enter your email"
            autocomplete="email"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- PHONE -->
        <label for="phone">Phone number</label>

        <input
            type="tel"
            id="phone"
            name="phone"
            required
            maxlength="30"
            placeholder="Enter your phone number"
            autocomplete="tel"
            value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- COMPLETE ADDRESS -->
        <h3>Delivery Address</h3>

        <label for="house_number">House/Building Number</label>

        <input
            type="text"
            id="house_number"
            name="house_number"
            required
            maxlength="100"
            placeholder="House/Building number"
            value="<?= htmlspecialchars($house_number, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="street">Street</label>

        <input
            type="text"
            id="street"
            name="street"
            required
            maxlength="100"
            placeholder="Street"
            value="<?= htmlspecialchars($street, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="barangay">Barangay</label>

        <input
            type="text"
            id="barangay"
            name="barangay"
            required
            maxlength="100"
            placeholder="Barangay"
            value="<?= htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="city">City/Municipality</label>

        <input
            type="text"
            id="city"
            name="city"
            required
            maxlength="100"
            placeholder="City/Municipality"
            value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="province">Province</label>

        <input
            type="text"
            id="province"
            name="province"
            required
            maxlength="100"
            placeholder="Province"
            value="<?= htmlspecialchars($province, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- PASSWORD -->
        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            required
            minlength="8"
            autocomplete="new-password"
            placeholder="Create password"
        >

        <!-- CONFIRM PASSWORD -->
        <label for="confirm_password">Confirm Password</label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
            minlength="8"
            autocomplete="new-password"
            placeholder="Confirm password"
        >

        <!-- SUBMIT -->
        <button type="submit" class="btn full">
            Create Account
        </button>

    </form>

    <p class="center muted">
        Already registered?
        <a href="login.php">Login</a>
    </p>

</div>

<script src="js/script.js"></script>

</body>
</html>
