<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Blog';
$basePath = $basePath ?? '';
$cssVersion = (string)(@filemtime(__DIR__ . '/../assets/css/style.css') ?: time());
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css?v=<?= e($cssVersion) ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="logo" href="<?= e($basePath) ?>index.php">MultiBlog</a>
        <button class="burger" id="burgerBtn" type="button">&#9776;</button>
        <nav id="mainNav">
            <a href="<?= e($basePath) ?>index.php">Главная</a>
            <?php if (isLoggedIn()): ?>
                                <?php if (isAdmin()): ?>
                    <a href="<?= e($basePath) ?>admin/posts.php">Админка</a>
                <?php endif; ?>
                <a href="<?= e($basePath) ?>logout.php">Выйти</a>
            <?php else: ?>
                <a href="<?= e($basePath) ?>login.php">Войти</a>
                <a href="<?= e($basePath) ?>register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
