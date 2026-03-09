<?php
echo "<h2>Тест сервера</h2>";

// Проверка PHP
echo "✅ PHP работает<br>";

// Проверка подключения к БД
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    echo "✅ MySQL доступен<br>";
    
    $dbs = $pdo->query('SHOW DATABASES');
    echo "📊 Базы данных:<br>";
    while ($db = $dbs->fetchColumn()) {
        echo " - $db<br>";
    }
} catch (PDOException $e) {
    echo "❌ Ошибка MySQL: " . $e->getMessage();
}