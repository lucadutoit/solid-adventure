FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql && a2enmod rewrite

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
