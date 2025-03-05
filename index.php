<?php
require 'db.php';

// Получение всех игр по умолчанию
$stmt = $pdo->query("SELECT * FROM games");
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Игровая база</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <h1>Список игр</h1>

        <div class="filters">
            <label>Поиск по названию: 
                <input type="text" id="searchName" placeholder="Введите название игры" pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия">
            </label>
            <label>Фирма-производитель: 
                <input type="text" id="searchCompany" placeholder="Введите фирму" pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия">
            </label>
            <label>Системные требования: 
                <input type="text" id="searchSysReq" placeholder="Например: Intel i5" pattern="[а-яА-Яa-zA-Z0-9\s,\.:]+" title="Только буквы, цифры, пробелы, запятые, точки и двоеточия">
            </label>
            <label>Стиль игры: 
                <select id="searchStyle">
                    <option value="">Все</option>
                    <option value="Стратегия">Стратегия</option>
                    <option value="Шутер">Шутер</option>
                    <option value="RPG">RPG</option>
                    <option value="Симулятор">Симулятор</option>
                </select>
            </label>
        </div>

        <div class="game-list" id="gameList">
            <?php if (empty($games)): ?>
                <p>Игры не найдены.</p>
            <?php else: ?>
                <?php foreach ($games as $game): ?>
                    <div class="game-block" onclick="showModal(<?php echo $game['id']; ?>)">
                        <img src="icons/<?php echo htmlspecialchars($game['icon']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-icon">
                        <h2><?php echo htmlspecialchars($game['name']); ?></h2>
                        <p><strong>Фирма:</strong> <?php echo htmlspecialchars($game['company']); ?></p>
                        <p><strong>Цена:</strong> <?php echo htmlspecialchars($game['price']); ?> руб.</p>
                        <p><strong>Системные требования:</strong> <?php echo htmlspecialchars($game['sys_requirements']); ?></p>
                        <p><strong>Стиль:</strong> <?php echo htmlspecialchars($game['style']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="admin.php">Вход для администратора</a>
    </div>

    <div id="gameModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">×</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <footer class="footer">
        <p>Developed by Pogorelov Demid</p>
    </footer>

    <script>
        const searchName = document.getElementById('searchName');
        const searchCompany = document.getElementById('searchCompany');
        const searchSysReq = document.getElementById('searchSysReq');
        const searchStyle = document.getElementById('searchStyle');
        const gameList = document.getElementById('gameList');

        function updateGameList() {
            const name = searchName.value.trim();
            const company = searchCompany.value.trim();
            const sysReq = searchSysReq.value.trim();
            const style = searchStyle.value;

            // Проверка на специальные символы
            const validInput = /^[а-яА-Яa-zA-Z0-9\s,\.:]*$/u;
            if ((name && !validInput.test(name)) || (company && !validInput.test(company)) || (sysReq && !validInput.test(sysReq))) {
                gameList.innerHTML = '<p class="error">Поля поиска должны содержать только буквы, цифры, пробелы, запятые, точки и двоеточия.</p>';
                return;
            }

            fetch('search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&company=${encodeURIComponent(company)}&sysReq=${encodeURIComponent(sysReq)}&style=${encodeURIComponent(style)}`
            })
            .then(response => {
                if (!response.ok) {
                    console.error('Ошибка сервера:', response.status);
                    throw new Error('Ошибка запроса');
                }
                return response.text();
            })
            .then(data => {
                if (data.trim() === '') {
                    gameList.innerHTML = '<p>Игры не найдены.</p>';
                } else {
                    gameList.innerHTML = data;
                    const gameBlocks = document.querySelectorAll('.game-block');
                    gameBlocks.forEach(block => {
                        block.classList.remove('visible');
                        observer.observe(block);
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                gameList.innerHTML = '<p>Ошибка загрузки данных. Проверьте консоль.</p>';
            });
        }

        searchName.addEventListener('input', updateGameList);
        searchCompany.addEventListener('input', updateGameList);
        searchSysReq.addEventListener('input', updateGameList);
        searchStyle.addEventListener('change', updateGameList);

        function showModal(id) {
            fetch(`get_game.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                    const modal = document.getElementById('gameModal');
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
        }

        function closeModal() {
            const modal = document.getElementById('gameModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('gameModal');
            if (event.target === modal) {
                closeModal();
            }
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                } else {
                    entry.target.classList.remove('visible');
                }
            });
        }, { threshold: 0.1 });

        const gameBlocks = document.querySelectorAll('.game-block');
        gameBlocks.forEach(block => observer.observe(block));
    </script>
</body>
</html>