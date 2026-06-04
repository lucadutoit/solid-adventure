FROM php:8.2-apache

RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/swift-swap/

RUN mkdir -p /var/www/html/swift-swap/uploads/listings \
    && chown -R www-data:www-data /var/www/html/swift-swap/uploads

RUN { \
    echo '<VirtualHost *:80>'; \
    echo '    DocumentRoot /var/www/html'; \
    echo '    RedirectMatch ^/$ /swift-swap/'; \
    echo '    <Directory /var/www/html>'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '    </Directory>'; \
    echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
