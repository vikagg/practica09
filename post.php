<?php
declare(strict_types=1);
require_once __DIR__ . '/config/init.php';

$postId = (int)($_GET['id'] ?? 0);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($postId <= 0) {
    http_response_code(404);
    exit('Post not found');
}

$postStmt = $pdo->prepare(
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
     WHERE p.id = :id
     LIMIT 1'
);
$postStmt->execute([
    'id' => $postId,
    'current_user_id' => $currentUserId,
]);
$post = $postStmt->fetch();

if (!$post) {
    http_response_code(404);
    exit('Post not found');
}

// Получаем комментарии к посту с информацией о пользователях
$commentsStmt = $pdo->prepare(
    'SELECT 
        c.id, 
        c.content, 
        c.created_at, 
        u.name AS user_name,
        u.id AS user_id
     FROM comments c
     JOIN users u ON u.id = c.user_id
     WHERE c.post_id = :post_id
     ORDER BY c.created_at DESC'
);
$commentsStmt->execute(['post_id' => $postId]);
$comments = $commentsStmt->fetchAll();

// Подсчитываем общее количество комментариев
$commentsCount = count($comments);

$pageTitle = $post['title'];
$basePath = '';
require_once __DIR__ . '/partials/header.php';
?>

<article class="full-post">
    <h1><?= e($post['title']) ?></h1>
    
    <div class="post-meta">
        <span class="author">
            <span class="icon">✍️</span> <?= e($post['author_name']) ?>
        </span>
        <span class="date">
            <span class="icon">📅</span> <?= e(date('d.m.Y H:i', strtotime($post['created_at']))) ?>
        </span>
        <span class="comments-count">
            <span class="icon">💬</span> <?= $commentsCount ?> <?= getCommentWord($commentsCount) ?>
        </span>
    </div>
    
    <?php if (!empty($post['image_path'])): ?>
        <div class="post-image">
            <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>">
        </div>
    <?php endif; ?>
    
    <div class="post-content">
        <?= nl2br(e($post['content'])) ?>
    </div>
    
    <!-- Кнопки действий под постом -->
    <div class="post-actions-bar">
        <button
            type="button"
            class="action-btn like-btn<?= !empty($post['liked_by_me']) ? ' liked' : '' ?><?= isLoggedIn() ? '' : ' disabled' ?>"
            data-post-id="<?= (int)$post['id'] ?>"
            data-auth="<?= isLoggedIn() ? '1' : '0' ?>"
            data-liked="<?= !empty($post['liked_by_me']) ? '1' : '0' ?>"
            data-csrf="<?= e(csrf_token()) ?>"
            <?= isLoggedIn() ? '' : 'disabled title="Войдите, чтобы поставить лайк"' ?>
        >
            <span class="count"><?= (int)$post['likes_count'] ?></span>
            <span class="label">Нравится</span>
        </button>
        
        <button
            type="button"
            class="action-btn comment-scroll-btn"
            onclick="document.getElementById('comments-section').scrollIntoView({behavior: 'smooth'})"
        >
            <span class="icon">💬</span>
            <span class="count"><?= $commentsCount ?></span>
            <span class="label">Комментарии</span>
        </button>
    </div>
</article>

