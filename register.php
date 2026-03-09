<?php
declare(strict_types=1);
require_once __DIR__ . '/config/init.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!verify_csrf($csrfToken)) {
        $errors[] = 'Некорректный CSRF-токен.';
    }

    if ($name === '') {
        $errors[] = 'Имя обязательно.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email.';
    }
    if (mb_strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Пароли не совпадают.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR name = :name LIMIT 1');
        $stmt->execute(['email' => $email, 'name' => $name]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким email или именем уже существует.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at)
             VALUES (:name, :email, :password_hash, :role, NOW())'
        );
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'user',
        ]);
        redirect('login.php');
    }
}

$pageTitle = 'Регистрация';
$basePath = '';
require_once __DIR__ . '/partials/header.php';
?>

<section class="auth-card">
    <h1>Регистрация</h1>
    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <?= csrf_input() ?>
        <label>Имя
            <input type="text" name="name" value="<?= e($name) ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <label>Подтверждение пароля
            <input type="password" name="confirm_password" required>
        </label>
        <button type="submit">Зарегистрироваться</button>
    </form>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
