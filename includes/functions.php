<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function truncateText(string $text, int $length = 200): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . '...';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_admin_or_forbidden(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        exit('Forbidden');
    }
}
