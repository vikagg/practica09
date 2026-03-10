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

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$csrfToken = $_POST['csrf_token'] ?? null;
if (!verify_csrf($csrfToken)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Некорректный CSRF-токен.']);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$content = trim((string)($_POST['content'] ?? ''));

if ($postId <= 0 || $content === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Заполните комментарий.']);
    exit;
}

if (mb_strlen($content) > 1000) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Комментарий слишком длинный (максимум 1000 символов).']);
    exit;
}

$postCheck = $pdo->prepare('SELECT id FROM posts WHERE id = :id LIMIT 1');
$postCheck->execute(['id' => $postId]);
if (!$postCheck->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Пост не найден.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $insert = $pdo->prepare(
        'INSERT INTO comments (post_id, user_id, content, created_at)
         VALUES (:post_id, :user_id, :content, NOW())'
    );
    $insert->execute([
        'post_id' => $postId,
        'user_id' => (int)$_SESSION['user_id'],
        'content' => $content,
    ]);
    
    $commentId = (int)$pdo->lastInsertId();
    
    $pdo->commit();
    $safeUser = e((string)($_SESSION['user_name'] ?? 'User'));
    $safeContent = nl2br(e($content));
    $safeDate = e(date('d.m.Y H:i'));
    
    $commentHtml = '<article class="comment-item" data-comment-id="' . $commentId . '">' .
                   '<div class="post-meta">' .
                   '<strong>' . $safeUser . '</strong>' .
                   '<span>' . $safeDate . '</span>' .
                   '</div>' .
                   '<p>' . $safeContent . '</p>' .
                   '</article>';
    
    echo json_encode([
        'ok' => true,
        'message' => 'Комментарий добавлен.',
        'comment_html' => $commentHtml,
        'comment_id' => $commentId
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Ошибка при сохранении комментария.']);
}