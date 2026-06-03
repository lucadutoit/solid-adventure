FROM php:8.2-apache

RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite \
    && docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/swift-swap/

RUN mkdir -p /var/www/html/swift-swap/uploads/listings \
    && chown -R www-data:www-data /var/www/html/swift-swap/uploads

RUN printf '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    RedirectMatch ^/$ /swift-swap/\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
