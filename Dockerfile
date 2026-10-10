FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf
COPY index.html /var/www/html/index.html
COPY api/ /var/www/html/api/