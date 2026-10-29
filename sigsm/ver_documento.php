<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/config/Database.php';

requerirAutenticacion();

if (!tienePermiso('ver_documentos')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT url_archivo, titulo FROM documento WHERE id_documento = :id');
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        exit('Documento no encontrado.');
    }

    $hash = $doc['url_archivo'];
    
    if (!preg_match('/^[a-f0-9]{32}$/i', $hash)) {
        http_response_code(400);
        exit('Hash de archivo inválido.');
    }

    $file_path = __DIR__ . '/uploads/' . $hash;

    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('Archivo físico no encontrado en el servidor.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file_path);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file_path));
    header('Content-Disposition: inline; filename="' . addslashes($doc['titulo']) . '"');
    header('Cache-Control: private, max-age=3600');
    
    readfile($file_path);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    exit('Error interno al procesar el documento.');
}