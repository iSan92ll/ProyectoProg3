# Usa una imagen de PHP con Apache
FROM php:8.1-apache

# Instala extensiones necesarias de MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia los archivos de tu proyecto al servidor
COPY . /var/www/html/

# Expone el puerto 80 (necesario para Render)
EXPOSE 80

# Inicia Apache
CMD ["apache2-foreground"]
