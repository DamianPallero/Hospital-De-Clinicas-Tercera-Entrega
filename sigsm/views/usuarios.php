<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../includes/Cache.php';
requerirAutenticacion();
sinCache();
if (!tienePermiso('gestionar_usuarios')) {
    header('Location: /sigsm/index.php');
    exit();
}
$database = new Database();
$db = $database->getConnection();
$usuarioModel = new Usuario($db);
$usuarios = $usuarioModel->listar();
$roles = $usuarioModel->listarRoles();
$msg = $_GET['msg'] ?? '';
$alertClass = '';
$alertText = '';
switch ($msg) {
    case 'ok_crear': $alertClass = 'success'; $alertText = 'Usuario creado correctamente.'; break;
    case 'ok_editar': $alertClass = 'success'; $alertText = 'Usuario actualizado correctamente.'; break;
    case 'ok_toggle': $alertClass = 'success'; $alertText = 'Estado del usuario modificado.'; break;
    case 'ok_password': $alertClass = 'success'; $alertText = 'Contraseña restablecida correctamente.'; break;
    case 'error_email_duplicado': $alertClass = 'danger'; $alertText = 'Error: El correo electrónico ya está registrado.'; break;
    case 'error_validacion': $alertClass = 'danger'; $alertText = 'Error: Faltan datos obligatorios o la contraseña es muy corta (mín. 6).'; break;
    case 'error_propio': $alertClass = 'warning'; $alertText = 'No puede desactivar su propia cuenta.'; break;
    case 'error_csrf': $alertClass = 'danger'; $alertText = 'Error de seguridad. Intente nuevamente.'; break;
    case 'error': $alertClass = 'danger'; $alertText = 'Ocurrió un error al procesar la solicitud.'; break;
}

