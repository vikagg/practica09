<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$postId = (int)($_GET['id'] ?? 0);
$isEdit = $postId > 0;
$errors = [];

$post = [
    'title' => '',
    'content' => '',
    'image_path' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT id, title, content, image_path FROM posts WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $postId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        http_response_code(404);
        exit('Post not found');
    }
    $post = $existing;
}

$imageUrlValue = '';
if (!empty($post['image_path']) && (str_starts_with((string)$post['image_path'], 'http://') || str_starts_with((string)$post['image_path'], 'https://'))) {
    $imageUrlValue = (string)$post['image_path'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!verify_csrf($csrfToken)) {
        $errors[] = 'Некорректный CSRF-токен.';
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $imagePath = (string)$post['image_path'];

    if ($title === '') {
        $errors[] = 'Заголовок обязателен.';
    }
    if ($content === '') {
        $errors[] = 'Текст обязателен.';
    }

    if ($imageUrl !== '') {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Укажите корректную ссылку на изображение.';
        } else {
            $scheme = (string)parse_url($imageUrl, PHP_URL_SCHEME);
            if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
                $errors[] = 'Ссылка должна начинаться с http:// или https://';
            } else {
                if (!empty($post['image_path']) && str_starts_with((string)$post['image_path'], 'uploads/')) {
                    $oldPath = __DIR__ . '/../' . $post['image_path'];
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $imagePath = $imageUrl;
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Ошибка загрузки файла.';
        } else {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $originalName = (string)$_FILES['image']['name'];
            $tmpName = (string)$_FILES['image']['tmp_name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmpName) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($ext, $allowedExt, true) || !in_array((string)$mime, $allowedMime, true)) {
                $errors[] = 'Разрешены только изображения: jpg, png, webp, gif.';
            } else {
                $newName = uniqid('post_', true) . '.' . $ext;
                $relativePath = 'uploads/' . $newName;
                $destination = __DIR__ . '/../' . $relativePath;

                if (!is_dir(__DIR__ . '/../uploads')) {
                    mkdir(__DIR__ . '/../uploads', 0777, true);
                }

                if (move_uploaded_file($tmpName, $destination)) {
                    if (!empty($post['image_path']) && str_starts_with((string)$post['image_path'], 'uploads/')) {
                        $oldPath = __DIR__ . '/../' . $post['image_path'];
                        if (is_file($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $imagePath = $relativePath;
                } else {
                    $errors[] = 'Не удалось сохранить файл.';
                }
            }
        }
    }

    $post['title'] = $title;
    $post['content'] = $content;
    $post['image_path'] = $imagePath;
    $imageUrlValue = $imageUrl;

    if (!$errors) {
        if ($isEdit) {
            $update = $pdo->prepare(
                'UPDATE posts
                 SET title = :title, content = :content, image_path = :image_path
                 WHERE id = :id'
            );
            $update->execute([
                'title' => $title,
                'content' => $content,
                'image_path' => $imagePath ?: null,
                'id' => $postId,
            ]);
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO posts (user_id, title, content, image_path, created_at)
                 VALUES (:user_id, :title, :content, :image_path, NOW())'
            );
            $insert->execute([
                'user_id' => (int)$_SESSION['user_id'],
                'title' => $title,
                'content' => $content,
                'image_path' => $imagePath ?: null,
            ]);
        }
        redirect('posts.php');
    }
}

$pageTitle = $isEdit ? 'Редактирование поста' : 'Добавление поста';
$basePath = '../';
require_once __DIR__ . '/../partials/header.php';
?>

<section class="admin-shell admin-form-shell">
    <h1><?= e($pageTitle) ?></h1>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_input() ?>
        <label>Заголовок
            <input type="text" name="title" value="<?= e((string)$post['title']) ?>" required>
        </label>
        <label>Текст
            <textarea name="content" rows="10" required><?= e((string)$post['content']) ?></textarea>
        </label>
        <label>Картинка (файл)
            <input type="file" name="image" accept="image/*">
        </label>
        <label>Или ссылка на картинку (URL)
            <input type="url" name="image_url" value="<?= e($imageUrlValue) ?>" placeholder="https://example.com/image.jpg">
        </label>
        <?php if (!empty($post['image_path'])): ?>
            <?php
            $previewPath = (string)$post['image_path'];
            $previewSrc = (str_starts_with($previewPath, 'http://') || str_starts_with($previewPath, 'https://'))
                ? $previewPath
                : '../' . $previewPath;
            ?>
            <img class="preview" src="<?= e($previewSrc) ?>" alt="preview">
        <?php endif; ?>
        <button type="submit"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
    </form>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
