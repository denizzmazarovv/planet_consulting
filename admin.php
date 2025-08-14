<?php
session_start();

// Создаем папку для загрузок если она не существует
$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Простая авторизация (в реальном проекте используйте более безопасные методы)
$admin_username = 'admin';
$admin_password = 'password123';

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $login_error = 'Неверный логин или пароль';
        }
    }
}

// Если не авторизован, показываем форму входа
if (!isset($_SESSION['admin_logged_in'])) {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Вход</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #2563eb, #0f172a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .login-header {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .form-control {
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h3><i class="fas fa-lock me-2"></i>Вход в админку</h3>
                        <p class="mb-0">Панель управления сайтом</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($login_error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $login_error; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Логин</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required placeholder="Введите логин">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Пароль</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Введите пароль">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-login w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Войти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Обработка загрузки изображений
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['country_image'])) {
    header('Content-Type: application/json');
    
    $country_id = $_POST['country_id'] ?? 0;
    $response = ['success' => false, 'message' => 'Неизвестная ошибка'];
    
    if (isset($_FILES['country_image']) && $_FILES['country_image']['error'] === 0) {
        $file = $_FILES['country_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'country_' . $country_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
                $response = ['success' => true, 'url' => $image_url];
            } else {
                $response = ['success' => false, 'message' => 'Ошибка при сохранении файла'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Недопустимый тип файла или размер больше 5MB'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Ошибка загрузки файла: ' . ($_FILES['country_image']['error'] ?? 'Неизвестная ошибка')];
    }
    
    echo json_encode($response);
    exit;
}

// Обработка удаления изображений
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    header('Content-Type: application/json');
    
    $image_path = $_POST['image_path'];
    $response = ['success' => false, 'message' => 'Неизвестная ошибка'];
    
    if (file_exists($image_path) && strpos(realpath($image_path), realpath($upload_dir)) === 0) {
        if (unlink($image_path)) {
            $response = ['success' => true, 'message' => 'Изображение удалено'];
        } else {
            $response = ['success' => false, 'message' => 'Ошибка при удалении файла'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Файл не найден или недоступен'];
    }
    
    echo json_encode($response);
    exit;
}

// Обработка добавления новой страны
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_country'])) {
    header('Content-Type: application/json');
    
    // Загружаем текущие данные
    $data_file = 'data.json';
    if (file_exists($data_file)) {
        $data_content = file_get_contents($data_file);
        $data = json_decode($data_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = [];
        }
    } else {
        $data = [];
    }
    
    // Получаем максимальный ID
    $max_id = 0;
    if (isset($data['countries']['list']) && is_array($data['countries']['list'])) {
        foreach ($data['countries']['list'] as $country) {
            if (isset($country['id']) && $country['id'] > $max_id) {
                $max_id = $country['id'];
            }
        }
    }
    
    $new_id = $max_id + 1;
    
    // Создаем новую страну
    $new_country = [
        'id' => $new_id,
        'name' => [
            'ru' => 'Новая страна',
            'uz' => 'Yangi mamlakat',
            'en' => 'New country'
        ],
        'description' => [
            'ru' => 'Описание новой страны',
            'uz' => 'Yangi mamlakat tavsifi',
            'en' => 'Description of new country'
        ],
        'image' => 'https://placehold.co/400x200/2563eb/white?text=New+Country'
    ];
    
    // Добавляем страну в массив
    if (!isset($data['countries'])) {
        $data['countries'] = [];
    }
    if (!isset($data['countries']['list'])) {
        $data['countries']['list'] = [];
    }
    
    $data['countries']['list'][] = $new_country;
    
    // Сохраняем данные
    $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($data_file, $json_content) !== false) {
        $response = ['success' => true, 'message' => 'Страна добавлена', 'country' => $new_country];
    } else {
        $response = ['success' => false, 'message' => 'Ошибка сохранения файла'];
    }
    
    echo json_encode($response);
    exit;
}

// Обработка удаления страны
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_country'])) {
    header('Content-Type: application/json');
    
    $country_id = intval($_POST['country_id']);
    
    // Загружаем текущие данные
    $data_file = 'data.json';
    if (file_exists($data_file)) {
        $data_content = file_get_contents($data_file);
        $data = json_decode($data_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = [];
        }
    } else {
        $data = [];
    }
    
    // Удаляем страну из массива
    if (isset($data['countries']['list']) && is_array($data['countries']['list'])) {
        $new_list = [];
        foreach ($data['countries']['list'] as $country) {
            if (!isset($country['id']) || $country['id'] != $country_id) {
                $new_list[] = $country;
            } else {
                // Если у страны есть загруженное изображение, удаляем его
                if (isset($country['image']) && !empty($country['image']) && 
                    strpos($country['image'], $upload_dir) !== false && file_exists($country['image'])) {
                    unlink($country['image']);
                }
            }
        }
        $data['countries']['list'] = $new_list;
    }
    
    // Сохраняем данные
    $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($data_file, $json_content) !== false) {
        $response = ['success' => true, 'message' => 'Страна удалена'];
    } else {
        $response = ['success' => false, 'message' => 'Ошибка сохранения файла'];
    }
    
    echo json_encode($response);
    exit;
}

