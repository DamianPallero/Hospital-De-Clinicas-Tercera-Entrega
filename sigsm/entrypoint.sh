#!/bin/bash
set -e

echo "Ajustando permisos de carpetas..."

mkdir -p /var/www/html/sigsm/uploads /var/www/html/sigsm/temp_qr /var/www/html/sigsm/config

chown -R www-data:www-data /var/www/html/sigsm/uploads || true
chmod -R 777 /var/www/html/sigsm/uploads

chown -R www-data:www-data /var/www/html/sigsm/temp_qr || true
chmod -R 777 /var/www/html/sigsm/temp_qr

chown -R www-data:www-data /var/www/html/sigsm/config || true
chmod -R 777 /var/www/html/sigsm/config

# Iniciar Apache en primer plano
exec apache2-foreground