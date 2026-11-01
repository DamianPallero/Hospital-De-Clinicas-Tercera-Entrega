<?php
require_once __DIR__ . '/config/Database.php';

$codToken = $_GET['token'] ?? '';
$codToken = preg_replace('/[^a-f0-9]/i', '', $codToken);

if (!$codToken) {
    http_response_code(400);
    exit('Token invalido.');
}

$dirTemp = __DIR__ . '/temp_qr/';
$tokenDoc = $dirTemp . $codToken;

function invalidarTokenQr($rutaToken) {
    if (is_file($rutaToken)) {
        @unlink($rutaToken);
    }
}

if (!file_exists($tokenDoc)) {
    http_response_code(410);
    exit('El codigo QR ha sido utilizado o ha expirado.');
}

$docId = (int)file_get_contents($tokenDoc);
invalidarTokenQr($tokenDoc);

if ($docId <= 0) {
    http_response_code(400);
    exit('Documento invalido.');
}

try {
    $bd = (new Database())->getConnection();
    $prep = $bd->prepare('SELECT url_archivo, titulo FROM documento WHERE id_documento = :id');
    $prep->bindParam(':id', $docId, PDO::PARAM_INT);
    $prep->execute();
    $docFila = $prep->fetch(PDO::FETCH_ASSOC);

    if (!$docFila) {
        invalidarTokenQr($tokenDoc);
        http_response_code(404);
        exit('Documento no encontrado.');
    }

    $sello = $docFila['url_archivo'];
    $rutaFichero = __DIR__ . '/uploads/' . $sello;

    if (!file_exists($rutaFichero)) {
        invalidarTokenQr($tokenDoc);
        http_response_code(404);
        exit('Archivo no encontrado en el servidor.');
    }

    $infoMime = new finfo(FILEINFO_MIME_TYPE);
    $tipoMime = $infoMime->file($rutaFichero);
    $tituloArchivo = preg_replace('/[\r\n]+/', '', trim((string)$docFila['titulo']));
    $tituloArchivo = preg_replace('/[<>:"\\\/|?*]/', '', $tituloArchivo);
    $tituloArchivo = substr($tituloArchivo, 0, 120) ?: 'documento';

    header('Content-Type: ' . $tipoMime);
    header('Content-Length: ' . filesize($rutaFichero));
    header('Content-Disposition: inline; filename="' . $tituloArchivo . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    
    readfile($rutaFichero);
    exit;

} catch (Exception $err) {
    http_response_code(500);
    exit('Error interno.');
}