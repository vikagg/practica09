<?php
declare(strict_types=1);
require_once __DIR__ . '/config/init.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Требуется авторизация.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf(is_string($csrfToken) ? $csrfToken : null)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Некорректный CSRF токен.']);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
if ($postId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Некорректный post_id.']);
    exit;
}

$postCheck = $pdo->prepare('SELECT id FROM posts WHERE id = :id LIMIT 1');
$postCheck->execute(['id' => $postId]);
if (!$postCheck->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Пост не найден.']);
    exit;
}

$existsStmt = $pdo->prepare(
    'SELECT id
     FROM post_likes
     WHERE post_id = :post_id AND user_id = :user_id
     LIMIT 1'
);
$existsStmt->execute([
    'post_id' => $postId,
    'user_id' => $userId,
]);
$existing = $existsStmt->fetch();

if ($existing) {
    $deleteStmt = $pdo->prepare('DELETE FROM post_likes WHERE post_id = :post_id AND user_id = :user_id');
    $deleteStmt->execute([
        'post_id' => $postId,
        'user_id' => $userId,
    ]);
    $liked = false;
} else {
    $insertStmt = $pdo->prepare('INSERT INTO post_likes (post_id, user_id, created_at) VALUES (:post_id, :user_id, NOW())');
    $insertStmt->execute([
        'post_id' => $postId,
        'user_id' => $userId,
    ]);
    $liked = true;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id');
$countStmt->execute(['post_id' => $postId]);
$likesCount = (int)$countStmt->fetchColumn();

echo json_encode([
    'ok' => true,
    'liked' => $liked,
    'likes_count' => $likesCount,
]);
