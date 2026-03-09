<?php
declare(strict_types=1);
require_once __DIR__ . '/config/init.php';

$perPage = 5;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset = ($currentPage - 1) * $perPage;
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$countStmt = $pdo->query('SELECT COUNT(*) FROM posts');
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $perPage;
}

$stmt = $pdo->prepare(
    'SELECT
        p.id,
        p.title,
        p.content,
        p.image_path,
        p.created_at,
        u.name AS author_name,
        COALESCE(l.likes_count, 0) AS likes_count,
        CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
     FROM posts p
     JOIN users u ON u.id = p.user_id
     LEFT JOIN (
        SELECT post_id, COUNT(*) AS likes_count
        FROM post_likes
        GROUP BY post_id
     ) l ON l.post_id = p.id
     LEFT JOIN post_likes ul ON ul.post_id = p.id AND ul.user_id = :current_user_id
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = 'Главная';
$basePath = '';
require_once __DIR__ . '/partials/header.php';
?>

<section class="feed-section">
    <h1>Лента постов</h1>
    <?php if (!$posts): ?>
        <p>Пока нет публикаций.</p>
    <?php endif; ?>

    <div class="posts-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if (!empty($post['image_path'])): ?>
                    <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>">
                <?php endif; ?>
                <h2><a href="post.php?id=<?= (int)$post['id'] ?>"><?= e($post['title']) ?></a></h2>
                <p><?= e(truncateText($post['content'], 200)) ?></p>
                <div class="post-meta">
                    <span>Автор: <?= e($post['author_name']) ?></span>
                    <span><?= e(date('d.m.Y H:i', strtotime($post['created_at']))) ?></span>
                </div>
                <button
                    type="button"
                    class="like-btn<?= !empty($post['liked_by_me']) ? ' liked' : '' ?><?= isLoggedIn() ? '' : ' like-btn-disabled' ?>"
                    data-post-id="<?= (int)$post['id'] ?>"
                    data-auth="<?= isLoggedIn() ? '1' : '0' ?>"
                    data-liked="<?= !empty($post['liked_by_me']) ? '1' : '0' ?>"
                    data-csrf="<?= e(csrf_token()) ?>"
                    <?= isLoggedIn() ? '' : 'disabled title="Войдите, чтобы поставить лайк"' ?>
                ><span><?= (int)$post['likes_count'] ?></span></button>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>">Предыдущая</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="<?= $i === $currentPage ? 'active' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?>">Следующая</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
