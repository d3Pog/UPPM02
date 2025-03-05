<?php
$host = 'localhost';
$dbname = 'gamebase';
$username = 'root'; // По умолчанию в OpenServer
$password = 'root';     // По умолчанию пустой в OpenServer

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>