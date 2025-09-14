<?php
require 'db.php';

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit;
}

$short_code = trim($_GET['code']);
$stmt = $pdo->prepare("SELECT original_url FROM links WHERE short_code = :short_code");
$stmt->execute(['short_code' => $short_code]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if ($link) {
    header('Location: ' . $link['original_url']);
    exit;
} else {
    header('HTTP/1.0 404 Not Found');
    echo "Ссылка не найдена!";
    exit;
}
?>