// Обработка сохранения данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_data'])) {
    // Загружаем текущие данные
    $data_file = 'data.json';
    if (file_exists($data_file)) {
        $data_content = file_get_contents($data_file);
        $data = json_decode($data_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = [];
        }
    } else {
        $data = [];
    }
    
    // Обновление текстовых данных
    foreach ($_POST as $key => $value) {
        if (strpos($key, '_') !== false && $key !== 'countries_data' && $key !== 'about_items' && $key !== 'stats_items' && $key !== 'footer_links' && $key !== 'footer_services') {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section = $parts[0];
                $field = $parts[1];
                $lang = $parts[2];
                
                if (!isset($data[$section])) {
                    $data[$section] = [];
                }
                if (!isset($data[$section][$field])) {
                    $data[$section][$field] = [];
                }
                
                $data[$section][$field][$lang] = $value;
            }
        }
    }
    
    // Обновление данных стран
    if (isset($_POST['countries_data'])) {
        $countries_data = json_decode($_POST['countries_data'], true);
        if (is_array($countries_data)) {
            $data['countries']['list'] = $countries_data;
        }
    }
    
    // Обновление элементов массивов
    if (isset($_POST['about_items'])) {
        $about_items = json_decode($_POST['about_items'], true);
        if (is_array($about_items)) {
            $data['about']['items'] = $about_items;
        }
    }
    
    if (isset($_POST['stats_items'])) {
        $stats_items = json_decode($_POST['stats_items'], true);
        if (is_array($stats_items)) {
            $data['stats']['items'] = $stats_items;
        }
    }
    
    if (isset($_POST['footer_links'])) {
        $footer_links = json_decode($_POST['footer_links'], true);
        if (is_array($footer_links)) {
            $data['footer']['links'] = $footer_links;
        }
    }
    
    if (isset($_POST['footer_services'])) {
        $footer_services = json_decode($_POST['footer_services'], true);
        if (is_array($footer_services)) {
            $data['footer']['services'] = $footer_services;
        }
    }
    
    // Сохранение в файл
    $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($data_file, $json_content) !== false) {
        $save_success = true;
    } else {
        $save_error = 'Ошибка сохранения файла data.json';
    }
}

