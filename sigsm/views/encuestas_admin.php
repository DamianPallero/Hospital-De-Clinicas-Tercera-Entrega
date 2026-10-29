<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Encuesta.php';
require_once __DIR__ . '/../includes/Cache.php';

requerirAutenticacion();
sinCache();

if (!tienePermiso('gestionar_encuestas')) {
    header('Location: /sigsm/index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$encuestaModel = new Encuesta($db);
$encuestas = $encuestaModel->listarTodas();

$versionFile = __DIR__ . '/../config/portal_version.txt';
$timeFile = __DIR__ . '/../config/portal_version_time.txt';

if (!file_exists($versionFile) && is_writable(__DIR__ . '/../config')) {
    file_put_contents($versionFile, '1');
}
if (!file_exists($timeFile) && is_writable(__DIR__ . '/../config')) {
    file_put_contents($timeFile, (string)time());
}

$version = file_exists($versionFile) ? (int)file_get_contents($versionFile) : 1;
$creationTime = file_exists($timeFile) ? (int)file_get_contents($timeFile) : time();
$expirationSeconds = 30 * 24 * 60 * 60;
$expirationTime = $creationTime + $expirationSeconds;
$isExpired = time() > $expirationTime;

$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; 
$urlPortal = $protocolo . "://" . $host . "/sigsm/encuestas/portal.php?v=" . $version;

$rol = obtenerRol();
$nombreUsuario = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Gestión de Encuestas - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        @media (prefers-contrast: more) {
            .card { border: 2px solid currentColor !important; }
            .btn { border-width: 2px !important; }
        }
        :focus-visible { outline: 3px solid #124b7d !important; outline-offset: 2px !important; }
        
        .table-sm th, .table-sm td {
            padding: 0.6rem 0.75rem;
            vertical-align: middle;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Encuestas</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nombreUsuario) ?></span>
            <span class="badge bg-light text-primary"><?= e($rol) ?></span>
            <a href="../index.php" class="btn text-dark btn-info btn-outline-light btn-sm d-inline-flex align-items-center">Volver al Portal</a>
            <a href="logout.php" class="btn btn-danger btn-outline-light btn-sm d-inline-flex align-items-center">Salir</a>
        </div>
    </div>
</nav>

<main class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
    <div class="container-fluid p-0 flex-grow-1">
        <div class="row g-3 h-100">
            
            <div class="col-12 col-lg-8 d-flex flex-column">
                <div class="card border-0 shadow-sm mb-3 flex-shrink-0">
                    <div class="card-header bg-white py-3 fw-bold">Crear Nueva Encuesta (Servicio)</div>
                    <div class="card-body">
                        <form action="../controllers/EncuestaController.php" method="POST" class="row g-2 align-items-end">
                            <input type="hidden" name="accion" value="crear_encuesta">
                            <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-bold">Nombre del Servicio / Encuesta</label>
                                <input type="text" name="titulo" class="form-control" required placeholder="Ej: Atención de Emergencia">
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="btn btn-primary w-100 btn-sm">Crear</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column overflow-hidden">
                    <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                        <span>Encuestas Registradas</span>
                        <span class="badge bg-primary rounded-pill"><?= count($encuestas) ?></span>
                    </div>
                    <div class="card-body p-0 d-flex flex-column flex-grow-1 overflow-hidden">
                        <div class="table-responsive flex-grow-1 overflow-auto">
                            <table class="table table-hover align-middle mb-0 table-sm">
                                <thead class="sticky-top">
                                    <tr>
                                        <th class="ps-4">Servicio</th>
                                        <th>Estado</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($encuestas as $enc): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium"><?= e($enc['titulo']) ?></td>
                                            <td>
                                                <?php if ($enc['estado']): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                    <form action="../controllers/EncuestaController.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="accion" value="toggle_estado">
                                                        <input type="hidden" name="id_encuesta" value="<?= (int)$enc['id_encuesta'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            <?= $enc['estado'] ? 'Desactivar' : 'Activar' ?>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#preg_<?= $enc['id_encuesta'] ?>">
                                                        Gestionar Preguntas
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="preg_<?= $enc['id_encuesta'] ?>">
                                            <td colspan="3" class="bg-light p-3">
                                                <form action="../controllers/EncuestaController.php" method="POST" class="row g-2 mb-3">
                                                    <input type="hidden" name="accion" value="agregar_pregunta">
                                                    <input type="hidden" name="id_encuesta" value="<?= (int)$enc['id_encuesta'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                    <div class="col-12 col-md-6">
                                                        <input type="text" name="texto" class="form-control form-control-sm" placeholder="Texto de la pregunta" required>
                                                    </div>
                                                    <div class="col-12 col-md-3">
                                                        <select name="tipo" class="form-select form-select-sm">
                                                            <option value="escala_1_a_5">Escala 1 a 5</option>
                                                            <option value="si_no">Sí / No</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-3">
                                                        <button type="submit" class="btn btn-sm btn-primary w-100">Agregar</button>
                                                    </div>
                                                </form>
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($encuestaModel->obtenerPreguntas($enc['id_encuesta']) as $p): ?>
                                                        <li class="list-group-item px-0 py-2 small bg-transparent">
                                                            <strong><?= e($p['tipo_respuesta'] === 'si_no' ? '[Sí/No]' : '[1-5]') ?></strong>
                                                            <?= e($p['texto_pregunta']) ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 d-flex flex-column">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">Código QR del Portal Público</div>
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <p class="text-muted small mb-3">Escanee para acceder al portal de encuestas.</p>
                        <div class="border rounded p-3 d-inline-block bg-white mb-3">
                            <img id="qr-encuesta-img" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($urlPortal) ?>" alt="QR Encuestas" class="img-fluid">
                        </div>
                        <div class="text-start mb-3 px-2 w-100">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Versión actual:</span>
                                <strong>#<?= $version ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small align-items-center">
                                <span class="text-muted">Estado del QR:</span>
                                <?php if ($isExpired): ?>
                                    <span class="badge bg-danger">Expirado</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-auto">
                            <button class="btn btn-dark btn-sm" onclick="imprimirQR()">Imprimir QR</button>
                            <form action="../controllers/EncuestaController.php" method="POST" onsubmit="return confirm('Esto invalidará el QR actual y generará uno nuevo. ¿Continuar?');">
                                <input type="hidden" name="accion" value="rotar_version_qr">
                                <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Invalidar y Generar Nuevo QR</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
function imprimirQR() {
    var qrImg = document.getElementById('qr-encuesta-img');
    var printWindow = window.open('', '', 'height=400,width=400');
    printWindow.document.write('<html><head><title>QR Encuesta</title></head><body style="text-align:center; margin:0; padding:20px; font-family:sans-serif;">');
    printWindow.document.write('<img src="' + qrImg.src + '" style="width:250px;height:250px;">');
    printWindow.document.write('<p style="font-size:14px; margin-top:15px; color:#333;">Escanee para acceder a la encuesta de satisfaccion.</p>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>