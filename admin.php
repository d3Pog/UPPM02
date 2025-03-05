<?php
session_start();
require 'db.php';

// Функция для проверки и очистки данных
function sanitizeInput($input) {
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    // Разрешаем русские буквы, латинские буквы, цифры, пробелы, запятые, точки и двоеточия
    if (!preg_match('/^[а-яА-Яa-zA-Z0-9\s,\.:]+$/u', $input)) {
        return false; // Неверный ввод
    }
    return $input;
}

// Функция для проверки загрузки файла
function validateImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2 MB
    $fileType = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];

    if (!in_array($fileType, $allowedTypes) || $fileSize > $maxSize) {
        return false;
    }
    return true;
}

if (isset($_GET['logout']) && isset($_SESSION['admin'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = true;
        } else {
            $error = "Неверный логин или пароль";
        }
    }
} else {
    if (isset($_POST['add_game'])) {
        $name = sanitizeInput($_POST['name'] ?? '');
        $company = sanitizeInput($_POST['company'] ?? '');
        $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
        $sysReq = sanitizeInput($_POST['sys_requirements'] ?? '');
        $style = $_POST['style'] ?? '';
        $icon = $_FILES['icon'] ?? null;

        // Проверка всех полей
        if (!$name || !$company || $price === false || !$sysReq || !$style || !$icon) {
            $error = "Все поля обязательны и должны содержать допустимые символы.";
        } elseif (!in_array($style, ['Стратегия', 'Шутер', 'RPG', 'Симулятор'])) {
            $error = "Неверный стиль игры.";
        } elseif (!validateImage($icon)) {
            $error = "Иконка должна быть изображением (JPEG, PNG, GIF) размером до 2 МБ.";
        } else {
            $fileExt = pathinfo($icon['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid('game_', true) . '.' . $fileExt;
            $uploadDir = __DIR__ . '/icons/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($icon['tmp_name'], $uploadDir . $uniqueName)) {
                $stmt = $pdo->prepare("INSERT INTO games (name, company, price, sys_requirements, style, icon) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $company, $price, $sysReq, $style, $uniqueName]);
            } else {
                $error = "Ошибка загрузки иконки.";
            }
        }
    }

    if (isset($_POST['edit_game'])) {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $name = sanitizeInput($_POST['name'] ?? '');
        $company = sanitizeInput($_POST['company'] ?? '');
        $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
        $sysReq = sanitizeInput($_POST['sys_requirements'] ?? '');
        $style = $_POST['style'] ?? '';
        $icon = $_FILES['icon'] ?? null;

        if (!$id || !$name || !$company || $price === false || !$sysReq || !$style) {
            $error = "Все поля обязательны и должны содержать допустимые символы.";
        } elseif (!in_array($style, ['Стратегия', 'Шутер', 'RPG', 'Симулятор'])) {
            $error = "Неверный стиль игры.";
        } else {
            if ($icon && !empty($icon['name'])) {
                if (!validateImage($icon)) {
                    $error = "Иконка должна быть изображением (JPEG, PNG, GIF) размером до 2 МБ.";
                } else {
                    $fileExt = pathinfo($icon['name'], PATHINFO_EXTENSION);
                    $uniqueName = uniqid('game_', true) . '.' . $fileExt;
                    $uploadDir = __DIR__ . '/icons/';
                    move_uploaded_file($icon['tmp_name'], $uploadDir . $uniqueName);
                    $icon = $uniqueName;
                }
            } else {
                $icon = $_POST['old_icon'];
            }

            if (!$error) {
                $stmt = $pdo->prepare("UPDATE games SET name = ?, company = ?, price = ?, sys_requirements = ?, style = ?, icon = ? WHERE id = ?");
                $stmt->execute([$name, $company, $price, $sysReq, $style, $icon, $id]);
            }
        }
    }

    if (isset($_POST['delete_game'])) {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare("SELECT icon FROM games WHERE id = ?");
            $stmt->execute([$id]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($game) {
                $iconPath = __DIR__ . '/icons/' . $game['icon'];
                if (file_exists($iconPath)) {
                    unlink($iconPath);
                }
                $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Игра успешно удалена";
            }
        }
    }

    $editGame = null;
    if (isset($_POST['search_game'])) {
        $searchName = sanitizeInput($_POST['search_name'] ?? '');
        if ($searchName) {
            $stmt = $pdo->prepare("SELECT * FROM games WHERE name LIKE ? LIMIT 1");
            $stmt->execute(["%$searchName%"]);
            $editGame = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Название для поиска должно содержать допустимые символы.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body class="admin">
    <div class="container">
        <?php if (!isset($_SESSION['admin'])): ?>
            <h1>Вход для администратора</h1>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <label>Логин: <input type="text" name="login" required></label><br>
                <label>Пароль: <input type="password" name="password" required></label><br>
                <button type="submit">Войти</button>
            </form>
            <a href="index.php">Вернуться к играм</a>
        <?php else: ?>
            <h1>Управление играми</h1>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

            <h2>Добавить игру</h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Название: <input type="text" name="name" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label><br>
                <label>Фирма: <input type="text" name="company" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label><br>
                <label>Цена (руб.): <input type="number" step="0.01" name="price" required min="0"></label><br>
                <label>Системные требования: <input type="text" name="sys_requirements" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label><br>
                <label>Стиль: 
                    <select name="style" required>
                        <option value="Стратегия">Стратегия</option>
                        <option value="Шутер">Шутер</option>
                        <option value="RPG">RPG</option>
                        <option value="Симулятор">Симулятор</option>
                    </select>
                </label><br>
                <label>Иконка: <input type="file" name="icon" accept="image/*" required></label><br>
                <button type="submit" name="add_game">Добавить игру</button>
            </form>

            <h2>Редактировать или удалить игру</h2>
            <form method="POST">
                <label>Поиск по названию: <input type="text" name="search_name" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label>
                <button type="submit" name="search_game">Найти</button>
            </form>

            <?php if ($editGame): ?>
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <input type="hidden" name="id" value="<?php echo $editGame['id']; ?>">
                    <input type="hidden" name="old_icon" value="<?php echo htmlspecialchars($editGame['icon']); ?>">
                    <label>Название: <input type="text" name="name" value="<?php echo htmlspecialchars($editGame['name']); ?>" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label><br>
                    <label>Фирма: <input type="text" name="company" value="<?php echo htmlspecialchars($editGame['company']); ?>" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label><br>
                    <label>Цена (руб.): <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($editGame['price']); ?>" required min="0"></label><br>
                    <label>Системные требования: <input type="text" name="sys_requirements" value="<?php echo htmlspecialchars($editGame['sys_requirements']); ?>" required pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия"></label><br>
                    <label>Стиль: 
                        <select name="style" required>
                            <option value="Стратегия" <?php if ($editGame['style'] === 'Стратегия') echo 'selected'; ?>>Стратегия</option>
                            <option value="Шутер" <?php if ($editGame['style'] === 'Шутер') echo 'selected'; ?>>Шутер</option>
                            <option value="RPG" <?php if ($editGame['style'] === 'RPG') echo 'selected'; ?>>RPG</option>
                            <option value="Симулятор" <?php if ($editGame['style'] === 'Симулятор') echo 'selected'; ?>>Симулятор</option>
                        </select>
                    </label><br>
                    <label>Иконка: <input type="file" name="icon" accept="image/*"></label><br>
                    <p>Текущая иконка: <?php echo htmlspecialchars($editGame['icon']); ?></p>
                    <button type="submit" name="edit_game">Сохранить изменения</button>
                    <button type="submit" name="delete_game" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить эту игру?');">Удалить игру</button>
                </form>
            <?php endif; ?>

            <a href="?logout=1">Выйти</a>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>Developed by Pogorelov Demid</p>
    </footer>
</body>
</html>