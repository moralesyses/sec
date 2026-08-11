<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

if (!defined('PLAIN_PASSWORDS')) {
    define('PLAIN_PASSWORDS', false); // safe default: hashed
}

/*
|--------------------------------------------------------------------------
| Session / login helpers
|--------------------------------------------------------------------------
*/

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(string $to = 'dashboard.php'): void {
    if (is_logged_in()) {
        header('Location: ' . $to);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Password helpers (honor PLAIN_PASSWORDS in config.php)
|--------------------------------------------------------------------------
*/

function verify_password(string $password, string $stored): bool {
    return PLAIN_PASSWORDS
        ? hash_equals($stored, $password)
        : password_verify($password, $stored);
}

function make_password_hash(string $password): string {
    return PLAIN_PASSWORDS ? $password : password_hash($password, PASSWORD_DEFAULT);
}

/*
|--------------------------------------------------------------------------
| Login / logout
|--------------------------------------------------------------------------
*/

function log_user_in(int $id, string $name, string $username, string $role): void {
    session_regenerate_id(true);

    $_SESSION['user_id']  = $id;
    $_SESSION['name']     = $name;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = $role;
}

function log_user_out(): void {
    session_destroy();
}

/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
| superadmin      -> everything
| hr              -> job listings
| content_manager -> gallery / site content
|--------------------------------------------------------------------------
*/

const ROLE_LABELS = [
    'superadmin'      => 'Super Admin',
    'hr'              => 'HR',
    'content_manager' => 'Content Manager',
];

function user_role(): string {
    return $_SESSION['role'] ?? '';
}

function can(string $area): bool {
    $role = user_role();

    if ($role === 'superadmin') {
        return true;
    }

    return ($area === 'jobs' && $role === 'hr')
        || ($area === 'gallery' && $role === 'content_manager');
}

function require_role(string ...$roles): void {
    require_login();

    $role = user_role();

    if ($role === 'superadmin' || in_array($role, $roles, true)) {
        return;
    }

    http_response_code(403);
    die('<div style="font-family:sans-serif;max-width:480px;margin:80px auto;text-align:center">
         <h2 style="color:#07824E">Access denied</h2>
         <p>Your account (' . htmlspecialchars(ROLE_LABELS[$role] ?? $role) . ') does not have permission for this page.</p>
         <p><a href="dashboard.php" style="color:#07824E;font-weight:600">Back to dashboard</a></p></div>');
}