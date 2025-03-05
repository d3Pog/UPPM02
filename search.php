<?php
require 'db.php';

// Функция для очистки данных
function sanitizeInput($input) {
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    if (!preg_match('/^[а-яА-Яa-zA-Z0-9\s,\.:]*$/u', $input)) {
        return false; // Неверный ввод
    }
    return $input;
}

$name = sanitizeInput($_POST['name'] ?? '');
$company = sanitizeInput($_POST['company'] ?? '');
$sysReq = sanitizeInput($_POST['sysReq'] ?? '');
$style = $_POST['style'] ?? '';

// Проверка на корректность ввода
if (($name === false) || ($company === false) || ($sysReq === false)) {
    echo '<p class="error">Поля поиска должны содержать только буквы, цифры, пробелы, запятые, точки и двоеточия.</p>';
    exit;
}

// Проверка стиля
$validStyles = ['', 'Стратегия', 'Шутер', 'RPG', 'Симулятор'];
if (!in_array($style, $validStyles)) {
    echo '<p class="error">Неверный стиль игры.</p>';
    exit;
}

// Базовый запрос
$query = "SELECT * FROM games WHERE 1=1";
$params = [];

if (!empty($name)) {
    $query .= " AND name LIKE ?";
    $params[] = "%$name%";
}
if (!empty($company)) {
    $query .= " AND company LIKE ?";
    $params[] = "%$company%";
}
if (!empty($sysReq)) {
    $query .= " AND sys_requirements LIKE ?";
    $params[] = "%$sysReq%";
}
if (!empty($style)) {
    $query .= " AND style = ?";
    $params[] = $style;
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($games)) {
        echo '<p>Игры не найдены.</p>';
    } else {
        foreach ($games as $game) {
            echo '<div class="game-block" onclick="showModal(' . $game['id'] . ')">';
            echo '<img src="icons/' . htmlspecialchars($game['icon']) . '" alt="' . htmlspecialchars($game['name']) . '" class="game-icon">';
            echo '<h2>' . htmlspecialchars($game['name']) . '</h2>';
            echo '<p><strong>Фирма:</strong> ' . htmlspecialchars($game['company']) . '</p>';
            echo '<p><strong>Цена:</strong> ' . htmlspecialchars($game['price']) . ' руб.</p>';
            echo '<p><strong>Системные требования:</strong> ' . htmlspecialchars($game['sys_requirements']) . '</p>';
            echo '<p><strong>Стиль:</strong> ' . htmlspecialchars($game['style']) . '</p>';
            echo '</div>';
        }
    }
} catch (PDOException $e) {
    echo '<p>Ошибка базы данных: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>