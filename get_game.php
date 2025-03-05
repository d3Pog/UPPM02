<?php
require 'db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if ($game) {
    echo '<img src="icons/' . htmlspecialchars($game['icon']) . '" alt="' . htmlspecialchars($game['name']) . '" class="modal-icon">';
    echo '<h2>' . htmlspecialchars($game['name']) . '</h2>';
    echo '<p><strong>Фирма:</strong> ' . htmlspecialchars($game['company']) . '</p>';
    echo '<p><strong>Цена:</strong> ' . htmlspecialchars($game['price']) . ' руб.</p>';
    echo '<p><strong>Системные требования:</strong> ' . htmlspecialchars($game['sys_requirements']) . '</p>';
    echo '<p><strong>Стиль:</strong> ' . htmlspecialchars($game['style']) . '</p>';
}
?>