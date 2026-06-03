FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql && a2enmod rewrite

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
