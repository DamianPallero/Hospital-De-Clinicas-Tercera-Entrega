<?php
function asegurarCarpetaProtegida($rutaCarpeta) {
    if (!is_dir($rutaCarpeta)) {
        if (!mkdir($rutaCarpeta, 0755, true)) {
            return false;
        }
    }
    
    $htaccessPath = $rutaCarpeta . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $contenido = '<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>';
        
        if (file_put_contents($htaccessPath, $contenido) === false) {
            return false;
        }
    }
    
    return true;
}