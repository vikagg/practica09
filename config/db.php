<?php
declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbName = 'blog';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};port=3306git reset --hard HEAD@{1};dbname={$dbName};charset={$dbCharset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection error.');
}
