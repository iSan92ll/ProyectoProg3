FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install pdo_mysql mysqli

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
