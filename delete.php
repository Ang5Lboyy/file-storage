<?php
// delete.php
require_once 'config/database.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Доступ запрещен');
}

$id = (int)$_GET['id'];

$conn = getDBConnection();
$stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: admin.php?deleted=1');
} else {
    echo "Ошибка удаления: " . $conn->error;
}

$stmt->close();
$conn->close();
?>