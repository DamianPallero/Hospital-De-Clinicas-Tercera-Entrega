<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../includes/Cache.php';

requerirAutenticacion();
sinCache();

if (!tienePermiso('ver_documentos')) {
    header('Location: /sigsm/index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$documentoModel = new Documento($db);

$documentos = $documentoModel->listar();
$categorias = $documentoModel->listarCategorias();

$puedeCargar = tienePermiso('cargar_documentos');
$puedeAdministrar = tienePermiso('eliminar_documentos');
$puedeGestionarCategorias = tienePermiso('gestionar_categorias');

$msg = $_GET['msg'] ?? '';

$rol = obtenerRol();
$nombreUsuario = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Gestión Documental - S.I.G.S.M.</title>
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
        <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Documentos</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nombreUsuario) ?></span>
            <span class="badge bg-light text-primary"><?= e($rol) ?></span>
            <a href="../../index.php" class="btn text-dark btn-sm btn-outline-light btn-info d-inline-flex align-items-center">
                Volver al portal
            </a>
            <a href="logout.php" class="btn btn-sm btn-outline-light btn-danger text-light d-inline-flex align-items-center justify-content-center">
                Salir
            </a>
        </div>
    </div>
</nav>

<main class="flex-grow-1 d-flex flex-column p-3 p-lg-4">
    <?php if (in_array($msg, ['ok', 'eliminado', 'categoria_creada', 'categoria_editada', 'categoria_eliminada'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0">
            <?php
            $successMessages = [
                'ok' => 'Documento cargado correctamente.',
                'eliminado' => 'Documento eliminado correctamente.',
                'categoria_creada' => 'Categoría creada correctamente.',
                'categoria_editada' => 'Categoría actualizada correctamente.',
                'categoria_eliminada' => 'Categoría eliminada correctamente.'
            ];
            echo $successMessages[$msg] ?? 'Operación realizada correctamente.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($msg === 'error_categoria_en_uso'): ?>
        <div class="alert alert-warning alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0">
            No se puede eliminar la categoría porque tiene documentos asociados.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (in_array($msg, ['error_archivo', 'error_tamano', 'error_tipo', 'error_guardado', 'error', 'error_validacion'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm py-2 mb-3 flex-shrink-0 d-inline-flex align-items-center justify-content-between">
            <?php
            $errors = [
                'error_archivo' => 'No se seleccionó ningún archivo o hubo un error al subirlo.',
                'error_tamano' => 'El archivo supera el tamaño máximo permitido (10MB).',
                'error_tipo' => 'Formato no permitido. Solo se aceptan PDF, JPG y PNG.',
                'error_guardado' => 'Error al guardar el archivo en el servidor.',
                'error_validacion' => 'Faltan datos obligatorios.',
                'error' => 'Ocurrió un error al procesar la solicitud.'
            ];
            echo $errors[$msg] ?? 'Error desconocido.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($puedeGestionarCategorias): ?>
    <div class="card border-0 shadow-sm mb-3 flex-shrink-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Gestión de Categorías</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearCategoriaModal">
                + Nueva Categoría
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nombre</th>
                            <th>Documentos</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                            <tr><td colspan="3" class="text-center py-3 text-muted">No hay categorías registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                                <?php $count = $documentoModel->contarDocumentosPorCategoria($cat['id_categoria']); ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= e($cat['nombre']) ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $count ?></span></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#editarCategoriaModal"
                                                data-id="<?= (int)$cat['id_categoria'] ?>"
                                                data-nombre="<?= e($cat['nombre']) ?>">Editar</button>
                                        <?php if ($count === 0): ?>
                                            <form action="../controllers/DocumentoController.php" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta categoría?');">
                                                <input type="hidden" name="accion" value="eliminar_categoria">
                                                <input type="hidden" name="id_categoria" value="<?= (int)$cat['id_categoria'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">No disponible</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
        <h4 class="fw-bold mb-0">Documentos Registrados</h4>
        <?php if ($puedeCargar): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearModal">
                + Cargar Documento
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column overflow-hidden">
        <div class="card-body p-0 d-flex flex-column flex-grow-1 overflow-hidden">
            <div class="table-responsive flex-grow-1 overflow-auto">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="sticky-top">
                        <tr>
                            <th class="ps-4 d-none d-md-table-cell">ID</th>
                            <th>Título</th>
                            <th class="d-none d-lg-table-cell">Categoría</th>
                            <th class="d-none d-lg-table-cell">Fecha</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No hay documentos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($documentos as $doc): ?>
                                <tr>
                                    <td class="ps-4 fw-medium d-none d-md-table-cell text-muted">#<?= (int)$doc['id_documento'] ?></td>
                                    <td>
                                        <div class="fw-bold small"><?= e($doc['titulo']) ?></div>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($doc['categoria'] ?? 'Sin categoría') ?></span></td>
                                    <td class="text-muted small d-none d-lg-table-cell"><?= e(date('d/m/Y H:i', strtotime($doc['fecha_carga']))) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="table-actions">
                                            <a href="../ver_documento.php?id=<?= (int)$doc['id_documento'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center">Ver</a>
                                            <?php if ($puedeAdministrar): ?>
                                                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-id="<?= (int)$doc['id_documento'] ?>"
                                                    data-titulo="<?= e($doc['titulo']) ?>"
                                                    data-categoria="<?= (int)$doc['id_categoria'] ?>">Editar</button>
                                                <form action="../controllers/DocumentoController.php" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este documento?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id_documento" value="<?= (int)$doc['id_documento'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center">Eliminar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php if ($puedeGestionarCategorias): ?>
<div class="modal fade modal-fullscreen-sm-down" id="crearCategoriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/DocumentoController.php" method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Crear Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_categoria">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre de la Categoría</label>
                        <input type="text" name="nombre" class="form-control" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-fullscreen-sm-down" id="editarCategoriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/DocumentoController.php" method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_categoria">
                    <input type="hidden" name="id_categoria" id="edit_cat_id">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre de la Categoría</label>
                        <input type="text" name="nombre" id="edit_cat_nombre" class="form-control" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($puedeCargar): ?>
<div class="modal fade modal-fullscreen-sm-down" id="crearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/DocumentoController.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Cargar Nuevo Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="titulo" class="form-label small fw-bold">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255">
                        </div>
                        <div class="col-12">
                            <label for="archivo" class="form-label small fw-bold">Archivo (PDF o Imagen)</label>
                            <div class="border rounded-3 p-4 text-center bg-light" id="dropzone" style="cursor: pointer; border-style: dashed !important;">
                                <p class="mb-1 fw-bold">Arrastre un archivo aquí o haga clic para seleccionar</p>
                                <small class="text-muted">Formatos: PDF, JPG, PNG. Máx 10MB.</small>
                                <input type="file" name="archivo" id="archivo" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                                <p class="mt-2 mb-0 text-primary fw-bold d-none" id="file-name"></p>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="id_categoria" class="form-label small fw-bold">Categoría</label>
                            <select class="form-select" id="id_categoria" name="id_categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= (int)$cat['id_categoria'] ?>"><?= e($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($puedeAdministrar): ?>
<div class="modal fade modal-fullscreen-sm-down" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../controllers/DocumentoController.php" method="POST">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar Metadatos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_documento" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Título</label>
                        <input type="text" name="titulo" id="edit_titulo" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Categoría</label>
                        <select class="form-select" id="edit_categoria" name="id_categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)$cat['id_categoria'] ?>"><?= e($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($puedeGestionarCategorias): ?>
    var editarCategoriaModal = document.getElementById('editarCategoriaModal');
    if (editarCategoriaModal) {
        editarCategoriaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            editarCategoriaModal.querySelector('#edit_cat_id').value = button.getAttribute('data-id');
            editarCategoriaModal.querySelector('#edit_cat_nombre').value = button.getAttribute('data-nombre');
        });
    }
    <?php endif; ?>

    <?php if ($puedeAdministrar): ?>
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            editModal.querySelector('#edit_id').value = button.getAttribute('data-id');
            editModal.querySelector('#edit_titulo').value = button.getAttribute('data-titulo');
            editModal.querySelector('#edit_categoria').value = button.getAttribute('data-categoria');
        });
    }
    <?php endif; ?>

    <?php if ($puedeCargar): ?>
    var dropzone = document.getElementById('dropzone');
    var fileInput = document.getElementById('archivo');
    var fileNameDisplay = document.getElementById('file-name');

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', (e) => {
            if (e.target === dropzone || e.target.tagName === 'P' || e.target.tagName === 'SMALL') {
                fileInput.click();
            }
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('border-primary', 'bg-white');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('border-primary', 'bg-white');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-white');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFileName();
            }
        });

        fileInput.addEventListener('change', updateFileName);

        function updateFileName() {
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = fileInput.files[0].name;
                fileNameDisplay.classList.remove('d-none');
            } else {
                fileNameDisplay.classList.add('d-none');
            }
        }
    }
    <?php endif; ?>
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>