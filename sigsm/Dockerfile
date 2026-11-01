FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    APP_FORCE_HTTPS=false \
    APP_ENV=development

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo pdo_mysql \
    && a2enmod rewrite headers alias expires deflate

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf '%s\n' '<VirtualHost *:80>' '    ServerName localhost' '    DocumentRoot /var/www/html' '    Alias /sigsm /var/www/html/sigsm' '    RedirectMatch 302 ^/$ /sigsm/' '    <Directory /var/www/html>' '        Options FollowSymLinks' '        AllowOverride All' '        Require all granted' '    </Directory>' '    <Directory /var/www/html/sigsm>' '        Options FollowSymLinks' '        AllowOverride All' '        Require all granted' '    </Directory>' '</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

COPY config/php-custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY config/apache-custom.conf /etc/apache2/conf-available/servername.conf
RUN a2enconf servername

WORKDIR /var/www/html
COPY . /var/www/html/sigsm

RUN printf '%s\n' '<?php' 'header("Location: /sigsm/");' 'exit;' '?>' > /var/www/html/index.php \
    && mkdir -p /var/www/html/sigsm/uploads \
    /var/www/html/sigsm/temp_qr \
    /var/www/html/sigsm/config/reset_codes \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/sigsm/uploads \
    /var/www/html/sigsm/temp_qr \
    /var/www/html/sigsm/config \
    && chmod 755 /var/www/html \
    /var/www/html/index.php \
    /var/www/html/sigsm

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]