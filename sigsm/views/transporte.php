<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requerirAutenticacion();

if (obtenerRol() !== 'Transporte') {
    header('Location: /sigsm/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. - Transporte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">S.I.G.S.M. - Módulo de Transporte</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </nav>
    <div class="container py-5 text-center">
        <div class="card border-0 shadow-sm p-5 mx-auto" style="max-width: 600px;">
            <h3 class="mb-3">Módulo de Gestión de Traslados</h3>
            <p class="text-muted">Esta sección está en desarrollo. Aquí se visualizarán las solicitudes de traslado, rutas asignadas y estado de los vehículos.</p>
            <a href="../index.php" class="btn btn-primary mt-3">Volver al Portal</a>
        </div>
    </div>
</body>
</html>