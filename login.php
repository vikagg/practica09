<?php
declare(strict_types=1);
require_once __DIR__ . '/config/init.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$identity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!verify_csrf($csrfToken)) {
        $errors[] = 'Некорректный CSRF-токен.';
    }

    if ($identity === '' || $password === '') {
        $errors[] = 'Заполните логин/email и пароль.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = :email OR name = :name
             LIMIT 1'
        );
        $stmt->execute([
            'email' => $identity,
            'name' => $identity,
        ]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            $errors[] = 'Неверные учетные данные.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = (string)$user['name'];
            $_SESSION['role'] = (string)$user['role'];
            redirect('index.php');
        }
    }
}

$pageTitle = 'Вход';
$basePath = '';
require_once __DIR__ . '/partials/header.php';
?>

<section class="auth-card">
    <h1>Вход</h1>
    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <?= csrf_input() ?>
        <label>Логин или Email
            <input type="text" name="identity" value="<?= e($identity) ?>" required>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button type="submit">Войти</button>
    </form>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
