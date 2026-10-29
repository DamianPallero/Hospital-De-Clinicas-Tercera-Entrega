<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/Captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/login.php');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($csrf)) {
    $_SESSION['login_error'] = 'Token de seguridad inválido. Intente nuevamente.';
    header('Location: /sigsm/views/login.php');
    exit();
}

$captcha = $_POST['captcha'] ?? '';
if (!Captcha::validar($captcha)) {
    $_SESSION['login_error'] = 'Respuesta de verificación incorrecta. Intente nuevamente.';
    header('Location: /sigsm/views/login.php');
    exit();
}

$email = trim((string)($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$recordarme = !empty($_POST['recordarme']);

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Email y contraseña son requeridos.';
    header('Location: /sigsm/views/login.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare('
        SELECT u.id_usuario AS id, u.nombre, u.apellido, u.email, u.password_hash, r.nombre_rol AS role_name 
        FROM usuario u 
        INNER JOIN rol r ON u.id_rol = r.id_rol 
        WHERE u.email = :email AND u.activo = 1 
        LIMIT 1
    ');
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        
        if ($recordarme) {
            iniciarSesionSegura(7 * 24 * 60 * 60);
        } else {
            iniciarSesionSegura(0);
        }
        
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = trim($user['nombre'] . ' ' . $user['apellido']);
        $_SESSION['role_name'] = $user['role_name'] ?? 'Invitado';
        $_SESSION['last_login_at'] = date('c');
        $_SESSION['last_activity'] = time();
        
        header('Location: /sigsm/index.php');
        exit();
    } else {
        $_SESSION['login_error'] = 'Credenciales inválidas.';
        header('Location: /sigsm/views/login.php');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['login_error'] = 'Error de autenticación. Contacte al administrador.';
    header('Location: /sigsm/views/login.php');
    exit();
}