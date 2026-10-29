<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';

requerirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/documentos.php');
    exit();
}

$accion = $_POST['accion'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';

if (!validarTokenCSRF($csrf)) {
    header('Location: /sigsm/views/documentos.php?msg=error');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$documentoModel = new Documento($db);

if ($accion === 'crear') {
    if (!tienePermiso('cargar_documentos')) die("Acceso denegado.");
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        header('Location: /sigsm/views/documentos.php?msg=error_archivo');
        exit();
    }
    
    $file = $_FILES['archivo'];
    $max_size = 10 * 1024 * 1024;
    
    if ($file['size'] > $max_size) {
        header('Location: /sigsm/views/documentos.php?msg=error_tamano');
        exit();
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
    
    if (!in_array($mime, $allowed_mimes, true)) {
        header('Location: /sigsm/views/documentos.php?msg=error_tipo');
        exit();
    }
    
    $hash = bin2hex(random_bytes(16));
    $upload_dir = __DIR__ . '/../uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $dest_path = $upload_dir . $hash;
    
    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        header('Location: /sigsm/views/documentos.php?msg=error_guardado');
        exit();
    }
    
    $documentoModel->id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $documentoModel->id_usuario_carga = $_SESSION['user_id'];
    $documentoModel->titulo = trim($_POST['titulo'] ?? '');
    $documentoModel->url_archivo = $hash;
    
    if ($documentoModel->titulo !== '' && $documentoModel->id_categoria > 0) {
        if (!$documentoModel->crear()) {
            unlink($dest_path);
            $msg = 'error';
        } else {
            $msg = 'ok';
        }
    } else {
        unlink($dest_path);
        $msg = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $msg);
    exit();
}

if ($accion === 'editar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");
    
    $documentoModel->id_documento = (int)($_POST['id_documento'] ?? 0);
    $documentoModel->id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $documentoModel->titulo = trim($_POST['titulo'] ?? '');
    
    if ($documentoModel->id_documento > 0 && $documentoModel->titulo !== '') {
        $documentoModel->editar() ? $msg = 'ok' : $msg = 'error';
    } else {
        $msg = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $msg);
    exit();
}

if ($accion === 'eliminar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");
    
    $id = (int)($_POST['id_documento'] ?? 0);
    
    $stmt = $db->prepare('SELECT url_archivo FROM documento WHERE id_documento = :id');
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $documentoModel->id_documento = $id;
    
    if ($id > 0 && $documentoModel->eliminar()) {
        if ($doc && !empty($doc['url_archivo'])) {
            $file_path = __DIR__ . '/../uploads/' . $doc['url_archivo'];
            if (file_exists($file_path)) unlink($file_path);
        }
        $msg = 'eliminado';
    } else {
        $msg = 'error';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $msg);
    exit();
}

if ($accion === 'crear_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $nombre = trim($_POST['nombre'] ?? '');
    
    if ($nombre !== '') {
        $documentoModel->crearCategoria($nombre) ? $msg = 'categoria_creada' : $msg = 'error';
    } else {
        $msg = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $msg);
    exit();
}

if ($accion === 'editar_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    
    if ($id_categoria > 0 && $nombre !== '') {
        $documentoModel->editarCategoria($id_categoria, $nombre) ? $msg = 'categoria_editada' : $msg = 'error';
    } else {
        $msg = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $msg);
    exit();
}

if ($accion === 'eliminar_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    
    if ($id_categoria > 0) {
        $documentos_asociados = $documentoModel->contarDocumentosPorCategoria($id_categoria);
        
        if ($documentos_asociados > 0) {
            $msg = 'error_categoria_en_uso';
        } else {
            $documentoModel->eliminarCategoria($id_categoria) ? $msg = 'categoria_eliminada' : $msg = 'error';
        }
    } else {
        $msg = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $msg);
    exit();
}

header('Location: /sigsm/views/documentos.php');
exit();