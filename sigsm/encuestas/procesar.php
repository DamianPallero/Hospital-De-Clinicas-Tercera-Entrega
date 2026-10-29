<?php
session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: portal.php');
    exit();
}

$id_encuesta = (int)($_POST['id_encuesta'] ?? 0);
$respuestas = $_POST['respuestas'] ?? [];
$version = (int)($_POST['v'] ?? 1);
$sessionKey = 'survey_completed_v' . $version;

if ($id_encuesta > 0 && !empty($respuestas)) {
    $db = (new Database())->getConnection();
    $encuestaModel = new Encuesta($db);
    
    $respuestas_limpias = [];
    foreach ($respuestas as $id_pregunta => $valor) {
        $respuestas_limpias[(int)$id_pregunta] = trim((string)$valor);
    }
    
    $encuestaModel->guardarRespuestas($respuestas_limpias);
}

$_SESSION[$sessionKey] = true;

header("Location: gracias.php");
exit();