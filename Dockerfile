FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/swift-swap/

RUN mkdir -p /var/www/html/swift-swap/uploads/listings \
    && chmod 755 /var/www/html/swift-swap/uploads/listings

RUN echo '<?php header("Location: /swift-swap/"); exit;' > /var/www/html/index.php

WORKDIR /var/www/html

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80"]
