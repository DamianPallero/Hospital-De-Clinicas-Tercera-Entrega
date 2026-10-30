<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/config/Database.php';

requerirAutenticacion();

if (!tienePermiso('ver_documentos')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$clave = (int)($_GET['id'] ?? 0);
if ($clave <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

try {
    $bd = (new Database())->getConnection();
    $prep = $bd->prepare('SELECT url_archivo, titulo FROM documento WHERE id_documento = :id');
    $prep->bindParam(':id', $clave, PDO::PARAM_INT);
    $prep->execute();
    $docFila = $prep->fetch(PDO::FETCH_ASSOC);

    if (!$docFila) {
        http_response_code(404);
        exit('Documento no encontrado.');
    }

    $sello = $docFila['url_archivo'];
    
    if (!preg_match('/^[a-f0-9]{32}$/i', $sello)) {
        http_response_code(400);
        exit('Hash de archivo inválido.');
    }

    $rutaFichero = __DIR__ . '/uploads/' . $sello;

    if (!file_exists($rutaFichero)) {
        http_response_code(404);
        exit('Archivo físico no encontrado en el servidor.');
    }

    $infoMime = new finfo(FILEINFO_MIME_TYPE);
    $tipoMime = $infoMime->file($rutaFichero);

    header('Content-Type: ' . $tipoMime);
    header('Content-Length: ' . filesize($rutaFichero));
    header('Content-Disposition: inline; filename="' . addslashes($docFila['titulo']) . '"');
    header('Cache-Control: private, max-age=3600');
    
    readfile($rutaFichero);
    exit;

} catch (Exception $err) {
    http_response_code(500);
    exit('Error interno al procesar el documento.');
}