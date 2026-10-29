<?php
function cachePublico(int $segundos = 60): void {
    header('Cache-Control: public, max-age=' . $segundos);
    header('Vary: Cookie');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $segundos) . ' GMT');
}

function cachePrivado(int $segundos = 0): void {
    if ($segundos <= 0) {
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Expires: 0');
    } else {
        header('Cache-Control: private, max-age=' . $segundos);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $segundos) . ' GMT');
    }
}

function sinCache(): void {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}