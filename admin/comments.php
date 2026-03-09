<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!verify_csrf($csrfToken)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    $commentId = (int)$_POST['delete_comment_id'];
    if ($commentId > 0) {
        $stmt = $pdo->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->execute(['id' => $commentId]);
    }
    redirect('comments.php');
}

$stmt = $pdo->query(
    'SELECT c.id, c.content, c.created_at, u.name AS user_name, p.id AS post_id, p.title AS post_title
     FROM comments c
     JOIN users u ON u.id = c.user_id
     JOIN posts p ON p.id = c.post_id
     ORDER BY c.created_at DESC
     LIMIT 100'
);
$comments = $stmt->fetchAll();

$pageTitle = 'Админка: Комментарии';
$basePath = '../';
require_once __DIR__ . '/../partials/header.php';
?>

<section class="admin-shell">
    <h1>Последние комментарии</h1>
    <div class="admin-toolbar">
        <a class="btn btn-soft" href="posts.php">Назад к постам</a>
    </div>

    <?php if (!$comments): ?>
        <p>Комментариев пока нет.</p>
    <?php endif; ?>

    <div class="comments-list-admin">
        <?php foreach ($comments as $comment): ?>
            <article class="comment-item">
                <div class="post-meta">
                    <strong><?= e((string)$comment['user_name']) ?></strong>
                    <span><?= e(date('d.m.Y H:i', strtotime((string)$comment['created_at']))) ?></span>
                </div>
                <p class="admin-post-line">
                    <a class="admin-post-link" href="../post.php?id=<?= (int)$comment['post_id'] ?>">
                        <?= e((string)$comment['post_title']) ?>
                    </a>
                </p>
                <p><?= nl2br(e((string)$comment['content'])) ?></p>
                <form method="post" onsubmit="return confirm('Удалить комментарий?');">
                    <?= csrf_input() ?>
                    <input type="hidden" name="delete_comment_id" value="<?= (int)$comment['id'] ?>">
                    <button class="danger btn-sm" type="submit">Удалить</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
