<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем имя пользователя
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $user['username'] ?? 'Гость';

// Функция для генерации короткого кода
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $short_code = '';
    for ($i = 0; $i < $length; $i++) {
        $short_code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $short_code;
}

// Обработка формы создания ссылки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_url = trim($_POST['url']);

    // Простая валидация URL
    if (!empty($original_url) && filter_var($original_url, FILTER_VALIDATE_URL)) {
        // Проверяем, существует ли URL уже в базе
        $stmt = $pdo->prepare("SELECT id FROM links WHERE original_url = :url AND user_id = :user_id");
        $stmt->execute(['url' => $original_url, 'user_id' => $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = "Этот URL уже сокращён!";
        } else {
            // Генерируем уникальный короткий код
            do {
                $short_code = generateShortCode();
                $stmt = $pdo->prepare("SELECT id FROM links WHERE short_code = :short_code");
                $stmt->execute(['short_code' => $short_code]);
            } while ($stmt->fetch()); // Повторяем, пока не найдём уникальный код

            $stmt = $pdo->prepare("INSERT INTO links (user_id, original_url, short_code) VALUES (:user_id, :original_url, :short_code)");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'original_url' => $original_url,
                'short_code' => $short_code
            ]);
            header('Location: index.php');
            exit;
        }
    } else {
        $error = "Введите корректный URL!";
    }
}

// Получаем список ссылок пользователя
$stmt = $pdo->prepare("SELECT * FROM links WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генератор коротких ссылок</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Генератор коротких ссылок</h1>
        <p class="text-muted">Привет, <?php echo htmlspecialchars($username); ?>!</p>

        <!-- Форма создания ссылки -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Создать короткую ссылку</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com">
                    </div>
                    <button type="submit" class="btn btn-primary">Сократить</button>
                </form>
                <?php if (isset($error)) echo "<div class='alert alert-danger mt-3'>$error</div>"; ?>
            </div>
        </div>

        <!-- Список ссылок -->
        <h2 class="mb-3">Ваши ссылки</h2>
        <?php if ($links): ?>
            <div class="row">
                <?php foreach ($links as $link): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <p class="card-text">Оригинал: <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank"><?php echo htmlspecialchars(substr($link['original_url'], 0, 30)) . '...'; ?></a></p>
                                <p class="card-text">Короткая: <a href="s.php?code=<?php echo htmlspecialchars($link['short_code']); ?>" target="_blank"><?php echo htmlspecialchars($link['short_code']); ?></a></p>
                                <p class="card-text"><small class="text-muted">Создано: <?php echo $link['created_at']; ?></small></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Ссылок пока нет.</p>
        <?php endif; ?>
        <p class="mt-3"><a href="logout.php" class="btn btn-outline-secondary">Выйти</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>