<!-- Секция комментариев -->
<section id="comments-section" class="comments-section">
    <div class="comments-header">
        <h2>Комментарии <span class="comments-header-count">(<?= $commentsCount ?>)</span></h2>
        
        <?php if (isLoggedIn()): ?>
            <button type="button" id="showCommentFormBtn" class="btn btn-primary">
                <span class="icon">✏️</span> Написать комментарий
            </button>
        <?php endif; ?>
    </div>
    
    <?php if (isLoggedIn()): ?>
        <!-- Форма добавления комментария (изначально скрыта) -->
        <div id="commentFormContainer" class="comment-form-container hidden">
            <form id="commentForm" action="add_comment.php" method="post" class="comment-form">
                <?= csrf_input() ?>
                <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                
                <div class="form-header">
                    <h3>Новый комментарий</h3>
                    <button type="button" id="closeCommentFormBtn" class="btn-close" aria-label="Закрыть">✕</button>
                </div>
                
                <div class="form-body">
                    <textarea 
                        name="content" 
                        rows="4" 
                        maxlength="1000" 
                        required 
                        placeholder="Напишите комментарий (макс. 1000 символов)"
                        class="comment-textarea"
                    ></textarea>
                    
                    <div class="form-footer">
                        <div class="char-counter">
                            <span id="charCount">0</span>/1000
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancelCommentBtn" class="btn btn-soft">Отмена</button>
                            <button type="submit" class="btn btn-primary">Отправить комментарий</button>
                        </div>
                    </div>
                </div>
            </form>
            <div id="commentMessage" class="comment-message"></div>
        </div>
    <?php else: ?>
        <div class="auth-message">
            <p>💬 Чтобы оставить комментарий, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>.</p>
        </div>
    <?php endif; ?>
    <div id="commentsList" class="comments-list">
        <?php if (empty($comments)): ?>
            <div class="no-comments">
                <div class="no-comments-icon">💬</div>
                <p>Пока нет комментариев. Будьте первым, кто оставит комментарий!</p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <article class="comment-item" data-comment-id="<?= (int)$comment['id'] ?>">
                    <div class="comment-header">
                        <div class="comment-author">
                            <div class="author-avatar">
                                <?= getAvatar($comment['user_name']) ?>
                            </div>
                            <div class="author-info">
                                <strong class="author-name"><?= e($comment['user_name']) ?></strong>
                                <span class="comment-date">
                                    <?= e(getTimeAgo($comment['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <?php if (isAdmin() || (int)$_SESSION['user_id'] === (int)$comment['user_id']): ?>
                            <button 
                                type="button" 
                                class="comment-delete-btn" 
                                onclick="deleteComment(<?= (int)$comment['id'] ?>)"
                                title="Удалить комментарий"
                            >
                                <span class="icon">🗑️</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="comment-content">
                        <?= nl2br(e($comment['content'])) ?>
                    </div>
                    <div class="comment-footer">
                        <button class="comment-reply-btn" onclick="replyToComment('<?= e($comment['user_name']) ?>')">
                            <span class="icon">↩️</span> Ответить
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<script src="assets/js/comments.js"></script>

<?php
function getCommentWord($count) {
    $count = (int)$count;
    if ($count % 10 == 1 && $count % 100 != 11) {
        return 'комментарий';
    } elseif ($count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 10 || $count % 100 >= 20)) {
        return 'комментария';
    } else {
        return 'комментариев';
    }
}

function getAvatar($name) {
    $firstLetter = mb_strtoupper(mb_substr($name, 0, 1));
    return '<span class="avatar-placeholder">' . $firstLetter . '</span>';
}

function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'только что';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' ' . getMinuteWord($minutes) . ' назад';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . getHourWord($hours) . ' назад';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' ' . getDayWord($days) . ' назад';
    } else {
        return date('d.m.Y H:i', $timestamp);
    }
}

function getMinuteWord($minutes) {
    if ($minutes % 10 == 1 && $minutes % 100 != 11) return 'минуту';
    elseif ($minutes % 10 >= 2 && $minutes % 10 <= 4 && ($minutes % 100 < 10 || $minutes % 100 >= 20)) return 'минуты';
    else return 'минут';
}

function getHourWord($hours) {
    if ($hours % 10 == 1 && $hours % 100 != 11) return 'час';
    elseif ($hours % 10 >= 2 && $hours % 10 <= 4 && ($hours % 100 < 10 || $hours % 100 >= 20)) return 'часа';
    else return 'часов';
}

function getDayWord($days) {
    if ($days % 10 == 1 && $days % 100 != 11) return 'день';
    elseif ($days % 10 >= 2 && $days % 10 <= 4 && ($days % 100 < 10 || $days % 100 >= 20)) return 'дня';
    else return 'дней';
}

require_once __DIR__ . '/partials/footer.php'; 
?>