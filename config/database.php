<?php
// config/database.php - ДЛЯ INFINITYFREE
define('DB_HOST', 'sql205.infinityfree.com');
define('DB_USER', 'if0_42245941');  // ← ИМЯ ПОЛЬЗОВАТЕЛЯ (не БД!)
define('DB_PASS', 'K1ckScupbUD0Bxa');
define('DB_NAME', 'if0_42245941_file_storage');  // ← ИМЯ БД

function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}
?>