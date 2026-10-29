<?php
require_once __DIR__ . '/config/Database.php';

$token = $_GET['token'] ?? '';
$token = preg_replace('/[^a-f0-9]/i', '', $token);

if (!$token) {
    http_response_code(400);
    exit('Token invalido.');
}

$tempDir = __DIR__ . '/temp_qr/';
$fileToken = $tempDir . $token;

if (!file_exists($fileToken)) {
    http_response_code(410);
    exit('El codigo QR ha sido utilizado o ha expirado.');
}

$id_documento = (int)file_get_contents($fileToken);

unlink($fileToken);

if ($id_documento <= 0) {
    http_response_code(400);
    exit('Documento invalido.');
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT url_archivo, titulo FROM documento WHERE id_documento = :id');
    $stmt->bindParam(':id', $id_documento, PDO::PARAM_INT);
    $stmt->execute();
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        exit('Documento no encontrado.');
    }

    $hash = $doc['url_archivo'];
    $file_path = __DIR__ . '/uploads/' . $hash;

    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('Archivo no encontrado en el servidor.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file_path);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file_path));
    header('Content-Disposition: inline; filename="' . addslashes($doc['titulo']) . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    readfile($file_path);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    exit('Error interno.');
}