// Загрузка данных
$data_file = 'data.json';
if (file_exists($data_file)) {
    $data_content = file_get_contents($data_file);
    $data = json_decode($data_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        $data = [];
    }
} else {
    $data = [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Global Consulting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        
        body {
            background: #f1f5f9;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: #0f172a;
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .nav-link {
            color: #cbd5e1;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 3px solid #f97316;
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-save {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn-add-country {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn-add-country:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .preview-image {
            max-width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }
        
        .country-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            position: relative;
        }
        
        .delete-country-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ef4444;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .delete-country-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        .section-header {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            margin: -1px -1px 0 -1px;
        }
        
        .upload-btn {
            background: #10b981;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .upload-btn:hover {
            background: #059669;
        }
        
        .delete-btn {
            background: #ef4444;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .delete-btn:hover {
            background: #dc2626;
        }
        
        .btn-group-custom {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .upload-progress {
            width: 100%;
            height: 5px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .upload-progress-bar {
            height: 100%;
            background: #10b981;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            .main-content {
                margin-left: 70px;
            }
            .nav-text {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 border-bottom border-gray-700">
            <h4 class="text-white mb-0"><i class="fas fa-cog me-2"></i><span class="nav-text">Админка</span></h4>
        </div>
        <ul class="nav flex-column py-3">
            <li class="nav-item">
                <a class="nav-link active" href="#hero" data-bs-toggle="tab">
                    <i class="fas fa-home fa-lg me-3"></i><span class="nav-text">Главная</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#about" data-bs-toggle="tab">
                    <i class="fas fa-info-circle fa-lg me-3"></i><span class="nav-text">О нас</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#countries" data-bs-toggle="tab">
                    <i class="fas fa-globe fa-lg me-3"></i><span class="nav-text">Страны</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#stats" data-bs-toggle="tab">
                    <i class="fas fa-chart-bar fa-lg me-3"></i><span class="nav-text">Статистика</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#contact" data-bs-toggle="tab">
                    <i class="fas fa-envelope fa-lg me-3"></i><span class="nav-text">Контакты</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#footer" data-bs-toggle="tab">
                    <i class="fas fa-copyright fa-lg me-3"></i><span class="nav-text">Футер</span>
                </a>
            </li>
        </ul>
        <div class="p-4 mt-auto">
            <a href="?logout=true" class="btn btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-2"></i><span class="nav-text">Выход</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Панель управления</h1>
            <button class="btn btn-save" id="saveButton">
                <i class="fas fa-save me-2"></i>Сохранить изменения
            </button>
        </div>

        <?php if (isset($save_success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Данные успешно сохранены!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($save_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $save_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="adminForm" enctype="multipart/form-data">
            <input type="hidden" name="save_data" value="1">
            <input type="hidden" name="countries_data" id="countriesDataInput">
            <input type="hidden" name="about_items" id="aboutItemsInput">
            <input type="hidden" name="stats_items" id="statsItemsInput">
            <input type="hidden" name="footer_links" id="footerLinksInput">
            <input type="hidden" name="footer_services" id="footerServicesInput">

            <div class="tab-content">
                <!-- Hero Section -->
                <div class="tab-pane fade show active" id="hero">
                    <div class="card">
                        <div class="section-header">
                            <h5 class="mb-0"><i class="fas fa-home me-2"></i>Главная секция</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (RU)</label>
                                    <input type="text" class="form-control" name="hero_title_ru" value="<?php echo htmlspecialchars($data['hero']['title']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (UZ)</label>
                                    <input type="text" class="form-control" name="hero_title_uz" value="<?php echo htmlspecialchars($data['hero']['title']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (EN)</label>
                                    <input type="text" class="form-control" name="hero_title_en" value="<?php echo htmlspecialchars($data['hero']['title']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Подзаголовок (RU)</label>
                                    <textarea class="form-control" name="hero_subtitle_ru" rows="3"><?php echo htmlspecialchars($data['hero']['subtitle']['ru'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Подзаголовок (UZ)</label>
                                    <textarea class="form-control" name="hero_subtitle_uz" rows="3"><?php echo htmlspecialchars($data['hero']['subtitle']['uz'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Подзаголовок (EN)</label>
                                    <textarea class="form-control" name="hero_subtitle_en" rows="3"><?php echo htmlspecialchars($data['hero']['subtitle']['en'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Кнопка (RU)</label>
                                    <input type="text" class="form-control" name="hero_button_ru" value="<?php echo htmlspecialchars($data['hero']['button']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Кнопка (UZ)</label>
                                    <input type="text" class="form-control" name="hero_button_uz" value="<?php echo htmlspecialchars($data['hero']['button']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Кнопка (EN)</label>
                                    <input type="text" class="form-control" name="hero_button_en" value="<?php echo htmlspecialchars($data['hero']['button']['en'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About Section -->
                <div class="tab-pane fade" id="about">
                    <div class="card">
                        <div class="section-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>О нас</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (RU)</label>
                                    <input type="text" class="form-control" name="about_title_ru" value="<?php echo htmlspecialchars($data['about']['title']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (UZ)</label>
                                    <input type="text" class="form-control" name="about_title_uz" value="<?php echo htmlspecialchars($data['about']['title']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (EN)</label>
                                    <input type="text" class="form-control" name="about_title_en" value="<?php echo htmlspecialchars($data['about']['title']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (RU)</label>
                                    <textarea class="form-control" name="about_text_ru" rows="3"><?php echo htmlspecialchars($data['about']['text']['ru'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (UZ)</label>
                                    <textarea class="form-control" name="about_text_uz" rows="3"><?php echo htmlspecialchars($data['about']['text']['uz'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (EN)</label>
                                    <textarea class="form-control" name="about_text_en" rows="3"><?php echo htmlspecialchars($data['about']['text']['en'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-4">Элементы о нас:</h5>
                            
                            <div id="aboutItems">
                                <?php 
                                $about_items = $data['about']['items'] ?? [];
                                foreach ($about_items as $index => $item): ?>
                                    <div class="country-item" data-id="<?php echo $index; ?>">
                                        <h6 class="mb-3">Элемент <?php echo $index + 1; ?></h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Заголовок (RU)</label>
                                                <input type="text" class="form-control about-item-title-ru" value="<?php echo htmlspecialchars($item['title']['ru'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Заголовок (UZ)</label>
                                                <input type="text" class="form-control about-item-title-uz" value="<?php echo htmlspecialchars($item['title']['uz'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Заголовок (EN)</label>
                                                <input type="text" class="form-control about-item-title-en" value="<?php echo htmlspecialchars($item['title']['en'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (RU)</label>
                                                <textarea class="form-control about-item-text-ru" rows="2" data-id="<?php echo $index; ?>"><?php echo htmlspecialchars($item['text']['ru'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (UZ)</label>
                                                <textarea class="form-control about-item-text-uz" rows="2" data-id="<?php echo $index; ?>"><?php echo htmlspecialchars($item['text']['uz'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (EN)</label>
                                                <textarea class="form-control about-item-text-en" rows="2" data-id="<?php echo $index; ?>"><?php echo htmlspecialchars($item['text']['en'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Countries Section -->
                <div class="tab-pane fade" id="countries">
                    <div class="card">
                        <div class="section-header">
                            <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Страны</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (RU)</label>
                                    <input type="text" class="form-control" name="countries_title_ru" value="<?php echo htmlspecialchars($data['countries']['title']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (UZ)</label>
                                    <input type="text" class="form-control" name="countries_title_uz" value="<?php echo htmlspecialchars($data['countries']['title']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (EN)</label>
                                    <input type="text" class="form-control" name="countries_title_en" value="<?php echo htmlspecialchars($data['countries']['title']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (RU)</label>
                                    <textarea class="form-control" name="countries_text_ru" rows="3"><?php echo htmlspecialchars($data['countries']['text']['ru'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (UZ)</label>
                                    <textarea class="form-control" name="countries_text_uz" rows="3"><?php echo htmlspecialchars($data['countries']['text']['uz'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (EN)</label>
                                    <textarea class="form-control" name="countries_text_en" rows="3"><?php echo htmlspecialchars($data['countries']['text']['en'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <button type="button" class="btn btn-add-country" id="addCountryBtn">
                                <i class="fas fa-plus me-2"></i>Добавить страну
                            </button>
                            
                            <h5 class="mb-4">Список стран:</h5>
                            <div id="countriesList">
                                <?php 
                                $countries = $data['countries']['list'] ?? [];
                                foreach ($countries as $index => $country): ?>
                                    <div class="country-item" data-id="<?php echo $country['id'] ?? $index; ?>">
                                        <button type="button" class="delete-country-btn" data-id="<?php echo $country['id'] ?? $index; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <img src="<?php echo htmlspecialchars($country['image'] ?? 'https://placehold.co/400x200/cccccc/ffffff?text=No+Image'); ?>" class="preview-image" alt="Превью">
                                            </div>
                                            <div class="col-md-10">
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Название (RU)</label>
                                                        <input type="text" class="form-control country-name-ru" value="<?php echo htmlspecialchars($country['name']['ru'] ?? ''); ?>" data-id="<?php echo $country['id'] ?? $index; ?>">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Название (UZ)</label>
                                                        <input type="text" class="form-control country-name-uz" value="<?php echo htmlspecialchars($country['name']['uz'] ?? ''); ?>" data-id="<?php echo $country['id'] ?? $index; ?>">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Название (EN)</label>
                                                        <input type="text" class="form-control country-name-en" value="<?php echo htmlspecialchars($country['name']['en'] ?? ''); ?>" data-id="<?php echo $country['id'] ?? $index; ?>">
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Описание (RU)</label>
                                                        <textarea class="form-control country-desc-ru" rows="2" data-id="<?php echo $country['id'] ?? $index; ?>"><?php echo htmlspecialchars($country['description']['ru'] ?? ''); ?></textarea>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Описание (UZ)</label>
                                                        <textarea class="form-control country-desc-uz" rows="2" data-id="<?php echo $country['id'] ?? $index; ?>"><?php echo htmlspecialchars($country['description']['uz'] ?? ''); ?></textarea>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Описание (EN)</label>
                                                        <textarea class="form-control country-desc-en" rows="2" data-id="<?php echo $country['id'] ?? $index; ?>"><?php echo htmlspecialchars($country['description']['en'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label">URL изображения</label>
                                                        <input type="text" class="form-control country-image" value="<?php echo htmlspecialchars($country['image'] ?? ''); ?>" data-id="<?php echo $country['id'] ?? $index; ?>">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">ID</label>
                                                        <input type="text" class="form-control country-id" value="<?php echo $country['id'] ?? $index; ?>" readonly>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <label class="form-label">Загрузить новое изображение</label>
                                                        <div class="btn-group-custom">
                                                            <input type="file" class="form-control country-image-upload" data-id="<?php echo $country['id'] ?? $index; ?>" accept="image/*" style="flex: 1; min-width: 200px;">
                                                            <button type="button" class="upload-btn country-upload-btn" data-id="<?php echo $country['id'] ?? $index; ?>">
                                                                <i class="fas fa-upload me-1"></i>Загрузить
                                                            </button>
                                                            <?php if (!empty($country['image']) && strpos($country['image'], $upload_dir) !== false): ?>
                                                                <button type="button" class="delete-btn country-delete-btn" data-path="<?php echo htmlspecialchars($country['image']); ?>">
                                                                    <i class="fas fa-trash me-1"></i>Удалить
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="upload-progress upload-progress-<?php echo $country['id'] ?? $index; ?>" style="display: none;">
                                                            <div class="upload-progress-bar"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="tab-pane fade" id="stats">
                    <div class="card">
                        <div class="section-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Статистика</h5>
                        </div>
                        <div class="card-body">
                            <h5 class="mb-4">Элементы статистики:</h5>
                            
                            <div id="statsItems">
                                <?php 
                                $stats_items = $data['stats']['items'] ?? [];
                                foreach ($stats_items as $index => $item): ?>
                                    <div class="country-item" data-id="<?php echo $index; ?>">
                                        <h6 class="mb-3">Элемент <?php echo $index + 1; ?></h6>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Число</label>
                                                <input type="text" class="form-control stats-item-number" value="<?php echo htmlspecialchars($item['number'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Текст (RU)</label>
                                                <input type="text" class="form-control stats-item-text-ru" value="<?php echo htmlspecialchars($item['text']['ru'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Текст (UZ)</label>
                                                <input type="text" class="form-control stats-item-text-uz" value="<?php echo htmlspecialchars($item['text']['uz'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Текст (EN)</label>
                                                <input type="text" class="form-control stats-item-text-en" value="<?php echo htmlspecialchars($item['text']['en'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="tab-pane fade" id="contact">
                    <div class="card">
                        <div class="section-header">
                            <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Контакты</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (RU)</label>
                                    <input type="text" class="form-control" name="contact_title_ru" value="<?php echo htmlspecialchars($data['contact']['title']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (UZ)</label>
                                    <input type="text" class="form-control" name="contact_title_uz" value="<?php echo htmlspecialchars($data['contact']['title']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (EN)</label>
                                    <input type="text" class="form-control" name="contact_title_en" value="<?php echo htmlspecialchars($data['contact']['title']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (RU)</label>
                                    <textarea class="form-control" name="contact_text_ru" rows="3"><?php echo htmlspecialchars($data['contact']['text']['ru'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (UZ)</label>
                                    <textarea class="form-control" name="contact_text_uz" rows="3"><?php echo htmlspecialchars($data['contact']['text']['uz'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (EN)</label>
                                    <textarea class="form-control" name="contact_text_en" rows="3"><?php echo htmlspecialchars($data['contact']['text']['en'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер имени (RU)</label>
                                    <input type="text" class="form-control" name="contact_namePlaceholder_ru" value="<?php echo htmlspecialchars($data['contact']['namePlaceholder']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер имени (UZ)</label>
                                    <input type="text" class="form-control" name="contact_namePlaceholder_uz" value="<?php echo htmlspecialchars($data['contact']['namePlaceholder']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер имени (EN)</label>
                                    <input type="text" class="form-control" name="contact_namePlaceholder_en" value="<?php echo htmlspecialchars($data['contact']['namePlaceholder']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер email (RU)</label>
                                    <input type="text" class="form-control" name="contact_emailPlaceholder_ru" value="<?php echo htmlspecialchars($data['contact']['emailPlaceholder']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер email (UZ)</label>
                                    <input type="text" class="form-control" name="contact_emailPlaceholder_uz" value="<?php echo htmlspecialchars($data['contact']['emailPlaceholder']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер email (EN)</label>
                                    <input type="text" class="form-control" name="contact_emailPlaceholder_en" value="<?php echo htmlspecialchars($data['contact']['emailPlaceholder']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер телефона (RU)</label>
                                    <input type="text" class="form-control" name="contact_phonePlaceholder_ru" value="<?php echo htmlspecialchars($data['contact']['phonePlaceholder']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер телефона (UZ)</label>
                                    <input type="text" class="form-control" name="contact_phonePlaceholder_uz" value="<?php echo htmlspecialchars($data['contact']['phonePlaceholder']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер телефона (EN)</label>
                                    <input type="text" class="form-control" name="contact_phonePlaceholder_en" value="<?php echo htmlspecialchars($data['contact']['phonePlaceholder']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер сообщения (RU)</label>
                                    <input type="text" class="form-control" name="contact_messagePlaceholder_ru" value="<?php echo htmlspecialchars($data['contact']['messagePlaceholder']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер сообщения (UZ)</label>
                                    <input type="text" class="form-control" name="contact_messagePlaceholder_uz" value="<?php echo htmlspecialchars($data['contact']['messagePlaceholder']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Плейсхолдер сообщения (EN)</label>
                                    <input type="text" class="form-control" name="contact_messagePlaceholder_en" value="<?php echo htmlspecialchars($data['contact']['messagePlaceholder']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Кнопка (RU)</label>
                                    <input type="text" class="form-control" name="contact_button_ru" value="<?php echo htmlspecialchars($data['contact']['button']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Кнопка (UZ)</label>
                                    <input type="text" class="form-control" name="contact_button_uz" value="<?php echo htmlspecialchars($data['contact']['button']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Кнопка (EN)</label>
                                    <input type="text" class="form-control" name="contact_button_en" value="<?php echo htmlspecialchars($data['contact']['button']['en'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Section -->
                <div class="tab-pane fade" id="footer">
                    <div class="card">
                        <div class="section-header">
                            <h5 class="mb-0"><i class="fas fa-copyright me-2"></i>Футер</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (RU)</label>
                                    <input type="text" class="form-control" name="footer_title_ru" value="<?php echo htmlspecialchars($data['footer']['title']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (UZ)</label>
                                    <input type="text" class="form-control" name="footer_title_uz" value="<?php echo htmlspecialchars($data['footer']['title']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Заголовок (EN)</label>
                                    <input type="text" class="form-control" name="footer_title_en" value="<?php echo htmlspecialchars($data['footer']['title']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (RU)</label>
                                    <textarea class="form-control" name="footer_text_ru" rows="3"><?php echo htmlspecialchars($data['footer']['text']['ru'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (UZ)</label>
                                    <textarea class="form-control" name="footer_text_uz" rows="3"><?php echo htmlspecialchars($data['footer']['text']['uz'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Текст (EN)</label>
                                    <textarea class="form-control" name="footer_text_en" rows="3"><?php echo htmlspecialchars($data['footer']['text']['en'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Меню 1 (RU)</label>
                                    <input type="text" class="form-control" name="footer_menu1_ru" value="<?php echo htmlspecialchars($data['footer']['menu1']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Меню 1 (UZ)</label>
                                    <input type="text" class="form-control" name="footer_menu1_uz" value="<?php echo htmlspecialchars($data['footer']['menu1']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Меню 1 (EN)</label>
                                    <input type="text" class="form-control" name="footer_menu1_en" value="<?php echo htmlspecialchars($data['footer']['menu1']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Меню 2 (RU)</label>
                                    <input type="text" class="form-control" name="footer_menu2_ru" value="<?php echo htmlspecialchars($data['footer']['menu2']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Меню 2 (UZ)</label>
                                    <input type="text" class="form-control" name="footer_menu2_uz" value="<?php echo htmlspecialchars($data['footer']['menu2']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Меню 2 (EN)</label>
                                    <input type="text" class="form-control" name="footer_menu2_en" value="<?php echo htmlspecialchars($data['footer']['menu2']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Контакты (RU)</label>
                                    <input type="text" class="form-control" name="footer_contact_ru" value="<?php echo htmlspecialchars($data['footer']['contact']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Контакты (UZ)</label>
                                    <input type="text" class="form-control" name="footer_contact_uz" value="<?php echo htmlspecialchars($data['footer']['contact']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Контакты (EN)</label>
                                    <input type="text" class="form-control" name="footer_contact_en" value="<?php echo htmlspecialchars($data['footer']['contact']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-4">Ссылки меню:</h5>
                            
                            <div id="footerLinks">
                                <?php 
                                $footer_links = $data['footer']['links'] ?? [];
                                foreach ($footer_links as $index => $link): ?>
                                    <div class="country-item mb-3" data-id="<?php echo $index; ?>">
                                        <h6 class="mb-3">Ссылка <?php echo $index + 1; ?></h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (RU)</label>
                                                <input type="text" class="form-control footer-link-ru" value="<?php echo htmlspecialchars($link['ru'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (UZ)</label>
                                                <input type="text" class="form-control footer-link-uz" value="<?php echo htmlspecialchars($link['uz'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (EN)</label>
                                                <input type="text" class="form-control footer-link-en" value="<?php echo htmlspecialchars($link['en'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-4">Ссылки услуг:</h5>
                            
                            <div id="footerServices">
                                <?php 
                                $footer_services = $data['footer']['services'] ?? [];
                                foreach ($footer_services as $index => $service): ?>
                                    <div class="country-item mb-3" data-id="<?php echo $index; ?>">
                                        <h6 class="mb-3">Услуга <?php echo $index + 1; ?></h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (RU)</label>
                                                <input type="text" class="form-control footer-service-ru" value="<?php echo htmlspecialchars($service['ru'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (UZ)</label>
                                                <input type="text" class="form-control footer-service-uz" value="<?php echo htmlspecialchars($service['uz'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Текст (EN)</label>
                                                <input type="text" class="form-control footer-service-en" value="<?php echo htmlspecialchars($service['en'] ?? ''); ?>" data-id="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Адрес (RU)</label>
                                    <input type="text" class="form-control" name="footer_address_ru" value="<?php echo htmlspecialchars($data['footer']['address']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Адрес (UZ)</label>
                                    <input type="text" class="form-control" name="footer_address_uz" value="<?php echo htmlspecialchars($data['footer']['address']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Адрес (EN)</label>
                                    <input type="text" class="form-control" name="footer_address_en" value="<?php echo htmlspecialchars($data['footer']['address']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Телефон</label>
                                    <input type="text" class="form-control" name="footer_phone_ru" value="<?php echo htmlspecialchars($data['footer']['phone']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Телефон</label>
                                    <input type="text" class="form-control" name="footer_phone_uz" value="<?php echo htmlspecialchars($data['footer']['phone']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Телефон</label>
                                    <input type="text" class="form-control" name="footer_phone_en" value="<?php echo htmlspecialchars($data['footer']['phone']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="text" class="form-control" name="footer_email_ru" value="<?php echo htmlspecialchars($data['footer']['email']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="text" class="form-control" name="footer_email_uz" value="<?php echo htmlspecialchars($data['footer']['email']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="text" class="form-control" name="footer_email_en" value="<?php echo htmlspecialchars($data['footer']['email']['en'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Копирайт (RU)</label>
                                    <input type="text" class="form-control" name="footer_copyright_ru" value="<?php echo htmlspecialchars($data['footer']['copyright']['ru'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Копирайт (UZ)</label>
                                    <input type="text" class="form-control" name="footer_copyright_uz" value="<?php echo htmlspecialchars($data['footer']['copyright']['uz'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Копирайт (EN)</label>
                                    <input type="text" class="form-control" name="footer_copyright_en" value="<?php echo htmlspecialchars($data['footer']['copyright']['en'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Подготовка данных перед сохранением
        document.getElementById('saveButton').addEventListener('click', function() {
            try {
                // Подготовка данных стран
                const countriesData = [];
                const countryItems = document.querySelectorAll('#countriesList .country-item');
                
                countryItems.forEach(item => {
                    const idElement = item.querySelector('.country-id');
                    const imageElement = item.querySelector('.country-image');
                    
                    if (idElement && imageElement) {
                        const id = parseInt(idElement.value) || 0;
                        
                        countriesData.push({
                            id: id,
                            name: {
                                ru: item.querySelector('.country-name-ru')?.value || '',
                                uz: item.querySelector('.country-name-uz')?.value || '',
                                en: item.querySelector('.country-name-en')?.value || ''
                            },
                            description: {
                                ru: item.querySelector('.country-desc-ru')?.value || '',
                                uz: item.querySelector('.country-desc-uz')?.value || '',
                                en: item.querySelector('.country-desc-en')?.value || ''
                            },
                            image: imageElement.value || ''
                        });
                    }
                });
                
                // Подготовка данных "О нас"
                const aboutItemsData = [];
                const aboutItems = document.querySelectorAll('#aboutItems .country-item');
                
                aboutItems.forEach(item => {
                    aboutItemsData.push({
                        title: {
                            ru: item.querySelector('.about-item-title-ru')?.value || '',
                            uz: item.querySelector('.about-item-title-uz')?.value || '',
                            en: item.querySelector('.about-item-title-en')?.value || ''
                        },
                        text: {
                            ru: item.querySelector('.about-item-text-ru')?.value || '',
                            uz: item.querySelector('.about-item-text-uz')?.value || '',
                            en: item.querySelector('.about-item-text-en')?.value || ''
                        }
                    });
                });
                
                // Подготовка данных статистики
                const statsItemsData = [];
                const statsItems = document.querySelectorAll('#statsItems .country-item');
                
                statsItems.forEach(item => {
                    statsItemsData.push({
                        number: item.querySelector('.stats-item-number')?.value || '',
                        text: {
                            ru: item.querySelector('.stats-item-text-ru')?.value || '',
                            uz: item.querySelector('.stats-item-text-uz')?.value || '',
                            en: item.querySelector('.stats-item-text-en')?.value || ''
                        }
                    });
                });
                
                // Подготовка данных ссылок футера
                const footerLinksData = [];
                const footerLinks = document.querySelectorAll('#footerLinks .country-item');
                
                footerLinks.forEach(item => {
                    footerLinksData.push({
                        ru: item.querySelector('.footer-link-ru')?.value || '',
                        uz: item.querySelector('.footer-link-uz')?.value || '',
                        en: item.querySelector('.footer-link-en')?.value || ''
                    });
                });
                
                // Подготовка данных услуг футера
                const footerServicesData = [];
                const footerServices = document.querySelectorAll('#footerServices .country-item');
                
                footerServices.forEach(item => {
                    footerServicesData.push({
                        ru: item.querySelector('.footer-service-ru')?.value || '',
                        uz: item.querySelector('.footer-service-uz')?.value || '',
                        en: item.querySelector('.footer-service-en')?.value || ''
                    });
                });
                
                // Установка значений скрытых полей
                document.getElementById('countriesDataInput').value = JSON.stringify(countriesData);
                document.getElementById('aboutItemsInput').value = JSON.stringify(aboutItemsData);
                document.getElementById('statsItemsInput').value = JSON.stringify(statsItemsData);
                document.getElementById('footerLinksInput').value = JSON.stringify(footerLinksData);
                document.getElementById('footerServicesInput').value = JSON.stringify(footerServicesData);
                
                // Отправка формы
                document.getElementById('adminForm').submit();
                
            } catch (error) {
                console.error('Ошибка при подготовке данных:', error);
                alert('Произошла ошибка при подготовке данных. Проверьте консоль для получения деталей.');
            }
        });
        
        // Добавление новой страны
        document.getElementById('addCountryBtn').addEventListener('click', function() {
            const formData = new FormData();
            formData.append('add_country', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Добавляем новую страну в DOM
                    const countriesList = document.getElementById('countriesList');
                    const newCountry = data.country;
                    
                    const countryElement = document.createElement('div');
                    countryElement.className = 'country-item';
                    countryElement.setAttribute('data-id', newCountry.id);
                    countryElement.innerHTML = `
                        <button type="button" class="delete-country-btn" data-id="${newCountry.id}">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <img src="${newCountry.image}" class="preview-image" alt="Превью">
                            </div>
                            <div class="col-md-10">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Название (RU)</label>
                                        <input type="text" class="form-control country-name-ru" value="${newCountry.name.ru}" data-id="${newCountry.id}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Название (UZ)</label>
                                        <input type="text" class="form-control country-name-uz" value="${newCountry.name.uz}" data-id="${newCountry.id}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Название (EN)</label>
                                        <input type="text" class="form-control country-name-en" value="${newCountry.name.en}" data-id="${newCountry.id}">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Описание (RU)</label>
                                        <textarea class="form-control country-desc-ru" rows="2" data-id="${newCountry.id}">${newCountry.description.ru}</textarea>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Описание (UZ)</label>
                                        <textarea class="form-control country-desc-uz" rows="2" data-id="${newCountry.id}">${newCountry.description.uz}</textarea>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Описание (EN)</label>
                                        <textarea class="form-control country-desc-en" rows="2" data-id="${newCountry.id}">${newCountry.description.en}</textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">URL изображения</label>
                                        <input type="text" class="form-control country-image" value="${newCountry.image}" data-id="${newCountry.id}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">ID</label>
                                        <input type="text" class="form-control country-id" value="${newCountry.id}" readonly>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Загрузить новое изображение</label>
                                        <div class="btn-group-custom">
                                            <input type="file" class="form-control country-image-upload" data-id="${newCountry.id}" accept="image/*" style="flex: 1; min-width: 200px;">
                                            <button type="button" class="upload-btn country-upload-btn" data-id="${newCountry.id}">
                                                <i class="fas fa-upload me-1"></i>Загрузить
                                            </button>
                                        </div>
                                        <div class="upload-progress upload-progress-${newCountry.id}" style="display: none;">
                                            <div class="upload-progress-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    countriesList.appendChild(countryElement);
                    
                    // Добавляем обработчики событий для новой страны
                    setupCountryEventListeners(countryElement);
                    
                    alert('Страна добавлена!');
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при добавлении страны');
            });
        });
        
        // Удаление страны
        function deleteCountry(countryId) {
            if (confirm('Вы уверены, что хотите удалить эту страну?')) {
                const formData = new FormData();
                formData.append('delete_country', '1');
                formData.append('country_id', countryId);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Удаляем страну из DOM
                        const countryElement = document.querySelector(`.country-item[data-id="${countryId}"]`);
                        if (countryElement) {
                            countryElement.remove();
                        }
                        alert('Страна удалена!');
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Ошибка при удалении страны');
                });
            }
        }
        
        // Настройка обработчиков событий для стран
        function setupCountryEventListeners(countryElement) {
            // Кнопка удаления страны
            const deleteBtn = countryElement.querySelector('.delete-country-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const countryId = this.getAttribute('data-id');
                    deleteCountry(countryId);
                });
            }
            
            // Кнопка загрузки изображения
            const uploadBtn = countryElement.querySelector('.country-upload-btn');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', function() {
                    const countryId = this.getAttribute('data-id');
                    const fileInput = countryElement.querySelector(`.country-image-upload[data-id="${countryId}"]`);
                    
                    if (fileInput && fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        const formData = new FormData();
                        formData.append('country_image', file);
                        formData.append('country_id', countryId);
                        
                        // Показываем прогресс
                        const progressContainer = countryElement.querySelector(`.upload-progress-${countryId}`);
                        const progressBar = progressContainer?.querySelector('.upload-progress-bar');
                        if (progressContainer) {
                            progressContainer.style.display = 'block';
                            if (progressBar) progressBar.style.width = '0%';
                        }
                        
                        // Отправляем запрос
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Обновляем URL изображения
                                const imageInput = countryElement.querySelector(`.country-image[data-id="${countryId}"]`);
                                if (imageInput) {
                                    imageInput.value = data.url;
                                }
                                
                                // Обновляем превью
                                const previewImage = countryElement.querySelector('.preview-image');
                                if (previewImage) {
                                    previewImage.src = data.url;
                                }
                                
                                // Добавляем кнопку удаления изображения
                                const btnGroup = countryElement.querySelector('.btn-group-custom');
                                if (btnGroup && !btnGroup.querySelector('.country-delete-btn')) {
                                    const deleteImgBtn = document.createElement('button');
                                    deleteImgBtn.type = 'button';
                                    deleteImgBtn.className = 'delete-btn country-delete-btn';
                                    deleteImgBtn.setAttribute('data-path', data.url);
                                    deleteImgBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Удалить';
                                    btnGroup.appendChild(deleteImgBtn);
                                    
                                    // Добавляем обработчик для кнопки удаления изображения
                                    deleteImgBtn.addEventListener('click', function() {
                                        handleDeleteImage.call(this);
                                    });
                                }
                                
                                alert('Изображение успешно загружено!');
                            } else {
                                alert('Ошибка загрузки: ' + data.message);
                            }
                            
                            // Скрываем прогресс
                            if (progressContainer) {
                                progressContainer.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Ошибка при загрузке изображения: ' + error.message);
                            
                            // Скрываем прогресс
                            if (progressContainer) {
                                progressContainer.style.display = 'none';
                            }
                        });
                    } else {
                        alert('Пожалуйста, выберите файл для загрузки');
                    }
                });
            }
            
            // Обновление превью изображений при изменении URL
            const imageInput = countryElement.querySelector('.country-image');
            if (imageInput) {
                imageInput.addEventListener('input', function() {
                    const preview = countryElement.querySelector('.preview-image');
                    if (preview) {
                        preview.src = this.value || 'https://placehold.co/400x200/cccccc/ffffff?text=No+Image';
                    }
                });
            }
        }
        
        // Обработка удаления изображений
        function handleDeleteImage() {
            const imagePath = this.getAttribute('data-path');
            if (confirm('Вы уверены, что хотите удалить это изображение?')) {
                const formData = new FormData();
                formData.append('delete_image', '1');
                formData.append('image_path', imagePath);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Изображение удалено!');
                        // Удаляем кнопку удаления
                        this.remove();
                        
                        // Сбрасываем URL изображения на placeholder
                        const countryItem = this.closest('.country-item');
                        if (countryItem) {
                            const countryId = countryItem.getAttribute('data-id');
                            const imageInput = countryItem.querySelector(`.country-image[data-id="${countryId}"]`);
                            if (imageInput) {
                                imageInput.value = 'https://placehold.co/400x200/cccccc/ffffff?text=No+Image';
                            }
                            
                            const previewImage = countryItem.querySelector('.preview-image');
                            if (previewImage) {
                                previewImage.src = 'https://placehold.co/400x200/cccccc/ffffff?text=No+Image';
                            }
                        }
                    } else {
                        alert('Ошибка удаления: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Ошибка при удалении изображения');
                });
            }
        }
        
        // Инициализация обработчиков событий
        document.addEventListener('DOMContentLoaded', function() {
            // Обработчики для существующих стран
            document.querySelectorAll('.country-item').forEach(countryElement => {
                setupCountryEventListeners(countryElement);
            });
            
            // Обработчики для кнопок удаления изображений
            document.querySelectorAll('.country-delete-btn').forEach(button => {
                button.addEventListener('click', handleDeleteImage);
            });
            
            // Активация первой вкладки
            const firstTab = document.querySelector('.nav-link');
            if (firstTab) {
                firstTab.classList.add('active');
            }
            
            // Обработчики для вкладок
            const tabLinks = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        // Скрываем все вкладки
                        document.querySelectorAll('.tab-pane').forEach(tab => {
                            tab.classList.remove('show', 'active');
                        });
                        // Показываем нужную вкладку
                        target.classList.add('show', 'active');
                        // Обновляем активную ссылку
                        tabLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>