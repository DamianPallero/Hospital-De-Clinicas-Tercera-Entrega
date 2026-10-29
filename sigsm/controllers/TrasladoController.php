<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Traslado.php';

requerirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/traslados.php');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($csrf)) {
    header('Location: /sigsm/views/traslados.php?msg=error_csrf');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$trasladoModel = new Traslado($db);
$accion = $_POST['accion'] ?? '';
$msg = 'error';

if ($accion === 'crear' && tienePermiso('crear_traslado')) {
    $trasladoModel->origen = trim($_POST['origen'] ?? '');
    $trasladoModel->destino = trim($_POST['destino'] ?? '');
    $trasladoModel->fecha_hora_salida = date('Y-m-d H:i:s');
    $trasladoModel->necesita_oxigeno = isset($_POST['necesita_oxigeno']) ? 1 : 0;
    $trasladoModel->tipo_aislamiento = trim($_POST['tipo_aislamiento'] ?? 'Ninguno');
    $trasladoModel->requiere_ascensor_escalera = trim($_POST['requiere_ascensor_escalera'] ?? 'Ninguno');
    $trasladoModel->observaciones = trim($_POST['observaciones'] ?? '');

    if ($trasladoModel->origen !== '' && $trasladoModel->destino !== '' && $trasladoModel->fecha_hora_salida !== '') {
        $msg = $trasladoModel->crear() ? 'ok_crear' : 'error';
    } else {
        $msg = 'error_validacion';
    }
}

if ($accion === 'asignar' && tienePermiso('asignar_traslado')) {
    $trasladoModel->id_traslado = (int)($_POST['id_traslado'] ?? 0);
    $trasladoModel->id_conductor = (int)($_POST['id_conductor'] ?? 0);
    $trasladoModel->id_enfermero = (int)($_POST['id_enfermero'] ?? 0);
    $trasladoModel->id_vehiculo = (int)($_POST['id_vehiculo'] ?? 0);

    if ($trasladoModel->id_traslado > 0 && $trasladoModel->id_conductor > 0 && $trasladoModel->id_enfermero > 0 && $trasladoModel->id_vehiculo > 0) {
        $msg = $trasladoModel->asignarRecursos() ? 'ok_asignar' : 'error';
    } else {
        $msg = 'error_validacion';
    }
}

if ($accion === 'actualizar_estado' && tienePermiso('actualizar_estado_traslado')) {
    $trasladoModel->id_traslado = (int)($_POST['id_traslado'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if ($trasladoModel->id_traslado > 0 && $nuevo_estado !== '') {
        $msg = $trasladoModel->actualizarEstado($nuevo_estado) ? 'ok_estado' : 'error';
    }
}

header('Location: /sigsm/views/traslados.php?msg=' . $msg);
exit();