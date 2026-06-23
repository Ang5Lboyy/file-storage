<?php
// index.php - с РЕАЛЬНЫМ прогрессом загрузки
    ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config/database.php';

$message = '';
$message_type = '';

// Создаем папку для загрузок
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            0 => 'Нет ошибки',
            1 => 'Файл превышает upload_max_filesize',
            2 => 'Файл превышает MAX_FILE_SIZE',
            3 => 'Файл загружен частично',
            4 => 'Файл не выбран',
            6 => 'Нет временной папки',
            7 => 'Ошибка записи на диск',
            8 => 'Загрузка остановлена расширением'
        ];
        $message = '❌ Ошибка: ' . ($errors[$file['error']] ?? 'Код ' . $file['error']);
        $message_type = 'error';
    } else {
        if ($file['size'] == 0) {
            $message = '❌ Файл пустой!';
            $message_type = 'error';
        } else {
            try {
                // Проверка свободного места
                $free_space = disk_free_space('.');
                if ($file['size'] > $free_space) {
                    $message = '❌ Нет места на диске! Свободно: ' . round($free_space/1024/1024/1024, 2) . ' ГБ';
                    $message_type = 'error';
                } else {
                    $conn = getDBConnection();
                    
                    $filename = $file['name'];
                    $file_size = $file['size'];
                    $file_type = $file['type'];
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    // Генерируем уникальное имя
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $ext;
                    $file_path = $upload_dir . $new_filename;
                    
                    // ПЕРЕМЕЩАЕМ ФАЙЛ НА ДИСК!
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        // Сохраняем в БД только ПУТЬ
                        $stmt = $conn->prepare("INSERT INTO files (filename, original_name, file_size, file_type, file_path, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssisss", $new_filename, $filename, $file_size, $file_type, $file_path, $ip_address);
                        
                        if ($stmt->execute()) {
                            $message = '✅ Файл ЗАГРУЖЕН! ID: ' . $conn->insert_id . ' (' . round($file_size/1024/1024, 2) . ' MB)';
                            $message_type = 'success';
                        } else {
                            unlink($file_path);
                            $message = '❌ Ошибка БД: ' . $conn->error;
                            $message_type = 'error';
                        }
                        $stmt->close();
                    } else {
                        $message = '❌ НЕ УДАЛОСЬ СОХРАНИТЬ ФАЙЛ! Проверьте папку uploads/';
                        $message_type = 'error';
                    }
                    $conn->close();
                }
            } catch (Exception $e) {
                $message = '❌ Ошибка: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка файлов</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .upload-section {
            position: relative;
        }
        .progress-container {
            margin: 20px 0;
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .progress-container.active {
            display: block;
        }
        .progress-bar-wrapper {
            width: 100%;
            background-color: #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            height: 30px;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 8px;
            transition: width 0.3s ease-out;
            color: white;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 0%;
            position: relative;
            overflow: hidden;
            min-width: 40px;
        }
        .progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .progress-bar-fill.done::after {
            display: none;
        }
        .progress-bar-fill.error {
            background: linear-gradient(90deg, #fc8181, #f56565);
        }
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 14px;
            color: #4a5568;
            flex-wrap: wrap;
            gap: 8px;
        }
        .progress-stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .progress-stats span {
            background: #edf2f7;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
        }
        .progress-status {
            font-weight: 500;
            color: #2d3748;
        }
        .progress-status.success {
            color: #48bb78;
        }
        .progress-status.error {
            color: #fc8181;
        }
        .file-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px dashed #cbd5e0;
        }
        .file-info .file-name {
            font-weight: 600;
            color: #2d3748;
            word-break: break-all;
        }
        .file-info .file-size {
            color: #718096;
            font-size: 14px;
        }
        .btn-upload {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-upload:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-upload:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        .btn-upload.loading {
            background: #ed8936;
            animation: pulse 1.2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .drop-zone {
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f7fafc;
        }
        .drop-zone:hover {
            border-color: #667eea;
            background: #edf2f7;
        }
        .drop-zone.dragover {
            border-color: #667eea;
            background: #e2e8f0;
            transform: scale(1.01);
        }
        .drop-zone-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        .drop-zone-text {
            color: #4a5568;
            font-size: 16px;
        }
        .drop-zone-text strong {
            color: #667eea;
        }
        #fileInput {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Загрузка файлов</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h2>Загрузить файл</h2>
            
            <div class="file-info">
                <div>
                    📊 Макс. размер: <strong>100 ГБ</strong>
                </div>
                <div>
                    💾 Свободно: <strong><?php echo round(disk_free_space('.') / 1024 / 1024 / 1024, 2); ?> ГБ</strong>
                </div>
            </div>

            <!-- Drop Zone -->
            <div class="drop-zone" id="dropZone">
                <span class="drop-zone-icon">📤</span>
                <div class="drop-zone-text">
                    <strong>Нажмите</strong> или <strong>перетащите</strong> файл сюда
                </div>
                <div style="font-size: 13px; color: #a0aec0; margin-top: 5px;">
                    Максимальный размер: 100 ГБ
                </div>
            </div>

            <form id="uploadForm" action="" method="POST" enctype="multipart/form-data">
                <input type="file" name="file" id="fileInput" required>
                <button type="submit" class="btn-upload" id="uploadBtn">🚀 Загрузить</button>
            </form>

            <!-- Прогресс загрузки -->
            <div class="progress-container" id="progressContainer">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 600; color: #2d3748;" id="progressFileName">Файл</span>
                    <span style="font-size: 13px; color: #718096;" id="progressFileSize">0 MB</span>
                </div>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar-fill" id="progressBar">0%</div>
                </div>
                <div class="progress-info">
                    <div class="progress-stats">
                        <span id="progressPercent">0%</span>
                        <span id="progressSpeed">0 MB/s</span>
                        <span id="progressTime">⏳ 0 сек</span>
                    </div>
                    <div class="progress-status" id="progressStatus">⏳ Подготовка...</div>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <p>Поддерживаемые форматы: любые (ZIP, PDF, DOC, JPG, PNG, EXE, и т.д.)</p>
            <p><a href="admin.php" class="btn btn-admin">👑 Админ-панель</a></p>
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            // Элементы
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const uploadForm = document.getElementById('uploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const progressSpeed = document.getElementById('progressSpeed');
            const progressTime = document.getElementById('progressTime');
            const progressStatus = document.getElementById('progressStatus');
            const progressFileName = document.getElementById('progressFileName');
            const progressFileSize = document.getElementById('progressFileSize');

            let startTime = 0;
            let lastLoaded = 0;
            let lastTime = 0;
            let speed = 0;
            let isUploading = false;

            // Drag & Drop
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    updateFileInfo(files[0]);
                }
            });

            dropZone.addEventListener('click', function() {
                fileInput.click();
            });

            // При выборе файла
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    updateFileInfo(this.files[0]);
                }
            });

            function updateFileInfo(file) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                dropZone.innerHTML = `
                    <span class="drop-zone-icon">📄</span>
                    <div class="drop-zone-text">
                        <strong>${file.name}</strong>
                    </div>
                    <div style="font-size: 14px; color: #4a5568; margin-top: 5px;">
                        ${size} MB
                    </div>
                    <div style="font-size: 13px; color: #a0aec0; margin-top: 3px;">
                        Нажмите, чтобы выбрать другой файл
                    </div>
                `;
            }

            // Отправка формы с прогрессом
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const file = fileInput.files[0];
                if (!file) {
                    alert('Выберите файл!');
                    return;
                }

                // Проверка размера
                const maxSize = 100 * 1024 * 1024 * 1024; // 100 GB
                if (file.size > maxSize) {
                    alert('❌ Файл слишком большой! Максимум 100 ГБ.');
                    return;
                }

                // Проверка свободного места
                const freeSpace = <?php echo disk_free_space('.') ?>;
                if (file.size > freeSpace) {
                    alert('❌ Недостаточно места на диске!');
                    return;
                }

                // Показываем прогресс
                progressContainer.classList.add('active');
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressPercent.textContent = '0%';
                progressSpeed.textContent = '0 MB/s';
                progressTime.textContent = '⏳ 0 сек';
                progressStatus.textContent = '⏳ Подготовка...';
                progressStatus.className = 'progress-status';
                progressBar.className = 'progress-bar-fill';
                progressFileName.textContent = file.name;
                progressFileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                // Отключаем кнопку
                uploadBtn.disabled = true;
                uploadBtn.textContent = '⏳ Загрузка...';
                uploadBtn.classList.add('loading');

                // Сброс переменных
                startTime = Date.now();
                lastLoaded = 0;
                lastTime = startTime;
                speed = 0;
                isUploading = true;

                // Создаем FormData
                const formData = new FormData();
                formData.append('file', file);

                // Создаем XMLHttpRequest
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'index.php', true);

                // Отслеживаем прогресс загрузки
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        const now = Date.now();
                        const timeDiff = (now - lastTime) / 1000; // секунды
                        const bytesDiff = e.loaded - lastLoaded;
                        
                        // Вычисляем скорость
                        if (timeDiff > 0) {
                            speed = (bytesDiff / timeDiff) / (1024 * 1024); // MB/s
                            speed = Math.round(speed * 100) / 100;
                        }
                        
                        // Время загрузки
                        const elapsed = Math.round((now - startTime) / 1000);
                        
                        // Обновляем UI
                        progressBar.style.width = percent + '%';
                        progressBar.textContent = percent + '%';
                        progressPercent.textContent = percent + '%';
                        progressSpeed.textContent = speed.toFixed(2) + ' MB/s';
                        progressTime.textContent = '⏳ ' + elapsed + ' сек';
                        
                        // Статус
                        if (percent < 30) {
                            progressStatus.textContent = '⏳ Начинаем загрузку...';
                        } else if (percent < 60) {
                            progressStatus.textContent = '⏳ Загрузка...';
                        } else if (percent < 90) {
                            progressStatus.textContent = '⏳ Почти готово...';
                        } else if (percent < 100) {
                            progressStatus.textContent = '⏳ Завершаем...';
                        }

                        // Сохраняем для расчета скорости
                        lastLoaded = e.loaded;
                        lastTime = now;
                    }
                });

                // Когда загрузка завершена
                xhr.addEventListener('load', function() {
                    isUploading = false;
                    
                    if (xhr.status === 200) {
                        progressBar.style.width = '100%';
                        progressBar.textContent = '✅ 100%';
                        progressPercent.textContent = '100%';
                        progressStatus.textContent = '✅ Загрузка завершена!';
                        progressStatus.className = 'progress-status success';
                        progressBar.className = 'progress-bar-fill done';
                        
                        // Обновляем страницу через 1 секунду, чтобы показать сообщение
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        progressStatus.textContent = '❌ Ошибка загрузки! Код: ' + xhr.status;
                        progressStatus.className = 'progress-status error';
                        progressBar.className = 'progress-bar-fill error';
                        
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = '🚀 Загрузить';
                        uploadBtn.classList.remove('loading');
                    }
                });

                // Обработка ошибок
                xhr.addEventListener('error', function() {
                    isUploading = false;
                    progressStatus.textContent = '❌ Ошибка сети!';
                    progressStatus.className = 'progress-status error';
                    progressBar.className = 'progress-bar-fill error';
                    
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = '🚀 Загрузить';
                    uploadBtn.classList.remove('loading');
                });

                // Отправляем
                xhr.send(formData);
            });

            // Восстановление кнопки если пользователь обновляет страницу
            window.addEventListener('beforeunload', function() {
                if (isUploading) {
                    return 'Загрузка еще не завершена. Вы уверены?';
                }
            });

        })();
    </script>
</body>
</html>