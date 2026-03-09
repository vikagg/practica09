<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!verify_csrf($csrfToken)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    $postId = (int)$_POST['delete_post_id'];
    if ($postId > 0) {
        $imgStmt = $pdo->prepare('SELECT image_path FROM posts WHERE id = :id');
        $imgStmt->execute(['id' => $postId]);
        $row = $imgStmt->fetch();

        $deleteStmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
        $deleteStmt->execute(['id' => $postId]);

        if ($row && !empty($row['image_path']) && str_starts_with((string)$row['image_path'], 'uploads/')) {
            $absolutePath = __DIR__ . '/../' . $row['image_path'];
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }
    redirect('posts.php');
}

$stmt = $pdo->query(
    'SELECT p.id, p.title, p.created_at, u.name AS author_name
     FROM posts p
     JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC'
);
$posts = $stmt->fetchAll();

$pageTitle = 'Админка: Посты';
$basePath = '../';
require_once __DIR__ . '/../partials/header.php';
?>

<section class="admin-shell">
    <h1>Управление постами</h1>
    <div class="admin-toolbar">
        <a class="btn" href="post_form.php">Добавить пост</a>
        <a class="btn btn-soft" href="comments.php">Комментарии</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Заголовок</th>
                <th>Автор</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td><?= (int)$post['id'] ?></td>
                    <td><?= e((string)$post['title']) ?></td>
                    <td><?= e((string)$post['author_name']) ?></td>
                    <td><?= e(date('d.m.Y H:i', strtotime((string)$post['created_at']))) ?></td>
                    <td class="actions">
                        <a class="btn btn-soft btn-sm" href="post_form.php?id=<?= (int)$post['id'] ?>">Редактировать</a>
                        <form method="post" onsubmit="return confirm('Удалить пост?');">
                            <?= csrf_input() ?>
                            <input type="hidden" name="delete_post_id" value="<?= (int)$post['id'] ?>">
                            <button type="submit" class="danger btn-sm">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
