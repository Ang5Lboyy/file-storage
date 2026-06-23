<?php
// admin.php - с ПЛАВНЫМ реальным прогрессом
session_start();
require_once 'config/database.php';

// Проверка пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Неверный пароль!';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Вход</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div class="container">
            <h1>👑 Вход в админ-панель</h1>
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Пароль администратора:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
            <p><a href="index.php">← Вернуться к загрузке</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Получаем файлы с поиском
$conn = getDBConnection();

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $conn->prepare("SELECT id, original_name, file_size, file_type, upload_date, ip_address, file_path FROM files WHERE original_name LIKE ? ORDER BY id DESC");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $files_result = $stmt->get_result();
    $search_term = htmlspecialchars($_GET['search']);
} else {
    $files_result = $conn->query("SELECT id, original_name, file_size, file_type, upload_date, ip_address, file_path FROM files ORDER BY id DESC");
    $search_term = '';
}

if (!$files_result) {
    die("Ошибка запроса: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .progress-bar-container {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            height: 22px;
            min-width: 120px;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 5px;
            transition: width 0.5s ease-out;
            color: white;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 0%;
            position: relative;
            overflow: hidden;
        }
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .progress-bar.done::after {
            display: none;
        }
        .progress-text {
            font-size: 12px;
            color: #333;
            font-weight: 500;
            margin-left: 5px;
        }
        .progress-status {
            font-size: 11px;
            color: #888;
            margin-left: 5px;
        }
        .download-btn {
            background: #48bb78;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        .download-btn:hover {
            background: #38a169;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(72,187,120,0.3);
        }
        .download-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        .download-btn.downloading {
            background: #ed8936;
            animation: pulse 1s infinite;
        }
        .download-btn.done {
            background: #48bb78;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .file-info {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>👑 Админ-панель</h1>
            <div class="admin-actions">
                <a href="?logout=1" class="btn btn-danger">Выйти</a>
                <a href="index.php" class="btn btn-secondary">← На главную</a>
            </div>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="message success">✅ Файл успешно удален!</div>
        <?php endif; ?>
        
        <div class="search-section">
            <form method="GET" class="search-form">
                <div class="form-group" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="search" placeholder="Поиск по имени файла..." 
                           value="<?php echo $search_term; ?>" 
                           style="flex: 1; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    <button type="submit" class="btn btn-primary">🔍 Найти</button>
                    <?php if ($search_term): ?>
                        <a href="admin.php" class="btn btn-secondary">Сбросить</a>
                    <?php endif; ?>
                </div>
            </form>
            <?php if ($search_term): ?>
                <p style="margin-top: 10px; color: #718096;">
                    Результаты поиска: <strong>"<?php echo $search_term; ?>"</strong>
                    (найдено: <?php echo $files_result->num_rows; ?> файлов)
                </p>
            <?php endif; ?>
        </div>
        
        <div class="stats-section">
            <h2>Статистика</h2>
            <?php
            $total_files = $files_result->num_rows;
            $total_size = 0;
            if ($files_result->num_rows > 0) {
                $rows = [];
                while ($row = $files_result->fetch_assoc()) {
                    $rows[] = $row;
                    $total_size += $row['file_size'];
                }
                $display_rows = $rows;
                $total_files = count($rows);
            } else {
                $display_rows = [];
            }
            ?>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Всего файлов:</span>
                    <span class="stat-value"><?php echo $total_files; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Общий размер:</span>
                    <span class="stat-value"><?php echo formatFileSize($total_size); ?></span>
                </div>
            </div>
        </div>
        
        <div class="files-section">
            <h2>Список файлов</h2>
            <?php if ($total_files > 0): ?>
                <table class="files-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя файла</th>
                            <th>Размер</th>
                            <th>Тип</th>
                            <th>IP адрес</th>
                            <th>Дата загрузки</th>
                            <th>Прогресс</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($display_rows as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['original_name']); ?></td>
                                <td><?php echo formatFileSize($row['file_size']); ?></td>
                                <td><?php echo getFileTypeName($row['file_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                <td><?php echo $row['upload_date']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <div class="progress-bar-container" style="flex: 1; min-width: 80px;">
                                            <div class="progress-bar" id="progress-<?php echo $row['id']; ?>" style="width: 0%;">0%</div>
                                        </div>
                                        <span class="progress-text" id="progress-text-<?php echo $row['id']; ?>">0%</span>
                                        <span class="progress-status" id="progress-status-<?php echo $row['id']; ?>">⏸️</span>
                                    </div>
                                </td>
                                <td>
                                    <button class="download-btn" id="btn-<?php echo $row['id']; ?>" 
                                            onclick="startDownload(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['original_name']); ?>')">
                                        ⬇️ Скачать
                                    </button>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Удалить файл?')">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-files">
                    <?php if ($search_term): ?>
                        ❌ Нет файлов, соответствующих запросу "<strong><?php echo $search_term; ?></strong>"
                    <?php else: ?>
                        📁 Нет загруженных файлов
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Хранилище для отслеживания загрузок
        const downloads = {};

        function startDownload(fileId, fileName) {
            const progressBar = document.getElementById('progress-' + fileId);
            const progressText = document.getElementById('progress-text-' + fileId);
            const progressStatus = document.getElementById('progress-status-' + fileId);
            const btn = document.getElementById('btn-' + fileId);
            
            // Если уже скачивается - не даем нажать
            if (btn.disabled) return;
            
            // Меняем состояние кнопки
            btn.textContent = '⏳ Подготовка...';
            btn.disabled = true;
            btn.classList.add('downloading');
            
            // Сбрасываем прогресс с анимацией
            progressBar.style.transition = 'width 0.5s ease-out';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressText.textContent = '0%';
            progressStatus.textContent = '⏳ Подготовка...';
            progressBar.style.background = 'linear-gradient(90deg, #667eea, #764ba2)';
            progressBar.classList.remove('done');
            
            // Создаем XMLHttpRequest для скачивания
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'download.php?id=' + fileId, true);
            xhr.responseType = 'blob';
            
            let lastPercent = 0;
            let startTime = Date.now();
            let isComplete = false;
            
            // Отслеживаем прогресс
            xhr.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    
                    // Плавно обновляем прогресс-бар
                    if (percent > lastPercent) {
                        lastPercent = percent;
                        progressBar.style.width = percent + '%';
                        progressBar.textContent = percent + '%';
                        progressText.textContent = percent + '%';
                        
                        // Меняем статус
                        if (percent < 30) {
                            progressStatus.textContent = '⏳ Начинаем...';
                        } else if (percent < 60) {
                            progressStatus.textContent = '⏳ Загрузка...';
                        } else if (percent < 90) {
                            progressStatus.textContent = '⏳ Почти готово...';
                        } else if (percent < 100) {
                            progressStatus.textContent = '⏳ Завершаем...';
                        }
                        
                        // Меняем цвет при 100%
                        if (percent >= 100) {
                            progressBar.style.background = 'linear-gradient(90deg, #48bb78, #38a169)';
                            progressStatus.textContent = '✅ Готово!';
                        }
                    }
                }
            });
            
            // Когда файл полностью загружен
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    isComplete = true;
                    
                    // Создаем ссылку для скачивания
                    const url = window.URL.createObjectURL(xhr.response);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    // Обновляем UI
                    progressBar.style.transition = 'width 0.3s ease-out';
                    progressBar.style.width = '100%';
                    progressBar.textContent = '✅ 100%';
                    progressText.textContent = '100%';
                    progressStatus.textContent = '✅ Скачано!';
                    progressBar.style.background = 'linear-gradient(90deg, #48bb78, #38a169)';
                    progressBar.classList.add('done');
                    
                    btn.textContent = '✅ Готово';
                    btn.disabled = false;
                    btn.classList.remove('downloading');
                    btn.classList.add('done');
                    
                    // Возвращаем кнопку в исходное состояние через 3 секунды
                    setTimeout(() => {
                        btn.textContent = '⬇️ Скачать';
                        btn.classList.remove('done');
                        progressStatus.textContent = '⏸️';
                        progressBar.style.background = 'linear-gradient(90deg, #667eea, #764ba2)';
                        progressBar.classList.remove('done');
                    }, 3000);
                } else {
                    alert('❌ Ошибка скачивания! (Статус: ' + xhr.status + ')');
                    resetUI();
                }
            });
            
            // Обработка ошибок
            xhr.addEventListener('error', function() {
                alert('❌ Ошибка сети! Проверьте подключение.');
                resetUI();
            });
            
            // Обработка прерывания
            xhr.addEventListener('abort', function() {
                resetUI();
            });
            
            // Функция сброса UI при ошибке
            function resetUI() {
                btn.textContent = '⬇️ Скачать';
                btn.disabled = false;
                btn.classList.remove('downloading');
                progressStatus.textContent = '❌ Ошибка';
                progressBar.style.background = 'linear-gradient(90deg, #fc8181, #f56565)';
                setTimeout(() => {
                    progressBar.style.background = 'linear-gradient(90deg, #667eea, #764ba2)';
                    progressStatus.textContent = '⏸️';
                }, 3000);
            }
            
            // Таймаут на случай, если загрузка зависла (5 минут)
            const timeout = setTimeout(() => {
                if (!isComplete) {
                    xhr.abort();
                    alert('⏰ Превышено время ожидания! Попробуйте снова.');
                    resetUI();
                }
            }, 300000); // 5 минут
            
            // Отправляем запрос
            xhr.send();
        }
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getFileTypeName($mime_type) {
    $types = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
        'application/msword' => 'DOC',
        'application/pdf' => 'PDF',
        'application/zip' => 'ZIP',
        'application/x-zip-compressed' => 'ZIP',
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
        'image/gif' => 'GIF',
        'video/mp4' => 'MP4',
        'audio/mpeg' => 'MP3',
    ];
    return $types[$mime_type] ?? $mime_type;
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>