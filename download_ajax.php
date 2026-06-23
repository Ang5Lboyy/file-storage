<?php
// download_ajax.php - скачивание с AJAX прогрессом
require_once 'config/database.php';

$file_id = (int)$_GET['id'];

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT original_name, file_type, file_path, file_size FROM files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($file && file_exists($file['file_path'])) {
    $file_size = filesize($file['file_path']);
    
    header('Content-Type: ' . $file['file_type']);
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: private');
    header('Pragma: public');
    
    $fp = fopen($file['file_path'], 'rb');
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
} else {
    die('Файл не найден');
}
?>