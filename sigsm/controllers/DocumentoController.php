<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';

requerirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sigsm/views/documentos.php');
    exit();
}

$op = $_POST['accion'] ?? '';
$tokenForm = $_POST['csrf_token'] ?? '';

if (!validarTokenCSRF($tokenForm)) {
    header('Location: /sigsm/views/documentos.php?msg=error');
    exit();
}

$baseObj = new Database();
$bd = $baseObj->getConnection();
$modDoc = new Documento($bd);

if ($op === 'crear') {
    if (!tienePermiso('cargar_documentos')) die("Acceso denegado.");
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        header('Location: /sigsm/views/documentos.php?msg=error_archivo');
        exit();
    }
    
    $fichero = $_FILES['archivo'];
    $maxBytes = 10 * 1024 * 1024;
    
    if ($fichero['size'] > $maxBytes) {
        header('Location: /sigsm/views/documentos.php?msg=error_tamano');
        exit();
    }
    
    $infoMime = new finfo(FILEINFO_MIME_TYPE);
    $tipoMime = $infoMime->file($fichero['tmp_name']);
    $mimes = ['application/pdf', 'image/jpeg', 'image/png'];
    
    if (!in_array($tipoMime, $mimes, true)) {
        header('Location: /sigsm/views/documentos.php?msg=error_tipo');
        exit();
    }
    
    $sello = bin2hex(random_bytes(16));
    $dirCarga = __DIR__ . '/../uploads/';
    
    if (!is_dir($dirCarga)) {
        mkdir($dirCarga, 0755, true);
    }
    
    $rutaFin = $dirCarga . $sello;
    
    if (!move_uploaded_file($fichero['tmp_name'], $rutaFin)) {
        header('Location: /sigsm/views/documentos.php?msg=error_guardado');
        exit();
    }
    
    $modDoc->catId = (int)($_POST['id_categoria'] ?? 0);
    $modDoc->cargaId = $_SESSION['user_id'];
    $modDoc->tituloDoc = trim($_POST['titulo'] ?? '');
    $modDoc->urlArchivo = $sello;
    
    if ($modDoc->tituloDoc !== '' && $modDoc->catId > 0) {
        if (!$modDoc->crear()) {
            unlink($rutaFin);
            $mensaje = 'error';
        } else {
            $mensaje = 'ok';
        }
    } else {
        unlink($rutaFin);
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'editar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");
    
    $modDoc->docId = (int)($_POST['id_documento'] ?? 0);
    $modDoc->catId = (int)($_POST['id_categoria'] ?? 0);
    $modDoc->tituloDoc = trim($_POST['titulo'] ?? '');
    
    if ($modDoc->docId > 0 && $modDoc->tituloDoc !== '') {
        $modDoc->editar() ? $mensaje = 'ok' : $mensaje = 'error';
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'eliminar') {
    if (!tienePermiso('eliminar_documentos')) die("Acceso denegado.");
    
    $clave = (int)($_POST['id_documento'] ?? 0);
    
    $prep = $bd->prepare('SELECT url_archivo FROM documento WHERE id_documento = :id');
    $prep->bindParam(':id', $clave, PDO::PARAM_INT);
    $prep->execute();
    $docFila = $prep->fetch(PDO::FETCH_ASSOC);
    
    $modDoc->docId = $clave;
    
    if ($clave > 0 && $modDoc->eliminar()) {
        if ($docFila && !empty($docFila['url_archivo'])) {
            $rutaFichero = __DIR__ . '/../uploads/' . $docFila['url_archivo'];
            if (file_exists($rutaFichero)) unlink($rutaFichero);
        }
        $mensaje = 'eliminado';
    } else {
        $mensaje = 'error';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'crear_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $nom = trim($_POST['nombre'] ?? '');
    
    if ($nom !== '') {
        $modDoc->crearCategoria($nom) ? $mensaje = 'categoria_creada' : $mensaje = 'error';
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'editar_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $catId = (int)($_POST['id_categoria'] ?? 0);
    $nom = trim($_POST['nombre'] ?? '');
    
    if ($catId > 0 && $nom !== '') {
        $modDoc->editarCategoria($catId, $nom) ? $mensaje = 'categoria_editada' : $mensaje = 'error';
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

if ($op === 'eliminar_categoria') {
    if (!tienePermiso('gestionar_categorias')) die("Acceso denegado.");
    
    $catId = (int)($_POST['id_categoria'] ?? 0);
    
    if ($catId > 0) {
        $docsAsoc = $modDoc->contarDocumentosPorCategoria($catId);
        
        if ($docsAsoc > 0) {
            $mensaje = 'error_categoria_en_uso';
        } else {
            $modDoc->eliminarCategoria($catId) ? $mensaje = 'categoria_eliminada' : $mensaje = 'error';
        }
    } else {
        $mensaje = 'error_validacion';
    }
    
    header('Location: /sigsm/views/documentos.php?msg=' . $mensaje);
    exit();
}

header('Location: /sigsm/views/documentos.php');
exit();