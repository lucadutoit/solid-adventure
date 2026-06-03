FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql && a2enmod rewrite

COPY . /var/www/html/swift-swap/
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /var/www/html/swift-swap/uploads/listings \
    && chown -R www-data:www-data /var/www/html/swift-swap/uploads

EXPOSE 80
