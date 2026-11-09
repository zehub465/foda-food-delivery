
<?php
session_start();
require_once "database.php";
require_once "auth.php";

if (!empty($_SESSION['users_id']) && !empty($_SESSION['role'])) {
    redirectByRole();
    exit;
}

$error = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, fname, lname, email, password, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $user &&
                password_verify($password, $user['password'])
            ) {
                session_regenerate_id(true);

                $_SESSION['users_id'] = (int) $user['id'];
                $_SESSION['first_name'] = $user['fname'];
                $_SESSION['last_name'] = $user['lname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                redirectByRole();
                exit;

            } else {
                $error = "Invalid email or password.";
            }

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Unable to log in right now. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | FoodGo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <a class="brand center" href="index.php">
        🍔 Food<span>Go</span>
    </a>

    <h1>Welcome back</h1>
    <p class="muted">Login to continue ordering.</p>

    <?php if ($error !== ''): ?>
        <div class="alert error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label>
            Email
            <input
                type="email"
                name="email"
                required
                autocomplete="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>

        <label>
            Password
            <input
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </label>

        <button type="submit" class="btn full">
            Login
        </button>
    </form>

    <p class="center muted">
        New here?
        <a href="register.php">Create an account</a>
    </p>
</div>

<script src="js/script.js"></script>
</body>
</html>