$rol = obtenerRol();
$nombreUsuario = $_SESSION['user_name'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
        @media (prefers-contrast: more) {
            .card { border: 2px solid currentColor !important; }
            .btn { border-width: 2px !important; }
        }
        :focus-visible {
            outline: 3px solid #124b7d !important;
            outline-offset: 2px !important;
        }
        .table-actions .btn {
            white-space: nowrap;
        }
        .mobile-compact-table {
            width: 100%;
            table-layout: fixed;
        }
        .mobile-compact-table th,
        .mobile-compact-table td {
            overflow-wrap: anywhere;
        }
        .table-actions {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }
        .table-actions form {
            display: flex !important;
        }
        .table-actions .btn {
            width: 100%;
            margin-right: 0 !important;
            padding: .25rem .35rem;
            font-size: .75rem;
        }
        .table-actions form .btn {
            width: 100%;
        }
        @media (min-width: 576px) {
            .table-actions {
                flex-direction: row;
                justify-content: flex-end;
                flex-wrap: wrap;
            }
            .table-actions form {
                display: inline-flex !important;
            }
            .table-actions .btn {
                width: auto;
            }
            .table-actions form .btn {
                width: auto;
            }
        }
    </style>
    <title>Gestión de Usuarios - S.I.G.S.M.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary shadow-sm flex-shrink-0">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold mb-0" href="index.php">S.I.G.S.M. - Administración de Usuarios</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small d-none d-sm-inline"><?= e($nombreUsuario) ?></span>
            <span class="badge bg-light text-primary"><?= e($rol) ?></span>
            <a href="../index.php" class="btn text-dark btn-info btn-outline-light btn-sm d-inline-flex align-items-center">Volver al Portal</a>
            <a href="logout.php" class="btn btn-danger btn-outline-light btn-sm d-inline-flex align-items-center">Salir</a>
            <a href="cambiar_password.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center">Cambiar Contraseña</a>
        </div>
    </div>
</nav>

<div class="container py-4">

    <?php if (tienePermiso('gestionar_usuarios')):
        $resetDir = __DIR__ . '/../config/reset_codes/';
        $resets = [];
        if (is_dir($resetDir)) {
            foreach (glob($resetDir . '*.txt') as $file) {
                $data = explode('|', file_get_contents($file));
                if (count($data) === 3) {
                    $expires = 900 - (time() - (int)$data[2]);
                    if ($expires > 0) {
                        $resets[] = ['email' => $data[0], 'code' => $data[1], 'expires' => floor($expires / 60)];
                    } else {
                        @unlink($file);
                    }
                }
            }
        }
    ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning bg-opacity-10 fw-bold d-flex justify-content-between">
            <span>Solicitudes de Restablecimiento de Contraseña</span>
            <span class="badge bg-dark rounded-pill"><?= count($resets) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($resets)): ?>
                <p class="text-muted small p-3 mb-0">No hay solicitudes pendientes.</p>
            <?php else: ?>
                <table class="table table-sm mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Email del Usuario</th>
                            <th>Código a dictar</th>
                            <th>Expira en</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resets as $r): ?>
                            <tr>
                                <td class="ps-3 small"><?= e($r['email']) ?></td>
                                <td><span class="badge bg-dark fs-6 px-3 py-2"><?= e($r['code']) ?></span></td>
                                <td class="small text-muted"><?= $r['expires'] ?> min</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($msg && $alertText): ?>
        <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show shadow-sm">
            <?= e($alertText) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-4">
        <h1 class="h4 fw-bold mb-0">Personal del Sistema</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearModal">Nuevo Usuario</button>
    </div>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 mobile-compact-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nombre Completo</th>
                            <th class="d-none d-md-table-cell">Correo Electrónico</th>
                            <th class="d-none d-md-table-cell">Rol</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay usuarios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= e($user['apellido'] . ', ' . $user['nombre']) ?></td>
                                    <td class="small d-none d-md-table-cell"><?= e($user['email']) ?></td>
                                    <td class="d-none d-md-table-cell"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($user['nombre_rol']) ?></span></td>
                                    <td>
                                        <?php if ($user['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="table-actions">
                                            <button class="btn text-light btn-primary btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-id="<?= (int)$user['id_usuario'] ?>"
                                                    data-nombre="<?= e($user['nombre']) ?>"
                                                    data-apellido="<?= e($user['apellido']) ?>"
                                                    data-email="<?= e($user['email']) ?>"
                                                    data-rol="<?= (int)$user['id_rol'] ?>">Editar</button>
                                            <button class="btn text-dark btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#resetModal"
                                                    data-id="<?= (int)$user['id_usuario'] ?>"
                                                    data-nombre="<?= e($user['nombre']) ?>">Clave</button>
                                            <?php if ((int)$user['id_usuario'] !== (int)$_SESSION['user_id']): ?>
                                                <form action="../controllers/UsuarioController.php" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de cambiar el estado de este usuario?');">
                                                    <input type="hidden" name="accion" value="toggle_estado">
                                                    <input type="hidden" name="id_usuario" value="<?= (int)$user['id_usuario'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                                                    <button type="submit" class="btn text-dark btn-sm btn-outline-<?= $user['activo'] ? 'danger' : 'success' ?>">
                                                        <?= $user['activo'] ? 'Desactivar' : 'Activar' ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border d-inline-flex align-items-center">Usted</span>
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
</div>

<div class="modal fade" id="crearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/UsuarioController.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Apellido</label>
                            <input type="text" name="apellido" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Rol Asignado</label>
                            <select name="id_rol" class="form-select" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= (int)$r['id_rol'] ?>"><?= e($r['nombre_rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Contraseña Inicial</label>
                            <input type="text" name="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/UsuarioController.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Datos de Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_usuario" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Nombre</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Apellido</label>
                            <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Rol Asignado</label>
                            <select name="id_rol" id="edit_rol" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= (int)$r['id_rol'] ?>"><?= e($r['nombre_rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/UsuarioController.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Restablecer Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="reset_password">
                    <input type="hidden" name="id_usuario" id="reset_id">
                    <input type="hidden" name="csrf_token" value="<?= e(generarTokenCSRF()) ?>">
                    <p class="text-muted small mb-3">Ingrese una nueva contraseña para el usuario: <strong id="reset_nombre"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nueva Contraseña</label>
                        <input type="text" name="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Restablecer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            editModal.querySelector('#edit_id').value = button.getAttribute('data-id');
            editModal.querySelector('#edit_nombre').value = button.getAttribute('data-nombre');
            editModal.querySelector('#edit_apellido').value = button.getAttribute('data-apellido');
            editModal.querySelector('#edit_email').value = button.getAttribute('data-email');
            editModal.querySelector('#edit_rol').value = button.getAttribute('data-rol');
        });
    }
    var resetModal = document.getElementById('resetModal');
    if (resetModal) {
        resetModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            resetModal.querySelector('#reset_id').value = button.getAttribute('data-id');
            resetModal.querySelector('#reset_nombre').textContent = button.getAttribute('data-nombre');
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>