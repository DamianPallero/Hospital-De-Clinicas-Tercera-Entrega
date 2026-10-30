<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($datoV) {
    return htmlspecialchars($datoV ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF($codToken) {
    return !empty($codToken) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $codToken);
}

function obtenerPermisosPorRol($rolNom) {
    $rolNom = (string)$rolNom;
    $mapa = [
        'Administrador General' => [
            'ver_documentos', 'cargar_documentos', 'eliminar_documentos',
            'ver_encuestas', 'gestionar_encuestas', 'ver_respuestas_encuestas',
            'ver_traslados', 'crear_traslado', 'actualizar_estado_traslado', 'asignar_traslado',
            'gestionar_usuarios',
            'gestionar_categorias',
        ],
        'Enfermería' => [
            'ver_documentos', 'cargar_documentos',
            'ver_encuestas',
            'ver_traslados', 'crear_traslado',
        ],
        'Médico' => [
            'ver_documentos', 'cargar_documentos',
            'ver_encuestas',
            'ver_traslados', 'crear_traslado',
        ],
        'IT' => [
            'ver_documentos',
            'ver_encuestas', 'gestionar_encuestas', 'ver_respuestas_encuestas', 'gestionar_categorias',
            'ver_traslados', 'actualizar_estado_traslado',],
        'Transporte' => [
            'ver_traslados', 'actualizar_estado_traslado',
        ],
    ];
    return $mapa[$rolNom] ?? [];
}

function tienePermiso($permiso) {
    if (empty($_SESSION['role_name'])) {
        return false;
    }
    $permisos = obtenerPermisosPorRol($_SESSION['role_name']);
    return in_array($permiso, $permisos, true);
}

function obtenerRol() {
    return $_SESSION['role_name'] ?? 'Invitado';
}

if (!function_exists('iniciarSesionSegura')) {
    function iniciarSesionSegura(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
            $optsCookie = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => 1800,
                'path' => $optsCookie['path'] ?? '/',
                'domain' => $optsCookie['domain'] ?? '',
                'secure' => $seguro,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }
}

if (!function_exists('esAutenticado')) {
    function esAutenticado(): bool {
        iniciarSesionSegura();
        return !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('cerrarSesion')) {
    function cerrarSesion(): void {
        iniciarSesionSegura();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
    }
}

if (!function_exists('requerirAutenticacion')) {
    function requerirAutenticacion(): void {
        iniciarSesionSegura();
        if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
            header('Location: /sigsm/views/login.php');
            exit();
        }
        $limite = 1800;
        $ult = $_SESSION['last_activity'] ?? null;
        $ahora = time();
        if ($ult === null || ($ahora - (int)$ult) > $limite) {
            cerrarSesion();
            header('Location: /sigsm/views/login.php?expired=1');
            exit();
        }
        $_SESSION['last_activity'] = $ahora;
    }
}