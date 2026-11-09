```php
<?php
session_start();
require_once "database.php";

if (!empty($_SESSION["users_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

$fname = "";
$lname = "";
$email = "";
$phone = "";
$address = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fname = trim($_POST["fname"] ?? "");
    $lname = trim($_POST["lname"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (
        $fname === "" ||
        $lname === "" ||
        $email === "" ||
        $phone === "" ||
        $address === "" ||
        $password === "" ||
        $confirm === ""
    ) {
        $error = "Please complete all required fields, including your phone number and delivery address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";

    } else {
        try {
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );
            $check->execute([$email]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $error = "That email is already registered.";

            } else {
                $pdo->beginTransaction();

                // Save customer account and registration address.
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

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

                $userId = (int) $pdo->lastInsertId();

                // Also save the address in the addresses table.
                $addressStmt = $pdo->prepare(
                    "INSERT INTO addresses
                    (user_id, label, full_address, is_default)
                    VALUES (?, 'Home', ?, 1)"
                );

                $addressStmt->execute([
                    $userId,
                    $address
                ]);

                $pdo->commit();

                $success = "Account created successfully. Your delivery address has been saved. You can now log in.";

                $fname = "";
                $lname = "";
                $email = "";
                $phone = "";
                $address = "";
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Registration error: " . $e->getMessage());

            if ($e->getCode() === "23000") {
                $error = "This email is already registered or a database constraint was violated.";
            } else {
                $error = "Registration failed. Please check your database configuration.";
            }
        }
    }
}
?>