<?php
// download.php
require_once 'config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Неверный ID файла');
}

$file_id = (int)$_GET['id'];

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT original_name, file_type, file_path FROM files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die('Файл не найден');
    }
    
    $file = $result->fetch_assoc();
    
    if (!empty($file['file_path']) && file_exists($file['file_path'])) {
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . filesize($file['file_path']));
        header('Cache-Control: private');
        header('Pragma: public');
        
        readfile($file['file_path']);
        exit;
    } else {
        die('Файл не найден на сервере');
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    die('Ошибка: ' . $e->getMessage());
}
?>