<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/data-store.php';

function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    foreach (get_all_users() as $user) {
        if ((int)$user['id'] === (int)$_SESSION['user_id']) {
            return $user;
        }
    }
    return null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    $user = current_user();
    return $user && ($user['role'] ?? 'user') === 'admin';
}

function login_user(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['user_name'] = $user['name'] ?? '';
    $_SESSION['user_email'] = $user['email'] ?? '';
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Please sign in with an admin account to access the admin area.';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

function flash_message(string $key): string {
    $message = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return $message;
}
