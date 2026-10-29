<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/Captcha.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/login.php');
    exit();
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'forgot_password') {
    $email = trim((string)($_POST['email'] ?? ''));
    if ($email !== '') {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare('SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1');
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetch()) {
                $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $dir = __DIR__ . '/../config/reset_codes/';
                asegurarCarpetaProtegida($dir);
                $archivo = $dir . md5($email) . '.txt';
                file_put_contents($archivo, $email . '|' . $codigo . '|' . time());
            }
        } catch (Exception $e) {
        }
    }
    header('Location: /sigsm/views/forgot_password.php?msg=requested');
    exit();
}

if ($accion === 'reset_password') {
    $email = trim((string)($_POST['email'] ?? ''));
    $codigo = trim((string)($_POST['codigo'] ?? ''));
    $nueva = $_POST['nueva_password'] ?? '';
    
    $archivo = __DIR__ . '/../config/reset_codes/' . md5($email) . '.txt';
    $valido = false;
    
    if (file_exists($archivo)) {
        $data = explode('|', file_get_contents($archivo));
        // Válido por 15 minutos (900 seg)
        if (count($data) === 3 && $data[0] === $email && $data[1] === $codigo && (time() - (int)$data[2]) < 900) {
            $valido = true;
        }
    }
    
    if ($valido && strlen($nueva) >= 6) {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare('SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1');
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $usuarioModel = new Usuario($db);
                $usuarioModel->id_usuario = $user['id_usuario'];
                $usuarioModel->cambiarPassword($nueva);
                @unlink($archivo); // Borrar token usado
                header('Location: /sigsm/views/login.php?reset=ok');
                exit();
            }
        } catch (Exception $e) {
            // Fallo silencioso
        }
    }
    header('Location: /sigsm/views/reset_password.php?msg=invalid_code');
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
        iniciarSesionSegura();
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