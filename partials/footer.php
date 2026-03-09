    </main>
    <footer class="site-footer">
        <div class="container footer-wrap">
            <div class="footer-brand">
                <strong>MultiBlog</strong>
                <p>Платформа для публикаций, обсуждений и развития идей.</p>
            </div>
            <div class="footer-links">
                <a href="<?= e($basePath ?? '') ?>index.php">Главная</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?= e($basePath ?? '') ?>admin/posts.php">Админка</a>
                    <?php endif; ?>
                    <a href="<?= e($basePath ?? '') ?>logout.php">Выйти</a>
                <?php else: ?>
                    <a href="<?= e($basePath ?? '') ?>login.php">Войти</a>
                    <a href="<?= e($basePath ?? '') ?>register.php">Регистрация</a>
                <?php endif; ?>
            </div>
            <div class="footer-copy">
                <span>&copy; <?= date('Y') ?> MultiBlog vs</span>
            </div>
        </div>
    </footer>
    <script src="<?= e($basePath ?? '') ?>assets/js/main.js"></script>
</body>
</html>
