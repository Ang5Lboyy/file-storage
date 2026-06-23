<?php
// check_all.php - полная проверка всех настроек
echo "<h2>📊 ПОЛНАЯ ПРОВЕРКА НАСТРОЕК</h2>";

echo "<h3>PHP настройки:</h3>";
echo "upload_max_filesize: <strong>" . ini_get('upload_max_filesize') . "</strong><br>";
echo "post_max_size: <strong>" . ini_get('post_max_size') . "</strong><br>";
echo "memory_limit: <strong>" . ini_get('memory_limit') . "</strong><br>";
echo "max_execution_time: <strong>" . ini_get('max_execution_time') . "</strong> сек<br>";
echo "max_input_time: <strong>" . ini_get('max_input_time') . "</strong> сек<br>";

echo "<h3>MySQL настройки:</h3>";
require_once 'config/database.php';
$conn = getDBConnection();

$result = $conn->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
$row = $result->fetch_assoc();
$gb = round($row['Value'] / 1024 / 1024 / 1024, 2);
echo "max_allowed_packet: <strong>" . $gb . " GB</strong><br>";

$result = $conn->query("SHOW VARIABLES LIKE 'wait_timeout'");
$row = $result->fetch_assoc();
echo "wait_timeout: <strong>" . $row['Value'] . "</strong> сек (" . round($row['Value']/3600, 1) . " часов)<br>";

$result = $conn->query("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
$row = $result->fetch_assoc();
$gb = round($row['Value'] / 1024 / 1024 / 1024, 2);
echo "innodb_buffer_pool_size: <strong>" . $gb . " GB</strong><br>";

$conn->close();

echo "<h3>Диск:</h3>";
$free = disk_free_space('C:');
echo "Свободно на диске C: <strong>" . round($free / 1024 / 1024 / 1024, 2) . " GB</strong><br>";

if ($free < 100 * 1024 * 1024 * 1024) {
    echo "<p style='color:red;'>⚠️ Нужно минимум 100 ГБ свободного места!</p>";
} else {
    echo "<p style='color:green;'>✅ Места достаточно!</p>";
}
?>