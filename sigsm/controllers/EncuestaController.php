<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';

requerirAutenticacion();
if (!tienePermiso('gestionar_encuestas')) {
    header('Location: /sigsm/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/encuestas_admin.php');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($csrf)) {
    header('Location: /sigsm/views/encuestas_admin.php?error=csrf');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$encuestaModel = new Encuesta($db);
$accion = $_POST['accion'] ?? '';

if ($accion === 'crear_encuesta') {
    $titulo = trim($_POST['titulo'] ?? '');
    if ($titulo !== '') {
        $encuestaModel->crear($titulo);
    }
}

if ($accion === 'agregar_pregunta') {
    $id_encuesta = (int)($_POST['id_encuesta'] ?? 0);
    $texto = trim($_POST['texto'] ?? '');
    $tipo = $_POST['tipo'] ?? 'escala_1_a_5';
    if ($id_encuesta > 0 && $texto !== '') {
        $encuestaModel->agregarPregunta($id_encuesta, $texto, $tipo);
    }
}

if ($accion === 'toggle_estado') {
    $id = (int)($_POST['id_encuesta'] ?? 0);
    if ($id > 0) {
        $encuestaModel->toggleEstado($id);
    }
}

if ($accion === 'rotar_version_qr') {
    $versionFile = __DIR__ . '/../config/portal_version.txt';
    $timeFile = __DIR__ . '/../config/portal_version_time.txt';
    
    $currentVersion = (file_exists($versionFile) && is_writable($versionFile)) ? (int)file_get_contents($versionFile) : 0;
    
    if (is_writable(__DIR__ . '/../config')) {
        file_put_contents($versionFile, (string)($currentVersion + 1));
        file_put_contents($timeFile, (string)time());
    }
}

header('Location: /sigsm/views/encuestas_admin.php');
exit();