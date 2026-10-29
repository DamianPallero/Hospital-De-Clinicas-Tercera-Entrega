<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
requerirAutenticacion();

$accion = $_POST['accion'] ?? '';

if ($accion !== 'cambiar_password_propio' && !tienePermiso('gestionar_usuarios')) {
    header('Location: /sigsm/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/usuarios.php');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($csrf)) {
    $redirect = ($accion === 'cambiar_password_propio') ? '/sigsm/views/cambiar_password.php?msg=error_csrf' : '/sigsm/views/usuarios.php?msg=error_csrf';
    header('Location: ' . $redirect);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$usuarioModel = new Usuario($db);
$msg = 'error';

if ($accion === 'cambiar_password_propio') {
    $id = $_SESSION['user_id'];
    $password_actual = $_POST['password_actual'] ?? '';
    $nueva = $_POST['nueva_password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';
    
    if ($nueva !== $confirmar) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_coincidencia'); exit();
    }
    if (strlen($nueva) < 6) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_longitud'); exit();
    }
    
    $stmt = $db->prepare('SELECT password_hash FROM usuario WHERE id_usuario = :id LIMIT 1');
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password_actual, $user['password_hash'])) {
        $usuarioModel->id_usuario = $id;
        $usuarioModel->cambiarPassword($nueva);
        header('Location: /sigsm/views/cambiar_password.php?msg=ok'); exit();
    } else {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_actual'); exit();
    }
}

if ($accion === 'crear') {
    $usuarioModel->id_rol = (int)($_POST['id_rol'] ?? 0);
    $usuarioModel->nombre = trim($_POST['nombre'] ?? '');
    $usuarioModel->apellido = trim($_POST['apellido'] ?? '');
    $usuarioModel->email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuarioModel->id_rol > 0 && $usuarioModel->nombre !== '' && $usuarioModel->email !== '' && strlen($password) >= 6) {
        $usuarioModel->password_hash = password_hash($password, PASSWORD_DEFAULT);
        $msg = $usuarioModel->crear() ? 'ok_crear' : 'error_email_duplicado';
    } else {
        $msg = 'error_validacion';
    }
}

if ($accion === 'editar') {
    $usuarioModel->id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $usuarioModel->id_rol = (int)($_POST['id_rol'] ?? 0);
    $usuarioModel->nombre = trim($_POST['nombre'] ?? '');
    $usuarioModel->apellido = trim($_POST['apellido'] ?? '');
    $usuarioModel->email = trim($_POST['email'] ?? '');

    if ($usuarioModel->id_usuario > 0 && $usuarioModel->id_rol > 0 && $usuarioModel->nombre !== '') {
        $msg = $usuarioModel->editar() ? 'ok_editar' : 'error_email_duplicado';
    }
}

if ($accion === 'toggle_estado') {
    $id = (int)($_POST['id_usuario'] ?? 0);
    if ($id > 0 && $id != $_SESSION['user_id']) {
        $usuarioModel->id_usuario = $id;
        $msg = $usuarioModel->toggleEstado() ? 'ok_toggle' : 'error';
    } else {
        $msg = 'error_propio';
    }
}

if ($accion === 'reset_password') {
    $id = (int)($_POST['id_usuario'] ?? 0);
    $password = $_POST['password'] ?? '';
    
    if ($id > 0 && strlen($password) >= 6) {
        $usuarioModel->id_usuario = $id;
        $msg = $usuarioModel->cambiarPassword($password) ? 'ok_password' : 'error';
    } else {
        $msg = 'error_validacion';
    }
}

if ($accion === 'cambiar_password_propio') {
    $id = $_SESSION['user_id'];
    $password_actual = $_POST['password_actual'] ?? '';
    $nueva = $_POST['nueva_password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';
    
    if ($nueva !== $confirmar) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_coincidencia'); exit();
    }
    if (strlen($nueva) < 6) {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_longitud'); exit();
    }
    
    $usuario = $usuarioModel->obtenerPorId($id);
    if ($usuario && password_verify($password_actual, $usuario['password_hash'])) {
        $usuarioModel->id_usuario = $id;
        $usuarioModel->cambiarPassword($nueva);
        header('Location: /sigsm/views/cambiar_password.php?msg=ok'); exit();
    } else {
        header('Location: /sigsm/views/cambiar_password.php?msg=error_actual'); exit();
    }
}

header('Location: /sigsm/views/usuarios.php?msg=' . $msg);
exit();