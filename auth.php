<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['users_id'])) {
        header("Location: login.php");
        exit;
    }
}
function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        header("Location: index.php");
        exit;
    }
}
function currentUserId(): ?int {
    return isset($_SESSION['users_id']) ? (int)$_SESSION['users_id'] : null;
}
function currentUserName(): string {
    return $_SESSION['name'] ?? '';
}

function redirectByRole(): void
{
    switch ($_SESSION['role'] ?? '') {
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
