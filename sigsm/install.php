<?php
require_once __DIR__ . '/includes/Security.php';

$carpetas = [
    __DIR__ . '/uploads',
    __DIR__ . '/temp_qr',
    __DIR__ . '/config'
];

$errores = [];

foreach ($carpetas as $carpeta) {
    if (!asegurarCarpetaProtegida($carpeta)) {
        $errores[] = "No se pudo asegurar: $carpeta";
    }
}

if (empty($errores)) {
    echo "<h2>Instalación completada</h2>";
    echo "<p>Todas las carpetas críticas han sido aseguradas correctamente.</p>";
    echo "<p><a href='index.php'>Ir al portal</a></p>";
} else {
    echo "<h2>Errores en la instalación</h2>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<p>Verifique los permisos de escritura del servidor web.</p>";
}