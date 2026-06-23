<?php
// test_upload.php - диагностика загрузки
echo "<h2>🔍 Диагностика загрузки файлов</h2>";

// 1. Проверка настроек PHP
echo "<h3>1. Настройки PHP:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " сек<br>";

// 2. Проверка ошибок загрузки
echo "<h3>2. Коды ошибок загрузки:</h3>";
echo "UPLOAD_ERR_OK: " . UPLOAD_ERR_OK . " - Нет ошибок<br>";
echo "UPLOAD_ERR_INI_SIZE: " . UPLOAD_ERR_INI_SIZE . " - Файл превышает upload_max_filesize<br>";
echo "UPLOAD_ERR_FORM_SIZE: " . UPLOAD_ERR_FORM_SIZE . " - Файл превышает MAX_FILE_SIZE<br>";
echo "UPLOAD_ERR_PARTIAL: " . UPLOAD_ERR_PARTIAL . " - Файл загружен частично<br>";
echo "UPLOAD_ERR_NO_FILE: " . UPLOAD_ERR_NO_FILE . " - Файл не загружен<br>";

// 3. Проверка свободного места
echo "<h3>3. Свободное место:</h3>";
$free = disk_free_space('.');
echo "Свободно на диске: " . round($free / 1024 / 1024 / 1024, 2) . " GB<br>";

// 4. Проверка размера вашего ZIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    echo "<h3>4. Информация о загруженном файле:</h3>";
    $file = $_FILES['file'];
    echo "Имя: " . $file['name'] . "<br>";
    echo "Размер: " . round($file['size'] / 1024 / 1024, 2) . " MB<br>";
    echo "Ошибка: " . $file['error'] . "<br>";
    
    if ($file['error'] == UPLOAD_ERR_OK) {
        echo "✅ Файл загружен!<br>";
    } elseif ($file['error'] == UPLOAD_ERR_INI_SIZE) {
        echo "❌ Файл превышает upload_max_filesize (1 ГБ)!<br>";
    } elseif ($file['error'] == UPLOAD_ERR_PARTIAL) {
        echo "❌ Файл загружен частично!<br>";
    } else {
        echo "❌ Ошибка загрузки: " . $file['error'] . "<br>";
    }
}
?>

<h3>5. Загрузите файл для теста:</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Загрузить</button>
</form>