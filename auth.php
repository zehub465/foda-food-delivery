<?php
/*
|--------------------------------------------------------------------------
| Session Contract
|--------------------------------------------------------------------------
| Authenticated sessions use users_id, role, first_name, last_name, email,
| and name. Use loginUser() to establish the contract; use currentUserId(),
| currentUserRole(), and currentUserName() to read identity values.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['users_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (currentUserRole() !== $role) {
        header('Location: index.php');
        exit;
    }
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);

    $_SESSION['users_id'] = (int) $user['id'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['first_name'] = trim((string) ($user['fname'] ?? ''));
    $_SESSION['last_name'] = trim((string) ($user['lname'] ?? ''));
    $_SESSION['email'] = (string) ($user['email'] ?? '');
    $_SESSION['name'] = trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
    csrfToken();
}

function currentUserId(): ?int
{
    return isset($_SESSION['users_id']) ? (int) $_SESSION['users_id'] : null;
}

function currentUserRole(): string
{
    return (string) ($_SESSION['role'] ?? '');
}

function currentUserName(): string
{
    if (!empty($_SESSION['name'])) {
        return (string) $_SESSION['name'];
    }
    $name = trim((string) ($_SESSION['first_name'] ?? '') . ' ' . (string) ($_SESSION['last_name'] ?? ''));
    return $name !== '' ? $name : 'Customer';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || $submitted === '') {
        $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    $stored = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submitted) || !is_string($stored) || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(419);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Your session expired, please go back and try again';
        exit;
    }
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function redirectByRole(): void
{
    switch (currentUserRole()) {
        case 'Customer':
            header('Location: customer_dashboard.php');
            exit;
        case 'Admin':
            header('Location: admin_dashboard.php');
            exit;
        case 'Rider':
            header('Location: rider_dashboard.php');
            exit;
        default:
            session_unset();
            session_destroy();
            header('Location: login.php');
            exit;
    }
}
