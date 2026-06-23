<?php
// check_file.php - проверка файлов на диске
require_once 'config/database.php';

$conn = getDBConnection();

echo "<h2>📁 Проверка файлов</h2>";

$result = $conn->query("SELECT id, original_name, file_path, file_size FROM files");

if ($result->num_rows === 0) {
    echo "❌ Нет файлов в БД<br>";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "<hr>";
        echo "ID: <strong>" . $row['id'] . "</strong><br>";
        echo "Имя: <strong>" . htmlspecialchars($row['original_name']) . "</strong><br>";
        echo "Путь в БД: <code>" . htmlspecialchars($row['file_path']) . "</code><br>";
        
        // Проверяем файл
        if (!empty($row['file_path'])) {
            if (file_exists($row['file_path'])) {
                $size = filesize($row['file_path']);
                echo "✅ Файл НА ДИСКЕ! Размер: " . round($size/1024/1024, 2) . " MB<br>";
                echo "📂 Полный путь: <code>" . realpath($row['file_path']) . "</code><br>";
            } else {
                echo "❌ ФАЙЛ НЕ НАЙДЕН НА ДИСКЕ!<br>";
                echo "Проверьте путь: <code>" . $row['file_path'] . "</code><br>";
                
                // Проверяем папку uploads
                $upload_dir = 'uploads/';
                echo "Файлы в папке uploads: <br>";
                if (is_dir($upload_dir)) {
                    $files = scandir($upload_dir);
                    foreach ($files as $f) {
                        if ($f != '.' && $f != '..') {
                            echo "  - " . $f . " (" . round(filesize($upload_dir . $f)/1024/1024, 2) . " MB)<br>";
                        }
                    }
                } else {
                    echo "  ❌ Папка uploads не существует!<br>";
                }
            }
        } else {
            echo "⚠️ Путь к файлу пустой!<br>";
        }
    }
}

$conn->close();
?>