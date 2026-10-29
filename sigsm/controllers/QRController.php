<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';

requerirAutenticacion();

if (!tienePermiso('ver_documentos')) {
    die("Acceso denegado.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/qr_documentos.php');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validarTokenCSRF($csrf)) {
    header('Location: /sigsm/views/qr_documentos.php?error=csrf');
    exit();
}

$id_documento = (int)($_POST['id_documento'] ?? 0);

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare('SELECT id_documento FROM documento WHERE id_documento = :id');
$stmt->bindParam(':id', $id_documento, PDO::PARAM_INT);
$stmt->execute();

if (!$stmt->fetch()) {
    header('Location: /sigsm/views/qr_documentos.php?error=doc_not_found');
    exit();
}

$token = bin2hex(random_bytes(16));

$tempDir = __DIR__ . '/../temp_qr/';

if (!asegurarCarpetaProtegida($tempDir)) {
    header('Location: /sigsm/views/qr_documentos.php?error=permisos');
    exit();
}

file_put_contents($tempDir . $token, $id_documento);

header('Location: /sigsm/views/qr_documentos.php?token=' . $token);
exit();