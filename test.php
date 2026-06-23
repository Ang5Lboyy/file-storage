<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Тест PHP работает!<br>";

require_once 'config/database.php';

echo "Подключение к БД...<br>";

try {
    $conn = getDBConnection();
    echo "✅ Подключено к БД!<br>";
    
    $result = $conn->query("SELECT 1");
    echo "✅ Запрос выполнен!<br>";
    
    $conn->close();
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>